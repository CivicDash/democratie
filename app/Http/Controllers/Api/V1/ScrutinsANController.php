<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ScrutinAN;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScrutinsANController extends Controller
{
    /**
     * Liste des scrutins avec filtres
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = ScrutinAN::query();

        // Filtres
        if ($request->has('legislature')) {
            $query->legislature($request->legislature);
        }

        if ($request->has('date_min')) {
            $query->where('date_scrutin', '>=', $request->date_min);
        }

        if ($request->has('date_max')) {
            $query->where('date_scrutin', '<=', $request->date_max);
        }

        if ($request->has('resultat')) {
            $query->where('resultat_code', $request->resultat);
        }

        if ($request->boolean('adoptes_only')) {
            $query->adopte();
        }

        if ($request->boolean('rejetes_only')) {
            $query->rejete();
        }

        if ($request->has('search')) {
            $query->whereRaw(
                "to_tsvector('french', titre) @@ plainto_tsquery('french', ?)",
                [$request->search]
            );
        }

        // Relations
        if ($request->boolean('with_organe')) {
            $query->with('organe');
        }

        // Tri
        $sortBy = $request->get('sort_by', 'date_scrutin');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $scrutins = $query->paginate($perPage);

        return response()->json($scrutins);
    }

    /**
     * Détails d'un scrutin
     * 
     * @param string $uid
     * @return JsonResponse
     */
    public function show(string $uid): JsonResponse
    {
        $scrutin = ScrutinAN::with(['organe'])->findOrFail($uid);

        return response()->json([
            'data' => $scrutin,
            'stats' => [
                'taux_participation' => $scrutin->taux_participation,
                'taux_pour' => $scrutin->taux_pour,
                'taux_contre' => $scrutin->taux_contre,
                'taux_abstention' => $scrutin->taux_abstention,
            ],
        ]);
    }

    /**
     * Votes individuels d'un scrutin
     * 
     * @param string $uid
     * @param Request $request
     * @return JsonResponse
     */
    public function votes(string $uid, Request $request): JsonResponse
    {
        $scrutin = ScrutinAN::findOrFail($uid);

        $query = $scrutin->votesIndividuels()
            ->with(['acteur', 'groupe']);

        // Filtres
        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        if ($request->has('groupe')) {
            $query->where('groupe_ref', $request->groupe);
        }

        if ($request->boolean('rebelles_only')) {
            $query->whereRaw('position != position_groupe')
                  ->whereNotNull('position_groupe')
                  ->where('position_groupe', '!=', 'mixte');
        }

        // Tri
        $query->orderBy('acteur_ref');

        // Pagination
        $perPage = min($request->get('per_page', 50), 200);
        $votes = $query->paginate($perPage);

        return response()->json($votes);
    }

    /**
     * Statistiques par groupe pour un scrutin
     * 
     * @param string $uid
     * @return JsonResponse
     */
    public function statsParGroupe(string $uid): JsonResponse
    {
        $scrutin = ScrutinAN::findOrFail($uid);

        $stats = $scrutin->votesIndividuels()
            ->with('groupe')
            ->get()
            ->groupBy('groupe_ref')
            ->map(function($votes, $groupeRef) {
                $groupe = $votes->first()->groupe;
                
                return [
                    'groupe' => [
                        'uid' => $groupeRef,
                        'libelle' => $groupe ? $groupe->libelle_abrege : 'Inconnu',
                    ],
                    'total' => $votes->count(),
                    'pour' => $votes->where('position', 'pour')->count(),
                    'contre' => $votes->where('position', 'contre')->count(),
                    'abstention' => $votes->where('position', 'abstention')->count(),
                    'non_votant' => $votes->where('position', 'non_votant')->count(),
                    'position_majoritaire' => $votes->first()->position_groupe,
                ];
            })
            ->values();

        return response()->json([
            'scrutin' => [
                'uid' => $scrutin->uid,
                'numero' => $scrutin->numero,
                'titre' => $scrutin->titre,
                'resultat' => $scrutin->resultat_code,
            ],
            'stats_par_groupe' => $stats,
        ]);
    }
}

