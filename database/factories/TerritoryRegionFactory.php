<?php

namespace Database\Factories;

use App\Models\TerritoryRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TerritoryRegion>
 */
class TerritoryRegionFactory extends Factory
{
    protected $model = TerritoryRegion::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('##'),
            // `region` n'existe pas non plus en fr_FR.
            'name' => fake()->unique()->randomElement([
                'Auvergne-Rhône-Alpes', 'Bourgogne-Franche-Comté', 'Bretagne',
                'Centre-Val de Loire', 'Corse', 'Grand Est', 'Hauts-de-France',
                'Île-de-France', 'Normandie', 'Nouvelle-Aquitaine', 'Occitanie',
                'Pays de la Loire', "Provence-Alpes-Côte d'Azur",
            ]),
        ];
    }

    /**
     * Factory d'une région réelle
     */
    public function real(string $code, string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
            'name' => $name,
        ]);
    }
}
