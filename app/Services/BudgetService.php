<?php

namespace App\Services;

use App\Models\PublicRevenue;
use App\Models\PublicSpend;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service pour gérer le budget participatif
 */
class BudgetService
{
    /**
     * Alloue le budget d'un utilisateur à un secteur.
     * 
     * @param User $user
     * @param Sector $sector
     * @param float $percent Pourcentage à allouer (entre 0 et 100)
     * 
     * @throws RuntimeException Si l'allocation est invalide
     */
    public function allocate(User $user, Sector $sector, float $percent): UserAllocation
    {
        // Vérifier les contraintes du secteur
        if ($percent < $sector->min_allocation_percent) {
            throw new RuntimeException(
                "Allocation below minimum: {$sector->min_allocation_percent}% required for {$sector->name}."
            );
        }

        if ($percent > $sector->max_allocation_percent) {
            throw new RuntimeException(
                "Allocation exceeds maximum: {$sector->max_allocation_percent}% allowed for {$sector->name}."
            );
        }

        return DB::transaction(function () use ($user, $sector, $percent) {
            // Vérifier le total avec cette nouvelle allocation
            $currentTotal = $this->getUserTotalAllocation($user, $sector->id);
            $newTotal = $currentTotal + $percent;

            if ($newTotal > 100) {
                throw new RuntimeException(
                    "Total allocation would exceed 100% (current: {$currentTotal}%, trying to add: {$percent}%)."
                );
            }

            // Créer ou mettre à jour l'allocation
            return UserAllocation::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'sector_id' => $sector->id,
                ],
                [
                    'allocated_percent' => $percent,
                ]
            );
        });
    }

    /**
     * Répartit le budget complet d'un utilisateur en une seule transaction.
     * 
     * @param User $user
     * @param array $allocations Format : ['sector_id' => percent, ...]
     * 
     * @throws RuntimeException Si les allocations sont invalides
     */
    public function bulkAllocate(User $user, array $allocations): Collection
    {
        return DB::transaction(function () use ($user, $allocations) {
            // Vérifier que le total = 100%
            $total = array_sum($allocations);
            if (abs($total - 100.0) > 0.01) { // Tolérance de 0.01%
                throw new RuntimeException(
                    "Total allocation must equal 100% (got {$total}%)."
                );
            }

            // Vérifier les contraintes de chaque secteur
            foreach ($allocations as $sectorId => $percent) {
                $sector = Sector::findOrFail($sectorId);

                if ($percent < $sector->min_allocation_percent) {
                    throw new RuntimeException(
                        "Allocation for {$sector->name} below minimum ({$sector->min_allocation_percent}%)."
                    );
                }

                if ($percent > $sector->max_allocation_percent) {
                    throw new RuntimeException(
                        "Allocation for {$sector->name} exceeds maximum ({$sector->max_allocation_percent}%)."
                    );
                }
            }

            // Supprimer les anciennes allocations
            UserAllocation::where('user_id', $user->id)->delete();

            // Créer les nouvelles allocations
            $created = collect();
            foreach ($allocations as $sectorId => $percent) {
                $created->push(UserAllocation::create([
                    'user_id' => $user->id,
                    'sector_id' => $sectorId,
                    'allocated_percent' => $percent,
                ]));
            }

            return $created;
        });
    }

    /**
     * Réinitialise toutes les allocations d'un utilisateur.
     */
    public function resetAllocations(User $user): int
    {
        return UserAllocation::where('user_id', $user->id)->delete();
    }

    /**
     * Calcule le total alloué par un utilisateur (excluant un secteur optionnel).
     */
    public function getUserTotalAllocation(User $user, ?int $excludeSectorId = null): float
    {
        $query = UserAllocation::where('user_id', $user->id);

        if ($excludeSectorId) {
            $query->where('sector_id', '!=', $excludeSectorId);
        }

        return $query->sum('allocated_percent');
    }

    /**
     * Vérifie si un utilisateur a complété son allocation budgétaire.
     */
    public function hasCompletedAllocation(User $user): bool
    {
        $total = $this->getUserTotalAllocation($user);
        return abs($total - 100.0) < 0.01; // Tolérance de 0.01%
    }

    /**
     * Obtient les allocations d'un utilisateur.
     */
    public function getUserAllocations(User $user): Collection
    {
        return UserAllocation::with('sector')
            ->where('user_id', $user->id)
            ->get();
    }

    /**
     * Calcule les allocations moyennes par secteur (tous les utilisateurs).
     */
    public function getAverageAllocations(): Collection
    {
        return Sector::withAvg('allocations', 'percent')
            ->get()
            ->map(function ($sector) {
                return [
                    'sector_id' => $sector->id,
                    'sector_name' => $sector->name,
                    'average_percent' => round($sector->allocations_avg_percent ?? 0, 2),
                    'total_allocators' => $sector->allocations()->distinct('user_id')->count(),
                ];
            });
    }

    /**
     * Obtient le classement des secteurs par allocation moyenne.
     */
    public function getSectorRanking(): Collection
    {
        return $this->getAverageAllocations()
            ->sortByDesc('average_percent')
            ->values();
    }

    /**
     * Calcule le budget simulé en fonction des allocations citoyennes.
     * 
     * @param int $year Année budgétaire
     * @param float $totalBudget Budget total disponible
     */
    public function calculateSimulatedBudget(int $year, float $totalBudget): array
    {
        $averages = $this->getAverageAllocations();
        $totalParticipants = UserAllocation::distinct('user_id')->count();

        $simulatedBudget = [];

        foreach ($averages as $allocation) {
            $simulatedBudget[$allocation['sector_name']] = [
                'average_percent' => $allocation['average_percent'],
                'simulated_amount' => round(($allocation['average_percent'] / 100) * $totalBudget, 2),
                'participants' => $allocation['total_allocators'],
            ];
        }

        return [
            'year' => $year,
            'total_budget' => $totalBudget,
            'total_participants' => $totalParticipants,
            'sectors' => $simulatedBudget,
        ];
    }

    /**
     * Compare les allocations citoyennes avec les dépenses réelles.
     */
    public function compareWithRealSpending(int $year): array
    {
        $citizenAllocations = $this->getAverageAllocations();
        $realSpending = $this->getRealSpendingByYear($year);
        
        $totalRevenue = PublicRevenue::where('year', $year)->sum('amount');
        $totalSpend = PublicSpend::where('year', $year)->sum('amount');

        $comparison = [];

        foreach ($citizenAllocations as $allocation) {
            $sectorName = $allocation['sector_name'];
            $citizenPercent = $allocation['average_percent'];
            
            $realAmount = $realSpending->firstWhere('sector_name', $sectorName)['total_amount'] ?? 0;
            $realPercent = $totalSpend > 0 ? round(($realAmount / $totalSpend) * 100, 2) : 0;

            $comparison[] = [
                'sector' => $sectorName,
                'citizen_allocation_percent' => $citizenPercent,
                'real_spending_percent' => $realPercent,
                'real_spending_amount' => $realAmount,
                'difference' => round($citizenPercent - $realPercent, 2),
            ];
        }

        return [
            'year' => $year,
            'total_revenue' => $totalRevenue,
            'total_spending' => $totalSpend,
            'comparison' => collect($comparison)->sortByDesc('difference')->values()->toArray(),
        ];
    }

    /**
     * Obtient les dépenses réelles par année.
     */
    protected function getRealSpendingByYear(int $year): Collection
    {
        return PublicSpend::with('sector')
            ->where('year', $year)
            ->get()
            ->groupBy('sector.name')
            ->map(function ($spends, $sectorName) {
                return [
                    'sector_name' => $sectorName,
                    'total_amount' => $spends->sum('amount'),
                ];
            })
            ->values();
    }

    /**
     * Obtient les statistiques de participation au budget participatif.
     */
    public function getParticipationStats(): array
    {
        $totalUsers = User::role('citizen')->count();
        $participatingUsers = UserAllocation::distinct('user_id')->count();
        $completedUsers = User::role('citizen')
            ->get()
            ->filter(fn($user) => $this->hasCompletedAllocation($user))
            ->count();

        return [
            'total_citizens' => $totalUsers,
            'participating_citizens' => $participatingUsers,
            'completed_allocations' => $completedUsers,
            'participation_rate' => $totalUsers > 0 ? round(($participatingUsers / $totalUsers) * 100, 2) : 0,
            'completion_rate' => $participatingUsers > 0 ? round(($completedUsers / $participatingUsers) * 100, 2) : 0,
        ];
    }

    /**
     * Obtient les statistiques globales du budget participatif.
     * Alias pour getParticipationStats() pour compatibilité.
     */
    public function getStats(): array
    {
        return $this->getParticipationStats();
    }

    /**
     * Exporte les données budgétaires pour analyse.
     */
    public function exportData(int $year): array
    {
        return [
            'year' => $year,
            'participation' => $this->getParticipationStats(),
            'average_allocations' => $this->getAverageAllocations(),
            'sector_ranking' => $this->getSectorRanking(),
            'comparison_with_real' => $this->compareWithRealSpending($year),
            'exported_at' => now()->toIso8601String(),
        ];
    }
}

