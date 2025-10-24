<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Verification>
 */
class VerificationFactory extends Factory
{
    protected $model = Verification::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'verifier_id' => User::factory(),
            'status' => fake()->randomElement(['verified', 'rejected', 'needs_review']),
            'notes' => fake()->optional()->sentence(),
            'metadata' => fake()->optional()->passthrough([
                'sources' => [fake()->url(), fake()->url()],
                'confidence' => fake()->numberBetween(1, 5),
            ]),
        ];
    }

    public function verified(string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'notes' => $notes ?? 'Document vérifié et authentique',
        ]);
    }

    public function rejected(string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'notes' => $notes ?? 'Document rejeté - source non fiable',
        ]);
    }

    public function needsReview(string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'needs_review',
            'notes' => $notes ?? 'Nécessite une vérification supplémentaire',
        ]);
    }
}

