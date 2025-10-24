<?php

namespace Database\Factories;

use App\Models\BallotToken;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BallotToken>
 */
class BallotTokenFactory extends Factory
{
    protected $model = BallotToken::class;

    public function definition(): array
    {
        $topic = Topic::factory()->withBallot()->create();
        
        return [
            'topic_id' => $topic->id,
            'user_id' => User::factory(),
            'token' => BallotToken::generateToken(),
            'consumed' => fake()->boolean(30), // 30% consommés
            'consumed_at' => fn (array $attributes) => 
                $attributes['consumed'] ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'expires_at' => $topic->voting_deadline_at,
        ];
    }

    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumed' => false,
            'consumed_at' => null,
            'expires_at' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    public function consumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumed' => true,
            'consumed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumed' => false,
            'consumed_at' => null,
            'expires_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}

