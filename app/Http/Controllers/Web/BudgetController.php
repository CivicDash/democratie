<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\BudgetService;
use App\Http\Requests\Budget\AllocateBudgetRequest;
use App\Http\Requests\Budget\BulkAllocateBudgetRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Page d'allocation du budget
     */
    public function index(): Response
    {
        $user = auth()->user();
        
        $sectors = Sector::orderBy('name')->get();
        $averages = $this->budgetService->getAverageAllocations();
        $stats = $this->budgetService->getStats();
        
        $userAllocations = $user 
            ? $this->budgetService->getUserAllocations($user)
            : null;

        return Inertia::render('Budget/Index', [
            'sectors' => $sectors,
            'userAllocations' => $userAllocations,
            'averages' => $averages,
            'stats' => $stats,
        ]);
    }

    /**
     * Page de statistiques
     */
    public function stats(): Response
    {
        $sectors = Sector::orderBy('name')->get();
        $averages = $this->budgetService->getAverageAllocations();
        $ranking = $this->budgetService->getSectorRanking();
        $stats = $this->budgetService->getStats();

        return Inertia::render('Budget/Stats', [
            'sectors' => $sectors,
            'averages' => $averages,
            'ranking' => $ranking,
            'stats' => $stats,
        ]);
    }

    /**
     * Liste des secteurs
     */
    public function sectors(): Response
    {
        return Inertia::render('Budget/Sectors', [
            'sectors' => Sector::orderBy('name')->get(),
        ]);
    }

    /**
     * Mes allocations
     */
    public function myAllocations()
    {
        $this->authorize('allocate', Sector::class);

        $allocations = $this->budgetService->getUserAllocations(auth()->user());

        return response()->json($allocations);
    }

    /**
     * Allouer le budget
     */
    public function allocate(AllocateBudgetRequest $request)
    {
        $this->budgetService->allocateBudget(
            $request->user(),
            $request->validated()['sector_id'],
            $request->validated()['percentage']
        );

        return back()->with('success', 'Allocation enregistrée avec succès !');
    }

    /**
     * Allocation en masse
     */
    public function bulkAllocate(BulkAllocateBudgetRequest $request)
    {
        $this->budgetService->bulkAllocate(
            $request->user(),
            $request->validated()['allocations']
        );

        return back()->with('success', 'Budget alloué avec succès !');
    }

    /**
     * Réinitialiser l'allocation
     */
    public function reset(Request $request)
    {
        $this->budgetService->resetAllocations($request->user());

        return back()->with('success', 'Allocation réinitialisée avec succès.');
    }
}

