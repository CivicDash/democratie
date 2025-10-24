<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraphs(fake()->numberBetween(1, 3), true),
            'is_official' => fake()->boolean(10), // 10% officiels
            'upvotes' => fake()->numberBetween(0, 100),
            'downvotes' => fake()->numberBetween(0, 50),
            'is_pinned' => fake()->boolean(5), // 5% épinglés
            'is_hidden' => fake()->boolean(2), // 2% masqués
            'hidden_reason' => fn (array $attributes) => 
                $attributes['is_hidden'] ? fake()->sentence() : null,
        ];
    }

    public function reply(?Post $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Post::factory(),
            'topic_id' => $parent?->topic_id ?? $attributes['topic_id'],
        ]);
    }

    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    public function official(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_official' => true,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function hidden(string $reason = 'Masqué par modération'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
            'hidden_reason' => $reason,
        ]);
    }

    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => false,
            'hidden_reason' => null,
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'upvotes' => fake()->numberBetween(50, 200),
            'downvotes' => fake()->numberBetween(0, 20),
        ]);
    }

    public function controversial(): static
    {
        return $this->state(fn (array $attributes) => [
            'upvotes' => fake()->numberBetween(30, 100),
            'downvotes' => fake()->numberBetween(30, 100),
        ]);
    }
}

