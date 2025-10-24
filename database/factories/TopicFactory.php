<?php

namespace Database\Factories;

use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $hasBallot = fake()->boolean(40); // 40% des topics ont un scrutin
        
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'scope' => fake()->randomElement(['national', 'region', 'dept']),
            'region_id' => null,
            'department_id' => null,
            'type' => fake()->randomElement(['debate', 'bill', 'referendum']),
            'status' => fake()->randomElement(['draft', 'open', 'closed']),
            'author_id' => User::factory(),
            'has_ballot' => $hasBallot,
            'voting_opens_at' => $hasBallot ? fake()->dateTimeBetween('now', '+7 days') : null,
            'voting_deadline_at' => $hasBallot ? fake()->dateTimeBetween('+8 days', '+30 days') : null,
            'ballot_type' => $hasBallot ? fake()->randomElement(['yes_no', 'multiple_choice']) : null,
            'ballot_options' => $hasBallot ? $this->generateBallotOptions() : null,
        ];
    }

    protected function generateBallotOptions(): ?array
    {
        if (fake()->boolean()) {
            // Multiple choice
            return [
                'options' => [
                    ['id' => 1, 'label' => fake()->sentence(3)],
                    ['id' => 2, 'label' => fake()->sentence(3)],
                    ['id' => 3, 'label' => fake()->sentence(3)],
                ]
            ];
        }
        
        return null; // yes_no n'a pas besoin d'options
    }

    public function debate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'debate',
        ]);
    }

    public function bill(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bill',
        ]);
    }

    public function referendum(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'referendum',
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    public function withBallot(string $type = 'yes_no'): static
    {
        $opensAt = fake()->dateTimeBetween('-7 days', 'now');
        $deadlineAt = fake()->dateTimeBetween('now', '+30 days');
        
        return $this->state(fn (array $attributes) => [
            'has_ballot' => true,
            'voting_opens_at' => $opensAt,
            'voting_deadline_at' => $deadlineAt,
            'ballot_type' => $type,
            'ballot_options' => $type === 'multiple_choice' ? $this->generateBallotOptions() : null,
        ]);
    }

    public function withoutBallot(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_ballot' => false,
            'voting_opens_at' => null,
            'voting_deadline_at' => null,
            'ballot_type' => null,
            'ballot_options' => null,
        ]);
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

