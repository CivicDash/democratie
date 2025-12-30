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

                // Député de la circonscription
                // Format: 75-01 => département 75, circonscription 01
                $data['depute'] = $this->findDeputeByCirconscription($postalData->circonscription, $groupeService);

                // Sénateurs du département (via modèle Senateur)
                $senateurs = Senateur::actifs()
                    ->where(function($q) use ($deptCode, $postalData) {
                        $q->where('departement_code', $deptCode)
                          ->orWhere('circonscription', 'ILIKE', '%' . $postalData->department_name . '%');
                    })
                    ->get();

                $data['senateurs'] = $senateurs->map(function($senateur) use ($groupeService) {
                    return [
                        'id' => $senateur->matricule,
                        'nom_complet' => trim($senateur->prenom_usuel . ' ' . $senateur->nom_usuel),
                        'photo_url' => $senateur->photo_url,
                        'profession' => $senateur->description_profession,
                        'circonscription' => $senateur->circonscription,
                        'groupe' => $senateur->groupe_politique ? [
                            'sigle' => $senateur->groupe_politique,
                            'nom' => $senateur->groupe_politique,
                            'couleur' => $groupeService->getCouleurGroupe($senateur->groupe_politique),
                        ] : null,
                        'nb_amendements' => \App\Models\AmendementSenat::where('senateur_matricule', $senateur->matricule)->count(),
                        'nb_votes' => \App\Models\VoteSenat::where('senateur_matricule', $senateur->matricule)->count(),
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

            // Député de la circonscription
            $data['depute'] = $this->findDeputeByCirconscription($profile->circonscription, $groupeService);

            // Sénateurs du département (via modèle Senateur)
            $senateurs = Senateur::actifs()
                ->where(function($q) use ($deptCode, $profile) {
                    $q->where('departement_code', $deptCode)
                      ->orWhere('circonscription', 'ILIKE', '%' . ($profile->department?->name ?? '') . '%');
                })
                ->get();

            $data['senateurs'] = $senateurs->map(function($senateur) use ($groupeService) {
                return [
                    'id' => $senateur->matricule,
                    'nom_complet' => trim($senateur->prenom_usuel . ' ' . $senateur->nom_usuel),
                    'photo_url' => $senateur->photo_url,
                    'profession' => $senateur->description_profession,
                    'circonscription' => $senateur->circonscription,
                    'groupe' => $senateur->groupe_politique ? [
                        'sigle' => $senateur->groupe_politique,
                        'nom' => $senateur->groupe_politique,
                        'couleur' => $groupeService->getCouleurGroupe($senateur->groupe_politique),
                    ] : null,
                    'nb_amendements' => \App\Models\AmendementSenat::where('senateur_matricule', $senateur->matricule)->count(),
                    'nb_votes' => \App\Models\VoteSenat::where('senateur_matricule', $senateur->matricule)->count(),
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

        // Données régionales pour la carte interactive
        $regions = \App\Models\TerritoryRegion::orderBy('name')->get(['id', 'code', 'name']);
        $deputesByRegion = [];
        $senateursByRegion = [];

        foreach ($regions as $region) {
            // Départements de cette région
            $departments = \App\Models\TerritoryDepartment::where('region_id', $region->id)
                ->pluck('code')
                ->toArray();

            // Compter les députés par région (TODO: via ActeurAN)
            $deputesByRegion[$region->code] = 0;

            // Compter les sénateurs par région
            $senateursByRegion[$region->code] = Senateur::actifs()
                ->where(function($q) use ($departments) {
                    foreach ($departments as $deptCode) {
                        $q->orWhere('departement_code', $deptCode);
                    }
                })
                ->count();
        }

        $data['regions'] = $regions->map(fn($r) => ['code' => $r->code, 'name' => $r->name]);
        $data['deputesByRegion'] = $deputesByRegion;
        $data['senateursByRegion'] = $senateursByRegion;

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

    /**
     * Trouver le député d'une circonscription
     * Format circonscription: "75-01" (département-numéro)
     */
    private function findDeputeByCirconscription(?string $circonscription, GroupeParlementaireService $groupeService): ?array
    {
        if (!$circonscription) {
            return null;
        }

        // Parser la circonscription: "75-01" => département 75, numéro 01
        $parts = explode('-', $circonscription);
        if (count($parts) !== 2) {
            return null;
        }

        $deptCode = $parts[0];
        $numCirco = intval($parts[1]);

        // Récupérer le nom du département
        $postalCode = \App\Models\FrenchPostalCode::where('postal_code', 'LIKE', $deptCode . '%')->first();
        $deptName = $postalCode->department_name ?? '';

        // Construire le pattern de recherche pour le libellé de circonscription
        // Ex: "1ère circonscription de Paris" ou "2ème circonscription de Paris"
        if ($numCirco === 1) {
            $ordinal = '1%re';
        } else {
            $ordinal = $numCirco . '%me';
        }

        // Chercher l'organe CIRCONSCRIPTION correspondant
        $circoOrgane = \App\Models\OrganeAN::where('code_type', 'CIRCONSCRIPTION')
            ->where('libelle', 'ILIKE', '%' . $deptName . '%')
            ->where('libelle', 'LIKE', $ordinal . '%circonscription%')
            ->first();

        if (!$circoOrgane) {
            // Fallback: message informatif
            return [
                'not_found' => true,
                'message' => "Député de la {$numCirco}" . ($numCirco === 1 ? 'ère' : 'ème') . " circonscription de {$deptName}",
                'circonscription' => $circonscription,
            ];
        }

        // Chercher le mandat actif pour cette circonscription
        $mandat = \App\Models\MandatAN::where('organe_ref', $circoOrgane->uid)
            ->whereNull('date_fin')
            ->first();

        if (!$mandat) {
            return [
                'not_found' => true,
                'message' => "Député de la {$numCirco}" . ($numCirco === 1 ? 'ère' : 'ème') . " circonscription de {$deptName}",
                'circonscription' => $circonscription,
            ];
        }

        // Récupérer l'acteur
        $acteur = \App\Models\ActeurAN::find($mandat->acteur_ref);
        if (!$acteur) {
            return null;
        }

        $groupeActuel = $acteur->groupe_politique_actuel;

        return [
            'id' => $acteur->uid,
            'nom_complet' => $acteur->nom_complet,
            'photo_url' => $acteur->photo_url,
            'profession' => $acteur->profession,
            'circonscription' => $circoOrgane->libelle,
            'groupe' => $groupeActuel ? [
                'sigle' => $groupeActuel->libelle_abrege,
                'nom' => $groupeActuel->libelle,
                'couleur' => $groupeService->getCouleurGroupe($groupeActuel->libelle_abrege),
            ] : null,
            'nb_amendements' => $acteur->amendementsAuteur()->where('legislature', 17)->count(),
            'nb_votes' => $acteur->votesIndividuels()->whereHas('scrutin', fn($q) => $q->where('legislature', 17))->count(),
            'url_profil' => route('representants.deputes.show', $acteur->uid),
        ];
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
     * @deprecated La page régions a été fusionnée dans mesRepresentants avec la carte interactive
     */
}

