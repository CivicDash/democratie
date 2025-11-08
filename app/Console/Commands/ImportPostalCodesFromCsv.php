<?php

namespace App\Console\Commands;

use App\Models\FrenchPostalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ImportPostalCodesFromCsv extends Command
{
    protected $signature = 'postal-codes:import-csv {--fresh : Vider la table avant l\'import}';
    protected $description = 'Importe les codes postaux français depuis le CSV de data.gouv.fr';

    public function handle()
    {
        $this->info('🇫🇷 Import des codes postaux depuis CSV data.gouv.fr...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des données existantes...');
            FrenchPostalCode::truncate();
        }

        // URL du CSV
        $csvUrl = 'https://www.data.gouv.fr/api/1/datasets/r/008a2dda-2c60-4b63-b910-998f6f818089';
        
        $this->info('📥 Téléchargement du fichier CSV...');
        
        try {
            $response = Http::timeout(60)->get($csvUrl);
            
            if ($response->failed()) {
                $this->error('❌ Erreur lors du téléchargement du CSV');
                return Command::FAILURE;
            }

            $csvContent = $response->body();
            $this->info('✓ Fichier téléchargé (' . strlen($csvContent) . ' octets)');
            $this->newLine();

            // Parser le CSV
            $lines = explode("\n", $csvContent);
            $header = array_shift($lines); // Retirer l'en-tête
            
            $this->info('📊 Parsing et import des données...');
            $bar = $this->output->createProgressBar(count($lines));
            $bar->setFormat('verbose');
            
            $imported = 0;
            $errors = 0;
            $batch = [];
            $batchSize = 500; // Import par batch pour performance

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $data = str_getcsv($line, ';');
                
                // Format CSV: Code_commune_INSEE;Nom_de_la_commune;Code_postal;Libellé_d_acheminement;Ligne_5
                if (count($data) < 4) {
                    $errors++;
                    continue;
                }

                $inseeCode = $data[0] ?? null;
                $cityName = $data[1] ?? null;
                $postalCode = $data[2] ?? null;
                $deliveryLabel = $data[3] ?? null;
                
                if (!$inseeCode || !$postalCode) {
                    $errors++;
                    continue;
                }

                // Extraire département (2 premiers chiffres du code INSEE)
                $departmentCode = substr($inseeCode, 0, 2);
                
                // Départements spéciaux (Corse, DOM)
                if (substr($inseeCode, 0, 3) === '97') {
                    $departmentCode = substr($inseeCode, 0, 3);
                }
                if (substr($inseeCode, 0, 2) === '98') {
                    $departmentCode = substr($inseeCode, 0, 3);
                }
                if (in_array(substr($inseeCode, 0, 2), ['2A', '2B'])) {
                    $departmentCode = substr($inseeCode, 0, 2);
                }

                $batch[] = [
                    'postal_code' => $postalCode,
                    'insee_code' => $inseeCode,
                    'city_name' => $cityName ?: $deliveryLabel,
                    'department_code' => $departmentCode,
                    'department_name' => $this->getDepartmentName($departmentCode),
                    'region_code' => null, // À compléter si besoin
                    'region_name' => null,
                    'circonscription' => $departmentCode . '-01', // Simplifié
                    'latitude' => null,
                    'longitude' => null,
                    'population' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $batchSize) {
                    // Insert batch avec gestion des doublons
                    foreach ($batch as $record) {
                        try {
                            FrenchPostalCode::updateOrCreate(
                                [
                                    'postal_code' => $record['postal_code'],
                                    'insee_code' => $record['insee_code'],
                                ],
                                $record
                            );
                            $imported++;
                        } catch (\Exception $e) {
                            $errors++;
                        }
                    }
                    $batch = [];
                    $bar->advance($batchSize);
                }
            }

            // Insérer le dernier batch
            if (!empty($batch)) {
                foreach ($batch as $record) {
                    try {
                        FrenchPostalCode::updateOrCreate(
                            [
                                'postal_code' => $record['postal_code'],
                                'insee_code' => $record['insee_code'],
                            ],
                            $record
                        );
                        $imported++;
                    } catch (\Exception $e) {
                        $errors++;
                    }
                }
                $bar->advance(count($batch));
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Import terminé !");
            $this->info("   ✓ {$imported} codes postaux importés");
            if ($errors > 0) {
                $this->warn("   ⚠️  {$errors} erreurs ignorées");
            }
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'import: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Obtenir le nom du département (simplifié)
     */
    private function getDepartmentName(string $code): string
    {
        $departments = [
            '01' => 'Ain', '02' => 'Aisne', '03' => 'Allier', '04' => 'Alpes-de-Haute-Provence',
            '05' => 'Hautes-Alpes', '06' => 'Alpes-Maritimes', '07' => 'Ardèche', '08' => 'Ardennes',
            '09' => 'Ariège', '10' => 'Aube', '11' => 'Aude', '12' => 'Aveyron',
            '13' => 'Bouches-du-Rhône', '14' => 'Calvados', '15' => 'Cantal', '16' => 'Charente',
            '17' => 'Charente-Maritime', '18' => 'Cher', '19' => 'Corrèze', '21' => 'Côte-d\'Or',
            '22' => 'Côtes-d\'Armor', '23' => 'Creuse', '24' => 'Dordogne', '25' => 'Doubs',
            '26' => 'Drôme', '27' => 'Eure', '28' => 'Eure-et-Loir', '29' => 'Finistère',
            '2A' => 'Corse-du-Sud', '2B' => 'Haute-Corse', '30' => 'Gard', '31' => 'Haute-Garonne',
            '32' => 'Gers', '33' => 'Gironde', '34' => 'Hérault', '35' => 'Ille-et-Vilaine',
            '36' => 'Indre', '37' => 'Indre-et-Loire', '38' => 'Isère', '39' => 'Jura',
            '40' => 'Landes', '41' => 'Loir-et-Cher', '42' => 'Loire', '43' => 'Haute-Loire',
            '44' => 'Loire-Atlantique', '45' => 'Loiret', '46' => 'Lot', '47' => 'Lot-et-Garonne',
            '48' => 'Lozère', '49' => 'Maine-et-Loire', '50' => 'Manche', '51' => 'Marne',
            '52' => 'Haute-Marne', '53' => 'Mayenne', '54' => 'Meurthe-et-Moselle', '55' => 'Meuse',
            '56' => 'Morbihan', '57' => 'Moselle', '58' => 'Nièvre', '59' => 'Nord',
            '60' => 'Oise', '61' => 'Orne', '62' => 'Pas-de-Calais', '63' => 'Puy-de-Dôme',
            '64' => 'Pyrénées-Atlantiques', '65' => 'Hautes-Pyrénées', '66' => 'Pyrénées-Orientales',
            '67' => 'Bas-Rhin', '68' => 'Haut-Rhin', '69' => 'Rhône', '70' => 'Haute-Saône',
            '71' => 'Saône-et-Loire', '72' => 'Sarthe', '73' => 'Savoie', '74' => 'Haute-Savoie',
            '75' => 'Paris', '76' => 'Seine-Maritime', '77' => 'Seine-et-Marne', '78' => 'Yvelines',
            '79' => 'Deux-Sèvres', '80' => 'Somme', '81' => 'Tarn', '82' => 'Tarn-et-Garonne',
            '83' => 'Var', '84' => 'Vaucluse', '85' => 'Vendée', '86' => 'Vienne',
            '87' => 'Haute-Vienne', '88' => 'Vosges', '89' => 'Yonne', '90' => 'Territoire de Belfort',
            '91' => 'Essonne', '92' => 'Hauts-de-Seine', '93' => 'Seine-Saint-Denis', '94' => 'Val-de-Marne',
            '95' => 'Val-d\'Oise', '971' => 'Guadeloupe', '972' => 'Martinique', '973' => 'Guyane',
            '974' => 'La Réunion', '976' => 'Mayotte', '98' => 'Monaco',
        ];

        return $departments[$code] ?? $code;
    }
}

