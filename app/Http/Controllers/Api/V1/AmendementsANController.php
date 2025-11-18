<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AmendementAN;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmendementsANController extends Controller
{
    /**
     * Liste des amendements avec filtres
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = AmendementAN::query();

        // Filtres
        if ($request->has('legislature')) {
            $query->legislature($request->legislature);
        }

        if ($request->has('auteur')) {
            $query->where('auteur_acteur_ref', $request->auteur);
        }

        if ($request->has('groupe')) {
            $query->where('auteur_groupe_ref', $request->groupe);
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

        if ($request->boolean('gouvernement_only')) {
            $query->gouvernement();
        }

        if ($request->has('texte')) {
            $query->where('texte_legislatif_ref', $request->texte);
        }

        if ($request->has('date_min')) {
            $query->where('date_depot', '>=', $request->date_min);
        }

        if ($request->has('date_max')) {
            $query->where('date_depot', '<=', $request->date_max);
        }

        if ($request->has('search')) {
            $query->whereRaw(
                "to_tsvector('french', dispositif || ' ' || expose) @@ plainto_tsquery('french', ?)",
                [$request->search]
            );
        }

        // Relations
        $with = [];
        if ($request->boolean('with_auteur')) {
            $with[] = 'auteurActeur';
        }
        if ($request->boolean('with_groupe')) {
            $with[] = 'auteurGroupe';
        }
        if ($request->boolean('with_texte')) {
            $with[] = 'texteLegislatif';
        }

        if (!empty($with)) {
            $query->with($with);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'date_depot');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $amendements = $query->paginate($perPage);

        return response()->json($amendements);
    }

    /**
     * Détails d'un amendement
     * 
     * @param string $uid
     * @return JsonResponse
     */
    public function show(string $uid): JsonResponse
    {
        $amendement = AmendementAN::with([
            'auteurActeur',
            'auteurGroupe',
            'texteLegislatif.dossier'
        ])->findOrFail($uid);

        return response()->json([
            'data' => $amendement,
            'statut' => [
                'est_adopte' => $amendement->est_adopte,
                'est_rejete' => $amendement->est_rejete,
                'est_irrecevable' => $amendement->est_irrecevable,
            ],
            'cosignataires_count' => $amendement->nombre_cosignataires,
        ]);
    }

    /**
     * Statistiques générales des amendements
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $legislature = $request->get('legislature', 17);

        $query = AmendementAN::where('legislature', $legislature);

        $stats = [
            'total' => $query->count(),
            'adoptes' => (clone $query)->adoptes()->count(),
            'rejetes' => (clone $query)->rejetes()->count(),
            'gouvernement' => (clone $query)->gouvernement()->count(),
        ];

        if ($stats['total'] > 0) {
            $stats['taux_adoption'] = round(($stats['adoptes'] / $stats['total']) * 100, 2);
        } else {
            $stats['taux_adoption'] = 0;
        }

        // Top 10 auteurs
        $topAuteurs = AmendementAN::select('auteur_acteur_ref', \DB::raw('COUNT(*) as total'))
            ->where('legislature', $legislature)
            ->whereNotNull('auteur_acteur_ref')
            ->groupBy('auteur_acteur_ref')
            ->orderByDesc('total')
            ->limit(10)
            ->with('auteurActeur')
            ->get()
            ->map(function($item) {
                return [
                    'acteur' => $item->auteurActeur ? [
                        'uid' => $item->auteurActeur->uid,
                        'nom_complet' => $item->auteurActeur->nom_complet,
                    ] : null,
                    'total' => $item->total,
                ];
            });

        return response()->json([
            'legislature' => $legislature,
            'stats' => $stats,
            'top_auteurs' => $topAuteurs,
        ]);
    }
}

