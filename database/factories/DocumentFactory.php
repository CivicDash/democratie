<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $filename = fake()->word() . '.pdf';
        
        return [
            'title' => fake()->sentence(),
            'description' => fake()->optional()->sentence(),
            'filename' => $filename,
            'path' => 'documents/' . fake()->uuid() . '/' . $filename,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10000, 5000000),
            'hash' => hash('sha256', fake()->unique()->uuid()),
            'documentable_type' => fake()->randomElement([Topic::class, Post::class]),
            'documentable_id' => 1,
            'uploader_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'verified', 'rejected']),
            'is_public' => fake()->boolean(80), // 80% publics
        ];
    }

    public function forTopic(?Topic $topic = null): static
    {
        $topic = $topic ?? Topic::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'documentable_type' => Topic::class,
            'documentable_id' => $topic->id,
        ]);
    }

    public function forPost(?Post $post = null): static
    {
        $post = $post ?? Post::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'documentable_type' => Post::class,
            'documentable_id' => $post->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function pdf(): static
    {
        $filename = fake()->word() . '.pdf';
        
        return $this->state(fn (array $attributes) => [
            'filename' => $filename,
            'mime_type' => 'application/pdf',
        ]);
    }
}

