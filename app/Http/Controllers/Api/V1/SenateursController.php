<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Senateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SenateursController extends Controller
{
    /**
     * Liste des sénateurs avec filtres
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Senateur::query();

        // Filtres
        if ($request->has('nom')) {
            $query->where('nom_usuel', 'ILIKE', '%' . $request->nom . '%');
        }

        if ($request->has('prenom')) {
            $query->where('prenom_usuel', 'ILIKE', '%' . $request->prenom . '%');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom_usuel', 'ILIKE', '%' . $search . '%')
                  ->orWhere('prenom_usuel', 'ILIKE', '%' . $search . '%');
            });
        }

        if ($request->has('etat')) {
            $query->where('etat', strtoupper($request->etat));
        }

        if ($request->boolean('actifs_only')) {
            $query->actifs();
        }

        if ($request->has('circonscription')) {
            $query->parCirconscription($request->circonscription);
        }

        if ($request->has('groupe')) {
            $query->parGroupe($request->groupe);
        }

        // Relations
        $with = [];
        if ($request->boolean('with_commissions')) {
            $with[] = 'commissions';
        }
        if ($request->boolean('with_mandats')) {
            $with[] = 'mandats';
        }
        if ($request->boolean('with_groupes')) {
            $with[] = 'historiqueGroupes';
        }

        if (!empty($with)) {
            $query->with($with);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'nom_usuel');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $senateurs = $query->paginate($perPage);

        return response()->json($senateurs);
    }

    /**
     * Détails d'un sénateur
     * 
     * @param string $matricule
     * @return JsonResponse
     */
    public function show(string $matricule): JsonResponse
    {
        $senateur = Senateur::with([
            'commissions' => function($query) {
                $query->orderBy('date_debut', 'desc');
            },
            'mandats' => function($query) {
                $query->orderBy('date_debut', 'desc');
            },
            'historiqueGroupes' => function($query) {
                $query->orderBy('date_debut', 'desc');
            },
        ])->findOrFail($matricule);

        return response()->json([
            'data' => $senateur,
            'commissions_actuelles' => $senateur->commissions_actuelles,
            'mandats_actifs' => $senateur->mandats_actifs,
        ]);
    }

    /**
     * Mandats d'un sénateur
     * 
     * @param string $matricule
     * @param Request $request
     * @return JsonResponse
     */
    public function mandats(string $matricule, Request $request): JsonResponse
    {
        $senateur = Senateur::findOrFail($matricule);

        $query = $senateur->mandats();

        // Filtres
        if ($request->has('type')) {
            $query->where('type_mandat', strtoupper($request->type));
        }

        if ($request->boolean('actifs_only')) {
            $query->actifs();
        }

        // Tri
        $query->orderBy('date_debut', 'desc');

        $mandats = $query->get();

        // Stats
        $stats = [
            'total' => $senateur->mandats()->count(),
            'senateur' => $senateur->mandats()->senateur()->count(),
            'depute' => $senateur->mandats()->depute()->count(),
            'municipal' => $senateur->mandats()->municipal()->count(),
            'actifs' => $senateur->mandats()->actifs()->count(),
        ];

        return response()->json([
            'data' => $mandats,
            'stats' => $stats,
        ]);
    }

    /**
     * Commissions d'un sénateur
     * 
     * @param string $matricule
     * @param Request $request
     * @return JsonResponse
     */
    public function commissions(string $matricule, Request $request): JsonResponse
    {
        $senateur = Senateur::findOrFail($matricule);

        $query = $senateur->commissions();

        // Filtres
        if ($request->boolean('actuelles_only')) {
            $query->actifs();
        }

        // Tri
        $query->orderBy('date_debut', 'desc');

        $commissions = $query->get();

        return response()->json([
            'data' => $commissions,
            'total' => $commissions->count(),
            'actuelles' => $commissions->where('date_fin', null)->count(),
        ]);
    }

    /**
     * Historique des groupes politiques d'un sénateur
     * 
     * @param string $matricule
     * @return JsonResponse
     */
    public function groupes(string $matricule): JsonResponse
    {
        $senateur = Senateur::findOrFail($matricule);

        $groupes = $senateur->historiqueGroupes()
            ->orderBy('date_debut', 'desc')
            ->get();

        return response()->json([
            'data' => $groupes,
            'groupe_actuel' => $senateur->groupe_politique,
        ]);
    }

    /**
     * Statistiques générales des sénateurs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = [
            'total' => Senateur::count(),
            'actifs' => Senateur::actifs()->count(),
            'anciens' => Senateur::anciens()->count(),
        ];

        // Répartition par groupe (actifs uniquement)
        $parGroupe = Senateur::actifs()
            ->whereNotNull('groupe_politique')
            ->select('groupe_politique', \DB::raw('COUNT(*) as total'))
            ->groupBy('groupe_politique')
            ->orderByDesc('total')
            ->get()
            ->map(function($item) {
                return [
                    'groupe' => $item->groupe_politique,
                    'total' => $item->total,
                ];
            });

        return response()->json([
            'stats' => $stats,
            'par_groupe' => $parGroupe,
        ]);
    }
}

