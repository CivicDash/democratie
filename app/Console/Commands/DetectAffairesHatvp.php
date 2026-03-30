<?php

namespace App\Console\Commands;

use App\Models\AffaireJudiciaire;
use App\Models\HatvpDeclaration;
use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DetectAffairesHatvp extends Command
{
    protected $signature = 'affaires:detect-hatvp {--dry-run : Simuler sans écrire}';

    protected $description = 'Détecte les manquements/signalements via les déclarations HATVP';

    private const KEYWORDS = [
        'irrégularit',
        'manquement',
        'signalement',
        'parquet',
        'incompatibilit',
        'omission substantielle',
        'évaluation insuffisante',
        'observation',
    ];

    private int $detected = 0;

    private int $duplicates = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Détection HATVP des manquements/signalements...');
        if ($dryRun) {
            $this->warn('Mode simulation (dry-run)');
        }

        $declarations = HatvpDeclaration::query()
            ->where(function ($q) {
                foreach (self::KEYWORDS as $keyword) {
                    $q->orWhere('observations_interet', 'ILIKE', "%{$keyword}%")
                        ->orWhere('observations_patrimoine', 'ILIKE', "%{$keyword}%");
                }
            })
            ->get();

        $this->info("Déclarations candidates : {$declarations->count()}");

        $bar = $this->output->createProgressBar($declarations->count());

        foreach ($declarations as $declaration) {
            $this->processDeclaration($declaration, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Résultat : {$this->detected} affaire(s) détectée(s), {$this->duplicates} doublon(s) ignoré(s)");

        return self::SUCCESS;
    }

    private function processDeclaration(HatvpDeclaration $declaration, bool $dryRun): void
    {
        $eluData = $this->resolveElu($declaration);
        if (! $eluData) {
            return;
        }

        $observations = trim(
            ($declaration->observations_interet ?? '')."\n".
            ($declaration->observations_patrimoine ?? '')
        );
        if (empty($observations)) {
            return;
        }

        $titre = Str::limit("Signalement HATVP — {$eluData['prenom']} {$eluData['nom']}", 497);

        $isDuplicate = AffaireJudiciaire::where(function ($q) use ($eluData) {
            if (isset($eluData['acteur_an_uid'])) {
                $q->where('acteur_an_uid', $eluData['acteur_an_uid']);
            } elseif (isset($eluData['senateur_matricule'])) {
                $q->where('senateur_matricule', $eluData['senateur_matricule']);
            } elseif (isset($eluData['personne_politique_id'])) {
                $q->where('personne_politique_id', $eluData['personne_politique_id']);
            }
        })->where('source_detection', 'hatvp')
            ->where('titre', $titre)
            ->exists();

        if ($isDuplicate) {
            $this->duplicates++;

            return;
        }

        $typeAffaire = str_contains(mb_strtolower($observations), 'incompatibilit')
            ? 'conflit_interets'
            : 'manquement_probite';

        if ($dryRun) {
            $this->line("  [DRY] {$eluData['prenom']} {$eluData['nom']} : {$titre}");
            $this->detected++;

            return;
        }

        $affaire = AffaireJudiciaire::create(array_merge($eluData, [
            'titre' => $titre,
            'description' => Str::limit($observations, 5000),
            'type_affaire' => $typeAffaire,
            'categorie' => 'manquement',
            'statut_judiciaire' => 'en_cours',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'source_detection' => 'hatvp',
            'detecte_at' => now(),
            'detection_confidence' => 0.90,
            'detection_raw_data' => [
                'declaration_id' => $declaration->id,
                'type_declaration' => $declaration->type_declaration ?? null,
                'observations_interet' => Str::limit($declaration->observations_interet ?? '', 500),
                'observations_patrimoine' => Str::limit($declaration->observations_patrimoine ?? '', 500),
            ],
        ]));

        $affaire->sources()->create([
            'type_source' => 'hatvp_signalement',
            'titre' => "Déclaration HATVP — {$declaration->type_declaration}",
            'url' => $declaration->url_hatvp,
            'fiabilite' => 'haute',
            'extrait' => Str::limit($observations, 500),
        ]);

        $affaire->moderationLogs()->create([
            'action' => 'detection',
            'nouveau_statut' => 'detecte',
            'commentaire' => 'Détection HATVP (confiance : 0.90)',
            'metadata' => ['source' => 'hatvp', 'confidence' => 0.90],
        ]);

        $this->detected++;
        $this->line("  + {$eluData['prenom']} {$eluData['nom']} : {$titre}");
    }

    private function resolveElu(HatvpDeclaration $declaration): ?array
    {
        $parlementaireType = $declaration->parlementaire_type ?? null;
        $parlementaireId = $declaration->parlementaire_id ?? null;

        if ($parlementaireType === 'depute' && $parlementaireId) {
            $depute = $declaration->depute;
            if (! $depute) {
                return null;
            }

            return [
                'acteur_an_uid' => $depute->uid,
                'nom' => $depute->nom,
                'prenom' => $depute->prenom,
                'parti_politique' => $depute->groupe_politique_actuel?->libelle_abrege,
                'fonction_au_moment' => 'Député',
            ];
        }

        if ($parlementaireType === 'senateur' && $parlementaireId) {
            $senateur = $declaration->senateur;
            if (! $senateur) {
                return null;
            }

            return [
                'senateur_matricule' => $senateur->matricule,
                'nom' => $senateur->nom_usuel,
                'prenom' => $senateur->prenom_usuel,
                'parti_politique' => $senateur->groupe_politique,
                'fonction_au_moment' => 'Sénateur',
            ];
        }

        $typeMandat = mb_strtolower($declaration->type_mandat ?? '');
        $isGouvernement = str_contains($typeMandat, 'gouvernement')
            || str_contains($typeMandat, 'ministre')
            || str_contains($typeMandat, 'secrétaire d\'état')
            || $parlementaireType === 'personne_politique';

        if ($isGouvernement || ! $parlementaireId) {
            $nom = $declaration->nom;
            $prenom = $declaration->prenom;
            if (! $nom || ! $prenom) {
                return null;
            }

            $personne = PersonnePolitique::where('nom', 'ILIKE', $nom)
                ->where('prenom', 'ILIKE', $prenom)
                ->first();

            if ($personne) {
                return [
                    'personne_politique_id' => $personne->id,
                    'nom' => $personne->nom,
                    'prenom' => $personne->prenom,
                    'parti_politique' => $personne->parti_politique,
                    'fonction_au_moment' => 'Membre du gouvernement',
                ];
            }
        }

        return null;
    }
}
