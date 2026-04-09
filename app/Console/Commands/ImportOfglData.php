<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Importe les données OFGL (Observatoire des Finances et de la Gestion publique Locales)
 *
 * Source : https://data.ofgl.fr
 *
 * Dataset : ofgl-base-communes-consolidee (13M+ lignes)
 * Pour le PoC on agrège par département et année
 */
class ImportOfglData extends Command
{
    protected $signature = 'ofgl:import 
                            {--year=2022 : Année à importer}
                            {--departement= : Code département spécifique (ex: 75)}
                            {--limit=100 : Nombre max de communes}';

    protected $description = 'Importe les données budgétaires des collectivités locales (OFGL)';

    private const API_URL = 'https://data.ofgl.fr/api/records/1.0/search/';

    public function handle(): int
    {
        $year = $this->option('year');
        $dep = $this->option('departement');
        $limit = (int) $this->option('limit');

        $this->info('🏛️ Import des données OFGL - Collectivités locales');
        $this->info('   Source : https://data.ofgl.fr');
        $this->info("   Année : {$year}");
        $this->newLine();

        // Assurer que la table existe
        $this->ensureTableExists();

        // Récupérer les agrégats par département
        $this->importDepartementStats($year, $dep);

        $this->newLine();
        $this->info('✅ Import terminé !');

        return Command::SUCCESS;
    }

    private function importDepartementStats(int $year, ?string $dep): void
    {
        $this->info('📊 Récupération des statistiques par département...');

        // On récupère les totaux des recettes et dépenses par département
        $agregats = [
            'Recettes réelles de fonctionnement' => 'recettes_fonctionnement',
            'Dépenses réelles de fonctionnement' => 'depenses_fonctionnement',
            'Recettes réelles d\'investissement' => 'recettes_investissement',
            'Dépenses réelles d\'investissement' => 'depenses_investissement',
            'Encours de dette au 31 décembre' => 'dette',
        ];

        foreach ($agregats as $agregat => $column) {
            $this->info("   → {$agregat}...");

            try {
                $refine = "exer:{$year}";
                if ($dep) {
                    $refine .= "&refine.dep_code={$dep}";
                }

                // Facet pour grouper par département
                $response = Http::timeout(60)->get(self::API_URL, [
                    'dataset' => 'ofgl-base-communes-consolidee',
                    'q' => "agregat:\"{$agregat}\"",
                    'refine.exer' => $year,
                    'facet' => 'dep_code',
                    'rows' => 0,
                ]);

                if (! $response->successful()) {
                    $this->warn('     ⚠️ Erreur API : '.$response->status());

                    continue;
                }

                $data = $response->json();
                $facets = $data['facet_groups'][0]['facets'] ?? [];

                if (empty($facets)) {
                    $this->warn('     ⚠️ Pas de données');

                    continue;
                }

                // Pour chaque département, on récupère le total
                $bar = $this->output->createProgressBar(count($facets));
                foreach ($facets as $facet) {
                    $depCode = $facet['name'];
                    $count = $facet['count'];

                    // Récupérer le montant total pour ce département
                    $this->fetchDepartementTotal($year, $depCode, $agregat, $column);
                    $bar->advance();
                }
                $bar->finish();
                $this->newLine();

            } catch (\Exception $e) {
                $this->error('     ❌ Erreur : '.$e->getMessage());
            }
        }
    }

    private function fetchDepartementTotal(int $year, string $depCode, string $agregat, string $column): void
    {
        try {
            $response = Http::timeout(30)->get(self::API_URL, [
                'dataset' => 'ofgl-base-communes-consolidee',
                'q' => "agregat:\"{$agregat}\"",
                'refine.exer' => $year,
                'refine.dep_code' => $depCode,
                'rows' => 0,
            ]);

            if (! $response->successful()) {
                return;
            }

            $data = $response->json();
            $total = $data['nhits'] ?? 0;

            // On doit calculer la somme - pas disponible directement via l'API
            // Pour le PoC, on stocke le nombre de communes
            DB::table('ofgl_departements')->updateOrInsert(
                ['annee' => $year, 'dep_code' => $depCode],
                [
                    'nb_communes_'.$column => $total,
                    'updated_at' => now(),
                ]
            );

        } catch (\Exception $e) {
            // Ignorer les erreurs individuelles
        }
    }

    private function ensureTableExists(): void
    {
        if (\Schema::hasTable('ofgl_departements')) {
            return;
        }

        \Schema::create('ofgl_departements', function ($table) {
            $table->id();
            $table->integer('annee');
            $table->string('dep_code', 3);
            $table->string('dep_name', 100)->nullable();
            $table->string('reg_name', 100)->nullable();
            $table->decimal('recettes_fonctionnement', 20, 2)->nullable();
            $table->decimal('depenses_fonctionnement', 20, 2)->nullable();
            $table->decimal('recettes_investissement', 20, 2)->nullable();
            $table->decimal('depenses_investissement', 20, 2)->nullable();
            $table->decimal('dette', 20, 2)->nullable();
            $table->integer('nb_communes')->nullable();
            $table->integer('population')->nullable();
            $table->integer('nb_communes_recettes_fonctionnement')->nullable();
            $table->integer('nb_communes_depenses_fonctionnement')->nullable();
            $table->integer('nb_communes_recettes_investissement')->nullable();
            $table->integer('nb_communes_depenses_investissement')->nullable();
            $table->integer('nb_communes_dette')->nullable();
            $table->timestamps();

            $table->unique(['annee', 'dep_code']);
            $table->index('annee');
        });

        $this->info('   📋 Table ofgl_departements créée');
    }
}
