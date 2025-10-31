<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropositionLoi;
use App\Models\ThematiqueLegislation;
use App\Services\ThematiqueDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller pour les thématiques législatives
 */
class ThematiquesController extends Controller
{
    public function __construct(
        private ThematiqueDetectionService $thematiqueService
    ) {}

    /**
     * Liste des thématiques
     * 
     * GET /api/thematiques
     */
    public function index(Request $request): JsonResponse
    {
        $query = ThematiqueLegislation::query();

        // Filtrer par principale (sans parent)
        if ($request->query('principales')) {
            $query->principales();
        }

        // Avec ou sans enfants
        if ($request->query('avec_enfants')) {
            $query->avecEnfants();
        }

        // Recherche
        if ($search = $request->query('search')) {
            $query->recherche($search);
        }

        $thematiques = $query->orderBy('ordre')->get();

        $thematiques = $thematiques->map(fn($thematique) => [
            'id' => $thematique->id,
            'code' => $thematique->code,
            'nom' => $thematique->nom,
            'description' => $thematique->description,
            'couleur_hex' => $thematique->couleur_hex,
            'icone' => $thematique->icone,
            'parent_id' => $thematique->parent_id,
            'ordre' => $thematique->ordre,
            'nb_propositions' => $thematique->nb_propositions,
            'enfants' => $thematique->enfants->map(fn($enfant) => [
                'id' => $enfant->id,
                'code' => $enfant->code,
                'nom' => $enfant->nom,
                'nb_propositions' => $enfant->nb_propositions,
            ]),
        ]);

        return response()->json([
            'success' => true,
            'data' => $thematiques,
            'total' => $thematiques->count(),
        ]);
    }

    /**
     * Détails d'une thématique
     * 
     * GET /api/thematiques/{code}
     */
    public function show(string $code): JsonResponse
    {
        $thematique = ThematiqueLegislation::where('code', $code)->first();

        if (!$thematique) {
            return response()->json([
                'success' => false,
                'message' => 'Thématique non trouvée',
            ], 404);
        }

        // Statistiques
        $stats = $thematique->getStatistiques();

        // Groupes actifs
        $groupesActifs = $thematique->getGroupesActifs(10);

        return response()->json([
            'success' => true,
            'data' => [
                'thematique' => [
                    'id' => $thematique->id,
                    'code' => $thematique->code,
                    'nom' => $thematique->nom,
                    'description' => $thematique->description,
                    'couleur_hex' => $thematique->couleur_hex,
                    'icone' => $thematique->icone,
                    'parent_id' => $thematique->parent_id,
                    'ordre' => $thematique->ordre,
                    'nb_propositions' => $thematique->nb_propositions,
                    'mots_cles' => $thematique->mots_cles,
                    'synonymes' => $thematique->synonymes,
                ],
                'statistiques' => $stats,
                'groupes_actifs' => $groupesActifs,
            ],
        ]);
    }

    /**
     * Propositions d'une thématique
     * 
     * GET /api/thematiques/{code}/propositions
     */
    public function propositions(Request $request, string $code): JsonResponse
    {
        $thematique = ThematiqueLegislation::where('code', $code)->first();

        if (!$thematique) {
            return response()->json([
                'success' => false,
                'message' => 'Thématique non trouvée',
            ], 404);
        }

        $limit = $request->query('limit', 20);
        $principale = $request->query('principale', false);

        $query = $principale 
            ? $thematique->propositionsPrincipales()
            : $thematique->propositions();

        $propositions = $query
            ->orderBy('propositions_loi.date_depot', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($proposition) => [
                'id' => $proposition->id,
                'source' => $proposition->source,
                'numero' => $proposition->numero,
                'titre' => $proposition->titre,
                'resume' => $proposition->resume,
                'statut' => $proposition->statut,
                'date_depot' => $proposition->date_depot?->toISOString(),
                'url_externe' => $proposition->url_externe,
                'pivot' => [
                    'est_principal' => $proposition->pivot->est_principal,
                    'confiance' => $proposition->pivot->confiance,
                    'tagged_by' => $proposition->pivot->tagged_by,
                ],
            ]);

        return response()->json([
            'success' => true,
            'data' => $propositions,
            'total' => $propositions->count(),
        ]);
    }

    /**
     * Détection automatique des thématiques pour une proposition
     * 
     * POST /api/thematiques/detecter
     */
    public function detecter(Request $request): JsonResponse
    {
        $request->validate([
            'proposition_id' => 'required|exists:propositions_loi,id',
            'attach' => 'boolean',
        ]);

        $proposition = PropositionLoi::find($request->proposition_id);
        $attach = $request->input('attach', false);

        try {
            $thematiques = $this->thematiqueService->detecter($proposition, false, $attach);

            return response()->json([
                'success' => true,
                'message' => $attach ? 'Thématiques détectées et attachées' : 'Thématiques détectées',
                'data' => $thematiques->map(fn($item) => [
                    'thematique' => [
                        'id' => $item['thematique']->id,
                        'code' => $item['thematique']->code,
                        'nom' => $item['thematique']->nom,
                        'couleur_hex' => $item['thematique']->couleur_hex,
                        'icone' => $item['thematique']->icone,
                    ],
                    'score' => $item['score'],
                    'est_principal' => $item['est_principal'],
                ])->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la détection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détection en batch pour plusieurs propositions
     * 
     * POST /api/thematiques/detecter-batch
     */
    public function detecterBatch(Request $request): JsonResponse
    {
        $request->validate([
            'proposition_ids' => 'array',
            'proposition_ids.*' => 'exists:propositions_loi,id',
            'all' => 'boolean',
        ]);

        try {
            $propositions = $request->input('all')
                ? PropositionLoi::whereDoesntHave('thematiques')->get()
                : PropositionLoi::whereIn('id', $request->input('proposition_ids', []))->get();

            if ($propositions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune proposition à traiter',
                ], 400);
            }

            $stats = $this->thematiqueService->detecterBatch($propositions);

            return response()->json([
                'success' => true,
                'message' => 'Détection batch terminée',
                'statistiques' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la détection batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attacher manuellement une thématique à une proposition
     * 
     * POST /api/thematiques/attacher
     */
    public function attacher(Request $request): JsonResponse
    {
        $request->validate([
            'proposition_id' => 'required|exists:propositions_loi,id',
            'thematique_id' => 'required|exists:thematiques_legislation,id',
            'est_principal' => 'boolean',
        ]);

        $proposition = PropositionLoi::find($request->proposition_id);
        $thematique = ThematiqueLegislation::find($request->thematique_id);
        $estPrincipal = $request->input('est_principal', false);
        $userId = $request->user()?->id;

        try {
            $this->thematiqueService->attacherManuellement(
                $proposition,
                $thematique,
                $estPrincipal,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'Thématique attachée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'attachement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détacher une thématique d'une proposition
     * 
     * DELETE /api/thematiques/detacher
     */
    public function detacher(Request $request): JsonResponse
    {
        $request->validate([
            'proposition_id' => 'required|exists:propositions_loi,id',
            'thematique_id' => 'required|exists:thematiques_legislation,id',
        ]);

        $proposition = PropositionLoi::find($request->proposition_id);
        $thematique = ThematiqueLegislation::find($request->thematique_id);

        try {
            $proposition->thematiques()->detach($thematique->id);
            $thematique->recalculerNbPropositions();

            return response()->json([
                'success' => true,
                'message' => 'Thématique détachée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du détachement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recalculer les thématiques d'une proposition
     * 
     * POST /api/thematiques/recalculer
     */
    public function recalculer(Request $request): JsonResponse
    {
        $request->validate([
            'proposition_id' => 'required|exists:propositions_loi,id',
        ]);

        $proposition = PropositionLoi::find($request->proposition_id);

        try {
            $thematiques = $this->thematiqueService->recalculer($proposition);

            return response()->json([
                'success' => true,
                'message' => 'Thématiques recalculées',
                'data' => $thematiques->map(fn($item) => [
                    'thematique' => [
                        'id' => $item['thematique']->id,
                        'code' => $item['thematique']->code,
                        'nom' => $item['thematique']->nom,
                    ],
                    'score' => $item['score'],
                    'est_principal' => $item['est_principal'],
                ])->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du recalcul',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiques globales de détection
     * 
     * GET /api/thematiques/statistiques
     */
    public function statistiques(): JsonResponse
    {
        try {
            $stats = $this->thematiqueService->getStatistiques();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thématiques les plus populaires
     * 
     * GET /api/thematiques/populaires
     */
    public function populaires(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);

        $thematiques = ThematiqueLegislation::principales()
            ->orderBy('nb_propositions', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($thematique) => [
                'id' => $thematique->id,
                'code' => $thematique->code,
                'nom' => $thematique->nom,
                'couleur_hex' => $thematique->couleur_hex,
                'icone' => $thematique->icone,
                'nb_propositions' => $thematique->nb_propositions,
            ]);

        return response()->json([
            'success' => true,
            'data' => $thematiques,
        ]);
    }
}

