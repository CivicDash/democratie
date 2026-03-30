<?php

namespace App\Console\Commands;

use App\Models\FrenchPostalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportFrenchPostalCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postal-codes:import {--fresh : Vider la table avant l\'import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importe les codes postaux français depuis l\'API geo.api.gouv.fr';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🇫🇷 Import des codes postaux français...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des données existantes...');
            FrenchPostalCode::truncate();
        }

        // Étape 1 : Récupérer tous les départements
        $this->info('📍 Récupération de la liste des départements...');
        $departments = $this->getDepartments();
        $this->info("✓ {$departments->count()} départements trouvés");
        $this->newLine();

        // Étape 2 : Pour chaque département, récupérer les communes
        $bar = $this->output->createProgressBar($departments->count());
        $bar->setFormat('verbose');

        $totalCities = 0;
        $totalPostalCodes = 0;

        foreach ($departments as $department) {
            $deptCode = $department['code'];
            $deptName = $department['nom'];

            // Récupérer les communes du département
            $communes = $this->getCommunesByDepartment($deptCode);

            foreach ($communes as $commune) {
                // Chaque commune peut avoir plusieurs codes postaux
                $codesPostaux = $commune['codesPostaux'] ?? [$commune['codePostal'] ?? null];

                foreach ($codesPostaux as $codePostal) {
                    if (! $codePostal) {
                        continue;
                    }

                    // Déterminer la circonscription (simplifié pour l'instant)
                    $circonscription = $this->guessCirconscription($deptCode, $commune);

                    FrenchPostalCode::updateOrCreate(
                        [
                            'postal_code' => $codePostal,
                            'insee_code' => $commune['code'],
                        ],
                        [
                            'city_name' => $commune['nom'],
                            'department_code' => $deptCode,
                            'department_name' => $deptName,
                            'region_code' => $commune['codeRegion'] ?? null,
                            'region_name' => $commune['region']['nom'] ?? null,
                            'circonscription' => $circonscription,
                            'latitude' => $commune['centre']['coordinates'][1] ?? null,
                            'longitude' => $commune['centre']['coordinates'][0] ?? null,
                            'population' => $commune['population'] ?? null,
                        ]
                    );

                    $totalPostalCodes++;
                }

                $totalCities++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Import terminé !');
        $this->info("   📊 {$totalCities} communes importées");
        $this->info("   📮 {$totalPostalCodes} codes postaux créés");

        return Command::SUCCESS;
    }

    /**
     * Récupérer la liste des départements depuis l'API
     */
    private function getDepartments()
    {
        $response = Http::get('https://geo.api.gouv.fr/departements', [
            'fields' => 'nom,code,codeRegion',
        ]);

        if ($response->failed()) {
            $this->error('❌ Erreur lors de la récupération des départements');

            return collect();
        }

        return collect($response->json());
    }

    /**
     * Récupérer les communes d'un département
     */
    private function getCommunesByDepartment(string $departmentCode)
    {
        $response = Http::get("https://geo.api.gouv.fr/departements/{$departmentCode}/communes", [
            'fields' => 'nom,code,codesPostaux,codeRegion,region,centre,population',
            'format' => 'json',
            'geometry' => 'centre',
        ]);

        if ($response->failed()) {
            $this->warn("⚠️  Erreur pour le département {$departmentCode}");

            return [];
        }

        return $response->json();
    }

    /**
     * Deviner la circonscription (simplifié)
     * TODO: Améliorer avec une vraie correspondance commune -> circonscription
     */
    private function guessCirconscription(string $deptCode, array $commune): ?string
    {
        // Pour l'instant, on met juste le département
        // Il faudrait une vraie table de correspondance commune -> circonscription
        // qui peut être obtenue via l'API de l'Assemblée Nationale

        // Format: 75-01, 13-05, etc.
        // On va mettre 01 par défaut, mais c'est à affiner
        return $deptCode.'-01';
    }
}
