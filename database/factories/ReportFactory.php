<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reason' => fake()->randomElement(['spam', 'harassment', 'misinformation', 'off_topic', 'inappropriate', 'other']),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'reviewing', 'resolved', 'dismissed']),
            'moderator_id' => fn (array $attributes) => 
                in_array($attributes['status'], ['reviewing', 'resolved', 'dismissed']) ? User::factory() : null,
            'moderator_notes' => fn (array $attributes) => 
                in_array($attributes['status'], ['resolved', 'dismissed']) ? fake()->sentence() : null,
            'resolved_at' => fn (array $attributes) => 
                in_array($attributes['status'], ['resolved', 'dismissed']) ? fake()->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'moderator_id' => null,
            'moderator_notes' => null,
            'resolved_at' => null,
        ]);
    }

    public function reviewing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewing',
            'moderator_id' => User::factory(),
            'moderator_notes' => null,
            'resolved_at' => null,
        ]);
    }

    public function resolved(string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'moderator_id' => User::factory(),
            'moderator_notes' => $notes ?? fake()->sentence(),
            'resolved_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function dismissed(string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dismissed',
            'moderator_id' => User::factory(),
            'moderator_notes' => $notes ?? fake()->sentence(),
            'resolved_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'spam',
        ]);
    }

    public function harassment(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'harassment',
        ]);
    }
}

