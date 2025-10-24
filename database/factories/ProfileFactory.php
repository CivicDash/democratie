<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => Profile::generateDisplayName(),
            'citizen_ref_hash' => Profile::hashCitizenRef(fake()->unique()->ssn()),
            'scope' => fake()->randomElement(['national', 'region', 'dept']),
            'region_id' => null,
            'department_id' => null,
            'is_verified' => fake()->boolean(30), // 30% verified
            'verified_at' => fn (array $attributes) => 
                $attributes['is_verified'] ? fake()->dateTimeBetween('-1 year', 'now') : null,
        ];
    }

    /**
     * Profil national
     */
    public function national(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'national',
            'region_id' => null,
            'department_id' => null,
        ]);
    }

    /**
     * Profil régional
     */
    public function regional(?TerritoryRegion $region = null): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'region',
            'region_id' => $region?->id ?? TerritoryRegion::factory(),
            'department_id' => null,
        ]);
    }

    /**
     * Profil départemental
     */
    public function departmental(?TerritoryDepartment $department = null): static
    {
        $dept = $department ?? TerritoryDepartment::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'scope' => 'dept',
            'region_id' => $dept->region_id,
            'department_id' => $dept->id,
        ]);
    }

    /**
     * Profil vérifié
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Profil non vérifié
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
}

