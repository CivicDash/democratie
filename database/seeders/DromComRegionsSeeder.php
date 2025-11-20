<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DromComRegionsSeeder extends Seeder
{
    /**
     * Seed DROM-COM regions
     */
    public function run(): void
    {
        $regions = [
            ['code' => '01', 'name' => 'Guadeloupe'],
            ['code' => '02', 'name' => 'Martinique'],
            ['code' => '03', 'name' => 'Guyane'],
            ['code' => '04', 'name' => 'La Réunion'],
            ['code' => '06', 'name' => 'Mayotte'],
        ];

        foreach ($regions as $region) {
            DB::table('territories_regions')->updateOrInsert(
                ['code' => $region['code']],
                [
                    'name' => $region['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ 5 régions DROM-COM ajoutées');
    }
}

