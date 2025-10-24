<?php

namespace Database\Factories;

use App\Models\PublicSpend;
use App\Models\Sector;
use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PublicSpend>
 */
class PublicSpendFactory extends Factory
{
    protected $model = PublicSpend::class;

    public function definition(): array
    {
        return [
            'year' => fake()->numberBetween(2020, 2024),
            'scope' => fake()->randomElement(['national', 'region', 'dept']),
            'region_id' => null,
            'department_id' => null,
            'sector_id' => Sector::factory(),
            'amount' => fake()->randomFloat(2, 1000000, 50000000000),
            'program' => fake()->optional()->sentence(3),
            'source' => fake()->randomElement(['INSEE', 'DGFiP', 'Cour des comptes']),
        ];
    }

    public function national(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'national',
            'region_id' => null,
            'department_id' => null,
        ]);
    }

    public function regional(?TerritoryRegion $region = null): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'region',
            'region_id' => $region?->id ?? TerritoryRegion::factory(),
            'department_id' => null,
        ]);
    }

    public function departmental(?TerritoryDepartment $department = null): static
    {
        $dept = $department ?? TerritoryDepartment::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'scope' => 'dept',
            'region_id' => $dept->region_id,
            'department_id' => $dept->id,
        ]);
    }
}

