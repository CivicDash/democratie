<?php

namespace Database\Factories;

use App\Models\Argument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArgumentFactory extends Factory
{
    protected $model = Argument::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'controverse_id' => null,
            'titre' => $this->faker->sentence(5),
            'contenu' => $this->faker->text(300),
            'type_argument' => $this->faker->randomElement(Argument::TYPES),
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'ordre' => 0,
        ];
    }

    public function valide(): static
    {
        return $this->state(fn () => [
            'statut_validation' => 'valide',
            'valide_par' => \App\Models\User::factory(),
            'valide_at' => now(),
        ]);
    }

    public function publie(): static
    {
        return $this->state(fn () => [
            'statut_validation' => 'valide',
            'valide_par' => \App\Models\User::factory(),
            'valide_at' => now(),
            'affiche_publiquement' => true,
        ]);
    }
}
