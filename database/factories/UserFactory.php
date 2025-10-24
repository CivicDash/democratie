<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * User avec un profil citoyen
     */
    public function withProfile(string $scope = 'national'): static
    {
        return $this->afterCreating(function (User $user) use ($scope) {
            Profile::factory()->create([
                'user_id' => $user->id,
                'scope' => $scope,
            ]);
        });
    }

    /**
     * User citoyen avec profil
     */
    public function citizen(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('citizen');
            Profile::factory()->create([
                'user_id' => $user->id,
            ]);
        });
    }

    /**
     * User modérateur
     */
    public function moderator(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('moderator');
        });
    }

    /**
     * User journaliste
     */
    public function journalist(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('journalist');
        });
    }

    /**
     * User ONG
     */
    public function ong(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('ong');
        });
    }

    /**
     * User législateur
     */
    public function legislator(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('legislator');
        });
    }

    /**
     * User état
     */
    public function state(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('state');
        });
    }

    /**
     * User admin
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }
}
