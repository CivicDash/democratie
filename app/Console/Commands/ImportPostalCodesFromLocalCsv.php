<?php

namespace App\Console\Commands;

use App\Models\FrenchPostalCode;
use Illuminate\Console\Command;

class ImportPostalCodesFromLocalCsv extends Command
{
    protected $signature = 'postal-codes:import-local {--fresh : Vider la table avant l\'import}';

    protected $description = 'Importe les codes postaux français depuis le fichier CSV local (public/data)';

    public function handle()
    {
        $this->info('🇫🇷 Import des codes postaux depuis fichier CSV local...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des données existantes...');
            FrenchPostalCode::truncate();
        }

        // Chemin du fichier CSV local
        $csvPath = public_path('data/019HexaSmal.csv');

        if (! file_exists($csvPath)) {
            $this->error('❌ Fichier CSV introuvable: '.$csvPath);

            return Command::FAILURE;
        }

        $this->info('📂 Lecture du fichier: '.basename($csvPath));
        $this->newLine();

        try {
            $handle = fopen($csvPath, 'r');

            if (! $handle) {
                $this->error('❌ Impossible d\'ouvrir le fichier CSV');

                return Command::FAILURE;
            }

            // Lire l'en-tête
            $header = fgetcsv($handle, 1000, ';');

            // Compter les lignes pour la barre de progression
            $lineCount = count(file($csvPath)) - 1;

            $this->info("📊 {$lineCount} lignes à traiter");
            $bar = $this->output->createProgressBar($lineCount);
            $bar->setFormat('verbose');

            $imported = 0;
            $errors = 0;
            $batch = [];
            $batchSize = 500; // Import par batch pour performance

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                // Format CSV: Code_commune_INSEE;Nom_de_la_commune;Code_postal;Libellé_d_acheminement;Ligne_5
                if (count($data) < 4) {
                    $errors++;
                    $bar->advance();

                    continue;
                }

                $inseeCode = trim($data[0] ?? '');
                $cityName = trim($data[1] ?? '');
                $postalCode = trim($data[2] ?? '');
                $deliveryLabel = trim($data[3] ?? '');

                if (empty($inseeCode) || empty($postalCode) || ! is_numeric($postalCode)) {
                    $errors++;
                    $bar->advance();

                    continue;
                }

                // Extraire département
                $departmentCode = $this->extractDepartmentCode($inseeCode, $postalCode);

                $batch[] = [
                    'postal_code' => $postalCode,
                    'insee_code' => $inseeCode,
                    'city_name' => ! empty($cityName) ? $cityName : $deliveryLabel,
                    'department_code' => $departmentCode,
                    'department_name' => $this->getDepartmentName($departmentCode),
                    'region_code' => null,
                    'region_name' => null,
                    'circonscription' => $departmentCode.'-01', // Simplifié
                    'latitude' => null,
                    'longitude' => null,
                    'population' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $batchSize) {
                    $imported += $this->insertBatch($batch);
                    $batch = [];
                    $bar->advance($batchSize);
                }
            }

            // Insérer le dernier batch
            if (! empty($batch)) {
                $imported += $this->insertBatch($batch);
                $bar->advance(count($batch));
            }

            fclose($handle);
            $bar->finish();
            $this->newLine(2);

            $this->info('✅ Import terminé !');
            $this->info("   ✓ {$imported} codes postaux importés");
            if ($errors > 0) {
                $this->warn("   ⚠️  {$errors} lignes ignorées");
            }

            // Vérification finale
            $total = FrenchPostalCode::count();
            $this->newLine();
            $this->info("📊 Total en base: {$total} codes postaux");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'import: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Insérer un batch de données
     */
    private function insertBatch(array $batch): int
    {
        $count = 0;
        foreach ($batch as $record) {
            try {
                // On utilise seulement postal_code + city_name pour éviter les conflits avec insee_code nullable
                FrenchPostalCode::updateOrCreate(
                    [
                        'postal_code' => $record['postal_code'],
                        'city_name' => $record['city_name'],
                    ],
                    $record
                );
                $count++;
            } catch (\Exception $e) {
                // Log l'erreur pour debug
                if ($count < 5) { // Afficher seulement les 5 premières erreurs
                    $this->warn('⚠️  Erreur: '.$e->getMessage());
                }
            }
        }

        return $count;
    }

    /**
     * Extraire le code département
     */
    private function extractDepartmentCode(string $inseeCode, string $postalCode): string
    {
        // DOM-TOM (97x, 98x)
        if (preg_match('/^97[0-6]/', $inseeCode)) {
            return substr($inseeCode, 0, 3);
        }
        if (preg_match('/^98[0-8]/', $inseeCode)) {
            return substr($inseeCode, 0, 3);
        }

        // Corse (2A, 2B)
        if (preg_match('/^2[AB]/', $inseeCode)) {
            return substr($inseeCode, 0, 2);
        }

        // Fallback sur les 2 premiers caractères du code postal
        if (strlen($postalCode) >= 2) {
            $prefix = substr($postalCode, 0, 2);

            // Corse via code postal
            if ($prefix === '20') {
                // Corse-du-Sud: 200xx-201xx, Haute-Corse: 202xx-206xx
                $thirdDigit = intval(substr($postalCode, 2, 1));

                return $thirdDigit <= 1 ? '2A' : '2B';
            }

            return $prefix;
        }

        return substr($inseeCode, 0, 2);
    }

    /**
     * Obtenir le nom du département
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
            '974' => 'La Réunion', '975' => 'Saint-Pierre-et-Miquelon', '976' => 'Mayotte',
            '977' => 'Saint-Barthélemy', '978' => 'Saint-Martin', '986' => 'Wallis-et-Futuna',
            '987' => 'Polynésie française', '988' => 'Nouvelle-Calédonie',
        ];

        return $departments[$code] ?? $code;
    }
}
