<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrenchPostalCode;
use App\Models\ActeurAN;
use App\Models\DeputeCirconscription;
use App\Models\DeputeSenateur;
use App\Models\Senateur;
use App\Models\Maire;
use App\Services\GroupeParlementaireService;
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
        $groupeService = app(GroupeParlementaireService::class);
        
        // Maire
        $maire = Maire::where('code_commune', $postalCode->insee_code)
            ->where('en_exercice', true)
            ->first();
        
        // Député (par circonscription) - utiliser la nouvelle table deputes_circonscriptions
        // Format circonscription dans french_postal_codes : "75-01" pour Paris 1ère
        $deputeData = null;
        
        if ($postalCode->circonscription) {
            // Parser la circonscription: "75-01" => département 75, numéro 01
            $parts = explode('-', $postalCode->circonscription);
            if (count($parts) === 2) {
                $deptCode = $parts[0];
                $numCirco = intval($parts[1]);
                
                // Chercher dans la nouvelle table deputes_circonscriptions
                $deputeCirco = DeputeCirconscription::where('num_departement', $deptCode)
                    ->where('num_circo', $numCirco)
                    ->legislature(17)
                    ->actif()
                    ->with('depute')
                    ->first();
                
                if ($deputeCirco && $deputeCirco->depute) {
                    $acteur = $deputeCirco->depute;
                    $groupeActuel = $acteur->groupe_politique_actuel;
                    
                    $deputeData = [
                        'uid' => $acteur->uid,
                        'nom' => $acteur->nom,
                        'prenom' => $acteur->prenom,
                        'nom_complet' => $acteur->nom_complet,
                        'photo_url' => $acteur->photo_url,
                        'groupe' => $groupeActuel?->libelle,
                        'groupe_sigle' => $groupeActuel?->libelle_abrege,
                        'groupe_couleur' => $groupeActuel ? $groupeService->getCouleurGroupe($groupeActuel->libelle_abrege) : null,
                        'circonscription' => $deputeCirco->libelle_circonscription,
                        'place_hemicycle' => $deputeCirco->place_hemicycle,
                        'url' => route('representants.deputes.show', $acteur->uid),
                    ];
                }
            }
        }
        
        // Fallback sur ancien système si pas trouvé
        $depute = null;
        if (!$deputeData) {
            $depute = DeputeSenateur::deputes()
                ->enExercice()
                ->where('circonscription', $postalCode->circonscription)
                ->first();
        }
        
        // Sénateurs (par département)
        // Essayer d'abord avec le nouveau modèle Senateur
        $senateurs = Senateur::actifs()
            ->where(function($q) use ($postalCode) {
                // Chercher par nom de département dans la circonscription
                $q->where('circonscription', 'ILIKE', '%' . $postalCode->department_name . '%')
                  // Ou par code département si stocké différemment
                  ->orWhere('circonscription', 'LIKE', $postalCode->department_code . '%');
            })
            ->get();
        
        // Fallback: utiliser DeputeSenateur pour les sénateurs
        $senateursOld = collect();
        if ($senateurs->isEmpty()) {
            $senateursOld = DeputeSenateur::senateurs()
                ->enExercice()
                ->where(function($q) use ($postalCode) {
                    $q->where('circonscription', 'LIKE', $postalCode->department_code . '%')
                      ->orWhere('circonscription', 'LIKE', '%' . $postalCode->department_name . '%');
                })
                ->get();
        }
        
        // Format député pour la réponse
        $deputeResponse = $deputeData ?: ($depute ? [
            'id' => $depute->id,
            'uid' => $depute->uid,
            'nom' => $depute->nom,
            'prenom' => $depute->prenom,
            'nom_complet' => $depute->nom_complet,
            'photo_url' => $depute->photo_url,
            'groupe' => $depute->groupe_politique,
            'circonscription' => $depute->circonscription,
            'url' => route('representants.deputes.show', $depute->uid ?? $depute->id),
        ] : null);

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
                    'id' => $maire->id,
                    'nom' => $maire->nom,
                    'prenom' => $maire->prenom,
                    'nom_complet' => $maire->nom_complet ?? trim("{$maire->prenom} {$maire->nom}"),
                    'email' => $maire->email,
                    'commune' => $maire->nom_commune ?? $maire->commune,
                    'civilite' => $maire->civilite,
                    'url' => route('elus.public-profile', ['type' => 'maire', 'ref' => $maire->id]),
                ] : null,
                'depute' => $deputeResponse,
                'senateurs' => $senateurs->isNotEmpty() 
                    ? $senateurs->map(fn($s) => [
                        'matricule' => $s->matricule,
                        'nom' => $s->nom_usuel,
                        'prenom' => $s->prenom_usuel,
                        'nom_complet' => trim($s->prenom_usuel . ' ' . $s->nom_usuel),
                        'photo_url' => $s->photo_url,
                        'groupe' => $s->groupe_politique,
                        'url' => route('representants.senateurs.show', $s->matricule),
                    ])->values()
                    : $senateursOld->map(fn($s) => [
                        'id' => $s->id,
                        'uid' => $s->uid,
                        'nom' => $s->nom,
                        'prenom' => $s->prenom,
                        'nom_complet' => $s->nom_complet,
                        'photo_url' => $s->photo_url,
                        'groupe' => $s->groupe_politique,
                        'url' => route('representants.senateurs.show', $s->uid),
                    ])->values(),
            ],
            'stats' => [
                'total_representants' => ($maire ? 1 : 0) + ($deputeResponse ? 1 : 0) + max($senateurs->count(), $senateursOld->count()),
                'has_maire' => $maire !== null,
                'has_depute' => $deputeResponse !== null,
                'nb_senateurs' => max($senateurs->count(), $senateursOld->count()),
            ],
        ]);
    }
}
