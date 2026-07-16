<?php

namespace Database\Factories;

use App\Models\Controverse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ControverseFactory extends Factory
{
    protected $model = Controverse::class;

    public function definition(): array
    {
        $titre = $this->faker->sentence(4);

        return [
            'slug' => Str::slug($titre).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'titre' => $titre,
            'theme_id' => null,
            'note_methodologique' => $this->faker->paragraph(),
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'ordre' => 0,
        ];
    }

    public function publie(): static
    {
        return $this->state(fn () => [
            'statut_validation' => 'valide',
            'valide_par' => User::factory(),
            'valide_at' => now(),
            'affiche_publiquement' => true,
        ]);
    }
}
