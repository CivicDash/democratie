<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BudgetTerritorialService;
use App\Services\DataGouvService;
use App\Models\CommuneBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller pour exposer les données data.gouv.fr via l'API CivicDash
 */
class DataGouvController extends Controller
{
    public function __construct(
        private DataGouvService $dataGouvService,
        private BudgetTerritorialService $budgetService
    ) {}

    /**
     * Récupère le budget d'une commune
     * 
     * GET /api/datagouv/commune/{codeInsee}/budget/{annee?}
     * 
     * @param string $codeInsee Code INSEE de la commune (5 caractères)
     * @param int|null $annee Année du budget (optionnel, défaut: année en cours)
     * @return JsonResponse
     */
    public function getCommuneBudget(string $codeInsee, ?int $annee = null): JsonResponse
    {
        // Validation du code INSEE
        if (!preg_match('/^\d{5}$/', $codeInsee)) {
            return response()->json([
                'error' => 'Code INSEE invalide',
                'message' => 'Le code INSEE doit contenir exactement 5 chiffres',
            ], 400);
        }

        $annee = $annee ?? date('Y');

        // Validation de l'année
        if ($annee < 2000 || $annee > date('Y')) {
            return response()->json([
                'error' => 'Année invalide',
                'message' => 'L\'année doit être entre 2000 et ' . date('Y'),
            ], 400);
        }

        try {
            $budget = $this->budgetService->getCommuneBudget($codeInsee, $annee);
            
            if (!$budget) {
                return response()->json([
                    'error' => 'Budget non trouvé',
                    'message' => "Aucun budget trouvé pour la commune {$codeInsee} en {$annee}",
                    'code_insee' => $codeInsee,
                    'annee' => $annee,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $budget,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur getCommuneBudget', [
                'code_insee' => $codeInsee,
                'annee' => $annee,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors de la récupération du budget',
            ], 500);
        }
    }

    /**
     * Compare les budgets de plusieurs communes
     * 
     * GET /api/datagouv/communes/compare?codes[]=75056&codes[]=69123&annee=2024
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function compareBudgets(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'codes' => 'required|array|min:2|max:10',
            'codes.*' => 'required|string|size:5|regex:/^\d{5}$/',
            'annee' => 'nullable|integer|min:2000|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $codesInsee = $request->input('codes');
        $annee = $request->input('annee', date('Y'));

        try {
            $comparison = $this->budgetService->compareBudgets($codesInsee, $annee);

            if (empty($comparison)) {
                return response()->json([
                    'error' => 'Aucune donnée trouvée',
                    'message' => 'Aucun budget trouvé pour les communes spécifiées',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur compareBudgets', [
                'codes' => $codesInsee,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors de la comparaison',
            ], 500);
        }
    }

    /**
     * Génère le contexte budgétaire pour un projet citoyen
     * 
     * GET /api/datagouv/project/context?code_insee=75056&montant=150000&categorie=culture
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getProjectContext(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code_insee' => 'required|string|size:5|regex:/^\d{5}$/',
            'montant' => 'required|numeric|min:0|max:100000000',
            'categorie' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $codeInsee = $request->input('code_insee');
        $montant = (float) $request->input('montant');
        $categorie = $request->input('categorie');

        try {
            $context = $this->budgetService->getProjectContext($codeInsee, $montant, $categorie);

            if (isset($context['error'])) {
                return response()->json([
                    'error' => $context['error'],
                    'code_insee' => $codeInsee,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $context,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur getProjectContext', [
                'code_insee' => $codeInsee,
                'montant' => $montant,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors de la génération du contexte',
            ], 500);
        }
    }

    /**
     * Recherche de communes par nom
     * 
     * GET /api/datagouv/communes/search?q=paris&annee=2024
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCommunes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'annee' => 'nullable|integer|min:2000|max:' . date('Y'),
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('q');
        $annee = $request->input('annee', date('Y'));
        $limit = $request->input('limit', 20);

        try {
            $communes = CommuneBudget::where('nom_commune', 'ILIKE', "%{$query}%")
                ->forYear($annee)
                ->orderByPopulation()
                ->limit($limit)
                ->get()
                ->map(function ($commune) {
                    return [
                        'code_insee' => $commune->code_insee,
                        'nom' => $commune->nom_commune,
                        'population' => $commune->population,
                        'budget_total' => $commune->budget_total_euros,
                        'depenses_par_habitant' => $commune->depenses_par_habitant,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $communes,
                'count' => $communes->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur searchCommunes', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors de la recherche',
            ], 500);
        }
    }

    /**
     * Récupère les statistiques du service data.gouv.fr
     * 
     * GET /api/datagouv/stats
     * 
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $serviceStats = $this->dataGouvService->getStats();
            
            $dbStats = [
                'cached_datasets' => \DB::table('datagouv_cache')->count(),
                'cached_budgets' => CommuneBudget::count(),
                'communes_with_budget' => CommuneBudget::distinct('code_insee')->count(),
                'annees_disponibles' => CommuneBudget::distinct('annee')
                    ->pluck('annee')
                    ->sort()
                    ->values()
                    ->toArray(),
                'last_fetch' => CommuneBudget::max('fetched_at'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $serviceStats,
                    'database' => $dbStats,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur getStats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors de la récupération des statistiques',
            ], 500);
        }
    }

    /**
     * Invalide le cache pour une commune spécifique
     * 
     * DELETE /api/datagouv/cache/commune/{codeInsee}
     * 
     * @param string $codeInsee
     * @return JsonResponse
     */
    public function invalidateCommuneCache(string $codeInsee): JsonResponse
    {
        if (!preg_match('/^\d{5}$/', $codeInsee)) {
            return response()->json([
                'error' => 'Code INSEE invalide',
            ], 400);
        }

        try {
            // Supprimer de Redis
            \Cache::forget("budget:commune:{$codeInsee}:*");
            
            // Supprimer de la BDD
            $deleted = CommuneBudget::forCommune($codeInsee)->delete();

            return response()->json([
                'success' => true,
                'message' => "Cache invalidé pour la commune {$codeInsee}",
                'budgets_supprimes' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur invalidateCommuneCache', [
                'code_insee' => $codeInsee,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur serveur',
            ], 500);
        }
    }
}

