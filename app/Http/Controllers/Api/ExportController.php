<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {}

    /**
     * Exporter un groupe parlementaire en PDF
     */
    public function groupe(int $id)
    {
        return $this->exportService->exportGroupe($id);
    }

    /**
     * Exporter une thématique en PDF
     */
    public function thematique(string $code)
    {
        return $this->exportService->exportThematique($code);
    }

    /**
     * Exporter une proposition de loi en PDF
     */
    public function proposition(int $id)
    {
        return $this->exportService->exportProposition($id);
    }

    /**
     * Exporter les statistiques globales en PDF
     */
    public function statistiques(Request $request)
    {
        $filters = $request->all();
        return $this->exportService->exportStatistiques($filters);
    }

    /**
     * Exporter une comparaison de groupes en PDF
     */
    public function comparaison(Request $request)
    {
        $groupeIds = $request->input('groupe_ids', []);
        
        if (empty($groupeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner au moins un groupe',
            ], 400);
        }

        return $this->exportService->exportComparaison($groupeIds);
    }
}
