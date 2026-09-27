<?php

namespace Database\Factories;

use App\Models\ProgrammeTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

class AffirmationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'election' => '2027',
            'slug' => 'affirmation-'.$this->faker->unique()->numerify('#####'),
            'enonce' => 'Il y a trop de ceci',
            'question' => 'Combien y a-t-il de ceci ?',
            'resume' => 'Ce que disent les données publiques.',
            'theme_id' => ProgrammeTheme::factory(),
            'derniere_verification' => now()->toDateString(),
            'statut_validation' => 'valide',
            'affiche_publiquement' => false,
        ];
    }
}
