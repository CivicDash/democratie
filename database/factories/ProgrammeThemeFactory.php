<?php

namespace Database\Factories;

use App\Models\ProgrammeTheme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgrammeThemeFactory extends Factory
{
    protected $model = ProgrammeTheme::class;

    public function definition(): array
    {
        $nom = $this->faker->unique()->words(2, true);

        return [
            'slug' => Str::slug($nom).'-'.$this->faker->unique()->numberBetween(1, 999999),
            'nom' => ucfirst($nom),
            'icone' => 'lucide-flag',
            'description' => $this->faker->sentence(),
            'sources_taxonomie' => 'Mission budgétaire LOLF ; COFOG.',
            'ordre' => $this->faker->numberBetween(1, 15),
            'actif' => true,
        ];
    }
}
