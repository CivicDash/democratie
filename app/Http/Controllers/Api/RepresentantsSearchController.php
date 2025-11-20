<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrenchPostalCode;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\Maire;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RepresentantsSearchController extends Controller
{
    /**
     * Rechercher tous les représentants (député, sénateur, maire) par code postal ou ville
     * 
     * GET /api/representants/search?q=75001
     * GET /api/representants/search?q=Paris
     * GET /api/representants/search?postal_code=75001
     * GET /api/representants/search?insee_code=75101
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');
        $postalCode = $request->input('postal_code');
        $inseeCode = $request->input('insee_code');
        
        // Recherche par code INSEE (le plus précis)
        if ($inseeCode) {
            return $this->searchByInseeCode($inseeCode);
        }
        
        // Recherche par code postal
        if ($postalCode || (is_numeric($query) && strlen($query) === 5)) {
            $code = $postalCode ?: $query;
            return $this->searchByPostalCode($code);
        }
        
        // Recherche par nom de ville
        if ($query && strlen($query) >= 2) {
            return $this->searchByCity($query);
        }
        
        return response()->json([
            'error' => 'Veuillez fournir un code postal, un code INSEE, ou un nom de ville (min 2 caractères)',
        ], 400);
    }
    
    /**
     * Rechercher par code INSEE (le plus précis)
     */
    private function searchByInseeCode(string $inseeCode): JsonResponse
    {
        $postalCode = FrenchPostalCode::where('insee_code', $inseeCode)->first();
        
        if (!$postalCode) {
            return response()->json([
                'error' => 'Code INSEE non trouvé',
            ], 404);
        }
        
        return $this->getRepresentantsByCommune($postalCode);
    }
    
    /**
     * Rechercher par code postal
     */
    private function searchByPostalCode(string $code): JsonResponse
    {
        $postalCodes = FrenchPostalCode::where('postal_code', $code)->get();
        
        if ($postalCodes->isEmpty()) {
            return response()->json([
                'error' => 'Code postal non trouvé',
            ], 404);
        }
        
        // Si plusieurs communes pour le même CP, retourner la liste
        if ($postalCodes->count() > 1) {
            return response()->json([
                'multiple_communes' => true,
                'communes' => $postalCodes->map(fn($pc) => [
                    'insee_code' => $pc->insee_code,
                    'city_name' => $pc->city_name,
                    'department_name' => $pc->department_name,
                    'postal_code' => $pc->postal_code,
                ]),
                'message' => 'Plusieurs communes trouvées pour ce code postal. Veuillez sélectionner une commune.',
            ]);
        }
        
        return $this->getRepresentantsByCommune($postalCodes->first());
    }
    
    /**
     * Rechercher par nom de ville
     */
    private function searchByCity(string $cityName): JsonResponse
    {
        $postalCodes = FrenchPostalCode::where('city_name', 'ILIKE', "%{$cityName}%")
            ->limit(20)
            ->get();
        
        if ($postalCodes->isEmpty()) {
            return response()->json([
                'error' => 'Aucune ville trouvée',
            ], 404);
        }
        
        // Si plusieurs résultats, retourner la liste
        if ($postalCodes->count() > 1) {
            return response()->json([
                'multiple_results' => true,
                'communes' => $postalCodes->map(fn($pc) => [
                    'insee_code' => $pc->insee_code,
                    'city_name' => $pc->city_name,
                    'postal_code' => $pc->postal_code,
                    'department_name' => $pc->department_name,
                ]),
                'message' => 'Plusieurs communes trouvées. Veuillez sélectionner une commune.',
            ]);
        }
        
        return $this->getRepresentantsByCommune($postalCodes->first());
    }
    
    /**
     * Récupérer tous les représentants d'une commune
     */
    private function getRepresentantsByCommune(FrenchPostalCode $postalCode): JsonResponse
    {
        // Maire
        $maire = Maire::where('code_commune', $postalCode->insee_code)
            ->where('en_exercice', true)
            ->first();
        
        // Député (par circonscription)
        // Format circonscription : "Ain (1re circonscription)" -> extraire le département et le numéro
        $depute = ActeurAN::whereHas('mandatActif', function ($q) use ($postalCode) {
            $q->where('circonscription', 'LIKE', $postalCode->department_name . '%');
        })->first();
        
        // Sénateur (par département)
        $senateurs = Senateur::where('circonscription', 'LIKE', $postalCode->department_name . '%')
            ->actifs()
            ->get();
        
        return response()->json([
            'commune' => [
                'insee_code' => $postalCode->insee_code,
                'nom' => $postalCode->city_name,
                'code_postal' => $postalCode->postal_code,
                'departement' => [
                    'code' => $postalCode->department_code,
                    'nom' => $postalCode->department_name,
                ],
                'circonscription' => $postalCode->circonscription,
            ],
            'representants' => [
                'maire' => $maire ? [
                    'nom' => $maire->nom,
                    'prenom' => $maire->prenom,
                    'email' => $maire->email,
                ] : null,
                'depute' => $depute ? [
                    'uid' => $depute->uid,
                    'nom' => $depute->nom,
                    'prenom' => $depute->prenom,
                    'photo_url' => $depute->photo_url,
                    'url' => route('representants.deputes.show', $depute->uid),
                ] : null,
                'senateurs' => $senateurs->map(fn($s) => [
                    'matricule' => $s->matricule,
                    'nom' => $s->nom_usuel,
                    'prenom' => $s->prenom_usuel,
                    'url' => route('representants.senateurs.show', $s->matricule),
                ])->values(),
            ],
            'stats' => [
                'total_representants' => ($maire ? 1 : 0) + ($depute ? 1 : 0) + $senateurs->count(),
                'has_maire' => $maire !== null,
                'has_depute' => $depute !== null,
                'nb_senateurs' => $senateurs->count(),
            ],
        ]);
    }
}
