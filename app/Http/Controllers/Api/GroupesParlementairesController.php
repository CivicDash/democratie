<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupeParlementaire;
use App\Services\LegislationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller pour les groupes parlementaires
 */
class GroupesParlementairesController extends Controller
{
    public function __construct(
        private LegislationService $legislationService
    ) {}

    /**
     * Liste des groupes parlementaires
     * 
     * GET /api/groupes-parlementaires
     */
    public function index(Request $request): JsonResponse
    {
        $source = $request->query('source', 'assemblee'); // assemblee ou senat
        $legislature = $request->query('legislature');
        $actif = $request->query('actif', true);

        $query = GroupeParlementaire::query()
            ->where('source', $source)
            ->orderBy('nombre_membres', 'desc');

        if ($actif) {
            $query->actif();
        }

        if ($legislature) {
            $query->legislature((int)$legislature);
        }

        $groupes = $query->get();

        // Enrichir avec statistiques
        $groupes = $groupes->map(function ($groupe) {
            return [
                'id' => $groupe->id,
                'source' => $groupe->source,
                'nom' => $groupe->nom,
                'sigle' => $groupe->sigle,
                'nom_complet' => $groupe->nom_complet,
                'couleur_hex' => $groupe->couleur_hex,
                'position_politique' => $groupe->position_politique,
                'position_label' => $groupe->position_label,
                'nombre_membres' => $groupe->nombre_membres,
                'president_nom' => $groupe->president_nom,
                'logo_url' => $groupe->logo_url,
                'url_officiel' => $groupe->url_officiel,
                'legislature' => $groupe->legislature,
                'actif' => $groupe->actif,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $groupes,
            'total' => $groupes->count(),
        ]);
    }

    /**
     * Détails d'un groupe parlementaire
     * 
     * GET /api/groupes-parlementaires/{id}
     */
    public function show(int $id): JsonResponse
    {
        $groupe = GroupeParlementaire::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe parlementaire non trouvé',
            ], 404);
        }

        // Statistiques de vote
        $stats = $groupe->getStatistiquesVote();

        // Thématiques favorites
        $thematiques = $groupe->getThematiquesFavorites(10);

        return response()->json([
            'success' => true,
            'data' => [
                'groupe' => [
                    'id' => $groupe->id,
                    'source' => $groupe->source,
                    'nom' => $groupe->nom,
                    'sigle' => $groupe->sigle,
                    'nom_complet' => $groupe->nom_complet,
                    'couleur_hex' => $groupe->couleur_hex,
                    'position_politique' => $groupe->position_politique,
                    'position_label' => $groupe->position_label,
                    'nombre_membres' => $groupe->nombre_membres,
                    'president_nom' => $groupe->president_nom,
                    'logo_url' => $groupe->logo_url,
                    'url_officiel' => $groupe->url_officiel,
                    'legislature' => $groupe->legislature,
                    'actif' => $groupe->actif,
                    'description' => $groupe->description,
                ],
                'statistiques' => $stats,
                'thematiques_favorites' => $thematiques,
            ],
        ]);
    }

    /**
     * Statistiques d'un groupe
     * 
     * GET /api/groupes-parlementaires/{id}/statistiques
     */
    public function statistiques(Request $request, int $id): JsonResponse
    {
        $groupe = GroupeParlementaire::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe parlementaire non trouvé',
            ], 404);
        }

        $debut = $request->query('debut') ? new \DateTime($request->query('debut')) : null;
        $fin = $request->query('fin') ? new \DateTime($request->query('fin')) : null;

        $stats = $groupe->getStatistiquesVote($debut, $fin);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Députés/Sénateurs d'un groupe
     * 
     * GET /api/groupes-parlementaires/{id}/membres
     */
    public function membres(int $id): JsonResponse
    {
        $groupe = GroupeParlementaire::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe parlementaire non trouvé',
            ], 404);
        }

        $deputes = $groupe->deputes()
            ->where('en_exercice', true)
            ->orderBy('nom')
            ->get()
            ->map(fn($depute) => [
                'id' => $depute->id,
                'nom' => $depute->nom,
                'prenom' => $depute->prenom,
                'nom_complet' => $depute->nom_complet,
                'civilite' => $depute->civilite,
                'circonscription' => $depute->circonscription,
                'photo_url' => $depute->photo_url,
                'url_profil' => $depute->url_profil,
            ]);

        return response()->json([
            'success' => true,
            'data' => $deputes,
            'total' => $deputes->count(),
        ]);
    }

    /**
     * Votes récents d'un groupe
     * 
     * GET /api/groupes-parlementaires/{id}/votes
     */
    public function votes(Request $request, int $id): JsonResponse
    {
        $groupe = GroupeParlementaire::find($id);

        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe parlementaire non trouvé',
            ], 404);
        }

        $limit = $request->query('limit', 20);

        $votes = $groupe->votesGroupes()
            ->with(['voteLegislatif.proposition'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($vote) => [
                'id' => $vote->id,
                'position' => $vote->position_groupe,
                'position_label' => $vote->position_label,
                'pour' => $vote->nombre_pour,
                'contre' => $vote->nombre_contre,
                'abstention' => $vote->nombre_abstention,
                'absents' => $vote->nombre_absents,
                'discipline' => $vote->pourcentage_discipline,
                'vote_legislatif' => $vote->voteLegislatif ? [
                    'id' => $vote->voteLegislatif->id,
                    'numero_scrutin' => $vote->voteLegislatif->numero_scrutin,
                    'date_vote' => $vote->voteLegislatif->date_vote->toISOString(),
                    'resultat' => $vote->voteLegislatif->resultat,
                    'proposition' => $vote->voteLegislatif->proposition ? [
                        'id' => $vote->voteLegislatif->proposition->id,
                        'titre' => $vote->voteLegislatif->proposition->titre,
                    ] : null,
                ] : null,
                'date' => $vote->created_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $votes,
            'total' => $votes->count(),
        ]);
    }

    /**
     * Synchroniser les groupes depuis l'API
     * 
     * POST /api/groupes-parlementaires/sync
     */
    public function sync(Request $request): JsonResponse
    {
        $source = $request->input('source', 'assemblee');
        $legislature = $request->input('legislature');

        try {
            $groupesData = $this->legislationService->getGroupesParlementaires($source, $legislature);

            $synced = 0;
            foreach ($groupesData as $groupeData) {
                GroupeParlementaire::updateOrCreate(
                    [
                        'source' => $source,
                        'sigle' => $groupeData['sigle'],
                        'legislature' => $legislature ?? 17,
                    ],
                    [
                        'uid' => $groupeData['uid'] ?? null,
                        'nom' => $groupeData['nom'],
                        'couleur_hex' => $groupeData['couleur_hex'] ?? '#6B7280',
                        'position_politique' => $groupeData['position_politique'] ?? 'centre',
                        'nombre_membres' => $groupeData['nombre_membres'] ?? 0,
                        'actif' => true,
                    ]
                );
                $synced++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$synced} groupes synchronisés",
                'synced' => $synced,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Comparaison de groupes
     * 
     * GET /api/groupes-parlementaires/comparaison
     */
    public function comparaison(Request $request): JsonResponse
    {
        $ids = $request->query('ids'); // ex: "1,2,3"
        
        if (!$ids) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre ids requis (ex: ?ids=1,2,3)',
            ], 400);
        }

        $groupeIds = explode(',', $ids);
        $groupes = GroupeParlementaire::whereIn('id', $groupeIds)->get();

        if ($groupes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun groupe trouvé',
            ], 404);
        }

        $comparaison = $groupes->map(function ($groupe) {
            $stats = $groupe->getStatistiquesVote();
            $thematiques = $groupe->getThematiquesFavorites(5);

            return [
                'groupe' => [
                    'id' => $groupe->id,
                    'nom' => $groupe->nom,
                    'sigle' => $groupe->sigle,
                    'couleur_hex' => $groupe->couleur_hex,
                    'position_politique' => $groupe->position_politique,
                    'nombre_membres' => $groupe->nombre_membres,
                ],
                'statistiques' => $stats,
                'thematiques_top5' => $thematiques,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $comparaison,
        ]);
    }
}

