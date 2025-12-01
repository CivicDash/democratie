<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\DeputeSenateur;
use App\Models\GroupeParlementaire;
use App\Models\Profile;
use App\Models\Senateur;
use App\Services\GroupeParlementaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class RepresentantController extends Controller
{
    /**
     * Vérifie si la table deputes_senateurs existe
     */
    private function deputesSenateursTableExists(): bool
    {
        static $exists = null;
        if ($exists === null) {
            $exists = Schema::hasTable('deputes_senateurs');
        }
        return $exists;
    }

    /**
     * Page "Mes Représentants"
     */
    public function mesRepresentants(Request $request): Response
    {
        $user = auth()->user();
        $profile = $user->profile;

        $data = [
            'hasLocation' => false,
            'depute' => null,
            'senateurs' => [],
            'location' => null,
        ];

        $groupeService = app(GroupeParlementaireService::class);
        
        // Mode simulation via paramètre GET
        $simulatePostalCode = $request->input('simulate_postal_code');
        
        if ($simulatePostalCode) {
            $postalData = \App\Models\FrenchPostalCode::where('postal_code', $simulatePostalCode)->first();
            
            if ($postalData) {
                $data['hasLocation'] = true;
                $deptCode = substr($postalData->circonscription ?? '', 0, 2);
                
                $data['location'] = [
                    'city' => $postalData->city_name,
                    'postal_code' => $postalData->postal_code,
                    'circonscription' => $postalData->circonscription,
                    'department' => $postalData->department_name,
                    'is_simulated' => true,
                ];

                // Député de la circonscription - TODO: implémenter avec ActeurAN
                // Note: La table deputes_senateurs n'existe plus en production
                $data['depute'] = null;

                // Sénateurs du département (via modèle Senateur)
                $senateurs = Senateur::actifs()
                    ->where('departement_code', $deptCode)
                    ->get();

                $data['senateurs'] = $senateurs->map(function($senateur) use ($groupeService) {
                    return [
                        'id' => $senateur->matricule,
                        'nom_complet' => $senateur->prenom . ' ' . $senateur->nom,
                        'photo_url' => $senateur->photo_url,
                        'profession' => $senateur->profession,
                        'groupe' => $senateur->groupe_politique ? [
                            'sigle' => $senateur->groupe_politique,
                            'nom' => $senateur->groupe_politique,
                            'couleur' => $groupeService->getCouleurGroupe($senateur->groupe_politique),
                        ] : null,
                        'nb_propositions' => 0,
                        'nb_amendements' => 0,
                        'taux_presence' => 0,
                        'url_profil' => route('representants.senateurs.show', $senateur->matricule),
                    ];
                })->toArray();
            }
        } elseif ($profile && $profile->circonscription && $profile->department_id) {
            $data['hasLocation'] = true;
            $deptCode = substr($profile->circonscription ?? '', 0, 2);
            
            $data['location'] = [
                'city' => $profile->city_name,
                'postal_code' => $profile->postal_code,
                'circonscription' => $profile->circonscription,
                'department' => $profile->department?->name,
            ];

            // Député de la circonscription - TODO: implémenter avec ActeurAN
            // Note: La table deputes_senateurs n'existe plus en production
            $data['depute'] = null;

            // Sénateurs du département (via modèle Senateur)
            $senateurs = Senateur::actifs()
                ->where('departement_code', $deptCode)
                ->get();

            $data['senateurs'] = $senateurs->map(function($senateur) use ($groupeService) {
                return [
                    'id' => $senateur->matricule,
                    'nom_complet' => $senateur->prenom . ' ' . $senateur->nom,
                    'photo_url' => $senateur->photo_url,
                    'profession' => $senateur->profession,
                    'groupe' => $senateur->groupe_politique ? [
                        'sigle' => $senateur->groupe_politique,
                        'nom' => $senateur->groupe_politique,
                        'couleur' => $groupeService->getCouleurGroupe($senateur->groupe_politique),
                    ] : null,
                    'nb_propositions' => 0,
                    'nb_amendements' => 0,
                    'taux_presence' => 0,
                    'url_profil' => route('representants.senateurs.show', $senateur->matricule),
                ];
            })->toArray();
        }

        // Répartition nationale des députés par département
        // Note: La table deputes_senateurs n'existe plus, on utilise les données des sénateurs uniquement
        // TODO: Ajouter les données des députés quand la structure sera disponible
        $data['deputesByDepartment'] = [];

        $data['senateursByDepartment'] = Senateur::where('etat', 'ACTIF')
            ->selectRaw('SUBSTRING(departement_code, 1, 2) as department_code, COUNT(*) as count')
            ->whereNotNull('departement_code')
            ->groupBy('department_code')
            ->pluck('count', 'department_code')
            ->toArray();

        return Inertia::render('Representants/MesRepresentants', $data);
    }

    /**
     * Liste complète des députés
     */
    public function deputes(Request $request): Response
    {
        $query = DeputeSenateur::deputes()
            ->enExercice()
            ->with(['groupeParlementaire']);

        // Filtres
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('groupe')) {
            $query->where('groupe_sigle', $request->groupe);
        }

        if ($request->filled('department')) {
            $query->where('circonscription', 'like', $request->department . '%');
        }

        // Tri
        $sortBy = $request->get('sort', 'nom');
        $sortOrder = $request->get('order', 'asc');

        switch ($sortBy) {
            case 'groupe':
                $query->orderBy('groupe_sigle', $sortOrder);
                break;
            case 'circonscription':
                $query->orderBy('circonscription', $sortOrder);
                break;
            default:
                $query->orderBy('nom', $sortOrder);
        }

        $deputes = $query->paginate(30)->withQueryString();
        
        // Récupérer les groupes parlementaires pour les filtres et l'hémicycle
        $groupes = GroupeParlementaire::where('source', 'assemblee')
            ->where('actif', true)
            ->get(['sigle', 'nom', 'couleur_hex', 'position_politique'])
            ->map(fn($g) => [
                'sigle' => $g->sigle,
                'nom' => $g->nom,
                'couleur_hex' => $g->couleur_hex,
                'position_politique' => $g->position_politique,
            ]);

        return Inertia::render('Representants/Deputes/Index', [
            'deputes' => $deputes,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'department', 'sort', 'order']),
        ]);
    }

    /**
     * Fiche détaillée d'un député
     */
    public function showDepute(DeputeSenateur $depute): Response
    {
        $depute->load(['groupeParlementaire']);

        return Inertia::render('Representants/Deputes/Show', [
            'depute' => [
                'id' => $depute->id,
                'nom_complet' => $depute->nom_complet,
                'civilite' => $depute->civilite,
                'prenom' => $depute->prenom,
                'nom' => $depute->nom,
                'photo_url' => $depute->photo_url,
                'age' => $depute->age,
                'profession' => $depute->profession,
                'circonscription' => $depute->circonscription,
                'groupe' => [
                    'id' => $depute->groupeParlementaire?->id,
                    'nom' => $depute->groupe_politique,
                    'sigle' => $depute->groupe_sigle,
                    'couleur' => $depute->groupeParlementaire?->couleur_hex ?? '#6B7280',
                    'position' => $depute->groupeParlementaire?->position_politique,
                ],
                'mandat' => [
                    'debut' => $depute->debut_mandat?->format('d/m/Y'),
                    'fin' => $depute->fin_mandat?->format('d/m/Y'),
                    'legislature' => $depute->legislature,
                ],
                'statistiques' => [
                    'nb_propositions' => $depute->nb_propositions,
                    'nb_amendements' => $depute->nb_amendements,
                    'taux_presence' => $depute->taux_presence,
                ],
                'fonctions' => $depute->fonctions,
                'commissions' => $depute->commissions,
                'url_profil' => $depute->url_profil,
            ],
        ]);
    }

    /* ========================================
     * DEPRECATED METHODS - DO NOT USE
     * These methods have been moved to RepresentantANController
     * and updated to use the new Senateur model with SQL views
     * ======================================== */

    /**
     * @deprecated Moved to RepresentantANController::senateurs()
     * Liste complète des sénateurs
     */
    /*
    public function senateurs(Request $request): Response
    {
        // This method is obsolete. Use RepresentantANController::senateurs() instead.
    }
    */

    /**
     * @deprecated Moved to RepresentantANController::showSenateur()
     * Fiche détaillée d'un sénateur
     */
    /*
    public function showSenateur(DeputeSenateur $senateur): Response
    {
        // This method is obsolete. Use RepresentantANController::showSenateur() instead.
    }
    */

    /**
     * Vue par régions
     */
    public function regions(Request $request): Response
    {
        $selectedRegionCode = $request->input('region');
        
        // Toutes les régions
        $regions = \App\Models\TerritoryRegion::orderBy('name')->get(['id', 'code', 'name']);

        // Compter députés et sénateurs par région
        $deputesByRegion = [];
        $senateursByRegion = [];

        foreach ($regions as $region) {
            // Départements de cette région
            $departments = \App\Models\TerritoryDepartment::where('region_id', $region->id)
                ->pluck('code')
                ->toArray();

            // Compter les députés par région
            // Note: La table deputes_senateurs n'existe plus, on met 0 pour l'instant
            // TODO: Implémenter le comptage via ActeurAN quand les données de circonscription seront disponibles
            $deputesByRegion[$region->code] = 0;

            // Compter les sénateurs (via département)
            $senateursByRegion[$region->code] = \App\Models\Senateur::actifs()
                ->where(function($q) use ($departments) {
                    foreach ($departments as $deptCode) {
                        $q->orWhere('departement_code', $deptCode);
                    }
                })
                ->count();
        }

        $data = [
            'regions' => $regions,
            'deputesByRegion' => $deputesByRegion,
            'senateursByRegion' => $senateursByRegion,
            'selectedRegion' => null,
            'deputes' => [],
            'senateurs' => [],
        ];

        // Si une région est sélectionnée
        if ($selectedRegionCode) {
            $selectedRegion = \App\Models\TerritoryRegion::where('code', $selectedRegionCode)->first();

            if ($selectedRegion) {
                $data['selectedRegion'] = $selectedRegion;

                // Départements de la région
                $departments = \App\Models\TerritoryDepartment::where('region_id', $selectedRegion->id)
                    ->pluck('code')
                    ->toArray();

                // Députés de la région
                // Note: La table deputes_senateurs n'existe plus
                // TODO: Implémenter via ActeurAN quand les données de circonscription seront disponibles
                $data['deputes'] = [];

                // Sénateurs de la région
                $senateurs = \App\Models\Senateur::actifs()
                    ->where(function($q) use ($departments) {
                        foreach ($departments as $deptCode) {
                            $q->orWhere('departement_code', $deptCode);
                        }
                    })
                    ->orderBy('nom')
                    ->get();

                $data['senateurs'] = $senateurs->map(function($s) use ($groupeService) {
                    $groupe = $s->groupeParlementaireActuel;
                    
                    return [
                        'matricule' => $s->matricule,
                        'nom_complet' => $s->prenom . ' ' . $s->nom,
                        'photo_url' => $s->photo_url,
                        'departement' => $s->departement_code,
                        'groupe' => $groupe ? [
                            'sigle' => $groupe->sigle,
                            'nom' => $groupe->libelle,
                            'couleur' => $groupeService->getCouleurGroupe($groupe->sigle),
                        ] : null,
                    ];
                })->toArray();
            }
        }

        return Inertia::render('Representants/Regions', $data);
    }
}

