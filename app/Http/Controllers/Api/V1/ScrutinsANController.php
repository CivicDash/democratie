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
        $allowedSorts = ['date_scrutin', 'uid', 'titre'];
        $sortBy = $request->input('sort_by', 'date_scrutin');
        $sortOrder = $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy(in_array($sortBy, $allowedSorts, true) ? $sortBy : 'date_scrutin', $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 20), 100);
        $scrutins = $query->paginate($perPage);

        return response()->json($scrutins);
    }

    /**
     * Détails d'un scrutin
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
        $perPage = min($request->input('per_page', 50), 200);
        $votes = $query->paginate($perPage);

        return response()->json($votes);
    }

    /**
     * Statistiques par groupe pour un scrutin
     */
    public function statsParGroupe(string $uid): JsonResponse
    {
        $scrutin = ScrutinAN::findOrFail($uid);

        $rawStats = $scrutin->votesIndividuels()
            ->selectRaw("
                groupe_ref,
                position_groupe,
                COUNT(*) as total,
                SUM(CASE WHEN position = 'pour' THEN 1 ELSE 0 END) as pour,
                SUM(CASE WHEN position = 'contre' THEN 1 ELSE 0 END) as contre,
                SUM(CASE WHEN position = 'abstention' THEN 1 ELSE 0 END) as abstention,
                SUM(CASE WHEN position = 'non_votant' THEN 1 ELSE 0 END) as non_votant
            ")
            ->groupBy('groupe_ref', 'position_groupe')
            ->get();

        $groupRefs = $rawStats->pluck('groupe_ref')->filter()->unique()->values();
        $groupLabels = \App\Models\OrganeAN::whereIn('uid', $groupRefs)
            ->pluck('libelle_abrege', 'uid');

        $stats = $rawStats
            ->groupBy('groupe_ref')
            ->map(function ($rows, $groupeRef) use ($groupLabels) {
                $row = $rows->first();

                return [
                    'groupe' => [
                        'uid' => $groupeRef,
                        'libelle' => $groupLabels[$groupeRef] ?? 'Inconnu',
                    ],
                    'total' => (int) $row->total,
                    'pour' => (int) $row->pour,
                    'contre' => (int) $row->contre,
                    'abstention' => (int) $row->abstention,
                    'non_votant' => (int) $row->non_votant,
                    'position_majoritaire' => $row->position_groupe,
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
