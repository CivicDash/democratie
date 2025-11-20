<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\OrganeAN;
use App\Models\VoteIndividuelAN;
use App\Models\AmendementAN;
use App\Services\GroupeParlementaireService;
use App\Services\DisciplineGroupeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RepresentantANController extends Controller
{
    /**
     * Liste complète des députés (nouvelle version avec ActeurAN)
     */
    public function deputes(Request $request): Response
    {
        $groupeService = app(GroupeParlementaireService::class);
        
        $query = ActeurAN::query()
            ->with(['mandats' => function($query) {
                $query->where('type_organe', 'ASSEMBLEE')
                      ->whereNull('date_fin')
                      ->with('organe');
            }]);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'ILIKE', "%{$search}%")
                  ->orWhere('prenom', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('groupe')) {
            $query->whereHas('mandats', function($q) use ($request) {
                $q->where('type_organe', 'GP')
                  ->whereHas('organe', function($oq) use ($request) {
                      $oq->where('libelle_abrege', $request->groupe);
                  });
            });
        }

        // Tri
        $sortBy = $request->get('sort', 'nom');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $deputes = $query->paginate(30)->withQueryString();

        // Transformer les données pour la vue
        $deputesData = $deputes->through(function($acteur) use ($groupeService) {
            $groupeActuel = $acteur->groupe_politique_actuel;
            
            return [
                'uid' => $acteur->uid,
                'nom_complet' => $acteur->nom_complet,
                'civilite' => $acteur->civilite,
                'prenom' => $acteur->prenom,
                'nom' => $acteur->nom,
                'trigramme' => $acteur->trigramme,
                'photo_url' => $acteur->photo_wikipedia_url,
                'profession' => $acteur->profession,
                'groupe' => $groupeActuel ? [
                    'uid' => $groupeActuel->uid,
                    'nom' => $groupeActuel->libelle,
                    'sigle' => $groupeActuel->libelle_abrege,
                    'couleur' => $groupeService->getCouleurGroupe($groupeActuel->libelle_abrege),
                ] : null,
                'wikipedia_url' => $acteur->wikipedia_url,
                'url_hatvp' => $acteur->url_hatvp,
            ];
        });

        // Récupérer les groupes pour les filtres
        $groupes = OrganeAN::groupesPolitiques()
            ->where('legislature', 17)
            ->actifs()
            ->get(['uid', 'libelle', 'libelle_abrege'])
            ->map(fn($g) => [
                'uid' => $g->uid,
                'sigle' => $g->libelle_abrege,
                'nom' => $g->libelle,
                'couleur' => $groupeService->getCouleurGroupe($g->libelle_abrege),
            ]);

        return Inertia::render('Representants/Deputes/Index', [
            'deputes' => $deputesData,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'sort', 'order']),
        ]);
    }

    /**
     * Fiche détaillée d'un député (nouvelle version)
     */
    public function showDepute(string $uid): Response
    {
        $groupeService = app(GroupeParlementaireService::class);
        
        $acteur = ActeurAN::with([
            'mandats' => function($query) {
                $query->orderBy('date_debut', 'desc')->with('organe');
            }
        ])->findOrFail($uid);

        $groupeActuel = $acteur->groupe_politique_actuel;
        $commissionsActuelles = $acteur->commissions_actuelles->filter();

        // Statistiques d'activité (L17)
        $stats = [
            'votes_total' => $acteur->votesIndividuels()
                ->whereHas('scrutin', fn($q) => $q->where('legislature', 17))
                ->count(),
            'amendements_total' => $acteur->amendementsAuteur()
                ->where('legislature', 17)
                ->count(),
            'amendements_adoptes' => $acteur->amendementsAuteur()
                ->where('legislature', 17)
                ->adoptes()
                ->count(),
        ];

        $stats['taux_adoption_amendements'] = $stats['amendements_total'] > 0
            ? round(($stats['amendements_adoptes'] / $stats['amendements_total']) * 100, 1)
            : 0;

        return Inertia::render('Representants/Deputes/Show', [
            'depute' => [
                'uid' => $acteur->uid,
                'nom_complet' => $acteur->nom_complet,
                'civilite' => $acteur->civilite,
                'prenom' => $acteur->prenom,
                'nom' => $acteur->nom,
                'trigramme' => $acteur->trigramme,
                'photo_url' => $acteur->photo_wikipedia_url,
                'date_naissance' => $acteur->date_naissance?->format('d/m/Y'),
                'age' => $acteur->date_naissance ? $acteur->date_naissance->age : null,
                'lieu_naissance' => trim("{$acteur->ville_naissance} {$acteur->departement_naissance}"),
                'profession' => $acteur->profession,
                'categorie_socio_pro' => $acteur->categorie_socio_pro,
                'groupe' => $groupeActuel ? [
                    'uid' => $groupeActuel->uid,
                    'nom' => $groupeActuel->libelle,
                    'sigle' => $groupeActuel->libelle_abrege,
                    'couleur' => $groupeService->getCouleurGroupe($groupeActuel->libelle_abrege),
                ] : null,
                'commissions' => $commissionsActuelles->map(fn($c) => [
                    'uid' => $c->uid,
                    'nom' => $c->libelle,
                    'sigle' => $c->libelle_abrege,
                ])->toArray(),
                'mandats' => $acteur->mandats->map(fn($m) => [
                    'uid' => $m->uid,
                    'type' => $m->type_organe,
                    'organe' => $m->organe ? [
                        'uid' => $m->organe->uid,
                        'nom' => $m->organe->libelle,
                        'sigle' => $m->organe->libelle_abrege,
                    ] : null,
                    'date_debut' => $m->date_debut?->format('d/m/Y'),
                    'date_fin' => $m->date_fin?->format('d/m/Y'),
                    'actif' => is_null($m->date_fin),
                ])->toArray(),
                'statistiques' => $stats,
                'wikipedia' => [
                    'url' => $acteur->wikipedia_url,
                    'photo_url' => $acteur->photo_wikipedia_url,
                    'extract' => $acteur->wikipedia_extract,
                ],
                'url_hatvp' => $acteur->url_hatvp,
                'reseaux_sociaux' => [
                    'twitter' => $acteur->twitter_url,
                    'facebook' => $acteur->facebook_url,
                    'linkedin' => $acteur->linkedin_url,
                    'instagram' => $acteur->instagram_url,
                ],
                'adresses' => $acteur->adresses,
            ],
        ]);
    }

    /**
     * Page votes d'un député
     */
    public function deputeVotes(Request $request, string $uid): Response
    {
        $acteur = ActeurAN::findOrFail($uid);

        $query = VoteIndividuelAN::query()
            ->where('acteur_ref', $uid)
            ->with(['scrutin' => function($q) {
                $q->where('legislature', 17);
            }])
            ->whereHas('scrutin', fn($q) => $q->where('legislature', 17));

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('scrutin', function($q) use ($search) {
                $q->where('titre', 'ILIKE', "%{$search}%")
                  ->orWhere('objet', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('position', $request->type);
        }

        $votes = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        // Statistiques
        $statsQuery = VoteIndividuelAN::where('acteur_ref', $uid)
            ->whereHas('scrutin', fn($q) => $q->where('legislature', 17));
        
        $total = $statsQuery->count();
        $pour = $statsQuery->clone()->where('position', 'pour')->count();
        $contre = $statsQuery->clone()->where('position', 'contre')->count();
        $abstention = $statsQuery->clone()->where('position', 'abstention')->count();

        $statistiques = [
            'total' => $total,
            'pour' => $pour,
            'contre' => $contre,
            'abstention' => $abstention,
            'pour_percent' => $total > 0 ? round(($pour / $total) * 100, 1) : 0,
            'contre_percent' => $total > 0 ? round(($contre / $total) * 100, 1) : 0,
            'abstention_percent' => $total > 0 ? round(($abstention / $total) * 100, 1) : 0,
        ];

        // Transformer les votes
        $votesData = $votes->through(function($vote) {
            return [
                'id' => $vote->id,
                'position' => $vote->position,
                'date' => $vote->scrutin->date_scrutin?->format('d/m/Y'),
                'scrutin' => [
                    'uid' => $vote->scrutin->uid,
                    'titre' => $vote->scrutin->titre,
                    'objet' => $vote->scrutin->objet,
                    'pour' => $vote->scrutin->nombre_pour,
                    'contre' => $vote->scrutin->nombre_contre,
                    'abstention' => $vote->scrutin->nombre_abstention,
                ],
            ];
        });

        return Inertia::render('Representants/Deputes/Votes', [
            'depute' => $this->formatDeputeBasic($acteur),
            'votes' => $votesData,
            'statistiques' => $statistiques,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Page amendements d'un député
     */
    public function deputeAmendements(Request $request, string $uid): Response
    {
        $acteur = ActeurAN::findOrFail($uid);

        $query = AmendementAN::query()
            ->where('auteur_acteur_ref', $uid)
            ->with(['dossier', 'texte']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('dispositif', 'ILIKE', "%{$search}%")
                  ->orWhereHas('dossier', fn($dq) => $dq->where('titre_court', 'ILIKE', "%{$search}%"))
                  ->orWhereHas('texte', fn($tq) => $tq->where('titre_court', 'ILIKE', "%{$search}%"));
            });
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'adopte':
                    $query->adoptes();
                    break;
                case 'rejete':
                    $query->rejetes();
                    break;
                case 'retire':
                    $query->retires();
                    break;
                case 'recent':
                    $query->orderBy('date_depot', 'desc');
                    break;
            }
        } else {
            $query->orderBy('date_depot', 'desc');
        }

        $amendements = $query->paginate(30)->withQueryString();

        // Statistiques
        $statsQuery = AmendementAN::where('auteur_acteur_ref', $uid);
        $total = $statsQuery->count();
        $adoptes = $statsQuery->clone()->adoptes()->count();
        $rejetes = $statsQuery->clone()->rejetes()->count();
        $retires = $statsQuery->clone()->retires()->count();

        $statistiques = [
            'total' => $total,
            'adoptes' => $adoptes,
            'rejetes' => $rejetes,
            'retires' => $retires,
            'taux_adoption' => $total > 0 ? round(($adoptes / $total) * 100, 1) : 0,
        ];

        // Transformer les amendements
        $amendementsData = $amendements->through(function($amendement) {
            return [
                'uid' => $amendement->uid,
                'numero' => $amendement->numero,
                'sort' => $amendement->sort,
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'dispositif' => $amendement->dispositif,
                'co_signataires' => $amendement->co_signataires ? count($amendement->co_signataires) : 0,
                'dossier' => $amendement->dossier ? [
                    'uid' => $amendement->dossier->uid,
                    'titre_court' => $amendement->dossier->titre_court,
                ] : null,
                'texte' => $amendement->texte ? [
                    'uid' => $amendement->texte->uid,
                    'titre_court' => $amendement->texte->titre_court,
                ] : null,
            ];
        });

        return Inertia::render('Representants/Deputes/Amendements', [
            'depute' => $this->formatDeputeBasic($acteur),
            'amendements' => $amendementsData,
            'statistiques' => $statistiques,
            'filters' => $request->only(['search', 'sort']),
        ]);
    }

    /**
     * Page activité d'un député avec graphiques
     */
    public function deputeActivite(string $uid): Response
    {
        $groupeService = app(GroupeParlementaireService::class);
        $disciplineService = app(DisciplineGroupeService::class);
        
        $acteur = ActeurAN::with(['mandats.organe'])->findOrFail($uid);

        // Statistiques globales votes
        $votesQuery = VoteIndividuelAN::where('acteur_ref', $uid)
            ->whereHas('scrutin', fn($q) => $q->where('legislature', 17));
        
        $votesTotal = $votesQuery->count();
        $votesPour = $votesQuery->clone()->where('position', 'pour')->count();
        $votesContre = $votesQuery->clone()->where('position', 'contre')->count();
        $votesAbstention = $votesQuery->clone()->where('position', 'abstention')->count();

        // Statistiques amendements
        $amendementsQuery = AmendementAN::where('auteur_acteur_ref', $uid);
        $amendementsTotal = $amendementsQuery->count();
        $amendementsAdoptes = $amendementsQuery->clone()->adoptes()->count();
        $amendementsRejetes = $amendementsQuery->clone()->rejetes()->count();

        // Discipline de groupe (CALCUL RÉEL)
        $disciplineGroupe = $disciplineService->calculateDiscipline($acteur, 17);

        $statistiques = [
            'votes' => [
                'total' => $votesTotal,
                'pour' => $votesPour,
                'contre' => $votesContre,
                'abstention' => $votesAbstention,
            ],
            'amendements' => [
                'total' => $amendementsTotal,
                'adoptes' => $amendementsAdoptes,
                'rejetes' => $amendementsRejetes,
                'taux_adoption' => $amendementsTotal > 0 
                    ? round(($amendementsAdoptes / $amendementsTotal) * 100, 1) 
                    : 0,
            ],
            'discipline_groupe' => $disciplineGroupe,
        ];

        // Activité mensuelle (12 derniers mois)
        $activiteMensuelle = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mois = $date->format('Y-m');
            
            $votesCount = VoteIndividuelAN::where('acteur_ref', $uid)
                ->whereHas('scrutin', function($q) use ($date) {
                    $q->whereYear('date_scrutin', $date->year)
                      ->whereMonth('date_scrutin', $date->month);
                })
                ->count();
            
            $amendementsCount = AmendementAN::where('auteur_acteur_ref', $uid)
                ->whereYear('date_depot', $date->year)
                ->whereMonth('date_depot', $date->month)
                ->count();
            
            $activiteMensuelle[] = [
                'mois' => $mois,
                'label' => $date->format('M Y'),
                'votes' => $votesCount,
                'amendements' => $amendementsCount,
                'total' => $votesCount + $amendementsCount,
            ];
        }

        // Derniers votes (5)
        $derniersVotes = VoteIndividuelAN::where('acteur_ref', $uid)
            ->whereHas('scrutin', fn($q) => $q->where('legislature', 17))
            ->with('scrutin')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($vote) => [
                'id' => $vote->id,
                'position' => $vote->position,
                'date' => $vote->scrutin->date_scrutin?->format('d/m/Y'),
                'scrutin' => [
                    'uid' => $vote->scrutin->uid,
                    'titre' => $vote->scrutin->titre,
                ],
            ]);

        // Derniers amendements (5)
        $derniersAmendements = AmendementAN::where('auteur_acteur_ref', $uid)
            ->with(['dossier', 'texte'])
            ->orderBy('date_depot', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($amendement) => [
                'uid' => $amendement->uid,
                'numero' => $amendement->numero,
                'sort' => $amendement->sort,
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'dossier' => $amendement->dossier ? [
                    'uid' => $amendement->dossier->uid,
                    'titre_court' => $amendement->dossier->titre_court,
                ] : null,
                'texte' => $amendement->texte ? [
                    'uid' => $amendement->texte->uid,
                    'titre_court' => $amendement->texte->titre_court,
                ] : null,
            ]);

        return Inertia::render('Representants/Deputes/Activite', [
            'depute' => $this->formatDeputeBasic($acteur),
            'statistiques' => $statistiques,
            'activite_mensuelle' => $activiteMensuelle,
            'derniers_votes' => $derniersVotes,
            'derniers_amendements' => $derniersAmendements,
        ]);
    }

    /**
     * Helper: Format minimal d'un député pour les sous-pages
     */
    private function formatDeputeBasic(ActeurAN $acteur): array
    {
        $groupeService = app(GroupeParlementaireService::class);
        $groupeActuel = $acteur->groupe_politique_actuel;
        
        return [
            'uid' => $acteur->uid,
            'nom_complet' => $acteur->nom_complet,
            'nom' => $acteur->nom,
            'prenom' => $acteur->prenom,
            'photo_url' => $acteur->photo_wikipedia_url,
            'groupe' => $groupeActuel ? [
                'uid' => $groupeActuel->uid,
                'nom' => $groupeActuel->libelle,
                'sigle' => $groupeActuel->libelle_abrege,
                'couleur' => $groupeService->getCouleurGroupe($groupeActuel->libelle_abrege),
            ] : null,
        ];
    }

    /**
     * Liste complète des sénateurs (nouvelle version avec Senateur)
     */
    public function senateurs(Request $request): Response
    {
        $query = Senateur::query()
            ->with(['commissions', 'historiqueGroupes']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom_usuel', 'ILIKE', "%{$search}%")
                  ->orWhere('prenom_usuel', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('actifs_only')) {
            $query->actifs();
        } else {
            // Par défaut, afficher uniquement les actifs
            $query->where('etat', 'ACTIF');
        }

        if ($request->filled('groupe')) {
            $query->where('groupe_politique', $request->groupe);
        }

        if ($request->filled('circonscription')) {
            $query->where('circonscription', 'ILIKE', "%{$request->circonscription}%");
        }

        // Tri
        $sortBy = $request->get('sort', 'nom_usuel');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $senateurs = $query->paginate(30)->withQueryString();

        // Transformer les données
        $senateursData = $senateurs->through(function($senateur) {
            return [
                'matricule' => $senateur->matricule,
                'nom_complet' => trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}"),
                'civilite' => $senateur->civilite,
                'prenom' => $senateur->prenom_usuel,
                'nom' => $senateur->nom_usuel,
                'photo_url' => $senateur->photo_url,
                'profession' => $senateur->profession,
                'circonscription' => $senateur->circonscription,
                'groupe' => [
                    'nom' => $senateur->groupe_politique,
                    'couleur' => '#6B7280',
                ],
                'commission' => $senateur->commission_permanente,
                'etat' => $senateur->etat,
            ];
        });

        // Récupérer les groupes uniques pour les filtres
        $groupes = Senateur::actifs()
            ->whereNotNull('groupe_politique')
            ->select('groupe_politique')
            ->distinct()
            ->orderBy('groupe_politique')
            ->pluck('groupe_politique')
            ->map(fn($g) => ['nom' => $g]);

        return Inertia::render('Representants/Senateurs/Index', [
            'senateurs' => $senateursData,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'circonscription', 'sort', 'order']),
        ]);
    }

    /**
     * Fiche détaillée d'un sénateur (nouvelle version)
     */
    public function showSenateur(string $matricule): Response
    {
        $senateur = Senateur::with([
            'commissions',
            'mandats' => function($query) {
                $query->orderBy('date_debut', 'desc');
            },
            'historiqueGroupes' => function($query) {
                $query->orderBy('date_debut', 'desc');
            },
        ])->findOrFail($matricule);

        return Inertia::render('Representants/Senateurs/Show', [
            'senateur' => [
                'matricule' => $senateur->matricule,
                'nom_complet' => trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}"),
                'civilite' => $senateur->civilite,
                'prenom' => $senateur->prenom_usuel,
                'nom' => $senateur->nom_usuel,
                'photo_url' => $senateur->photo_url,
                'date_naissance' => $senateur->date_naissance?->format('d/m/Y'),
                'age' => $senateur->date_naissance ? now()->diffInYears($senateur->date_naissance) : null,
                'lieu_naissance' => $senateur->lieu_naissance,
                'profession' => $senateur->profession,
                'circonscription' => $senateur->circonscription,
                'etat' => $senateur->etat,
                'groupe' => [
                    'nom' => $senateur->groupe_politique,
                    'couleur' => '#6B7280',
                ],
                'commission' => $senateur->commission_permanente,
                'commissions' => $senateur->commissions->map(fn($c) => [
                    'commission' => $c->commission,
                    'date_debut' => $c->date_debut?->format('d/m/Y'),
                    'date_fin' => $c->date_fin?->format('d/m/Y'),
                    'fonction' => $c->fonction,
                    'actif' => is_null($c->date_fin),
                ])->toArray(),
                'mandats' => $senateur->mandats->map(fn($m) => [
                    'type' => $m->type_mandat,
                    'circonscription' => $m->circonscription,
                    'date_debut' => $m->date_debut?->format('d/m/Y'),
                    'date_fin' => $m->date_fin?->format('d/m/Y'),
                    'numero' => $m->numero_mandat,
                    'actif' => is_null($m->date_fin),
                ])->toArray(),
                'historique_groupes' => $senateur->historiqueGroupes->map(fn($g) => [
                    'groupe' => $g->groupe_politique,
                    'date_debut' => $g->date_debut?->format('d/m/Y'),
                    'date_fin' => $g->date_fin?->format('d/m/Y'),
                ])->toArray(),
                'email' => $senateur->email,
                'telephone' => $senateur->telephone,
                'adresse_postale' => $senateur->adresse_postale,
            ],
        ]);
    }
}

