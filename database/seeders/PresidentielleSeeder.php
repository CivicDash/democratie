<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Point d'entrée du domaine présidentielle : thèmes (référentiel neutre) puis
 * candidats 2027 (en statut `detecte`, non publiés).
 * Exécuter : php artisan db:seed --class=PresidentielleSeeder
 */
class PresidentielleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProgrammeThemesSeeder::class,
            CandidatsPresidentielle2027Seeder::class,
        ]);
    }
}
