<?php

namespace Database\Factories;

use App\Models\MesureScrutinLien;
use App\Models\ProgrammeMesure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MesureScrutinLienFactory extends Factory
{
    protected $model = MesureScrutinLien::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'mesure_id' => ProgrammeMesure::factory(),
            'scrutin_type' => $this->faker->randomElement(MesureScrutinLien::SCRUTIN_TYPES),
            'scrutin_ref' => (string) $this->faker->numberBetween(1, 5000),
            'sens_lien' => $this->faker->randomElement(MesureScrutinLien::SENS_LIEN),
            'niveau' => $this->faker->randomElement(MesureScrutinLien::NIVEAUX),
            'explication' => $this->faker->paragraph(),
            'scrutin_date' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'scrutin_intitule' => $this->faker->sentence(8),
            'scrutin_resultat' => $this->faker->randomElement(['adopté', 'rejeté']),
            'scrutin_url' => 'https://civicdash.fr/democratie/votes/'.$this->faker->numberBetween(1, 5000),
            'source_detection' => 'manuel',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
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
