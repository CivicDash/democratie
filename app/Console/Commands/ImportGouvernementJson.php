<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\Ministre;
use App\Models\Senateur;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportGouvernementJson extends Command
{
    protected $signature = 'import:gouvernement-json 
                            {--file= : Chemin vers le fichier JSON}
                            {--force : Forcer le réimport et supprimer les anciens}
                            {--dry-run : Mode simulation}
                            {--detect-links : Détecter les liens avec députés/sénateurs}';

    protected $description = 'Import de la composition du Gouvernement depuis un fichier JSON';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $detectLinks = $this->option('detect-links');

        // Chercher le fichier le plus récent si non spécifié
        $file = $this->option('file');
        if (! $file) {
            $files = glob(database_path('data/gouvernement*.json'));
            $file = ! empty($files) ? end($files) : database_path('data/gouvernement.json');
        }

        $this->info('🏛️ Import du Gouvernement depuis JSON');
        $this->info('📄 Fichier : '.$file);
        $this->newLine();

        // 1. Lire le fichier
        if (! file_exists($file)) {
            $this->error('❌ Fichier non trouvé : '.$file);

            return Command::FAILURE;
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('❌ Erreur JSON : '.json_last_error_msg());

            return Command::FAILURE;
        }

        // 2. Valider les données - Support les 2 formats
        // Format 1: { gouvernement, membres, ministeres? }
        // Format 2: { gouvernement, ministeres: [{ nom, membres: [...] }] }

        $gouv = $data['gouvernement'] ?? null;
        $ministeresData = $data['ministeres'] ?? [];

        // Detecter le format
        $isNewFormat = ! empty($ministeresData) && isset($ministeresData[0]['membres']);

        // Dans le nouveau format, les membres sont dans chaque ministère
        $membres = [];
        if ($isNewFormat) {
            foreach ($ministeresData as $min) {
                foreach ($min['membres'] ?? [] as $membre) {
                    $membre['ministere'] = $min['nom'];
                    $membre['ministere_sigle'] = $min['sigle'] ?? null;
                    $membre['ministere_site'] = $min['site_web'] ?? null;
                    $membre['ministere_adresse'] = $min['adresse'] ?? null;
                    $membre['ministere_telephone'] = $min['telephone'] ?? null;
                    $membres[] = $membre;
                }
            }
        } else {
            $membres = $data['membres'] ?? [];
        }

        if (! $gouv) {
            $this->error('❌ Structure JSON invalide. Clé requise : gouvernement');

            return Command::FAILURE;
        }

        // Compter les ministères uniques
        $ministeresCount = $isNewFormat ? count($ministeresData) : count(array_unique(array_column($membres, 'ministere')));

        $this->info('📊 Gouvernement : '.($gouv['nom'] ?? 'Non défini'));
        $this->info('👔 Premier Ministre : '.($gouv['premier_ministre'] ?? 'Non défini'));
        $this->info('📅 Depuis : '.($gouv['date_debut'] ?? 'Non défini'));
        $this->info('🏢 Ministères : '.$ministeresCount);
        $this->info('👥 Membres : '.count($membres));
        $this->newLine();

        // 3. Afficher les ministères
        if ($isNewFormat) {
            $this->info('📋 Ministères :');
            $this->table(
                ['#', 'Nom', 'Sigle', 'Site web', 'Téléphone', 'Membres'],
                collect($ministeresData)->map(fn ($m, $i) => [
                    $i + 1,
                    Str::limit($m['nom'] ?? '', 50),
                    $m['sigle'] ?? '-',
                    ! empty($m['site_web']) ? '✓' : '-',
                    $m['telephone'] ?? '-',
                    count($m['membres'] ?? []),
                ])->toArray()
            );
            $this->newLine();
        }

        // 4. Afficher les membres
        $this->info('👔 Membres :');
        $this->table(
            ['#', 'Fonction', 'Nom', 'Type', 'Parti'],
            collect($membres)->map(fn ($m, $i) => [
                $i + 1,
                Str::limit($m['fonction'] ?? '', 40),
                trim(($m['prenom'] ?? '').' '.($m['nom'] ?? '')),
                $m['type'] ?? 'ministre',
                $m['parti'] ?? '-',
            ])->toArray()
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('🔄 Mode simulation - Aucune modification effectuée');

            // Montrer les liens potentiels avec les députés/sénateurs
            if ($detectLinks) {
                $this->detectExistingLinks($membres);
            }

            return Command::SUCCESS;
        }

        // 5. Sauvegarder
        $this->info('💾 Enregistrement en base de données...');

        // Créer/mettre à jour le gouvernement
        $gouvernement = Gouvernement::updateOrCreate(
            ['actif' => true],
            [
                'nom' => $gouv['nom'] ?? 'Gouvernement actuel',
                'slug' => Str::slug($gouv['nom'] ?? 'gouvernement-actuel'),
                'premier_ministre' => $gouv['premier_ministre'] ?? 'Non défini',
                'president' => $gouv['president'] ?? 'Emmanuel Macron',
                'date_debut' => $gouv['date_debut'] ?? now()->format('Y-m-d'),
                'legislature' => $gouv['legislature'] ?? null,
                'numero' => $gouv['numero'] ?? null,
                'contexte' => $gouv['contexte'] ?? null,
            ]
        );

        // Désactiver les anciens gouvernements
        Gouvernement::where('id', '!=', $gouvernement->id)->update(['actif' => false]);

        if ($force) {
            // Supprimer les anciens ministres et ministères de ce gouvernement
            Ministre::where('gouvernement_id', $gouvernement->id)->delete();
            Ministere::where('gouvernement_id', $gouvernement->id)->delete();
            $this->warn('   ⚠️ Anciennes données supprimées (--force)');
        } else {
            // Désactiver les anciens ministres
            Ministre::where('gouvernement_id', $gouvernement->id)->update(['actif' => false]);
        }

        // 6. Créer les ministères
        $ministeresMap = [];

        if ($isNewFormat) {
            // Nouveau format : créer les ministères depuis la liste
            foreach ($ministeresData as $ordre => $minData) {
                $ministere = Ministere::updateOrCreate(
                    [
                        'nom' => $minData['nom'],
                        'gouvernement_id' => $gouvernement->id,
                    ],
                    [
                        'slug' => Str::slug($minData['nom']),
                        'sigle' => $minData['sigle'] ?? null,
                        'site_web' => $minData['site_web'] ?? null,
                        'adresse' => $minData['adresse'] ?? null,
                        'telephone' => $minData['telephone'] ?? null,
                        'ordre' => $ordre + 1,
                        'couleur' => Ministere::getCouleurDefaut($minData['nom']),
                        'type' => $this->detectMinistereType($minData['nom']),
                        'actif' => true,
                    ]
                );
                $ministeresMap[$minData['nom']] = $ministere->id;
            }
        }

        $this->info('   → Ministères : '.count($ministeresMap));

        // 7. Créer les ministres
        $stats = ['created' => 0, 'updated' => 0, 'linked_an' => 0, 'linked_senat' => 0];

        foreach ($membres as $membre) {
            $prenom = $membre['prenom'] ?? '';
            $nom = $membre['nom'] ?? '';
            $fonction = $membre['fonction'] ?? 'Membre du gouvernement';
            $type = $membre['type'] ?? $this->detectType($fonction);

            // Trouver le ministère
            $ministereId = null;
            $ministereNom = $membre['ministere'] ?? null;

            if ($ministereNom) {
                $ministereId = $ministeresMap[$ministereNom] ?? null;

                // Créer le ministère s'il n'existe pas
                if (! $ministereId) {
                    $ministere = Ministere::firstOrCreate(
                        [
                            'nom' => $ministereNom,
                            'gouvernement_id' => $gouvernement->id,
                        ],
                        [
                            'slug' => Str::slug($ministereNom),
                            'sigle' => $membre['ministere_sigle'] ?? null,
                            'site_web' => $membre['ministere_site'] ?? null,
                            'adresse' => $membre['ministere_adresse'] ?? null,
                            'telephone' => $membre['ministere_telephone'] ?? null,
                            'type' => $this->detectMinistereType($ministereNom),
                            'couleur' => Ministere::getCouleurDefaut($ministereNom),
                            'actif' => true,
                        ]
                    );
                    $ministereId = $ministere->id;
                    $ministeresMap[$ministereNom] = $ministereId;
                }
            }

            // Chercher liens avec députés/sénateurs existants
            $uidAn = null;
            $uidSenat = null;

            if ($detectLinks) {
                // Chercher un député avec le même nom
                $depute = ActeurAN::where('nom', 'ilike', $nom)
                    ->where('prenom', 'ilike', $prenom)
                    ->first();
                if ($depute) {
                    $uidAn = $depute->uid;
                    $stats['linked_an']++;
                }

                // Chercher un sénateur avec le même nom
                $senateur = Senateur::where('nom', 'ilike', $nom)
                    ->where('prenom', 'ilike', $prenom)
                    ->first();
                if ($senateur) {
                    $uidSenat = $senateur->matricule;
                    $stats['linked_senat']++;
                }
            }

            // Créer/mettre à jour le ministre
            $ministre = Ministre::updateOrCreate(
                [
                    'gouvernement_id' => $gouvernement->id,
                    'prenom' => $prenom,
                    'nom' => $nom,
                ],
                [
                    'slug' => Str::slug($prenom.'-'.$nom),
                    'fonction' => $fonction,
                    'type_fonction' => $type,
                    'ministere_id' => $ministereId,
                    'parti_politique' => $membre['parti'] ?? null,
                    'photo_url' => $membre['photo_url'] ?? null,
                    'civilite' => $this->detectCivilite($membre['sexe'] ?? null),
                    'date_debut' => $gouvernement->date_debut,
                    'uid_an' => $uidAn,
                    'uid_senat' => $uidSenat,
                    'actif' => true,
                ]
            );

            if ($ministre->wasRecentlyCreated) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }
        }

        $this->newLine();
        $this->info('✅ Import terminé !');
        $this->info('   → Ministres créés : '.$stats['created']);
        $this->info('   → Ministres mis à jour : '.$stats['updated']);
        if ($detectLinks) {
            $this->info('   → Liens députés trouvés : '.$stats['linked_an']);
            $this->info('   → Liens sénateurs trouvés : '.$stats['linked_senat']);
        }
        $this->info('   → Gouvernement ID : '.$gouvernement->id);

        return Command::SUCCESS;
    }

    private function detectExistingLinks(array $membres): void
    {
        $this->newLine();
        $this->info('🔗 Recherche de liens avec les élus existants...');

        $links = [];
        foreach ($membres as $membre) {
            $prenom = $membre['prenom'] ?? '';
            $nom = $membre['nom'] ?? '';

            // Chercher un député
            $depute = ActeurAN::where('nom', 'ilike', $nom)
                ->where('prenom', 'ilike', $prenom)
                ->first();

            // Chercher un sénateur
            $senateur = Senateur::where('nom', 'ilike', $nom)
                ->where('prenom', 'ilike', $prenom)
                ->first();

            if ($depute || $senateur) {
                $links[] = [
                    'ministre' => "$prenom $nom",
                    'depute' => $depute ? "✓ ({$depute->uid})" : '-',
                    'senateur' => $senateur ? "✓ ({$senateur->matricule})" : '-',
                ];
            }
        }

        if (! empty($links)) {
            $this->table(
                ['Ministre', 'Député', 'Sénateur'],
                $links
            );
            $this->info('   → '.count($links).' liens potentiels trouvés');
        } else {
            $this->info('   Aucun lien trouvé');
        }
    }

    private function detectType(string $fonction): string
    {
        $fonctionLower = strtolower($fonction);

        if (str_contains($fonctionLower, 'premier ministre')) {
            return 'premier_ministre';
        }
        if (str_contains($fonctionLower, "secrétaire d'état") || str_contains($fonctionLower, 'secrétaire d\'état')) {
            return 'secretaire_etat';
        }
        if (str_contains($fonctionLower, 'ministre délégué') || str_contains($fonctionLower, 'ministre déléguée')) {
            return 'ministre_delegue';
        }

        return 'ministre';
    }

    private function detectMinistereType(string $nom): string
    {
        $nomLower = strtolower($nom);

        if (str_contains($nomLower, 'premier ministre')) {
            return 'premier_ministre';
        }

        return 'ministere';
    }

    private function detectCivilite(?string $sexe): ?string
    {
        if ($sexe === 'M') {
            return 'M.';
        }
        if ($sexe === 'F') {
            return 'Mme';
        }

        return null;
    }
}
