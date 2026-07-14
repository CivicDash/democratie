<?php

namespace App\Console\Commands;

use App\Models\AffaireJudiciaire;
use App\Models\AffaireSource;
use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importe une revue judiciaire périodique (contrat JSON, type_import=revue_judiciaire) :
 *  - `affaires[]` -> module affaires_judiciaires en statut detecte (double validation
 *    + sources archivées avant publication) ; les statuts suffixés _A_CONFIRMER sont
 *    mappés sur le statut de base avec drapeau `a_confirmer` (contrôle humain requis).
 *  - `revue_negative` -> date de dernière vérification (revue_judiciaire_at) des
 *    candidats listés : « aucune affaire recensée » devient une information datée.
 * Les procédures closes et vérifications en attente restent documentées dans le JSON
 * (non importées) — cf. règle d'affichage à trancher par l'association.
 */
class PresidentielleImportRevueJudiciaire extends Command
{
    protected $signature = 'presidentielle:import-revue-judiciaire {fichier} {--election=2027}';

    protected $description = 'Importe une revue judiciaire (affaires en detecte + dates de vérification négative).';

    private const STATUT_MAP = [
        'procedure' => 'en_cours',
        'information_judiciaire' => 'en_cours',
        'mis_en_examen' => 'mis_en_examen',
        'mise_en_examen' => 'mis_en_examen',
        'condamne_premiere_instance' => 'condamne_premiere_instance',
        'appel' => 'condamne_appel',
        'condamne_appel' => 'condamne_appel',
        'condamnation_definitive' => 'condamne_definitif',
        'condamne_definitif' => 'condamne_definitif',
    ];

    public function handle(): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }
        $data = json_decode((string) file_get_contents($fichier), true);
        if (! is_array($data)) {
            $this->error('JSON invalide.');

            return self::FAILURE;
        }

        $importees = 0;
        $dejaPresentes = 0;

        DB::beginTransaction();
        try {
            foreach ($data['affaires'] ?? [] as $a) {
                $personne = PersonnePolitique::where('slug', $a['personne_politique_slug'] ?? '')->first();
                if (! $personne) {
                    $this->warn('  ✗ personne introuvable : '.($a['personne_politique_slug'] ?? '?'));

                    continue;
                }

                // Dédoublonnage par (personne, titre)
                if (AffaireJudiciaire::where('personne_politique_id', $personne->id)->where('titre', $a['titre'] ?? '')->exists()) {
                    $dejaPresentes++;

                    continue;
                }

                $statutBrut = (string) ($a['statut_judiciaire'] ?? 'procedure');
                $aConfirmer = str_contains($statutBrut, 'A_CONFIRMER');
                $statut = self::STATUT_MAP[Str::before($statutBrut, '_A_CONFIRMER')] ?? 'en_cours';
                $decision = $a['decision'] ?? [];

                $affaire = AffaireJudiciaire::create([
                    'personne_politique_id' => $personne->id,
                    'nom' => $personne->nom,
                    'prenom' => $personne->prenom,
                    'parti_politique' => $personne->parti_politique,
                    'titre' => $a['titre'],
                    'description' => $a['resume_factuel'] ?? null,
                    'type_affaire' => $a['type_affaire'] ?? 'autre',
                    'categorie' => $a['categorie'] ?? 'autre',
                    'statut_judiciaire' => $statut,
                    'juridiction' => $decision['juridiction'] ?? ($a['autorite'] ?? null),
                    'date_jugement_appel' => $statut === 'condamne_appel' ? ($decision['date'] ?? null) : null,
                    'statut_validation' => 'detecte',
                    'affiche_publiquement' => false,
                    'source_detection' => $data['generateur'] ?? 'revue_judiciaire',
                    'detecte_at' => now(),
                    'detection_raw_data' => [
                        'contrat_version' => $data['contrat_version'] ?? null,
                        'statut_contrat' => $statutBrut,
                        'a_confirmer' => $aConfirmer,
                        'decision' => $decision,
                        'position_interesse' => $a['position_interesse'] ?? null,
                        'rappel_statut' => $a['_rappel_statut'] ?? null,
                    ],
                    'commentaire_validation' => $aConfirmer ? 'STATUT À CONFIRMER avant validation (cf. rappel dans les données de détection).' : null,
                ]);

                foreach ($a['sources'] ?? [] as $s) {
                    AffaireSource::create([
                        'affaire_id' => $affaire->id,
                        'type_source' => $s['type'] ?? 'presse_nationale',
                        'media' => $s['media'] ?? null,
                        'url' => $s['url'] ?? null,
                        'titre' => $s['titre'] ?? null,
                        'date_publication' => $s['date'] ?? null,
                        'fiabilite' => $s['fiabilite'] ?? 'moyenne',
                        'archive_url' => $s['archive_url'] ?? null,
                    ]);
                }
                $importees++;
                $this->line("  ✓ {$personne->nom_complet} — {$a['titre']} ({$statut}".($aConfirmer ? ', À CONFIRMER' : '').')');
            }

            // Revue négative : date de vérification pour les candidats sans affaire identifiée
            $revue = $data['revue_negative'] ?? null;
            $datees = 0;
            if ($revue && ! empty($revue['candidats_slugs'])) {
                $date = $revue['date_verification'] ?? ($data['genere_le'] ?? now()->toDateString());
                $datees = CandidatPresidentielle::where('election', $this->option('election'))
                    ->whereHas('personnePolitique', fn ($q) => $q->whereIn('slug', $revue['candidats_slugs']))
                    ->update(['revue_judiciaire_at' => $date]);
            }
            // Les candidats avec affaire importée sont aussi « vérifiés » à la date de la revue
            if (! empty($data['genere_le'])) {
                $slugsAffaires = collect($data['affaires'] ?? [])->pluck('personne_politique_slug')->filter()->all();
                if ($slugsAffaires) {
                    CandidatPresidentielle::where('election', $this->option('election'))
                        ->whereHas('personnePolitique', fn ($q) => $q->whereIn('slug', $slugsAffaires))
                        ->update(['revue_judiciaire_at' => $data['genere_le']]);
                }
            }

            DB::commit();
            $this->info("Affaires importées (detecte) : {$importees} · déjà présentes : {$dejaPresentes} · candidats datés (revue négative) : {$datees}");
            $this->warn('Publication : source primaire + archives + double validation obligatoires. Règle d\'affichage (procédures closes) à trancher par l\'association.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import : '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
