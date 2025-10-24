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
            'name' => fake()->unique()->region(),
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

