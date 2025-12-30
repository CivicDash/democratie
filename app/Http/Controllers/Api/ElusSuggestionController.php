<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\Maire;
use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API pour suggérer des élus en fonction du scope géographique
 */
class ElusSuggestionController extends Controller
{
    /**
     * Suggérer des élus selon le scope géographique et recherche textuelle
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['nullable', 'in:national,regional,departemental,communal'],
            'region_id' => ['nullable', 'exists:territories_regions,id'],
            'department_id' => ['nullable', 'exists:territories_departments,id'],
            'search' => ['nullable', 'string', 'min:2', 'max:100'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:depute,senateur,maire'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $scope = $validated['scope'] ?? 'national';
        $regionId = $validated['region_id'] ?? null;
        $departmentId = $validated['department_id'] ?? null;
        $search = $validated['search'] ?? null;
        $types = $validated['types'] ?? ['depute', 'senateur', 'maire'];
        $limit = $validated['limit'] ?? 20;

        $results = [
            'deputes' => [],
            'senateurs' => [],
            'maires' => [],
        ];

        // Charger département et région si nécessaire
        $department = $departmentId ? TerritoryDepartment::find($departmentId) : null;
        $region = $regionId ? TerritoryRegion::find($regionId) : null;

        // Si on a un département mais pas de région, récupérer la région associée
        if ($department && !$region) {
            $region = $department->region;
        }

        // ========================================================================
        // DÉPUTÉS
        // ========================================================================
        if (in_array('depute', $types)) {
            $deputesQuery = ActeurAN::deputes()
                ->with(['mandats.organe', 'circonscriptions']);

            // Filtre géographique
            if ($scope === 'departemental' && $department) {
                $deputesQuery->whereHas('circonscriptions', function ($q) use ($department) {
                    $q->where('departement', $department->code)
                      ->orWhere('departement', 'like', "%{$department->name}%");
                });
            } elseif ($scope === 'regional' && $region) {
                // Récupérer tous les départements de la région
                $deptCodes = TerritoryDepartment::where('region_id', $region->id)
                    ->pluck('code')
                    ->toArray();
                
                $deputesQuery->whereHas('circonscriptions', function ($q) use ($deptCodes) {
                    $q->whereIn('departement', $deptCodes);
                });
            }

            // Recherche textuelle
            if ($search) {
                $deputesQuery->where(function ($q) use ($search) {
                    $q->where('nom', 'ilike', "%{$search}%")
                      ->orWhere('prenom', 'ilike', "%{$search}%");
                });
            }

            $deputes = $deputesQuery->limit($limit)->get();

            $results['deputes'] = $deputes->map(function ($d) {
                $circo = $d->circonscriptions->first();
                $groupe = $d->groupe_politique_actuel;
                
                return [
                    'id' => $d->uid,
                    'type' => 'depute',
                    'nom_complet' => $d->nom_complet ?? trim("{$d->prenom} {$d->nom}"),
                    'photo_url' => $d->photo_url ?? null,
                    'groupe' => $groupe?->libelle_abrege ?? $groupe?->libelle ?? null,
                    'groupe_couleur' => $groupe?->couleur_hex ?? null,
                    'circonscription' => $circo 
                        ? "Circonscription {$circo->numero_circonscription} - {$circo->departement}"
                        : null,
                ];
            })->toArray();
        }

        // ========================================================================
        // SÉNATEURS
        // ========================================================================
        if (in_array('senateur', $types)) {
            $senateursQuery = Senateur::actifs()
                ->select('matricule', 'prenom_usuel', 'nom_usuel', 'circonscription', 'groupe_politique');

            // Filtre géographique
            if ($scope === 'departemental' && $department) {
                $senateursQuery->where(function ($q) use ($department) {
                    $q->where('circonscription', 'ilike', "%{$department->name}%")
                      ->orWhere('circonscription', 'ilike', "%{$department->code}%");
                });
            } elseif ($scope === 'regional' && $region) {
                // Récupérer tous les départements de la région
                $deptNames = TerritoryDepartment::where('region_id', $region->id)
                    ->pluck('name')
                    ->toArray();
                
                $senateursQuery->where(function ($q) use ($deptNames) {
                    foreach ($deptNames as $name) {
                        $q->orWhere('circonscription', 'ilike', "%{$name}%");
                    }
                });
            }

            // Recherche textuelle
            if ($search) {
                $senateursQuery->where(function ($q) use ($search) {
                    $q->where('nom_usuel', 'ilike', "%{$search}%")
                      ->orWhere('prenom_usuel', 'ilike', "%{$search}%");
                });
            }

            $senateurs = $senateursQuery->limit($limit)->get();

            $groupeService = app(\App\Services\GroupeParlementaireService::class);

            $results['senateurs'] = $senateurs->map(function ($s) use ($groupeService) {
                return [
                    'id' => $s->matricule,
                    'type' => 'senateur',
                    'nom_complet' => trim("{$s->prenom_usuel} {$s->nom_usuel}"),
                    'photo_url' => $s->photo_url ?? null,
                    'groupe' => $s->groupe_politique,
                    'groupe_couleur' => $groupeService->getCouleurGroupe($s->groupe_politique),
                    'circonscription' => $s->circonscription,
                ];
            })->toArray();
        }

        // ========================================================================
        // MAIRES
        // ========================================================================
        if (in_array('maire', $types) && ($scope === 'communal' || $scope === 'departemental' || $search)) {
            $mairesQuery = Maire::query()
                ->select('id', 'prenom', 'nom', 'nom_complet', 'nom_commune', 'code_commune', 'code_departement', 'nom_departement', 'nuance_politique', 'photo_url');

            // Filtre géographique
            if ($scope === 'communal' || $scope === 'departemental') {
                if ($department) {
                    $mairesQuery->where('code_departement', $department->code);
                }
            }

            // Recherche textuelle (obligatoire pour les maires à cause du volume)
            if ($search) {
                $mairesQuery->where(function ($q) use ($search) {
                    $q->where('nom', 'ilike', "%{$search}%")
                      ->orWhere('prenom', 'ilike', "%{$search}%")
                      ->orWhere('nom_commune', 'ilike', "%{$search}%");
                });
            }

            $maires = $mairesQuery->limit($limit)->get();

            $results['maires'] = $maires->map(function ($m) {
                return [
                    'id' => (string) $m->id,
                    'type' => 'maire',
                    'nom_complet' => $m->nom_complet ?? trim("{$m->prenom} {$m->nom}"),
                    'photo_url' => $m->photo_url,
                    'commune' => $m->nom_commune,
                    'departement' => $m->nom_departement ?? $m->code_departement,
                    'nuance' => $m->nuance_politique,
                ];
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'scope' => $scope,
            'results' => $results,
            'count' => [
                'deputes' => count($results['deputes']),
                'senateurs' => count($results['senateurs']),
                'maires' => count($results['maires']),
            ],
        ]);
    }
}
