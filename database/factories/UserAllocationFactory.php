<?php

namespace Database\Factories;

use App\Models\Sector;
use App\Models\User;
use App\Models\UserAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAllocation>
 */
class UserAllocationFactory extends Factory
{
    protected $model = UserAllocation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sector_id' => Sector::factory(),
            'percent' => fake()->randomFloat(2, 5, 40),
        ];
    }

    /**
     * Génère un ensemble complet d'allocations pour un user (total = 100%)
     */
    public function completeAllocation(User $user, array $sectors): array
    {
        $allocations = [];
        $remaining = 100.0;
        $count = count($sectors);
        
        foreach ($sectors as $index => $sector) {
            if ($index === $count - 1) {
                // Dernière allocation = remaining
                $percent = round($remaining, 2);
            } else {
                // Allocation aléatoire dans les limites
                $minPercent = max($sector->min_percent, 0);
                $maxPercent = min($sector->max_percent, $remaining - (($count - $index - 1) * $sector->min_percent));
                $percent = fake()->randomFloat(2, $minPercent, $maxPercent);
                $percent = round($percent, 2);
            }
            
            $allocations[] = UserAllocation::create([
                'user_id' => $user->id,
                'sector_id' => $sector->id,
                'percent' => $percent,
            ]);
            
            $remaining -= $percent;
        }
        
        return $allocations;
    }
}

