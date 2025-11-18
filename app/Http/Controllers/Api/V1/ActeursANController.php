<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActeursANController extends Controller
{
    /**
     * Liste des acteurs avec filtres
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActeurAN::query();

        // Filtres
        if ($request->has('nom')) {
            $query->where('nom', 'ILIKE', '%' . $request->nom . '%');
        }

        if ($request->has('prenom')) {
            $query->where('prenom', 'ILIKE', '%' . $request->prenom . '%');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'ILIKE', '%' . $search . '%')
                  ->orWhere('prenom', 'ILIKE', '%' . $search . '%');
            });
        }

        if ($request->boolean('deputes_only')) {
            $query->deputes();
        }

        // Relations
        $with = [];
        if ($request->boolean('with_mandats')) {
            $with[] = 'mandats.organe';
        }
        if ($request->boolean('with_groupe')) {
            $with[] = 'mandats.organe';
        }

        if (!empty($with)) {
            $query->with($with);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $acteurs = $query->paginate($perPage);

        return response()->json($acteurs);
    }

    /**
     * Détails d'un acteur
     * 
     * @param string $uid
     * @return JsonResponse
     */
    public function show(string $uid): JsonResponse
    {
        $acteur = ActeurAN::with([
            'mandats' => function($query) {
                $query->orderBy('date_debut', 'desc');
            },
            'mandats.organe'
        ])->findOrFail($uid);

        return response()->json([
            'data' => $acteur,
            'groupe_actuel' => $acteur->groupe_politique_actuel,
            'commissions_actuelles' => $acteur->commissions_actuelles,
        ]);
    }

    /**
     * Votes d'un acteur
     * 
     * @param string $uid
     * @param Request $request
     * @return JsonResponse
     */
    public function votes(string $uid, Request $request): JsonResponse
    {
        $acteur = ActeurAN::findOrFail($uid);

        $query = $acteur->votesIndividuels()
            ->with(['scrutin', 'groupe']);

        // Filtres
        if ($request->has('legislature')) {
            $query->whereHas('scrutin', function($q) use ($request) {
                $q->where('legislature', $request->legislature);
            });
        }

        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        if ($request->has('date_min')) {
            $query->whereHas('scrutin', function($q) use ($request) {
                $q->where('date_scrutin', '>=', $request->date_min);
            });
        }

        if ($request->has('date_max')) {
            $query->whereHas('scrutin', function($q) use ($request) {
                $q->where('date_scrutin', '<=', $request->date_max);
            });
        }

        // Tri
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $votes = $query->paginate($perPage);

        // Stats
        $stats = [
            'total' => $acteur->votesIndividuels()->count(),
            'pour' => $acteur->votesIndividuels()->pour()->count(),
            'contre' => $acteur->votesIndividuels()->contre()->count(),
            'abstention' => $acteur->votesIndividuels()->abstention()->count(),
            'non_votant' => $acteur->votesIndividuels()->nonVotant()->count(),
        ];

        return response()->json([
            'data' => $votes,
            'stats' => $stats,
        ]);
    }

    /**
     * Amendements d'un acteur
     * 
     * @param string $uid
     * @param Request $request
     * @return JsonResponse
     */
    public function amendements(string $uid, Request $request): JsonResponse
    {
        $acteur = ActeurAN::findOrFail($uid);

        $query = $acteur->amendementsAuteur()
            ->with(['texteLegislatif', 'auteurGroupe']);

        // Filtres
        if ($request->has('legislature')) {
            $query->where('legislature', $request->legislature);
        }

        if ($request->has('etat')) {
            $query->where('etat_code', $request->etat);
        }

        if ($request->boolean('adoptes_only')) {
            $query->adoptes();
        }

        if ($request->boolean('rejetes_only')) {
            $query->rejetes();
        }

        // Tri
        $query->orderBy('date_depot', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $amendements = $query->paginate($perPage);

        // Stats
        $stats = [
            'total' => $acteur->amendementsAuteur()->count(),
            'adoptes' => $acteur->amendementsAuteur()->adoptes()->count(),
            'rejetes' => $acteur->amendementsAuteur()->rejetes()->count(),
            'taux_adoption' => 0,
        ];

        if ($stats['total'] > 0) {
            $stats['taux_adoption'] = round(($stats['adoptes'] / $stats['total']) * 100, 2);
        }

        return response()->json([
            'data' => $amendements,
            'stats' => $stats,
        ]);
    }

    /**
     * Statistiques d'activité d'un acteur
     * 
     * @param string $uid
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(string $uid, Request $request): JsonResponse
    {
        $acteur = ActeurAN::findOrFail($uid);
        $legislature = $request->get('legislature', 17);

        // Votes
        $votesQuery = $acteur->votesIndividuels()
            ->whereHas('scrutin', function($q) use ($legislature) {
                $q->where('legislature', $legislature);
            });

        $votesStats = [
            'total' => $votesQuery->count(),
            'pour' => (clone $votesQuery)->pour()->count(),
            'contre' => (clone $votesQuery)->contre()->count(),
            'abstention' => (clone $votesQuery)->abstention()->count(),
            'non_votant' => (clone $votesQuery)->nonVotant()->count(),
        ];

        if ($votesStats['total'] > 0) {
            $votesStats['taux_participation'] = round((($votesStats['total'] - $votesStats['non_votant']) / $votesStats['total']) * 100, 2);
        } else {
            $votesStats['taux_participation'] = 0;
        }

        // Amendements
        $amendementsQuery = $acteur->amendementsAuteur()
            ->where('legislature', $legislature);

        $amendementsStats = [
            'total' => $amendementsQuery->count(),
            'adoptes' => (clone $amendementsQuery)->adoptes()->count(),
            'rejetes' => (clone $amendementsQuery)->rejetes()->count(),
        ];

        if ($amendementsStats['total'] > 0) {
            $amendementsStats['taux_adoption'] = round(($amendementsStats['adoptes'] / $amendementsStats['total']) * 100, 2);
        } else {
            $amendementsStats['taux_adoption'] = 0;
        }

        return response()->json([
            'acteur' => [
                'uid' => $acteur->uid,
                'nom_complet' => $acteur->nom_complet,
                'groupe_actuel' => $acteur->groupe_politique_actuel,
            ],
            'legislature' => $legislature,
            'votes' => $votesStats,
            'amendements' => $amendementsStats,
        ]);
    }
}

