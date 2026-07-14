<?php

namespace Database\Factories;

use App\Models\Argument;
use App\Models\ProgrammeMesure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArgumentFactory extends Factory
{
    protected $model = Argument::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'mesure_id' => ProgrammeMesure::factory(),
            'sens' => $this->faker->randomElement(Argument::SENS),
            'titre' => $this->faker->sentence(5),
            'contenu' => $this->faker->text(300),
            'type_argument' => $this->faker->randomElement(Argument::TYPES),
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'ordre' => 0,
        ];
    }

    public function pour(): static
    {
        return $this->state(fn () => ['sens' => 'pour']);
    }

    public function contre(): static
    {
        return $this->state(fn () => ['sens' => 'contre']);
    }

    public function publie(): static
    {
        return $this->state(fn () => [
            'statut_validation' => 'valide',
            'affiche_publiquement' => true,
        ]);
    }
}
