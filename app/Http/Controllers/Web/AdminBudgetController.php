<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BudgetMinistere;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminBudgetController extends Controller
{
    /**
     * Liste des budgets par année
     */
    public function index(Request $request): Response
    {
        $annee = $request->input('annee', now()->year);
        $annees = BudgetMinistere::distinct('annee')
            ->orderByDesc('annee')
            ->pluck('annee');

        $budgets = BudgetMinistere::where('annee', $annee)
            ->orderByDesc('budget_total')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'code' => $b->code,
                'nom' => $b->nom,
                'sigle' => $b->sigle,
                'annee' => $b->annee,
                'type_loi' => $b->type_loi,
                'budget_general' => $b->budget_general,
                'budgets_annexes' => $b->budgets_annexes,
                'comptes_affectation_speciale' => $b->comptes_affectation_speciale,
                'comptes_concours_financiers' => $b->comptes_concours_financiers,
                'budget_total' => $b->budget_total,
                'budget_formate' => $b->budget_formate,
                'couleur' => $b->couleur,
                'source' => $b->source,
            ]);

        // Statistiques
        $stats = [
            'total_budget_general' => $budgets->sum('budget_general'),
            'total_budgets_annexes' => $budgets->sum('budgets_annexes'),
            'total_comptes_affectation' => $budgets->sum('comptes_affectation_speciale'),
            'total_comptes_concours' => $budgets->sum('comptes_concours_financiers'),
            'total_global' => $budgets->sum('budget_total'),
            'nb_ministeres' => $budgets->count(),
        ];

        return Inertia::render('Admin/Budget/Index', [
            'budgets' => $budgets,
            'annee' => (int) $annee,
            'annees' => $annees,
            'stats' => $stats,
        ]);
    }

    /**
     * Formulaire de création
     */
    public function create(): Response
    {
        $annees = range(now()->year + 1, 2020, -1);

        return Inertia::render('Admin/Budget/Create', [
            'annees' => $annees,
        ]);
    }

    /**
     * Enregistrer un nouveau budget
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:20',
            'annee' => 'required|integer|min:2020|max:2030',
            'type_loi' => 'required|in:plf,lfi,lfr',
            'budget_general' => 'nullable|numeric|min:0',
            'budgets_annexes' => 'nullable|numeric|min:0',
            'comptes_affectation_speciale' => 'nullable|numeric|min:0',
            'comptes_concours_financiers' => 'nullable|numeric|min:0',
        ]);

        $validated['code'] = Str::slug($validated['nom']);
        $validated['budget_total'] =
            ($validated['budget_general'] ?? 0) +
            ($validated['budgets_annexes'] ?? 0) +
            ($validated['comptes_affectation_speciale'] ?? 0) +
            ($validated['comptes_concours_financiers'] ?? 0);
        $validated['budget_cp'] = $validated['budget_general'];
        $validated['couleur'] = BudgetMinistere::getCouleur($validated['nom']);
        $validated['source'] = 'Saisie manuelle';

        BudgetMinistere::create($validated);

        return redirect()
            ->route('admin.budget.index', ['annee' => $validated['annee']])
            ->with('success', 'Budget ministériel créé avec succès.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(BudgetMinistere $budget): Response
    {
        return Inertia::render('Admin/Budget/Edit', [
            'budget' => [
                'id' => $budget->id,
                'code' => $budget->code,
                'nom' => $budget->nom,
                'sigle' => $budget->sigle,
                'annee' => $budget->annee,
                'type_loi' => $budget->type_loi,
                'budget_general' => $budget->budget_general,
                'budgets_annexes' => $budget->budgets_annexes,
                'comptes_affectation_speciale' => $budget->comptes_affectation_speciale,
                'comptes_concours_financiers' => $budget->comptes_concours_financiers,
                'budget_total' => $budget->budget_total,
                'couleur' => $budget->couleur,
                'source' => $budget->source,
            ],
        ]);
    }

    /**
     * Mettre à jour un budget
     */
    public function update(Request $request, BudgetMinistere $budget)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:20',
            'budget_general' => 'nullable|numeric|min:0',
            'budgets_annexes' => 'nullable|numeric|min:0',
            'comptes_affectation_speciale' => 'nullable|numeric|min:0',
            'comptes_concours_financiers' => 'nullable|numeric|min:0',
        ]);

        $validated['budget_total'] =
            ($validated['budget_general'] ?? 0) +
            ($validated['budgets_annexes'] ?? 0) +
            ($validated['comptes_affectation_speciale'] ?? 0) +
            ($validated['comptes_concours_financiers'] ?? 0);
        $validated['budget_cp'] = $validated['budget_general'];
        $validated['couleur'] = BudgetMinistere::getCouleur($validated['nom']);

        $budget->update($validated);

        return redirect()
            ->route('admin.budget.index', ['annee' => $budget->annee])
            ->with('success', 'Budget mis à jour avec succès.');
    }

    /**
     * Supprimer un budget
     */
    public function destroy(BudgetMinistere $budget)
    {
        $annee = $budget->annee;
        $budget->delete();

        return redirect()
            ->route('admin.budget.index', ['annee' => $annee])
            ->with('success', 'Budget supprimé avec succès.');
    }

    /**
     * Dupliquer les budgets d'une année vers une autre
     */
    public function duplicate(Request $request)
    {
        $validated = $request->validate([
            'annee_source' => 'required|integer',
            'annee_cible' => 'required|integer|different:annee_source',
        ]);

        $budgetsSource = BudgetMinistere::where('annee', $validated['annee_source'])->get();

        if ($budgetsSource->isEmpty()) {
            return redirect()->back()->with('error', 'Aucun budget trouvé pour l\'année source.');
        }

        $count = 0;
        foreach ($budgetsSource as $budget) {
            $exists = BudgetMinistere::where('code', $budget->code)
                ->where('annee', $validated['annee_cible'])
                ->exists();

            if (! $exists) {
                $newBudget = $budget->replicate();
                $newBudget->annee = $validated['annee_cible'];
                $newBudget->type_loi = 'plf';
                $newBudget->source = "Copie de {$validated['annee_source']}";
                $newBudget->save();
                $count++;
            }
        }

        return redirect()
            ->route('admin.budget.index', ['annee' => $validated['annee_cible']])
            ->with('success', "{$count} budgets dupliqués vers {$validated['annee_cible']}.");
    }

    /**
     * Export CSV
     */
    public function export(Request $request)
    {
        $annee = $request->input('annee', now()->year);
        $budgets = BudgetMinistere::where('annee', $annee)
            ->orderByDesc('budget_total')
            ->get();

        $csv = "nom;Budget général;Budgets annexes;Comptes d'affectation spéciale;Comptes de concours financiers;\n";

        foreach ($budgets as $budget) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s;\n",
                $budget->nom,
                $budget->budget_general ?? '',
                $budget->budgets_annexes ?? '',
                $budget->comptes_affectation_speciale ?? '',
                $budget->comptes_concours_financiers ?? ''
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=PLF_{$annee}.csv",
        ]);
    }
}
