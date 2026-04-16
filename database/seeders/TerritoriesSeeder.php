<?php

namespace Database\Seeders;

use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use Illuminate\Database\Seeder;

class TerritoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🇫🇷 Seeding French territories...');

        // ==================== RÉGIONS ====================

        $regions = [
            ['code' => '84', 'name' => 'Auvergne-Rhône-Alpes'],
            ['code' => '27', 'name' => 'Bourgogne-Franche-Comté'],
            ['code' => '53', 'name' => 'Bretagne'],
            ['code' => '24', 'name' => 'Centre-Val de Loire'],
            ['code' => '94', 'name' => 'Corse'],
            ['code' => '44', 'name' => 'Grand Est'],
            ['code' => '32', 'name' => 'Hauts-de-France'],
            ['code' => '11', 'name' => 'Île-de-France'],
            ['code' => '28', 'name' => 'Normandie'],
            ['code' => '75', 'name' => 'Nouvelle-Aquitaine'],
            ['code' => '76', 'name' => 'Occitanie'],
            ['code' => '52', 'name' => 'Pays de la Loire'],
            ['code' => '93', 'name' => "Provence-Alpes-Côte d'Azur"],
        ];

        foreach ($regions as $regionData) {
            TerritoryRegion::create($regionData);
        }

        $this->command->info('✓ 13 régions créées');

        // ==================== DÉPARTEMENTS ====================

        $departments = [
            // Auvergne-Rhône-Alpes (84)
            ['code' => '01', 'name' => 'Ain', 'region_code' => '84'],
            ['code' => '03', 'name' => 'Allier', 'region_code' => '84'],
            ['code' => '07', 'name' => 'Ardèche', 'region_code' => '84'],
            ['code' => '15', 'name' => 'Cantal', 'region_code' => '84'],
            ['code' => '26', 'name' => 'Drôme', 'region_code' => '84'],
            ['code' => '38', 'name' => 'Isère', 'region_code' => '84'],
            ['code' => '42', 'name' => 'Loire', 'region_code' => '84'],
            ['code' => '43', 'name' => 'Haute-Loire', 'region_code' => '84'],
            ['code' => '63', 'name' => 'Puy-de-Dôme', 'region_code' => '84'],
            ['code' => '69', 'name' => 'Rhône', 'region_code' => '84'],
            ['code' => '73', 'name' => 'Savoie', 'region_code' => '84'],
            ['code' => '74', 'name' => 'Haute-Savoie', 'region_code' => '84'],

            // Bourgogne-Franche-Comté (27)
            ['code' => '21', 'name' => "Côte-d'Or", 'region_code' => '27'],
            ['code' => '25', 'name' => 'Doubs', 'region_code' => '27'],
            ['code' => '39', 'name' => 'Jura', 'region_code' => '27'],
            ['code' => '58', 'name' => 'Nièvre', 'region_code' => '27'],
            ['code' => '70', 'name' => 'Haute-Saône', 'region_code' => '27'],
            ['code' => '71', 'name' => 'Saône-et-Loire', 'region_code' => '27'],
            ['code' => '89', 'name' => 'Yonne', 'region_code' => '27'],
            ['code' => '90', 'name' => 'Territoire de Belfort', 'region_code' => '27'],

            // Bretagne (53)
            ['code' => '22', 'name' => "Côtes-d'Armor", 'region_code' => '53'],
            ['code' => '29', 'name' => 'Finistère', 'region_code' => '53'],
            ['code' => '35', 'name' => 'Ille-et-Vilaine', 'region_code' => '53'],
            ['code' => '56', 'name' => 'Morbihan', 'region_code' => '53'],

            // Centre-Val de Loire (24)
            ['code' => '18', 'name' => 'Cher', 'region_code' => '24'],
            ['code' => '28', 'name' => 'Eure-et-Loir', 'region_code' => '24'],
            ['code' => '36', 'name' => 'Indre', 'region_code' => '24'],
            ['code' => '37', 'name' => 'Indre-et-Loire', 'region_code' => '24'],
            ['code' => '41', 'name' => 'Loir-et-Cher', 'region_code' => '24'],
            ['code' => '45', 'name' => 'Loiret', 'region_code' => '24'],

            // Corse (94)
            ['code' => '2A', 'name' => 'Corse-du-Sud', 'region_code' => '94'],
            ['code' => '2B', 'name' => 'Haute-Corse', 'region_code' => '94'],

            // Grand Est (44)
            ['code' => '08', 'name' => 'Ardennes', 'region_code' => '44'],
            ['code' => '10', 'name' => 'Aube', 'region_code' => '44'],
            ['code' => '51', 'name' => 'Marne', 'region_code' => '44'],
            ['code' => '52', 'name' => 'Haute-Marne', 'region_code' => '44'],
            ['code' => '54', 'name' => 'Meurthe-et-Moselle', 'region_code' => '44'],
            ['code' => '55', 'name' => 'Meuse', 'region_code' => '44'],
            ['code' => '57', 'name' => 'Moselle', 'region_code' => '44'],
            ['code' => '67', 'name' => 'Bas-Rhin', 'region_code' => '44'],
            ['code' => '68', 'name' => 'Haut-Rhin', 'region_code' => '44'],
            ['code' => '88', 'name' => 'Vosges', 'region_code' => '44'],

            // Hauts-de-France (32)
            ['code' => '02', 'name' => 'Aisne', 'region_code' => '32'],
            ['code' => '59', 'name' => 'Nord', 'region_code' => '32'],
            ['code' => '60', 'name' => 'Oise', 'region_code' => '32'],
            ['code' => '62', 'name' => 'Pas-de-Calais', 'region_code' => '32'],
            ['code' => '80', 'name' => 'Somme', 'region_code' => '32'],

            // Île-de-France (11)
            ['code' => '75', 'name' => 'Paris', 'region_code' => '11'],
            ['code' => '77', 'name' => 'Seine-et-Marne', 'region_code' => '11'],
            ['code' => '78', 'name' => 'Yvelines', 'region_code' => '11'],
            ['code' => '91', 'name' => 'Essonne', 'region_code' => '11'],
            ['code' => '92', 'name' => 'Hauts-de-Seine', 'region_code' => '11'],
            ['code' => '93', 'name' => 'Seine-Saint-Denis', 'region_code' => '11'],
            ['code' => '94', 'name' => 'Val-de-Marne', 'region_code' => '11'],
            ['code' => '95', 'name' => "Val-d'Oise", 'region_code' => '11'],

            // Normandie (28)
            ['code' => '14', 'name' => 'Calvados', 'region_code' => '28'],
            ['code' => '27', 'name' => 'Eure', 'region_code' => '28'],
            ['code' => '50', 'name' => 'Manche', 'region_code' => '28'],
            ['code' => '61', 'name' => 'Orne', 'region_code' => '28'],
            ['code' => '76', 'name' => 'Seine-Maritime', 'region_code' => '28'],

            // Nouvelle-Aquitaine (75)
            ['code' => '16', 'name' => 'Charente', 'region_code' => '75'],
            ['code' => '17', 'name' => 'Charente-Maritime', 'region_code' => '75'],
            ['code' => '19', 'name' => 'Corrèze', 'region_code' => '75'],
            ['code' => '23', 'name' => 'Creuse', 'region_code' => '75'],
            ['code' => '24', 'name' => 'Dordogne', 'region_code' => '75'],
            ['code' => '33', 'name' => 'Gironde', 'region_code' => '75'],
            ['code' => '40', 'name' => 'Landes', 'region_code' => '75'],
            ['code' => '47', 'name' => 'Lot-et-Garonne', 'region_code' => '75'],
            ['code' => '64', 'name' => 'Pyrénées-Atlantiques', 'region_code' => '75'],
            ['code' => '79', 'name' => 'Deux-Sèvres', 'region_code' => '75'],
            ['code' => '86', 'name' => 'Vienne', 'region_code' => '75'],
            ['code' => '87', 'name' => 'Haute-Vienne', 'region_code' => '75'],

            // Occitanie (76)
            ['code' => '09', 'name' => 'Ariège', 'region_code' => '76'],
            ['code' => '11', 'name' => 'Aude', 'region_code' => '76'],
            ['code' => '12', 'name' => 'Aveyron', 'region_code' => '76'],
            ['code' => '30', 'name' => 'Gard', 'region_code' => '76'],
            ['code' => '31', 'name' => 'Haute-Garonne', 'region_code' => '76'],
            ['code' => '32', 'name' => 'Gers', 'region_code' => '76'],
            ['code' => '34', 'name' => 'Hérault', 'region_code' => '76'],
            ['code' => '46', 'name' => 'Lot', 'region_code' => '76'],
            ['code' => '48', 'name' => 'Lozère', 'region_code' => '76'],
            ['code' => '65', 'name' => 'Hautes-Pyrénées', 'region_code' => '76'],
            ['code' => '66', 'name' => 'Pyrénées-Orientales', 'region_code' => '76'],
            ['code' => '81', 'name' => 'Tarn', 'region_code' => '76'],
            ['code' => '82', 'name' => 'Tarn-et-Garonne', 'region_code' => '76'],

            // Pays de la Loire (52)
            ['code' => '44', 'name' => 'Loire-Atlantique', 'region_code' => '52'],
            ['code' => '49', 'name' => 'Maine-et-Loire', 'region_code' => '52'],
            ['code' => '53', 'name' => 'Mayenne', 'region_code' => '52'],
            ['code' => '72', 'name' => 'Sarthe', 'region_code' => '52'],
            ['code' => '85', 'name' => 'Vendée', 'region_code' => '52'],

            // Provence-Alpes-Côte d'Azur (93)
            ['code' => '04', 'name' => 'Alpes-de-Haute-Provence', 'region_code' => '93'],
            ['code' => '05', 'name' => 'Hautes-Alpes', 'region_code' => '93'],
            ['code' => '06', 'name' => 'Alpes-Maritimes', 'region_code' => '93'],
            ['code' => '13', 'name' => 'Bouches-du-Rhône', 'region_code' => '93'],
            ['code' => '83', 'name' => 'Var', 'region_code' => '93'],
            ['code' => '84', 'name' => 'Vaucluse', 'region_code' => '93'],
        ];

        foreach ($departments as $deptData) {
            $region = TerritoryRegion::where('code', $deptData['region_code'])->first();

            if ($region) {
                TerritoryDepartment::create([
                    'code' => $deptData['code'],
                    'name' => $deptData['name'],
                    'region_id' => $region->id,
                ]);
            }
        }

        $this->command->info('✓ 101 départements créés');
        $this->command->info('🎉 Territoires français complets !');
    }
}
