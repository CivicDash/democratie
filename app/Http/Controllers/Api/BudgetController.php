<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\AllocateBudgetRequest;
use App\Http\Requests\Budget\BulkAllocateBudgetRequest;
use App\Models\Sector;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Get all sectors.
     */
    public function sectors(): JsonResponse
    {
        $sectors = Sector::all();

        return response()->json($sectors);
    }

    /**
     * Get user's allocations.
     */
    public function index(): JsonResponse
    {
        $allocations = $this->budgetService->getUserAllocations(auth()->user());

        $total = $this->budgetService->getUserTotalAllocation(auth()->user());
        $isComplete = $this->budgetService->hasCompletedAllocation(auth()->user());

        return response()->json([
            'allocations' => $allocations,
            'total_allocated' => $total,
            'is_complete' => $isComplete,
        ]);
    }

    /**
     * Allocate budget to a sector.
     */
    public function allocate(AllocateBudgetRequest $request): JsonResponse
    {
        try {
            $sector = Sector::findOrFail($request->sector_id);

            $allocation = $this->budgetService->allocate(
                $request->user(),
                $sector,
                $request->allocated_percent
            );

            return response()->json([
                'message' => 'Allocation enregistrée avec succès.',
                'allocation' => $allocation->load('sector'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk allocate budget (complete allocation).
     */
    public function bulkAllocate(BulkAllocateBudgetRequest $request): JsonResponse
    {
        try {
            $allocations = $this->budgetService->bulkAllocate(
                $request->user(),
                $request->allocations
            );

            return response()->json([
                'message' => 'Allocations enregistrées avec succès.',
                'allocations' => $allocations->load('sector'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset all allocations.
     */
    public function reset(): JsonResponse
    {
        $deleted = $this->budgetService->resetAllocations(auth()->user());

        return response()->json([
            'message' => 'Allocations réinitialisées avec succès.',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Get average allocations (public).
     */
    public function averages(): JsonResponse
    {
        $averages = $this->budgetService->getAverageAllocations();

        return response()->json($averages);
    }

    /**
     * Get sector ranking (public).
     */
    public function ranking(): JsonResponse
    {
        $ranking = $this->budgetService->getSectorRanking();

        return response()->json($ranking);
    }

    /**
     * Get participation stats (public).
     */
    public function stats(): JsonResponse
    {
        $stats = $this->budgetService->getParticipationStats();

        return response()->json($stats);
    }

    /**
     * Calculate simulated budget.
     */
    public function simulate(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);
        $totalBudget = $request->input('total_budget', 50000000000); // 50 milliards par défaut

        $simulation = $this->budgetService->calculateSimulatedBudget($year, $totalBudget);

        return response()->json($simulation);
    }

    /**
     * Compare with real spending.
     */
    public function compare(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year - 1);

        $comparison = $this->budgetService->compareWithRealSpending($year);

        return response()->json($comparison);
    }

    /**
     * Export budget data (admin/state only).
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', \App\Models\UserAllocation::class);

        $year = $request->input('year', now()->year);
        $export = $this->budgetService->exportData($year);

        return response()->json($export);
    }
}

