<?php

namespace Database\Factories;

use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\ProgrammeMesure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArgumentMesureLienFactory extends Factory
{
    protected $model = ArgumentMesureLien::class;

    public function definition(): array
    {
        return [
            'argument_id' => Argument::factory(),
            'mesure_id' => ProgrammeMesure::factory(),
            'sens' => $this->faker->randomElement(ArgumentMesureLien::SENS),
            'note_contextuelle' => $this->faker->sentence(8),
            'source_detection' => 'manuel',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
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

    /** Liaison publiée. Un « contre » est aussi doublement validé (règle éditoriale). */
    public function publie(): static
    {
        return $this->state(fn (array $attrs) => [
            'statut_validation' => 'valide',
            'affiche_publiquement' => true,
            'valide_par' => User::factory(),
            'valide_at' => now(),
            'double_valide_par' => ($attrs['sens'] ?? 'pour') === 'contre' ? User::factory() : null,
            'double_valide_at' => ($attrs['sens'] ?? 'pour') === 'contre' ? now() : null,
        ]);
    }
}
