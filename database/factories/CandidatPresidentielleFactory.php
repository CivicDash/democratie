<?php

namespace Database\Factories;

use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CandidatPresidentielleFactory extends Factory
{
    protected $model = CandidatPresidentielle::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'personne_politique_id' => PersonnePolitique::factory(),
            'election' => '2027',
            'statut_candidature' => $this->faker->randomElement(CandidatPresidentielle::STATUTS_CANDIDATURE),
            'date_declaration' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'parti_soutien' => $this->faker->randomElement(['Renaissance', 'LFI', 'Horizons', 'LR', 'RN', 'PS']),
            'nuance_politique' => $this->faker->randomElement(['GAU', 'CEN', 'DR', 'EXD', 'EXG', 'ECO']),
            'couleur_hex' => $this->faker->hexColor(),
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'ordre_affichage' => 0,
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
