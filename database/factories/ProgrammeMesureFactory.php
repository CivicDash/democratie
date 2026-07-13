<?php

namespace Database\Factories;

use App\Models\CandidatPresidentielle;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgrammeMesureFactory extends Factory
{
    protected $model = ProgrammeMesure::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'candidat_id' => CandidatPresidentielle::factory(),
            'theme_id' => ProgrammeTheme::factory(),
            'titre' => $this->faker->sentence(6),
            'resume' => $this->faker->text(200),
            'description_complete' => $this->faker->paragraph(),
            'chiffrage_annonce' => $this->faker->optional()->randomElement(['5 Md€/an', 'non chiffré', '1,7 M d\'élèves']),
            'source_officielle_url' => $this->faker->url(),
            'date_annonce' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'statut_mesure' => 'annoncee',
            'est_mise_en_avant' => false,
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'ordre' => 0,
        ];
    }

    public function publie(): static
    {
        return $this->state(fn () => [
            'statut_validation' => 'valide',
            'affiche_publiquement' => true,
        ]);
    }
}
