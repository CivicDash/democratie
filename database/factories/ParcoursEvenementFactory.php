<?php

namespace Database\Factories;

use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ParcoursEvenementFactory extends Factory
{
    protected $model = ParcoursEvenement::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'personne_politique_id' => PersonnePolitique::factory(),
            'type' => $this->faker->randomElement(ParcoursEvenement::TYPES),
            'titre' => $this->faker->jobTitle(),
            'organisation' => $this->faker->company(),
            'description' => $this->faker->optional()->sentence(),
            'date_debut' => $this->faker->dateTimeBetween('-20 years', '-2 years'),
            'date_fin' => $this->faker->optional()->dateTimeBetween('-2 years', 'now'),
            'en_cours' => false,
            'source_url' => $this->faker->url(),
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'ordre' => 0,
        ];
    }
}
