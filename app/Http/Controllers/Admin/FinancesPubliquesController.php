<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetAnnuel;
use App\Models\BudgetMission;
use App\Models\BudgetMinistere;
use App\Models\FranceBudgetRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinancesPubliquesController extends Controller
{
    /**
     * Dashboard des finances publiques
     */
    public function index()
    {
        // Stats globales
        $stats = [
            'budget_annuel_count' => BudgetAnnuel::count(),
            'missions_count' => BudgetMission::count(),
            'ministeres_count' => BudgetMinistere::count(),
            'recettes_count' => FranceBudgetRevenue::count(),
            'urssaf_count' => DB::table('urssaf_effectifs_national')->count(),
            'annees_disponibles' => BudgetAnnuel::orderBy('annee', 'desc')->pluck('annee'),
        ];

        // Derniers budgets annuels
        $budgetsAnnuels = BudgetAnnuel::orderBy('annee', 'desc')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'annee' => $b->annee,
                'recettes_nettes' => $b->recettes_nettes,
                'recettes_formate' => $b->recettes_formate,
                'depenses_nettes' => $b->depenses_nettes,
                'depenses_formate' => $b->depenses_formate,
                'deficit' => $b->deficit,
                'deficit_formate' => $b->deficit_formate,
                'dette_pib_pct' => $b->dette_pib_pct,
                'deficit_pib_pct' => $b->deficit_pib_pct,
            ]);

        // Recettes consolidées
        $recettesConsolidees = FranceBudgetRevenue::orderBy('year', 'desc')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'year' => $r->year,
                'total_billions_euros' => $r->total_billions_euros,
                'tva_billions_euros' => $r->tva_billions_euros,
                'income_tax_billions_euros' => $r->income_tax_billions_euros,
                'corporate_tax_billions_euros' => $r->corporate_tax_billions_euros,
                'social_contributions_billions_euros' => $r->social_contributions_billions_euros,
                'other_taxes_billions_euros' => $r->other_taxes_billions_euros,
            ]);

        // Données URSSAF par année
        $urssafParAnnee = DB::table('urssaf_effectifs_national')
            ->select('annee')
            ->selectRaw('COUNT(*) as nb_secteurs')
            ->selectRaw('SUM(effectif) as total_effectifs')
            ->selectRaw('SUM(masse_salariale) as total_masse_salariale')
            ->groupBy('annee')
            ->orderBy('annee', 'desc')
            ->get();

        return Inertia::render('Admin/FinancesPubliques/Index', [
            'stats' => $stats,
            'budgetsAnnuels' => $budgetsAnnuels,
            'recettesConsolidees' => $recettesConsolidees,
            'urssafParAnnee' => $urssafParAnnee,
        ]);
    }

    /**
     * Éditer un budget annuel
     */
    public function editBudgetAnnuel(BudgetAnnuel $budgetAnnuel)
    {
        return Inertia::render('Admin/FinancesPubliques/EditBudgetAnnuel', [
            'budget' => $budgetAnnuel,
        ]);
    }

    /**
     * Mettre à jour un budget annuel
     */
    public function updateBudgetAnnuel(Request $request, BudgetAnnuel $budgetAnnuel)
    {
        $validated = $request->validate([
            'annee' => 'required|integer|min:2000|max:2100',
            'recettes_nettes' => 'nullable|numeric',
            'depenses_nettes' => 'nullable|numeric',
            'deficit' => 'nullable|numeric',
            'dette' => 'nullable|numeric',
            'dette_pib_pct' => 'nullable|numeric|min:0|max:500',
            'deficit_pib_pct' => 'nullable|numeric|min:-50|max:50',
        ]);

        $budgetAnnuel->update($validated);

        return redirect()->route('admin.finances.index')
            ->with('success', "Budget {$budgetAnnuel->annee} mis à jour !");
    }

    /**
     * Créer un nouveau budget annuel
     */
    public function createBudgetAnnuel()
    {
        return Inertia::render('Admin/FinancesPubliques/EditBudgetAnnuel', [
            'budget' => null,
            'nextYear' => (BudgetAnnuel::max('annee') ?? date('Y')) + 1,
        ]);
    }

    /**
     * Enregistrer un nouveau budget annuel
     */
    public function storeBudgetAnnuel(Request $request)
    {
        $validated = $request->validate([
            'annee' => 'required|integer|min:2000|max:2100|unique:budget_annuel,annee',
            'recettes_nettes' => 'nullable|numeric',
            'depenses_nettes' => 'nullable|numeric',
            'deficit' => 'nullable|numeric',
            'dette' => 'nullable|numeric',
            'dette_pib_pct' => 'nullable|numeric|min:0|max:500',
            'deficit_pib_pct' => 'nullable|numeric|min:-50|max:50',
        ]);

        BudgetAnnuel::create($validated);

        return redirect()->route('admin.finances.index')
            ->with('success', "Budget {$validated['annee']} créé !");
    }

    /**
     * Éditer les recettes consolidées
     */
    public function editRecettes(FranceBudgetRevenue $recette)
    {
        return Inertia::render('Admin/FinancesPubliques/EditRecettes', [
            'recette' => $recette,
        ]);
    }

    /**
     * Mettre à jour les recettes consolidées
     */
    public function updateRecettes(Request $request, FranceBudgetRevenue $recette)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'total_billions_euros' => 'nullable|numeric|min:0',
            'tva_billions_euros' => 'nullable|numeric|min:0',
            'income_tax_billions_euros' => 'nullable|numeric|min:0',
            'corporate_tax_billions_euros' => 'nullable|numeric|min:0',
            'property_tax_billions_euros' => 'nullable|numeric|min:0',
            'housing_tax_billions_euros' => 'nullable|numeric|min:0',
            'fuel_tax_billions_euros' => 'nullable|numeric|min:0',
            'social_contributions_billions_euros' => 'nullable|numeric|min:0',
            'other_taxes_billions_euros' => 'nullable|numeric|min:0',
        ]);

        $recette->update($validated);

        return redirect()->route('admin.finances.index')
            ->with('success', "Recettes {$recette->year} mises à jour !");
    }

    /**
     * Créer de nouvelles recettes
     */
    public function createRecettes()
    {
        return Inertia::render('Admin/FinancesPubliques/EditRecettes', [
            'recette' => null,
            'nextYear' => (FranceBudgetRevenue::max('year') ?? date('Y')) + 1,
        ]);
    }

    /**
     * Enregistrer de nouvelles recettes
     */
    public function storeRecettes(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:france_budget_revenue,year',
            'total_billions_euros' => 'nullable|numeric|min:0',
            'tva_billions_euros' => 'nullable|numeric|min:0',
            'income_tax_billions_euros' => 'nullable|numeric|min:0',
            'corporate_tax_billions_euros' => 'nullable|numeric|min:0',
            'property_tax_billions_euros' => 'nullable|numeric|min:0',
            'housing_tax_billions_euros' => 'nullable|numeric|min:0',
            'fuel_tax_billions_euros' => 'nullable|numeric|min:0',
            'social_contributions_billions_euros' => 'nullable|numeric|min:0',
            'other_taxes_billions_euros' => 'nullable|numeric|min:0',
        ]);

        FranceBudgetRevenue::create($validated);

        return redirect()->route('admin.finances.index')
            ->with('success', "Recettes {$validated['year']} créées !");
    }

    /**
     * Voir les données URSSAF
     */
    public function urssafDetails(Request $request)
    {
        $annee = $request->input('annee', date('Y'));
        
        $data = DB::table('urssaf_effectifs_national')
            ->where('annee', $annee)
            ->orderBy('effectif', 'desc')
            ->get();

        $anneesDisponibles = DB::table('urssaf_effectifs_national')
            ->distinct()
            ->pluck('annee')
            ->sort()
            ->values();

        return Inertia::render('Admin/FinancesPubliques/UrssafDetails', [
            'data' => $data,
            'annee' => (int) $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'totaux' => [
                'effectifs' => $data->sum('effectif'),
                'masse_salariale' => $data->sum('masse_salariale'),
                'etablissements' => $data->sum('nombre'),
            ],
        ]);
    }

    /**
     * Lancer un import
     */
    public function runImport(Request $request)
    {
        $type = $request->input('type');
        
        try {
            switch ($type) {
                case 'urssaf':
                    \Artisan::call('urssaf:import', [
                        '--dataset' => 'effectifs-national',
                        '--limit' => 2000,
                    ]);
                    $output = \Artisan::output();
                    break;
                    
                case 'budget-etat':
                    \Artisan::call('import:budget-etat');
                    $output = \Artisan::output();
                    break;
                    
                default:
                    return back()->with('error', 'Type d\'import inconnu');
            }
            
            return back()->with('success', "Import {$type} lancé avec succès !");
            
        } catch (\Exception $e) {
            return back()->with('error', "Erreur : " . $e->getMessage());
        }
    }
}
