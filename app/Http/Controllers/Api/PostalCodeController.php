<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrenchPostalCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostalCodeController extends Controller
{
    /**
     * Autocomplétion des codes postaux et villes
     * 
     * GET /api/postal-codes/search?q=75001
     * GET /api/postal-codes/search?q=Paris
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'message' => 'Veuillez saisir au moins 2 caractères',
            ]);
        }

        $results = FrenchPostalCode::autocomplete($query)
            ->select([
                'id',
                'postal_code',
                'city_name',
                'department_code',
                'department_name',
                'circonscription',
                'latitude',
                'longitude',
            ])
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'postal_code' => $item->postal_code,
                    'city_name' => $item->city_name,
                    'department_code' => $item->department_code,
                    'department_name' => $item->department_name,
                    'circonscription' => $item->circonscription,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'label' => $item->short_label,
                    'full_label' => $item->full_label,
                ];
            });

        return response()->json([
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Obtenir les détails d'un code postal
     * 
     * GET /api/postal-codes/{postalCode}
     */
    public function show(string $postalCode): JsonResponse
    {
        $results = FrenchPostalCode::byPostalCode($postalCode)
            ->select([
                'id',
                'postal_code',
                'city_name',
                'department_code',
                'department_name',
                'region_code',
                'region_name',
                'circonscription',
                'latitude',
                'longitude',
                'insee_code',
                'population',
            ])
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'Code postal non trouvé',
            ], 404);
        }

        return response()->json([
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Obtenir toutes les villes d'un département
     * 
     * GET /api/postal-codes/department/{departmentCode}
     */
    public function byDepartment(string $departmentCode): JsonResponse
    {
        $results = FrenchPostalCode::byDepartment($departmentCode)
            ->select([
                'id',
                'postal_code',
                'city_name',
                'department_code',
                'department_name',
                'circonscription',
            ])
            ->orderBy('city_name')
            ->get();

        return response()->json([
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Obtenir toutes les villes d'une circonscription
     * 
     * GET /api/postal-codes/circonscription/{circonscription}
     */
    public function byCirconscription(string $circonscription): JsonResponse
    {
        $results = FrenchPostalCode::byCirconscription($circonscription)
            ->select([
                'id',
                'postal_code',
                'city_name',
                'department_code',
                'department_name',
                'circonscription',
            ])
            ->orderBy('city_name')
            ->get();

        return response()->json([
            'results' => $results,
            'count' => $results->count(),
        ]);
    }
}

