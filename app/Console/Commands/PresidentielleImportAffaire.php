<?php

namespace App\Console\Commands;

use App\Models\AffaireJudiciaire;
use App\Models\AffaireSource;
use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importe une affaire judiciaire depuis le contrat JSON (type_import=affaire_judiciaire)
 * dans le module existant affaires_judiciaires. TOUT en statut detecte / non publié :
 * double validation humaine + sources archivées (archive.org) obligatoires avant publication.
 * Présomption d'innocence : statut exact conservé, détails des tiers exclus (déjà côté JSON).
 */
class PresidentielleImportAffaire extends Command
{
    protected $signature = 'presidentielle:import-affaire {fichier}';

    protected $description = 'Importe une affaire judiciaire (contrat JSON) en file de modération (detecte).';

    /** Mapping statut du contrat -> enum du modèle. */
    private const STATUT_MAP = [
        'procedure' => 'en_cours',
        'information_judiciaire' => 'en_cours',
        'mise_en_examen' => 'mis_en_examen',
        'appel' => 'condamne_appel',
        'condamnation_definitive' => 'condamne_definitif',
    ];

    public function handle(): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }
        $data = json_decode((string) file_get_contents($fichier), true);
        $a = $data['affaire'] ?? null;
        if (! is_array($a)) {
            $this->error('JSON invalide : clé "affaire" attendue.');

            return self::FAILURE;
        }

        $personne = PersonnePolitique::where('slug', $a['personne_politique_slug'] ?? '')->first();
        if (! $personne) {
            $this->error('Personne politique introuvable : '.($a['personne_politique_slug'] ?? '?'));

            return self::FAILURE;
        }

        $statut = self::STATUT_MAP[$a['statut_judiciaire'] ?? 'procedure'] ?? 'en_cours';

        DB::beginTransaction();
        try {
            $affaire = AffaireJudiciaire::create([
                'personne_politique_id' => $personne->id,
                'nom' => $personne->nom,
                'prenom' => $personne->prenom,
                'parti_politique' => $personne->parti_politique,
                'titre' => $a['titre'] ?? 'Affaire',
                'description' => $a['resume_factuel'] ?? null,
                'type_affaire' => $a['type_affaire'] ?? 'detournement_fonds',
                'categorie' => $a['categorie'] ?? 'probite',
                'statut_judiciaire' => $statut,
                'juridiction' => $a['autorite'] ?? null,
                'statut_validation' => 'detecte',
                'affiche_publiquement' => false,
                'source_detection' => $data['generateur'] ?? 'import',
                'detecte_at' => now(),
                'detection_raw_data' => [
                    'contrat_version' => $data['contrat_version'] ?? null,
                    'statut_contrat' => $a['statut_judiciaire'] ?? null,
                    'date_ouverture' => $a['date_ouverture'] ?? null,
                    'autorite' => $a['autorite'] ?? null,
                    'qualifications_parquet' => $a['qualifications_parquet'] ?? [],
                    'chronologie' => $a['chronologie'] ?? [],
                    'position_interesse' => $a['position_interesse'] ?? null,
                    'rappel_statut' => $a['_rappel_statut'] ?? null,
                    'taches_moderation' => $a['_taches_moderation'] ?? [],
                ],
            ]);

            $nbSources = 0;
            foreach ($a['sources'] ?? [] as $s) {
                AffaireSource::create([
                    'affaire_id' => $affaire->id,
                    'type_source' => $s['type'] ?? 'presse_nationale',
                    'media' => $s['media'] ?? null,
                    'url' => $s['url'] ?? null,
                    'date_publication' => $s['date'] ?? null,
                    'fiabilite' => $s['fiabilite'] ?? 'moyenne',
                    'archive_url' => $s['archive_url'] ?? null,
                ]);
                $nbSources++;
            }

            DB::commit();
            $this->info("Affaire importée (detecte, non publiée) : « {$affaire->titre} » pour {$personne->nom_complet}, {$nbSources} source(s).");
            $this->warn('À FAIRE au back-office : source primaire (PNF/AFP), archivage archive.org, double validation, puis publication.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import : '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
