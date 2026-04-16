<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Seeding budget sectors...');

        $sectors = [
            [
                'code' => 'EDU',
                'name' => 'Éducation',
                'description' => 'Enseignement primaire, secondaire, supérieur, formation professionnelle',
                'icon' => 'academic-cap',
                'color' => '#3B82F6', // Blue
                'min_percent' => 10.0,
                'max_percent' => 40.0,
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'HEALTH',
                'name' => 'Santé',
                'description' => 'Hôpitaux, soins, prévention, recherche médicale',
                'icon' => 'heart',
                'color' => '#EF4444', // Red
                'min_percent' => 10.0,
                'max_percent' => 35.0,
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'ECO',
                'name' => 'Écologie & Transition',
                'description' => 'Environnement, énergies renouvelables, biodiversité, climat',
                'icon' => 'leaf',
                'color' => '#10B981', // Green
                'min_percent' => 5.0,
                'max_percent' => 30.0,
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'DEFENSE',
                'name' => 'Défense & Sécurité',
                'description' => 'Armée, police, gendarmerie, sécurité civile',
                'icon' => 'shield-check',
                'color' => '#6366F1', // Indigo
                'min_percent' => 5.0,
                'max_percent' => 25.0,
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'SOCIAL',
                'name' => 'Solidarité & Social',
                'description' => 'Aides sociales, retraites, handicap, lutte contre la pauvreté',
                'icon' => 'users',
                'color' => '#8B5CF6', // Purple
                'min_percent' => 10.0,
                'max_percent' => 35.0,
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'CULTURE',
                'name' => 'Culture & Sport',
                'description' => 'Musées, patrimoine, médias, sport, vie associative',
                'icon' => 'sparkles',
                'color' => '#F59E0B', // Amber
                'min_percent' => 2.0,
                'max_percent' => 15.0,
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'code' => 'INFRA',
                'name' => 'Infrastructures & Transports',
                'description' => 'Routes, trains, transport public, logement',
                'icon' => 'truck',
                'color' => '#64748B', // Slate
                'min_percent' => 5.0,
                'max_percent' => 25.0,
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'code' => 'JUSTICE',
                'name' => 'Justice',
                'description' => 'Tribunaux, prisons, aide juridictionnelle',
                'icon' => 'scale',
                'color' => '#DC2626', // Red
                'min_percent' => 2.0,
                'max_percent' => 15.0,
                'display_order' => 8,
                'is_active' => true,
            ],
            [
                'code' => 'RESEARCH',
                'name' => 'Recherche & Innovation',
                'description' => 'Recherche scientifique, innovation, numérique',
                'icon' => 'beaker',
                'color' => '#06B6D4', // Cyan
                'min_percent' => 2.0,
                'max_percent' => 20.0,
                'display_order' => 9,
                'is_active' => true,
            ],
            [
                'code' => 'AGRI',
                'name' => 'Agriculture & Alimentation',
                'description' => 'Agriculture, pêche, forêts, sécurité alimentaire',
                'icon' => 'home-modern',
                'color' => '#84CC16', // Lime
                'min_percent' => 2.0,
                'max_percent' => 20.0,
                'display_order' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($sectors as $sectorData) {
            Sector::create($sectorData);
        }

        $this->command->info('✓ 10 secteurs budgétaires créés');

        // Vérifier que la somme des min_percent <= 100%
        $totalMin = collect($sectors)->sum('min_percent');
        $totalMax = collect($sectors)->sum('max_percent');

        $this->command->info("📊 Total min: {$totalMin}% | Total max: {$totalMax}%");

        if ($totalMin > 100) {
            $this->command->warn("⚠️  ATTENTION: La somme des minimums ({$totalMin}%) > 100%");
        } else {
            $this->command->info("✓ Contraintes cohérentes (min total: {$totalMin}%)");
        }
    }
}
