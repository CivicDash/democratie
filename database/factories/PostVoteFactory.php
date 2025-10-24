<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostVote>
 */
class PostVoteFactory extends Factory
{
    protected $model = PostVote::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'vote' => fake()->randomElement(['up', 'down']),
        ];
    }

    public function upvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote' => 'up',
        ]);
    }

    public function downvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote' => 'down',
        ]);
    }
}

