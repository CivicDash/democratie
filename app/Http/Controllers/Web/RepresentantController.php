<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeputeSenateur;
use App\Models\GroupeParlementaire;
use App\Models\Profile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RepresentantController extends Controller
{
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

        // Mode simulation via paramètre GET
        $simulatePostalCode = $request->input('simulate_postal_code');
        
        if ($simulatePostalCode) {
            $postalData = \App\Models\FrenchPostalCode::where('postal_code', $simulatePostalCode)->first();
            
            if ($postalData) {
                $data['hasLocation'] = true;
                $data['location'] = [
                    'city' => $postalData->city_name,
                    'postal_code' => $postalData->postal_code,
                    'circonscription' => $postalData->circonscription,
                    'department' => $postalData->department_name,
                    'is_simulated' => true,
                ];

                // Député de la circonscription
                $depute = DeputeSenateur::deputes()
                    ->enExercice()
                    ->where('circonscription', $postalData->circonscription)
                    ->with(['groupeParlementaire'])
                    ->first();

                if ($depute) {
                    $data['depute'] = [
                        'id' => $depute->id,
                        'nom_complet' => $depute->nom_complet,
                        'photo_url' => $depute->photo_url,
                        'circonscription' => $depute->circonscription,
                        'profession' => $depute->profession,
                        'url_profil' => $depute->url_profil,
                        'groupe' => $depute->groupeParlementaire ? [
                            'sigle' => $depute->groupeParlementaire->sigle,
                            'nom' => $depute->groupeParlementaire->nom,
                            'couleur' => $depute->groupeParlementaire->couleur_hex,
                        ] : null,
                        'nb_propositions' => $depute->nb_propositions ?? 0,
                        'nb_amendements' => $depute->nb_amendements ?? 0,
                        'taux_presence' => $depute->taux_presence ?? 0,
                    ];
                }

                // Sénateurs du département
                $deptCode = substr($postalData->circonscription, 0, 2);
                $senateurs = DeputeSenateur::senateurs()
                    ->enExercice()
                    ->where('circonscription', 'like', $deptCode . '%')
                    ->with(['groupeParlementaire'])
                    ->get();

                $data['senateurs'] = $senateurs->map(function($senateur) {
                    return [
                        'id' => $senateur->id,
                        'nom_complet' => $senateur->nom_complet,
                        'photo_url' => $senateur->photo_url,
                        'profession' => $senateur->profession,
                        'groupe' => $senateur->groupeParlementaire ? [
                            'sigle' => $senateur->groupeParlementaire->sigle,
                            'nom' => $senateur->groupeParlementaire->nom,
                            'couleur' => $senateur->groupeParlementaire->couleur_hex,
                        ] : null,
                        'nb_propositions' => $senateur->nb_propositions ?? 0,
                        'nb_amendements' => $senateur->nb_amendements ?? 0,
                        'taux_presence' => $senateur->taux_presence ?? 0,
                    ];
                })->toArray();
            }
        } elseif ($profile && $profile->circonscription && $profile->department_id) {
            $data['hasLocation'] = true;
            $data['location'] = [
                'city' => $profile->city_name,
                'postal_code' => $profile->postal_code,
                'circonscription' => $profile->circonscription,
                'department' => $profile->department?->name,
            ];

            // Député de la circonscription
            $depute = DeputeSenateur::deputes()
                ->enExercice()
                ->where('circonscription', $profile->circonscription)
                ->with(['groupeParlementaire'])
                ->first();

            if ($depute) {
                $data['depute'] = [
                    'id' => $depute->id,
                    'nom_complet' => $depute->nom_complet,
                    'photo_url' => $depute->photo_url,
                    'groupe' => [
                        'nom' => $depute->groupe_politique,
                        'sigle' => $depute->groupe_sigle,
                        'couleur' => $depute->groupeParlementaire?->couleur_hex ?? '#6B7280',
                    ],
                    'circonscription' => $depute->circonscription,
                    'profession' => $depute->profession,
                    'nb_propositions' => $depute->nb_propositions,
                    'nb_amendements' => $depute->nb_amendements,
                    'taux_presence' => $depute->taux_presence,
                    'url_profil' => $depute->url_profil,
                ];
            }

            // Sénateurs du département
            $senateurs = DeputeSenateur::senateurs()
                ->enExercice()
                ->where('circonscription', 'like', substr($profile->circonscription, 0, 2) . '%')
                ->with(['groupeParlementaire'])
                ->get();

            $data['senateurs'] = $senateurs->map(fn($senateur) => [
                'id' => $senateur->id,
                'nom_complet' => $senateur->nom_complet,
                'photo_url' => $senateur->photo_url,
                'groupe' => [
                    'nom' => $senateur->groupe_politique,
                    'sigle' => $senateur->groupe_sigle,
                    'couleur' => $senateur->groupeParlementaire?->couleur_hex ?? '#6B7280',
                ],
                'profession' => $senateur->profession,
                'nb_propositions' => $senateur->nb_propositions,
                'nb_amendements' => $senateur->nb_amendements,
                'taux_presence' => $senateur->taux_presence,
                'url_profil' => $senateur->url_profil,
            ])->toArray();
        }

        // Répartition nationale des députés et sénateurs par département
        $data['deputesByDepartment'] = DeputeSenateur::deputes()
            ->enExercice()
            ->selectRaw('SUBSTRING(circonscription, 1, 2) as department_code, COUNT(*) as count')
            ->groupBy('department_code')
            ->pluck('count', 'department_code')
            ->toArray();

        $data['senateursByDepartment'] = DeputeSenateur::senateurs()
            ->enExercice()
            ->selectRaw('SUBSTRING(circonscription, 1, 2) as department_code, COUNT(*) as count')
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

            // Compter les députés (via circonscriptions dans mandats AN)
            $deputesByRegion[$region->code] = \App\Models\ActeurAN::whereHas('mandatActif', function($q) use ($departments) {
                $q->where('type_organe', 'ASSEMBLEE')
                  ->where(function($sq) use ($departments) {
                      foreach ($departments as $deptCode) {
                          $sq->orWhere('code_departement', $deptCode);
                      }
                  });
            })->count();

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
                $deputes = \App\Models\ActeurAN::whereHas('mandatActif', function($q) use ($departments) {
                    $q->where('type_organe', 'ASSEMBLEE')
                      ->where(function($sq) use ($departments) {
                          foreach ($departments as $deptCode) {
                              $sq->orWhere('code_departement', $deptCode);
                          }
                      });
                })
                ->with(['mandatActif', 'mandatActif.organe'])
                ->orderBy('nom')
                ->get();

                $groupeService = app(\App\Services\GroupeParlementaireService::class);

                $data['deputes'] = $deputes->map(function($d) use ($groupeService) {
                    $mandat = $d->mandatActif;
                    $groupe = $mandat?->organe;
                    
                    return [
                        'uid' => $d->uid,
                        'nom_complet' => $d->prenom . ' ' . $d->nom,
                        'photo_url' => $d->photo_url,
                        'circonscription' => $mandat?->code_departement . '-' . $mandat?->num_circonscription,
                        'groupe' => $groupe ? [
                            'sigle' => $groupe->libelleAbrev,
                            'nom' => $groupe->libelle,
                            'couleur' => $groupeService->getCouleurGroupe($groupe->libelleAbrev),
                        ] : null,
                    ];
                })->toArray();

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

