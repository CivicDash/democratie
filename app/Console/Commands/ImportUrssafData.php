<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Importe les données URSSAF depuis leur API Open Data
 *
 * Source : https://open.urssaf.fr
 *
 * Datasets disponibles :
 * - Cotisations dues par secteur d'activité
 * - Effectifs salariés
 * - Masse salariale
 * - Déclarations d'embauche (DPAE)
 */
class ImportUrssafData extends Command
{
    protected $signature = 'urssaf:import 
                            {--dataset=all : Dataset à importer (cotisations|effectifs|masse-salariale|all)}
                            {--year= : Année à importer (défaut: année en cours)}
                            {--limit=1000 : Nombre max de lignes}';

    protected $description = 'Importe les données ouvertes URSSAF (cotisations, effectifs, masse salariale)';

    private const BASE_URL = 'https://open.urssaf.fr/api/explore/v2.1/catalog/datasets';

    private const DATASETS = [
        'effectifs-national' => [
            'id' => 'nombre-etab-effectifs-salaries-et-masse-salariale-secteur-prive-france-x-na88',
            'table' => 'urssaf_effectifs_national',
            'description' => 'Effectifs et masse salariale par secteur (national)',
        ],
        'effectifs-regions' => [
            'id' => 'nombre-etab-effectifs-salaries-et-masse-salariale-secteur-prive-regions-x-na27',
            'table' => 'urssaf_effectifs_regions',
            'description' => 'Effectifs et masse salariale par région et secteur',
        ],
        'effectifs-departements' => [
            'id' => 'effectifs-salaries-et-masse-salariale-du-secteur-prive-par-departement-x-grand-s',
            'table' => 'urssaf_effectifs_departements',
            'description' => 'Effectifs par département',
        ],
        'masse-salariale' => [
            'id' => 'masse-salariale-et-assiette-chomage-partiel-mensuelles-du-secteur-prive',
            'table' => 'urssaf_masse_salariale',
            'description' => 'Masse salariale mensuelle du secteur privé',
        ],
        'teletravail' => [
            'id' => 'urssaf-effectif-teletravail',
            'table' => 'urssaf_teletravail',
            'description' => 'Effectifs en télétravail',
        ],
    ];

    public function handle(): int
    {
        $dataset = $this->option('dataset');
        $year = $this->option('year') ?? date('Y');
        $limit = (int) $this->option('limit');

        $this->info('🏥 Import des données URSSAF');
        $this->info('   Source : https://open.urssaf.fr');
        $this->newLine();

        if ($dataset === 'all') {
            foreach (self::DATASETS as $key => $config) {
                $this->importDataset($key, $config, $year, $limit);
            }
        } elseif (isset(self::DATASETS[$dataset])) {
            $this->importDataset($dataset, self::DATASETS[$dataset], $year, $limit);
        } else {
            $this->error("Dataset inconnu : {$dataset}");
            $this->info('Datasets disponibles : '.implode(', ', array_keys(self::DATASETS)));

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Import terminé !');

        return Command::SUCCESS;
    }

    private function importDataset(string $key, array $config, int $year, int $limit): void
    {
        $this->info("📊 Import : {$config['description']}");

        try {
            // L'API URSSAF limite à 100 résultats par requête
            $pageSize = min($limit, 100);
            $offset = 0;
            $totalImported = 0;
            $totalAvailable = 0;
            $allRecords = [];

            // Paginer les résultats
            do {
                $url = self::BASE_URL."/{$config['id']}/records";

                $response = Http::timeout(60)->get($url, [
                    'limit' => $pageSize,
                    'offset' => $offset,
                    'order_by' => 'annee desc',
                ]);

                if (! $response->successful()) {
                    $this->warn('   ⚠️ Erreur API : '.$response->status());
                    break;
                }

                $data = $response->json();
                $records = $data['results'] ?? [];
                $totalAvailable = $data['total_count'] ?? 0;

                if (empty($records)) {
                    break;
                }

                $allRecords = array_merge($allRecords, $records);
                $offset += $pageSize;

                // Afficher la progression
                if ($offset % 500 === 0 || $offset >= min($limit, $totalAvailable)) {
                    $this->info('   ⬇️ Téléchargé '.count($allRecords).' / '.min($limit, $totalAvailable).' lignes...');
                }

            } while (count($records) === $pageSize && count($allRecords) < $limit);

            if (empty($allRecords)) {
                $this->warn('   ⚠️ Aucune donnée trouvée');

                return;
            }

            $this->info("   📦 {$totalAvailable} enregistrements disponibles sur l'API");
            $this->info('   ⬇️ Import de '.count($allRecords).' lignes...');

            // Sauvegarder dans une table dédiée
            $this->saveToDatabase($config['table'], $allRecords, $key);

            $this->info('   ✅ Importé avec succès');

        } catch (\Exception $e) {
            $this->error('   ❌ Erreur : '.$e->getMessage());
        }
    }

    private function saveToDatabase(string $table, array $records, string $type): void
    {
        // Créer la table si elle n'existe pas
        $this->ensureTableExists($table, $type);

        // Insérer les données
        $imported = 0;
        foreach ($records as $record) {
            try {
                $data = $this->mapRecord($record, $type);

                DB::table($table)->updateOrInsert(
                    ['hash' => md5(json_encode($data))],
                    array_merge($data, [
                        'raw_data' => json_encode($record),
                        'updated_at' => now(),
                    ])
                );
                $imported++;
            } catch (\Exception $e) {
                // Ignorer les erreurs individuelles
            }
        }

        $this->info("   💾 {$imported} enregistrements sauvegardés");
    }

    private function ensureTableExists(string $table, string $type): void
    {
        if (\Schema::hasTable($table)) {
            return;
        }

        \Schema::create($table, function ($t) {
            $t->id();
            $t->string('hash', 32)->unique();
            $t->integer('annee')->nullable();
            $t->string('trimestre', 10)->nullable();
            $t->string('region', 100)->nullable();
            $t->string('departement', 10)->nullable();
            $t->string('secteur_code', 100)->nullable(); // NA88 (ex: "26 Fabrication de produits informatiques")
            $t->string('secteur_libelle', 255)->nullable(); // Grand secteur (ex: "GS1 Industrie")
            $t->string('secteur_na38', 255)->nullable(); // NA38 (ex: "CI Fabrication...")
            $t->decimal('montant', 20, 2)->nullable();
            $t->integer('effectif')->nullable();
            $t->decimal('masse_salariale', 20, 2)->nullable();
            $t->integer('nombre')->nullable();
            $t->json('raw_data')->nullable();
            $t->timestamps();

            $t->index(['annee', 'secteur_libelle']); // Index sur grand secteur pour agrégation
            $t->index(['annee', 'secteur_code']);
            $t->index('region');
        });

        $this->info("   📋 Table {$table} créée");
    }

    private function mapRecord(array $record, string $type): array
    {
        // Mapping générique qui fonctionne avec tous les datasets URSSAF
        // Stocker le grand secteur séparément du sous-secteur
        $grandSecteur = $record['grand_secteur_d_activite'] ?? null;
        $secteurNa88 = $record['secteur_na88i'] ?? null;
        $secteurNa38 = $record['secteur_na38i'] ?? null;

        return [
            'annee' => isset($record['annee']) ? (int) $record['annee'] : null,
            'trimestre' => $record['trimestre'] ?? $record['mois'] ?? null,
            'region' => $record['region'] ?? $record['reg'] ?? null,
            'departement' => $record['departement'] ?? $record['dep'] ?? null,
            // Code du sous-secteur NA88 (ex: "26" pour électronique)
            'secteur_code' => $secteurNa88 ?? $secteurNa38 ?? $record['secteur_na17i'] ?? null,
            // Libellé du grand secteur (ex: "GS1 Industrie")
            'secteur_libelle' => $grandSecteur ?? $record['secteur'] ?? null,
            // Stocker aussi le secteur NA38 pour agrégation intermédiaire
            'secteur_na38' => $secteurNa38,
            'montant' => isset($record['masse_salariale']) ? (float) $record['masse_salariale'] : null,
            'effectif' => isset($record['effectifs_salaries_moyens']) ? (int) $record['effectifs_salaries_moyens'] :
                         (isset($record['effectifs']) ? (int) $record['effectifs'] : null),
            'masse_salariale' => isset($record['masse_salariale']) ? (float) $record['masse_salariale'] : null,
            'nombre' => isset($record['nombre_d_etablissements']) ? (int) $record['nombre_d_etablissements'] :
                       (isset($record['nombre']) ? (int) $record['nombre'] : null),
        ];
    }
}
