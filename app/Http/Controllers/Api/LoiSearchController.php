<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API pour rechercher des lois
 */
class LoiSearchController extends Controller
{
    /**
     * Rechercher des lois par titre ou thématique
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'etat' => ['nullable', 'string'],
            'annee' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ]);

        $query = $validated['q'];
        $limit = $validated['limit'] ?? 10;

        $loisQuery = Loi::query()
            ->with('etat:etaloicod,etaloilib')
            ->select('loicod', 'loitit', 'loidatjo', 'etaloicod', 'loinumjo', 'loiint');

        // Recherche textuelle sur le titre ou l'intitulé
        $loisQuery->where(function ($q) use ($query) {
            $q->where('loitit', 'ilike', "%{$query}%")
                ->orWhere('loiint', 'ilike', "%{$query}%")
                ->orWhere('loinumjo', 'ilike', "%{$query}%");
        });

        // Filtre par état
        if (! empty($validated['etat'])) {
            $loisQuery->where('etaloicod', $validated['etat']);
        }

        // Filtre par année
        if (! empty($validated['annee'])) {
            $loisQuery->whereYear('loidatjo', $validated['annee']);
        }

        // Priorité aux lois récentes
        $loisQuery->orderByDesc('loidatjo');

        $lois = $loisQuery->limit($limit)->get();

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $lois->map(function ($loi) {
                return [
                    'code' => $loi->loicod,
                    'titre' => $loi->loitit ?: $loi->loiint,
                    'numero' => $loi->loinumjo,
                    'date' => $loi->loidatjo?->toDateString(),
                    'annee' => $loi->loidatjo?->year,
                    'etat' => $loi->etat?->etaloilib ?? $loi->etaloicod,
                    'url' => route('lois.show', trim($loi->loicod)),
                ];
            }),
            'count' => $lois->count(),
        ]);
    }
}
