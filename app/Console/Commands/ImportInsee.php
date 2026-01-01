<?php

namespace App\Console\Commands;

use App\Models\InseeCommune;
use App\Models\InseeDepartement;
use App\Models\InseeRegion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportInsee extends Command
{
    protected $signature = 'import:insee 
                            {--type=all : Type de données (regions, departements, communes, all)}
                            {--force : Forcer le réimport}';

    protected $description = 'Import des données INSEE (démographie, économie)';

    // Données régions françaises (2024)
    private const REGIONS = [
        ['code' => '84', 'nom' => 'Auvergne-Rhône-Alpes', 'chef_lieu' => 'Lyon', 'population' => 8_078_000, 'pib' => 275_000, 'taux_chomage' => 6.2, 'nb_departements' => 12],
        ['code' => '27', 'nom' => 'Bourgogne-Franche-Comté', 'chef_lieu' => 'Dijon', 'population' => 2_800_000, 'pib' => 78_000, 'taux_chomage' => 6.5, 'nb_departements' => 8],
        ['code' => '53', 'nom' => 'Bretagne', 'chef_lieu' => 'Rennes', 'population' => 3_373_000, 'pib' => 98_000, 'taux_chomage' => 5.8, 'nb_departements' => 4],
        ['code' => '24', 'nom' => 'Centre-Val de Loire', 'chef_lieu' => 'Orléans', 'population' => 2_573_000, 'pib' => 73_000, 'taux_chomage' => 6.8, 'nb_departements' => 6],
        ['code' => '94', 'nom' => 'Corse', 'chef_lieu' => 'Ajaccio', 'population' => 344_000, 'pib' => 10_000, 'taux_chomage' => 7.2, 'nb_departements' => 2],
        ['code' => '44', 'nom' => 'Grand Est', 'chef_lieu' => 'Strasbourg', 'population' => 5_562_000, 'pib' => 158_000, 'taux_chomage' => 7.0, 'nb_departements' => 10],
        ['code' => '32', 'nom' => 'Hauts-de-France', 'chef_lieu' => 'Lille', 'population' => 5_997_000, 'pib' => 161_000, 'taux_chomage' => 9.2, 'nb_departements' => 5],
        ['code' => '11', 'nom' => 'Île-de-France', 'chef_lieu' => 'Paris', 'population' => 12_262_000, 'pib' => 710_000, 'taux_chomage' => 7.1, 'nb_departements' => 8],
        ['code' => '28', 'nom' => 'Normandie', 'chef_lieu' => 'Rouen', 'population' => 3_327_000, 'pib' => 95_000, 'taux_chomage' => 7.3, 'nb_departements' => 5],
        ['code' => '75', 'nom' => 'Nouvelle-Aquitaine', 'chef_lieu' => 'Bordeaux', 'population' => 6_033_000, 'pib' => 173_000, 'taux_chomage' => 6.5, 'nb_departements' => 12],
        ['code' => '76', 'nom' => 'Occitanie', 'chef_lieu' => 'Toulouse', 'population' => 5_973_000, 'pib' => 170_000, 'taux_chomage' => 8.2, 'nb_departements' => 13],
        ['code' => '52', 'nom' => 'Pays de la Loire', 'chef_lieu' => 'Nantes', 'population' => 3_838_000, 'pib' => 113_000, 'taux_chomage' => 5.9, 'nb_departements' => 5],
        ['code' => '93', 'nom' => 'Provence-Alpes-Côte d\'Azur', 'chef_lieu' => 'Marseille', 'population' => 5_098_000, 'pib' => 162_000, 'taux_chomage' => 8.5, 'nb_departements' => 6],
        // Outre-mer
        ['code' => '01', 'nom' => 'Guadeloupe', 'chef_lieu' => 'Basse-Terre', 'population' => 384_000, 'pib' => 9_500, 'taux_chomage' => 17.5, 'nb_departements' => 1],
        ['code' => '02', 'nom' => 'Martinique', 'chef_lieu' => 'Fort-de-France', 'population' => 361_000, 'pib' => 9_000, 'taux_chomage' => 12.8, 'nb_departements' => 1],
        ['code' => '03', 'nom' => 'Guyane', 'chef_lieu' => 'Cayenne', 'population' => 294_000, 'pib' => 4_500, 'taux_chomage' => 18.0, 'nb_departements' => 1],
        ['code' => '04', 'nom' => 'La Réunion', 'chef_lieu' => 'Saint-Denis', 'population' => 873_000, 'pib' => 19_000, 'taux_chomage' => 17.0, 'nb_departements' => 1],
        ['code' => '06', 'nom' => 'Mayotte', 'chef_lieu' => 'Mamoudzou', 'population' => 321_000, 'pib' => 2_800, 'taux_chomage' => 30.0, 'nb_departements' => 1],
    ];

    // Données départements (sélection)
    private const DEPARTEMENTS = [
        ['code' => '01', 'nom' => 'Ain', 'code_region' => '84', 'population' => 657_000, 'taux_chomage' => 5.4],
        ['code' => '02', 'nom' => 'Aisne', 'code_region' => '32', 'population' => 527_000, 'taux_chomage' => 11.2],
        ['code' => '03', 'nom' => 'Allier', 'code_region' => '84', 'population' => 337_000, 'taux_chomage' => 7.8],
        ['code' => '06', 'nom' => 'Alpes-Maritimes', 'code_region' => '93', 'population' => 1_094_000, 'taux_chomage' => 8.0],
        ['code' => '13', 'nom' => 'Bouches-du-Rhône', 'code_region' => '93', 'population' => 2_043_000, 'taux_chomage' => 9.5],
        ['code' => '31', 'nom' => 'Haute-Garonne', 'code_region' => '76', 'population' => 1_415_000, 'taux_chomage' => 7.2],
        ['code' => '33', 'nom' => 'Gironde', 'code_region' => '75', 'population' => 1_623_000, 'taux_chomage' => 6.8],
        ['code' => '34', 'nom' => 'Hérault', 'code_region' => '76', 'population' => 1_175_000, 'taux_chomage' => 10.5],
        ['code' => '35', 'nom' => 'Ille-et-Vilaine', 'code_region' => '53', 'population' => 1_093_000, 'taux_chomage' => 5.5],
        ['code' => '44', 'nom' => 'Loire-Atlantique', 'code_region' => '52', 'population' => 1_450_000, 'taux_chomage' => 5.8],
        ['code' => '59', 'nom' => 'Nord', 'code_region' => '32', 'population' => 2_608_000, 'taux_chomage' => 10.5],
        ['code' => '62', 'nom' => 'Pas-de-Calais', 'code_region' => '32', 'population' => 1_468_000, 'taux_chomage' => 10.8],
        ['code' => '67', 'nom' => 'Bas-Rhin', 'code_region' => '44', 'population' => 1_140_000, 'taux_chomage' => 6.2],
        ['code' => '68', 'nom' => 'Haut-Rhin', 'code_region' => '44', 'population' => 766_000, 'taux_chomage' => 6.5],
        ['code' => '69', 'nom' => 'Rhône', 'code_region' => '84', 'population' => 1_876_000, 'taux_chomage' => 6.8],
        ['code' => '75', 'nom' => 'Paris', 'code_region' => '11', 'population' => 2_165_000, 'taux_chomage' => 7.0],
        ['code' => '77', 'nom' => 'Seine-et-Marne', 'code_region' => '11', 'population' => 1_428_000, 'taux_chomage' => 6.8],
        ['code' => '78', 'nom' => 'Yvelines', 'code_region' => '11', 'population' => 1_448_000, 'taux_chomage' => 5.8],
        ['code' => '83', 'nom' => 'Var', 'code_region' => '93', 'population' => 1_076_000, 'taux_chomage' => 8.2],
        ['code' => '91', 'nom' => 'Essonne', 'code_region' => '11', 'population' => 1_306_000, 'taux_chomage' => 6.5],
        ['code' => '92', 'nom' => 'Hauts-de-Seine', 'code_region' => '11', 'population' => 1_624_000, 'taux_chomage' => 6.0],
        ['code' => '93', 'nom' => 'Seine-Saint-Denis', 'code_region' => '11', 'population' => 1_644_000, 'taux_chomage' => 10.5],
        ['code' => '94', 'nom' => 'Val-de-Marne', 'code_region' => '11', 'population' => 1_407_000, 'taux_chomage' => 7.2],
        ['code' => '95', 'nom' => 'Val-d\'Oise', 'code_region' => '11', 'population' => 1_249_000, 'taux_chomage' => 8.0],
    ];

    public function handle(): int
    {
        $type = $this->option('type');
        $force = $this->option('force');

        $this->info('📊 Import des données INSEE');

        if ($type === 'all' || $type === 'regions') {
            $this->importRegions($force);
        }

        if ($type === 'all' || $type === 'departements') {
            $this->importDepartements($force);
        }

        $this->newLine();
        $this->info('✅ Import INSEE terminé !');
        $this->displayStats();

        return Command::SUCCESS;
    }

    private function importRegions(bool $force): void
    {
        $this->info('🗺️ Import des régions...');

        if ($force) {
            InseeRegion::truncate();
        }

        $bar = $this->output->createProgressBar(count(self::REGIONS));

        foreach (self::REGIONS as $data) {
            InseeRegion::updateOrCreate(
                ['code' => $data['code']],
                [
                    'nom' => $data['nom'],
                    'chef_lieu' => $data['chef_lieu'],
                    'population' => $data['population'],
                    'population_annee' => 2024,
                    'pib' => $data['pib'] * 1_000_000, // Convertir en €
                    'pib_par_habitant' => ($data['pib'] * 1_000_000) / $data['population'],
                    'taux_chomage' => $data['taux_chomage'],
                    'nb_departements' => $data['nb_departements'],
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importDepartements(bool $force): void
    {
        $this->info('📍 Import des départements...');

        if ($force) {
            InseeDepartement::truncate();
        }

        $bar = $this->output->createProgressBar(count(self::DEPARTEMENTS));

        foreach (self::DEPARTEMENTS as $data) {
            InseeDepartement::updateOrCreate(
                ['code' => $data['code']],
                [
                    'nom' => $data['nom'],
                    'code_region' => $data['code_region'],
                    'population' => $data['population'],
                    'population_annee' => 2024,
                    'taux_chomage' => $data['taux_chomage'],
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function displayStats(): void
    {
        $this->newLine();
        $this->table(
            ['Indicateur', 'Valeur'],
            [
                ['Régions', InseeRegion::count()],
                ['Départements', InseeDepartement::count()],
                ['Population France (régions)', number_format(InseeRegion::sum('population'), 0, ',', ' ')],
            ]
        );
    }
}
