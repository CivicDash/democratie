<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\Sanction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sanction>
 */
class SanctionFactory extends Factory
{
    protected $model = Sanction::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['warning', 'mute', 'ban']);
        $startsAt = fake()->dateTimeBetween('-30 days', 'now');
        
        // Warnings et mutes sont temporaires, bans peuvent être permanents
        $isPermanent = $type === 'ban' && fake()->boolean(30);
        $expiresAt = $isPermanent ? null : fake()->dateTimeBetween($startsAt, '+30 days');
        
        return [
            'user_id' => User::factory(),
            'moderator_id' => User::factory(),
            'report_id' => fake()->boolean(70) ? Report::factory() : null,
            'type' => $type,
            'reason' => fake()->sentence(),
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ];
    }

    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'warning',
            'expires_at' => null, // Warnings sont permanents dans l'historique
        ]);
    }

    public function mute(int $hours = 24): static
    {
        $startsAt = now();
        
        return $this->state(fn (array $attributes) => [
            'type' => 'mute',
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->copy()->addHours($hours),
        ]);
    }

    public function ban(?int $days = null): static
    {
        $startsAt = now();
        
        return $this->state(fn (array $attributes) => [
            'type' => 'ban',
            'starts_at' => $startsAt,
            'expires_at' => $days ? $startsAt->copy()->addDays($days) : null,
        ]);
    }

    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    public function temporary(int $days = 7): static
    {
        $startsAt = now();
        
        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->copy()->addDays($days),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'starts_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'expires_at' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    public function expired(): static
    {
        $startsAt = fake()->dateTimeBetween('-60 days', '-30 days');
        
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'starts_at' => $startsAt,
            'expires_at' => fake()->dateTimeBetween($startsAt, '-1 day'),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

