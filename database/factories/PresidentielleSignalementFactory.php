<?php

namespace Database\Factories;

use App\Models\PresidentielleSignalement;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresidentielleSignalementFactory extends Factory
{
    protected $model = PresidentielleSignalement::class;

    public function definition(): array
    {
        return [
            'type_incident' => $this->faker->randomElement(array_keys(PresidentielleSignalement::TYPES_INCIDENT)),
            'description' => $this->faker->paragraph(),
            'email' => null,
            'candidat_slug' => null,
            'theme_slug' => null,
            'contexte_url' => 'https://objectif2027.fr/signaler',
            'statut' => 'nouveau',
        ];
    }
}
