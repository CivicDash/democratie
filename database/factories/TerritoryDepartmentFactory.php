<?php

namespace Database\Factories;

use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TerritoryDepartment>
 */
class TerritoryDepartmentFactory extends Factory
{
    protected $model = TerritoryDepartment::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('##'),
            'name' => fake()->unique()->city(),
            'region_id' => TerritoryRegion::factory(),
        ];
    }

    /**
     * Factory d'un département réel
     */
    public function real(string $code, string $name, int $regionId): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
            'name' => $name,
            'region_id' => $regionId,
        ]);
    }

    /**
     * Département d'une région spécifique
     */
    public function forRegion(TerritoryRegion $region): static
    {
        return $this->state(fn (array $attributes) => [
            'region_id' => $region->id,
        ]);
    }
}

