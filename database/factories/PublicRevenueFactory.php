<?php

namespace Database\Factories;

use App\Models\PublicRevenue;
use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PublicRevenue>
 */
class PublicRevenueFactory extends Factory
{
    protected $model = PublicRevenue::class;

    public function definition(): array
    {
        return [
            'year' => fake()->numberBetween(2020, 2024),
            'scope' => fake()->randomElement(['national', 'region', 'dept']),
            'region_id' => null,
            'department_id' => null,
            'category' => fake()->randomElement(['TVA', 'IRPP', 'IS', 'Taxe foncière', 'Autres']),
            'amount' => fake()->randomFloat(2, 1000000, 100000000000),
            'source' => fake()->randomElement(['INSEE', 'DGFiP', 'Ministère des Finances']),
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

