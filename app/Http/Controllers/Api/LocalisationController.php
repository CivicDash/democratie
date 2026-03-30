<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalisationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalisationController extends Controller
{
    public function __construct(
        private LocalisationService $localisationService
    ) {}

    /**
     * Recherche par code postal ou nom de ville
     * GET /api/localisation/search?q=75001
     * GET /api/localisation/search?q=Lyon
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $limit = min($request->input('limit', 20), 50);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->localisationService->search($query, $limit);

        return response()->json($results);
    }

    /**
     * Obtenir les représentants d'un lieu
     * GET /api/localisation/representants/75101
     */
    public function representants(string $inseeCode): JsonResponse
    {
        $data = $this->localisationService->getRepresentants($inseeCode);

        if (empty($data)) {
            return response()->json(['error' => 'Lieu non trouvé'], 404);
        }

        return response()->json($data);
    }

    /**
     * Liste des départements
     * GET /api/localisation/departements
     */
    public function departements(): JsonResponse
    {
        $departements = $this->localisationService->getDepartements();

        return response()->json($departements);
    }

    /**
     * Suggestions pour autocomplete
     * GET /api/localisation/suggest?q=Par
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->localisationService->search($query, 10)
            ->map(fn ($r) => [
                'label' => $r['city_name'].' ('.$r['postal_code'].')',
                'value' => $r['insee_code'],
                'postal_code' => $r['postal_code'],
                'city_name' => $r['city_name'],
                'department' => $r['department_name'],
            ]);

        return response()->json($results);
    }
}
