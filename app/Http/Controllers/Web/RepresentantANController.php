<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\OrganeAN;
use App\Models\VoteIndividuelAN;
use App\Models\AmendementAN;
use App\Models\VoteSenat;
use App\Models\ScrutinSenat;
use App\Models\AmendementSenat;
use App\Models\ParlementaireStats;
use App\Models\QuestionAN;
use App\Services\GroupeParlementaireService;
use App\Services\DisciplineGroupeService;
use App\Models\EluFollower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
                'photo_url' => $acteur->photo_url, // Priorité photo officielle AN
                'profession' => $acteur->profession,
                'circonscription' => $acteur->circonscription_info,
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

        // Statistiques pour le bandeau hero
        $stats = Cache::remember('deputes_index_stats', 3600, function () {
            $totalDeputes = ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))->count();
            $nbGroupes = OrganeAN::groupesPolitiques()->where('legislature', 17)->actifs()->count();
            $femmes = ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))
                ->where('civilite', 'Mme')
                ->count();
            $femmesPercent = $totalDeputes > 0 ? round(($femmes / $totalDeputes) * 100) : 0;
            
            return [
                'total' => $totalDeputes,
                'actifs' => 577,
                'groupes' => $nbGroupes,
                'femmes_pct' => $femmesPercent,
                'age_moyen' => 50, // À calculer dynamiquement si besoin
                'questions_semaine' => \App\Models\QuestionAN::where('date_question', '>=', now()->subWeek())->count(),
            ];
        });

        return Inertia::render('Representants/Deputes/Index', [
            'deputes' => $deputesData,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'sort', 'order']),
            'stats' => $stats,
        ]);
    }

    /**
     * Fiche détaillée d'un député (nouvelle version)
     * Utilise les stats pré-calculées pour de meilleures performances
     */
    public function showDepute(string $uid): Response
    {
        $groupeService = app(GroupeParlementaireService::class);
        $disciplineService = app(DisciplineGroupeService::class);
        
        $acteur = ActeurAN::with([
            'mandats' => function($query) {
                $query->orderBy('date_debut', 'desc')->with('organe');
            }
        ])->findOrFail($uid);

        $groupeActuel = $acteur->groupe_politique_actuel;
        $commissionsActuelles = $acteur->commissions_actuelles->filter();

        // Utiliser les stats pré-calculées (ou fallback sur calcul à la volée)
        $cachedStats = ParlementaireStats::forDepute($uid, 17);
        
        if ($cachedStats && !$cachedStats->isStale()) {
            // Utiliser les stats pré-calculées
            $stats = $cachedStats->toViewArray();
        } else {
            // Fallback: calcul à la volée (pour les premiers chargements)
            $totalScrutinsL17 = \App\Models\ScrutinAN::where('legislature', 17)->count();
            
            $votesTotal = $acteur->votesIndividuels()
                ->whereHas('scrutin', fn($q) => $q->where('legislature', 17))
                ->count();
            
            $amendementsTotal = $acteur->amendementsAuteur()
                ->where('legislature', 17)
                ->count();
            $amendementsAdoptes = $acteur->amendementsAuteur()
                ->where('legislature', 17)
                ->adoptes()
                ->count();
                
            $stats = [
                'votes_total' => $votesTotal,
                'taux_presence' => $totalScrutinsL17 > 0 
                    ? round(($votesTotal / $totalScrutinsL17) * 100, 1) 
                    : 0,
                'amendements_total' => $amendementsTotal,
                'amendements_adoptes' => $amendementsAdoptes,
                'taux_adoption_amendements' => $amendementsTotal > 0
                    ? round(($amendementsAdoptes / $amendementsTotal) * 100, 1)
                    : 0,
                'discipline_groupe' => $disciplineService->calculateDiscipline($acteur, 17),
                'calculated_at' => null, // Indique que c'est un calcul à la volée
            ];
        }

        // Déclarations HATVP avec détail des revenus (cache 1h)
        $hatvpData = Cache::remember("hatvp_depute_{$uid}", 3600, function () use ($uid, $acteur) {
            $declarationsHatvp = [];
            $hatvpSummary = null;
            try {
                $declarations = \App\Models\HatvpDeclaration::with([
                    'mandatsElectifs.remunerations',
                    'activitesProfessionnelles.remunerations',
                    'activitesConsultant.remunerations',
                    'participationsDirigeantes.remunerations',
                    'collaborateurs',
                    'fonctionsBenevoles',
                ])
                ->where('parlementaire_type', 'depute')
                ->where(function($q) use ($uid, $acteur) {
                    $q->where('parlementaire_id', $uid)
                      ->orWhere(function($q2) use ($acteur) {
                          $q2->where('nom', 'ILIKE', $acteur->nom)
                             ->where('prenom', 'ILIKE', $acteur->prenom);
                      });
                })
                ->orderBy('date_depot', 'desc')
                ->get();

            $declarationsHatvp = $declarations->map(fn($d) => [
                'uuid' => $d->uuid,
                'type' => $d->type_declaration,
                'type_label' => $d->type_declaration_label,
                'date_depot' => $d->date_depot?->format('d/m/Y'),
                'url' => "https://www.hatvp.fr/fiche-nominative/?declarant=" . strtolower($acteur->nom) . "-" . strtolower($acteur->prenom),
            ])->toArray();

            // Construire le résumé détaillé depuis la déclaration la plus récente
            $latestDeclaration = $declarations->first();
            if ($latestDeclaration) {
                // Revenus par année (toutes catégories)
                $revenusParAnnee = $latestDeclaration->revenus_par_annee ?? [];
                
                // Mandats électifs avec rémunérations
                $mandatsElectifs = $latestDeclaration->mandatsElectifs->map(fn($m) => [
                    'description' => $m->description_mandat ?? $m->description ?? 'Mandat électif',
                    'date_debut' => $m->date_debut instanceof \Carbon\Carbon ? $m->date_debut->format('d/m/Y') : ($m->date_debut ? \Carbon\Carbon::parse($m->date_debut)->format('d/m/Y') : null),
                    'date_fin' => $m->date_fin instanceof \Carbon\Carbon ? $m->date_fin->format('d/m/Y') : ($m->date_fin ? \Carbon\Carbon::parse($m->date_fin)->format('d/m/Y') : null),
                    'conserve' => $m->conservee,
                    'remunerations' => $m->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->sortByDesc('annee')->values()->toArray(),
                    'total_remunerations' => $m->remunerations->sum('montant'),
                ])->toArray();
                
                // Activités professionnelles avec rémunérations
                $activitesPro = $latestDeclaration->activitesProfessionnelles->map(fn($a) => [
                    'description' => $a->description ?? 'Activité professionnelle',
                    'employeur' => $a->employeur,
                    'date_debut' => $a->date_debut instanceof \Carbon\Carbon ? $a->date_debut->format('d/m/Y') : ($a->date_debut ? \Carbon\Carbon::parse($a->date_debut)->format('d/m/Y') : null),
                    'date_fin' => $a->date_fin instanceof \Carbon\Carbon ? $a->date_fin->format('d/m/Y') : ($a->date_fin ? \Carbon\Carbon::parse($a->date_fin)->format('d/m/Y') : null),
                    'conservee' => $a->conservee,
                    'remunerations' => $a->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->sortByDesc('annee')->values()->toArray(),
                    'total_remunerations' => $a->remunerations->sum('montant'),
                ])->toArray();
                
                // Activités consultant
                $activitesConsultant = $latestDeclaration->activitesConsultant->map(fn($a) => [
                    'description' => $a->description ?? 'Activité de conseil',
                    'date_debut' => $a->date_debut instanceof \Carbon\Carbon ? $a->date_debut->format('d/m/Y') : ($a->date_debut ? \Carbon\Carbon::parse($a->date_debut)->format('d/m/Y') : null),
                    'date_fin' => $a->date_fin instanceof \Carbon\Carbon ? $a->date_fin->format('d/m/Y') : ($a->date_fin ? \Carbon\Carbon::parse($a->date_fin)->format('d/m/Y') : null),
                    'remunerations' => $a->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->sortByDesc('annee')->values()->toArray(),
                    'total_remunerations' => $a->remunerations->sum('montant'),
                ])->toArray();
                
                // Participations dirigeantes
                $participationsDirigeantes = $latestDeclaration->participationsDirigeantes->map(fn($p) => [
                    'societe' => $p->nom_societe ?? $p->societe ?? 'Société',
                    'activite' => $p->activite,
                    'date_debut' => $p->date_debut instanceof \Carbon\Carbon ? $p->date_debut->format('d/m/Y') : ($p->date_debut ? \Carbon\Carbon::parse($p->date_debut)->format('d/m/Y') : null),
                    'date_fin' => $p->date_fin instanceof \Carbon\Carbon ? $p->date_fin->format('d/m/Y') : ($p->date_fin ? \Carbon\Carbon::parse($p->date_fin)->format('d/m/Y') : null),
                    'remunerations' => $p->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->sortByDesc('annee')->values()->toArray(),
                    'total_remunerations' => $p->remunerations->sum('montant'),
                ])->toArray();

                $hatvpSummary = [
                    'declaration_date' => $latestDeclaration->date_depot?->format('d/m/Y'),
                    'declaration_type' => $latestDeclaration->type_declaration_label,
                    'nombre_mandats' => $latestDeclaration->mandatsElectifs->count(),
                    'nombre_emplois' => $latestDeclaration->activitesProfessionnelles->count(),
                    'nombre_collaborateurs' => $latestDeclaration->collaborateurs->count(),
                    'revenus_par_annee' => $revenusParAnnee,
                    'mandats_electifs' => $mandatsElectifs,
                    'activites_professionnelles' => $activitesPro,
                    'activites_consultant' => $activitesConsultant,
                    'participations_dirigeantes' => $participationsDirigeantes,
                    'fonctions_benevoles' => $latestDeclaration->fonctionsBenevoles->map(fn($f) => [
                        'description' => $f->description,
                        'organisme' => $f->organisme,
                    ])->toArray(),
                ];
            }
            } catch (\Exception $e) {
                // Table peut ne pas exister encore
            }
            return ['declarations' => $declarationsHatvp, 'summary' => $hatvpSummary];
        });
        $declarationsHatvp = $hatvpData['declarations'];
        $hatvpSummary = $hatvpData['summary'];

        // Récupérer les 10 derniers votes avec les scrutins (cache 30 min)
        $derniersVotes = Cache::remember("depute_votes_{$uid}", 1800, function () use ($uid) {
            return VoteIndividuelAN::where('acteur_ref', $uid)
                ->whereHas('scrutin', fn($q) => $q->where('legislature', 17))
                ->with(['scrutin'])
                ->orderByDesc(
                    \App\Models\ScrutinAN::select('date_scrutin')
                        ->whereColumn('scrutins_an.uid', 'votes_individuels_an.scrutin_ref')
                        ->limit(1)
                )
                ->limit(10)
                ->get()
                ->map(function($vote) {
                    $scrutin = $vote->scrutin;
                    return [
                        'id' => $vote->id,
                        'position' => $vote->position,
                        'date' => $scrutin->date_scrutin?->format('d/m/Y'),
                        'scrutin' => [
                            'uid' => $scrutin->uid,
                            'titre' => $scrutin->titre,
                            'resultat' => $scrutin->resultat_format,
                            'pour' => $scrutin->pour_calcule,
                            'contre' => $scrutin->contre_calcule,
                        ],
                    ];
                });
        });

        return Inertia::render('Representants/Deputes/Show', [
            'depute' => [
                'uid' => $acteur->uid,
                'nom_complet' => $acteur->nom_complet,
                'civilite' => $acteur->civilite,
                'prenom' => $acteur->prenom,
                'nom' => $acteur->nom,
                'trigramme' => $acteur->trigramme,
                'photo_url' => $acteur->photo_url, // Priorité photo officielle AN
                'date_naissance' => $acteur->date_naissance?->format('d/m/Y'),
                'age' => $acteur->date_naissance ? $acteur->date_naissance->age : null,
                'lieu_naissance' => trim("{$acteur->ville_naissance} {$acteur->departement_naissance}"),
                'profession' => $acteur->profession,
                'categorie_socio_pro' => $acteur->categorie_socio_pro,
                'circonscription' => $acteur->circonscription_info,
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
                'declarations_hatvp' => $declarationsHatvp,
                'hatvp_summary' => $hatvpSummary,
                'reseaux_sociaux' => [
                    'twitter' => $acteur->twitter_url,
                    'facebook' => $acteur->facebook_url,
                    'linkedin' => $acteur->linkedin_url,
                    'instagram' => $acteur->instagram_url,
                ],
                'adresses' => $acteur->adresses,
                'derniers_votes' => $derniersVotes,
                'questions_stats' => $this->getQuestionsStats($uid),
                'dernieres_questions' => $this->getDernieresQuestions($uid),
                'is_followed' => Auth::check() && EluFollower::where('user_id', Auth::id())
                    ->where('elu_type', 'depute')
                    ->where('elu_id', $uid)
                    ->exists(),
            ],
        ]);
    }

    /**
     * Statistiques des questions pour un député (cache 1h)
     */
    protected function getQuestionsStats(string $uid): array
    {
        return Cache::remember("depute_questions_stats_{$uid}", 3600, function () use ($uid) {
            $total = QuestionAN::where('acteur_ref', $uid)->count();
            $repondues = QuestionAN::where('acteur_ref', $uid)->whereNotNull('date_reponse')->count();
            
            $parRubrique = QuestionAN::where('acteur_ref', $uid)
                ->selectRaw('rubrique, count(*) as nb')
                ->groupBy('rubrique')
                ->orderByDesc('nb')
                ->limit(5)
                ->get()
                ->map(fn($r) => ['rubrique' => $r->rubrique, 'nb' => $r->nb])
                ->toArray();

            return [
                'total' => $total,
                'repondues' => $repondues,
                'en_attente' => $total - $repondues,
                'par_rubrique' => $parRubrique,
            ];
        });
    }

    /**
     * Dernières questions pour un député
     */
    protected function getDernieresQuestions(string $uid, int $limit = 5): array
    {
        return QuestionAN::where('acteur_ref', $uid)
            ->orderByDesc('date_question')
            ->limit($limit)
            ->get()
            ->map(fn($q) => [
                'uid' => $q->uid,
                'numero' => $q->numero,
                'type' => $q->type,
                'analyse' => $q->analyse,
                'rubrique' => $q->rubrique,
                'ministere_sigle' => $q->ministere_sigle,
                'date_question' => $q->date_question?->format('d/m/Y'),
                'date_reponse' => $q->date_reponse?->format('d/m/Y'),
                'a_reponse' => !is_null($q->date_reponse),
            ])
            ->toArray();
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

        // Filtres (colonne 'objet' n'existe pas dans scrutins_an)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('scrutin', function($q) use ($search) {
                $q->where('titre', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('position', $request->type);
        }

        $votes = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        // Statistiques (cache 1h)
        $statistiques = Cache::remember("depute_votes_stats_{$uid}_l17", 3600, function () use ($uid) {
            // Récupérer les IDs de scrutins de la legislature 17
            $scrutinIds = \App\Models\ScrutinAN::where('legislature', 17)->pluck('uid');
            
            // Requête optimisée avec un seul COUNT groupé
            $positionCounts = VoteIndividuelAN::where('acteur_ref', $uid)
                ->whereIn('scrutin_ref', $scrutinIds)
                ->selectRaw('position, COUNT(*) as count')
                ->groupBy('position')
                ->pluck('count', 'position')
                ->toArray();
            
            $pour = $positionCounts['pour'] ?? 0;
            $contre = $positionCounts['contre'] ?? 0;
            $abstention = $positionCounts['abstention'] ?? 0;
            $total = array_sum($positionCounts);

            return [
                'total' => $total,
                'pour' => $pour,
                'contre' => $contre,
                'abstention' => $abstention,
                'pour_percent' => $total > 0 ? round(($pour / $total) * 100, 1) : 0,
                'contre_percent' => $total > 0 ? round(($contre / $total) * 100, 1) : 0,
                'abstention_percent' => $total > 0 ? round(($abstention / $total) * 100, 1) : 0,
            ];
        });

        // Transformer les votes
        $votesData = $votes->through(function($vote) {
            return [
                'id' => $vote->id,
                'position' => $vote->position,
                'date' => $vote->scrutin->date_scrutin?->format('d/m/Y'),
                'scrutin' => [
                    'uid' => $vote->scrutin->uid,
                    'titre' => $vote->scrutin->titre,
                    'pour' => $vote->scrutin->pour_calcule,
                    'contre' => $vote->scrutin->contre_calcule,
                    'abstention' => $vote->scrutin->abstentions_calcule,
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
                'numero' => $amendement->numero_long ?? $amendement->uid,
                'sort_code' => $amendement->sort_code,
                'sort_libelle' => $amendement->sort_libelle,
                'etat_libelle' => $amendement->etat_libelle,
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'dispositif' => $amendement->dispositif ? substr($amendement->dispositif, 0, 200) . '...' : '',
                'cosignataires_count' => $amendement->nombre_cosignataires,
                'dossier' => $amendement->dossier ? [
                    'uid' => $amendement->dossier->uid,
                    'titre_court' => $amendement->dossier->titre_court ?? $amendement->dossier->titre,
                ] : null,
                'texte' => $amendement->texte ? [
                    'uid' => $amendement->texte->uid,
                    'titre_court' => $amendement->texte->titre_court ?? $amendement->texte->titre,
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
                'numero' => $amendement->numero_long ?? $amendement->uid,
                'sort_code' => $amendement->sort_code,
                'sort_libelle' => $amendement->sort_libelle,
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'dossier' => $amendement->dossier ? [
                    'uid' => $amendement->dossier->uid,
                    'titre_court' => $amendement->dossier->titre_court ?? $amendement->dossier->titre,
                ] : null,
                'texte' => $amendement->texte ? [
                    'uid' => $amendement->texte->uid,
                    'titre_court' => $amendement->texte->titre_court ?? $amendement->texte->titre,
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
            'photo_url' => $acteur->photo_url, // Priorité photo officielle AN
            'groupe' => $groupeActuel ? [
                'uid' => $groupeActuel->uid,
                'nom' => $groupeActuel->libelle,
                'sigle' => $groupeActuel->libelle_abrege,
                'couleur' => $groupeService->getCouleurGroupe($groupeActuel->libelle_abrege),
            ] : null,
        ];
    }

    /**
     * Formatte un sénateur pour les vues (version basique)
     */
    private function formatSenateur(Senateur $senateur): array
    {
        return [
            'id' => $senateur->id,
            'matricule' => $senateur->matricule,
            'nom_complet' => trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}"),
            'nom_usuel' => $senateur->nom_usuel,
            'prenom_usuel' => $senateur->prenom_usuel,
            'photo_wikipedia_url' => $senateur->photo_wikipedia_url,
            'groupe_politique' => $senateur->groupe_politique,
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
        $groupeService = app(GroupeParlementaireService::class);
        $senateursData = $senateurs->through(function($senateur) use ($groupeService) {
            return [
                'matricule' => $senateur->matricule,
                'nom_complet' => trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}"),
                'civilite' => $senateur->civilite,
                'prenom' => $senateur->prenom_usuel,
                'nom' => $senateur->nom_usuel,
                'photo_url' => $senateur->photo_url,
                'profession' => $senateur->description_profession,
                'circonscription' => $senateur->circonscription,
                'groupe' => $senateur->groupe_politique ? [
                    'nom' => $senateur->groupe_politique,
                    'sigle' => $senateur->groupe_politique,
                    'couleur' => $groupeService->getCouleurGroupe($senateur->groupe_politique),
                ] : null,
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
            ->map(fn($g) => [
                'nom' => $g, 
                'sigle' => $g,
                'couleur' => $groupeService->getCouleurGroupe($g),
            ]);

        // Statistiques pour le header
        $stats = Cache::remember('senateurs_index_stats', 3600, function () {
            $totalSenateurs = Senateur::actifs()->count();
            $senateursFemmes = Senateur::actifs()->where('civilite', 'Mme')->count();
            $pourcentageFemmes = $totalSenateurs > 0 ? round(($senateursFemmes / $totalSenateurs) * 100, 1) : 0;

            $ageMoyen = Senateur::actifs()
                ->whereNotNull('date_naissance')
                ->selectRaw('AVG(EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_naissance))) as average_age')
                ->value('average_age');

            $nbGroupes = Senateur::actifs()
                ->whereNotNull('groupe_politique')
                ->distinct('groupe_politique')
                ->count('groupe_politique');

            return [
                'total' => $totalSenateurs,
                'groupes' => $nbGroupes,
                'femmes_pct' => $pourcentageFemmes,
                'age_moyen' => round($ageMoyen ?? 60),
                'serie' => 2, // Prochaine série à renouveler
            ];
        });

        return Inertia::render('Representants/Senateurs/Index', [
            'senateurs' => $senateursData,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'circonscription', 'sort', 'order']),
            'stats' => $stats,
        ]);
    }

    /**
     * Fiche détaillée d'un sénateur (nouvelle version)
     * Utilise les stats pré-calculées pour de meilleures performances
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
            'mandatsLocaux' => function($query) {
                $query->orderBy('date_debut', 'desc');
            },
            'etudes',
        ])->findOrFail($matricule);

        // Utiliser les stats pré-calculées (ou fallback sur calcul à la volée)
        $cachedStats = ParlementaireStats::forSenateur($matricule);
        
        if ($cachedStats && !$cachedStats->isStale()) {
            // Utiliser les stats pré-calculées
            $stats = $cachedStats->toViewArray();
        } else {
            // Fallback: calcul à la volée (pour les premiers chargements)
            $totalScrutinsSenat = ScrutinSenat::count();
            
            $votesTotal = VoteSenat::where('senateur_matricule', $matricule)->count();
            $amendementsTotal = AmendementSenat::where('senateur_matricule', $matricule)->count();
            $amendementsAdoptes = AmendementSenat::where('senateur_matricule', $matricule)->adoptes()->count();

            $stats = [
                'votes_total' => $votesTotal,
                'taux_presence' => $totalScrutinsSenat > 0 
                    ? round(($votesTotal / $totalScrutinsSenat) * 100, 1) 
                    : 0,
                'amendements_total' => $amendementsTotal,
                'amendements_adoptes' => $amendementsAdoptes,
                'taux_adoption_amendements' => $amendementsTotal > 0
                    ? round(($amendementsAdoptes / $amendementsTotal) * 100, 1)
                    : 0,
                'calculated_at' => null, // Indique que c'est un calcul à la volée
            ];
        }

        // Déclarations HATVP avec données enrichies (cache 1h)
        $hatvpData = Cache::remember("hatvp_senateur_{$matricule}", 3600, function () use ($matricule, $senateur) {
            $declarationsHatvp = [];
            $hatvpSummary = null;
            try {
                $declarations = \App\Models\HatvpDeclaration::with([
                    'mandatsElectifs.remunerations',
                    'activitesProfessionnelles.remunerations',
                    'activitesConsultant.remunerations',
                    'participationsDirigeantes.remunerations',
                    'collaborateurs',
                    'fonctionsBenevoles',
                ])
                ->where('parlementaire_type', 'senateur')
                ->where('parlementaire_id', $matricule)
                ->orderBy('date_depot', 'desc')
                ->get();
                
                // Si pas de liaison directe, chercher par nom/prénom
                if ($declarations->isEmpty()) {
                    $declarations = \App\Models\HatvpDeclaration::with([
                        'mandatsElectifs.remunerations',
                        'activitesProfessionnelles.remunerations',
                        'activitesConsultant.remunerations',
                        'participationsDirigeantes.remunerations',
                        'collaborateurs',
                        'fonctionsBenevoles',
                    ])
                    ->where('parlementaire_type', 'senateur')
                    ->where('nom', 'ILIKE', $senateur->nom_usuel)
                    ->where('prenom', 'ILIKE', $senateur->prenom_usuel)
                    ->orderBy('date_depot', 'desc')
                    ->get();
                }

            // Mapper les déclarations
            $declarationsHatvp = $declarations->map(fn($d) => [
                'uuid' => $d->uuid,
                'type' => $d->type_declaration,
                'type_label' => $d->type_declaration_label,
                'date_depot' => $d->date_depot?->format('d/m/Y'),
                'url' => "https://www.hatvp.fr/fiche-nominative/?declarant=" . strtolower($senateur->nom_usuel) . "-" . strtolower($senateur->prenom_usuel),
            ])->toArray();

            // Calculer le résumé HATVP à partir de la déclaration la plus récente
            $latestDeclaration = $declarations->first();
            if ($latestDeclaration) {
                // Récupérer les revenus par année
                $revenusParAnnee = $latestDeclaration->revenus_par_annee;
                
                // Mandats électifs avec rémunérations
                $mandatsElectifs = $latestDeclaration->mandatsElectifs->map(fn($m) => [
                    'description' => $m->description,
                    'date_debut' => $m->date_debut?->format('d/m/Y'),
                    'date_fin' => $m->date_fin?->format('d/m/Y'),
                    'actif' => $m->est_actif,
                    'remunerations' => $m->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->toArray(),
                    'total_remunerations' => $m->total_remunerations,
                ])->toArray();

                // Activités professionnelles avec rémunérations
                $activitesPro = $latestDeclaration->activitesProfessionnelles->map(fn($a) => [
                    'employeur' => $a->employeur,
                    'description' => $a->description,
                    'date_debut' => $a->date_debut?->format('d/m/Y'),
                    'date_fin' => $a->date_fin?->format('d/m/Y'),
                    'actif' => $a->conservee && is_null($a->date_fin),
                    'remunerations' => $a->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->toArray(),
                    'total_remunerations' => $a->total_remunerations,
                ])->toArray();

                // Activités consultant avec rémunérations
                $activitesConsultant = $latestDeclaration->activitesConsultant->map(fn($a) => [
                    'employeur' => $a->nom_employeur,
                    'description' => $a->description,
                    'date_debut' => $a->date_debut?->format('d/m/Y'),
                    'date_fin' => $a->date_fin?->format('d/m/Y'),
                    'actif' => $a->conservee && is_null($a->date_fin),
                    'remunerations' => $a->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->toArray(),
                    'total_remunerations' => $a->total_remunerations,
                ])->toArray();

                // Participations dirigeantes avec rémunérations
                $participationsDirigeantes = $latestDeclaration->participationsDirigeantes->map(fn($p) => [
                    'societe' => $p->nom_societe,
                    'activite' => $p->activite,
                    'date_debut' => $p->date_debut?->format('d/m/Y'),
                    'date_fin' => $p->date_fin?->format('d/m/Y'),
                    'actif' => $p->conservee && is_null($p->date_fin),
                    'remunerations' => $p->remunerations->map(fn($r) => [
                        'annee' => $r->annee,
                        'montant' => $r->montant,
                        'brut_net' => $r->brut_net,
                    ])->toArray(),
                    'total_remunerations' => $p->total_remunerations,
                ])->toArray();

                // Collaborateurs
                $collaborateurs = $latestDeclaration->collaborateurs->map(fn($c) => [
                    'nom' => $c->nom,
                    'employeur' => $c->employeur,
                    'description' => $c->description_activite,
                ])->toArray();

                $hatvpSummary = [
                    'declaration_date' => $latestDeclaration->date_depot?->format('d/m/Y'),
                    'declaration_type' => $latestDeclaration->type_declaration_label,
                    'nombre_mandats' => $latestDeclaration->getNombreMandatsCumules(),
                    'nombre_emplois' => $latestDeclaration->nombre_emplois,
                    'nombre_collaborateurs' => $latestDeclaration->nombre_collaborateurs,
                    'revenus_par_annee' => $revenusParAnnee,
                    'mandats_electifs' => $mandatsElectifs,
                    'activites_professionnelles' => $activitesPro,
                    'activites_consultant' => $activitesConsultant,
                    'participations_dirigeantes' => $participationsDirigeantes,
                    'collaborateurs' => $collaborateurs,
                ];
            }
            } catch (\Exception $e) {
                // Table peut ne pas exister encore
            }
            return ['declarations' => $declarationsHatvp, 'summary' => $hatvpSummary];
        });
        $declarationsHatvp = $hatvpData['declarations'];
        $hatvpSummary = $hatvpData['summary'];

        // Récupérer les 10 derniers votes du sénateur (cache 30 min)
        $derniersVotes = Cache::remember("senateur_votes_{$matricule}", 1800, function () use ($matricule) {
            return VoteSenat::where('senateur_matricule', $matricule)
                ->with('scrutin')
                ->orderByDesc('date_vote')
                ->limit(10)
                ->get()
                ->map(function($vote) {
                    return [
                        'id' => $vote->id,
                        'position' => $vote->position,
                        'date' => $vote->date_vote?->format('d/m/Y'),
                        'intitule' => $vote->intitule,
                        'scrutin' => $vote->scrutin ? [
                            'id' => $vote->scrutin->id,
                            'resultat' => $vote->scrutin->resultat ?? 'Non déterminé',
                            'pour' => $vote->scrutin->pour ?? 0,
                            'contre' => $vote->scrutin->contre ?? 0,
                        ] : null,
                    ];
                });
        });

        return Inertia::render('Representants/Senateurs/Show', [
            'senateur' => [
                'matricule' => $senateur->matricule,
                'nom_complet' => trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}"),
                'civilite' => $senateur->civilite,
                'prenom' => $senateur->prenom_usuel,
                'nom' => $senateur->nom_usuel,
                'photo_url' => $senateur->photo_url, // Priorité photo officielle Sénat
                'date_naissance' => $senateur->date_naissance?->format('d/m/Y'),
                'age' => $senateur->date_naissance?->age,
                'lieu_naissance' => null, // TODO: Ajouter à la vue SQL si disponible
                'profession' => $senateur->description_profession,
                'circonscription' => $senateur->circonscription,
                'etat' => $senateur->etat,
                'url_profil' => $senateur->url_profil,
                'groupe' => [
                    'nom' => $senateur->groupe_politique,
                    'couleur' => '#6B7280',
                ],
                'commission' => $senateur->commission_permanente,
                'wikipedia' => $senateur->wikipedia_url ? [
                    'url' => $senateur->wikipedia_url,
                    'photo' => $senateur->photo_wikipedia_url,
                    'extract' => $senateur->wikipedia_extract,
                ] : null,
                'commissions' => $senateur->commissions->map(fn($c) => [
                    'commission' => $c->commission_nom ?? $c->commission_code ?? 'Commission',
                    'date_debut' => $c->date_debut?->format('d/m/Y'),
                    'date_fin' => $c->date_fin?->format('d/m/Y'),
                    'fonction' => $c->fonction,
                    'actif' => is_null($c->date_fin),
                ])->toArray(),
                'mandats' => $senateur->mandats->map(fn($m) => [
                    'type' => $m->type_mandat ?? 'Mandat sénatorial',
                    'circonscription' => $m->circonscription ?? $senateur->circonscription,
                    'date_debut' => $m->date_debut?->format('d/m/Y'),
                    'date_fin' => $m->date_fin?->format('d/m/Y'),
                    'numero' => $m->numero_mandat ?? null,
                    'actif' => is_null($m->date_fin),
                ])->toArray(),
                'historique_groupes' => $senateur->historiqueGroupes->map(fn($g) => [
                    'groupe' => $g->groupe_nom ?? $g->groupe_code ?? 'Groupe',
                    'date_debut' => $g->date_debut?->format('d/m/Y'),
                    'date_fin' => $g->date_fin?->format('d/m/Y'),
                ])->toArray(),
                'mandats_locaux' => $senateur->mandatsLocaux->map(fn($m) => [
                    'type_mandat' => $m->type_mandat,
                    'fonction' => $m->fonction,
                    'collectivite' => $m->collectivite,
                    'code_collectivite' => $m->code_collectivite ?? null,
                    'periode' => $m->periode ?? "{$m->date_debut?->format('Y')} - {$m->date_fin?->format('Y')}",
                    'en_cours' => $m->en_cours ?? is_null($m->date_fin),
                ])->toArray(),
                'etudes' => $senateur->etudes->map(fn($e) => [
                    'etablissement' => $e->etablissement,
                    'diplome' => $e->diplome,
                    'niveau' => $e->niveau,
                    'domaine' => $e->domaine,
                    'annee' => $e->annee,
                ])->toArray(),
                'email' => $senateur->email,
                'telephone' => $senateur->telephone ?? null,
                'adresse_postale' => $senateur->adresse_postale ?? null,
                'statistiques' => $stats,
                'declarations_hatvp' => $declarationsHatvp,
                'hatvp_summary' => $hatvpSummary,
                'derniers_votes' => $derniersVotes,
                'is_followed' => Auth::check() && EluFollower::where('user_id', Auth::id())
                    ->where('elu_type', 'senateur')
                    ->where('elu_id', $matricule)
                    ->exists(),
            ],
        ]);
    }

    /**
     * Page votes d'un sénateur
     */
    public function senateurVotes(Request $request, string $matricule): Response
    {
        $senateur = Senateur::findOrFail($matricule);

        $query = VoteSenat::query()
            ->where('senateur_matricule', $matricule)
            ->with('scrutin');

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('scrutin', function($q) use ($search) {
                $q->where('intitule', 'ILIKE', "%{$search}%")
                  ->orWhere('intitule_complet', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('position', $request->type);
        }

        $votes = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        // Statistiques (cache 1h)
        $statistiques = Cache::remember("senateur_votes_stats_{$matricule}", 3600, function () use ($matricule) {
            // Requête optimisée avec un seul COUNT groupé
            $positionCounts = VoteSenat::where('senateur_matricule', $matricule)
                ->selectRaw('position, COUNT(*) as count')
                ->groupBy('position')
                ->pluck('count', 'position')
                ->toArray();
            
            $pour = $positionCounts['pour'] ?? 0;
            $contre = $positionCounts['contre'] ?? 0;
            $abstention = $positionCounts['abstention'] ?? 0;
            $total = array_sum($positionCounts);

            return [
                'total' => $total,
                'pour' => $pour,
                'contre' => $contre,
                'abstention' => $abstention,
                'pour_percent' => $total > 0 ? round(($pour / $total) * 100, 1) : 0,
                'contre_percent' => $total > 0 ? round(($contre / $total) * 100, 1) : 0,
                'abstention_percent' => $total > 0 ? round(($abstention / $total) * 100, 1) : 0,
            ];
        });

        // Transformer les votes (utilise la relation scrutin déjà chargée)
        $votesData = $votes->through(function($vote) {
            return [
                'id' => $vote->id,
                'position' => $vote->position,
                'date_vote' => $vote->date_vote?->format('d/m/Y'),
                'intitule' => $vote->intitule,
                'intitule_complet' => $vote->intitule_complet,
                'resultat_scrutin' => $vote->resultat_scrutin,
                'scrutin_id' => $vote->scrutin_id,
                'scrutin' => $vote->scrutin ? [
                    'id' => $vote->scrutin->id,
                    'pour' => $vote->scrutin->pour ?? 0,
                    'contre' => $vote->scrutin->contre ?? 0,
                    'votants' => $vote->scrutin->votants ?? 0,
                    'resultat' => $vote->scrutin->resultat ?? 'Non déterminé',
                ] : null,
            ];
        });

        return Inertia::render('Representants/Senateurs/Votes', [
            'senateur' => $this->formatSenateur($senateur),
            'votes' => $votesData,
            'statistiques' => $statistiques,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Page amendements d'un sénateur
     */
    public function senateurAmendements(Request $request, string $matricule): Response
    {
        $senateur = Senateur::findOrFail($matricule);

        $query = AmendementSenat::query()
            ->where('senateur_matricule', $matricule);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'ILIKE', "%{$search}%")
                  ->orWhere('dispositif', 'ILIKE', "%{$search}%")
                  ->orWhere('expose', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('sort')) {
            // Utilise les scopes pour supporter tous les formats de codes
            switch ($request->sort) {
                case 'adopte':
                case 'ADO':
                case 'A':
                    $query->adoptes();
                    break;
                case 'rejete':
                case 'REJ':
                case 'RJS':
                    $query->rejetes();
                    break;
                case 'retire':
                case 'RET':
                case 'R':
                    $query->retires();
                    break;
                case 'tombe':
                case 'S':
                    $query->tombes();
                    break;
            }
        }

        $amendements = $query->orderBy('date_depot', 'desc')
            ->paginate(30)
            ->withQueryString();

        // Statistiques (utilise les scopes du modèle pour supporter tous les formats de codes)
        $statsQuery = AmendementSenat::where('senateur_matricule', $matricule);
        
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

        // Transformer les amendements (avec décodage HTML et nettoyage UTF-8)
        $amendementsData = $amendements->through(function($amendement) {
            return [
                'id' => $amendement->id,
                'numero' => $amendement->numero,
                'type_amendement' => $amendement->type_amendement,
                'dispositif' => $this->cleanUtf8(substr($amendement->dispositif_decode ?? '', 0, 200)),
                'expose' => $this->cleanUtf8(substr($amendement->expose_decode ?? '', 0, 200)),
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'sort_code' => $amendement->sort_code,
                'sort_libelle' => $amendement->sort_libelle_formate,
                'texte_nom' => $amendement->texte_nom ?? null,
            ];
        });

        return Inertia::render('Representants/Senateurs/Amendements', [
            'senateur' => $this->formatSenateur($senateur),
            'amendements' => $amendementsData,
            'statistiques' => $statistiques,
            'filters' => $request->only(['search', 'sort']),
        ]);
    }

    /**
     * Page activité d'un sénateur
     */
    public function senateurActivite(string $matricule): Response
    {
        $senateur = Senateur::findOrFail($matricule);

        // Statistiques votes
        $votesQuery = VoteSenat::where('senateur_matricule', $matricule);
        $votesTotal = $votesQuery->count();
        $votesPour = $votesQuery->clone()->where('position', 'pour')->count();
        $votesContre = $votesQuery->clone()->where('position', 'contre')->count();
        $votesAbstention = $votesQuery->clone()->where('position', 'abstention')->count();

        // Statistiques amendements (utilise les scopes du modèle)
        $amendementsQuery = AmendementSenat::where('senateur_matricule', $matricule);
        $amendementsTotal = $amendementsQuery->count();
        $amendementsAdoptes = $amendementsQuery->clone()->adoptes()->count();
        $amendementsRejetes = $amendementsQuery->clone()->rejetes()->count();
        $amendementsRetires = $amendementsQuery->clone()->retires()->count();

        // Derniers votes
        $derniersVotes = VoteSenat::where('senateur_matricule', $matricule)
            ->with('scrutin')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($vote) => [
                'id' => $vote->id,
                'position' => $vote->position,
                'date_vote' => $vote->date_vote?->format('d/m/Y'),
                'intitule' => $vote->intitule,
                'resultat_scrutin' => $vote->resultat_scrutin,
            ]);

        // Derniers amendements (avec décodage HTML et nettoyage UTF-8)
        $derniersAmendements = AmendementSenat::where('senateur_matricule', $matricule)
            ->orderBy('date_depot', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($amendement) => [
                'id' => $amendement->id,
                'numero' => $amendement->numero,
                'dispositif' => $this->cleanUtf8(substr($amendement->dispositif_decode ?? '', 0, 150)),
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'sort_code' => $amendement->sort_code,
                'sort_libelle' => $amendement->sort_libelle_formate,
            ]);

        return Inertia::render('Representants/Senateurs/Activite', [
            'senateur' => $this->formatSenateur($senateur),
            'statistiques' => [
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
                    'retires' => $amendementsRetires,
                    'taux_adoption' => $amendementsTotal > 0 ? round(($amendementsAdoptes / $amendementsTotal) * 100, 1) : 0,
                ],
            ],
            'derniers_votes' => $derniersVotes,
            'derniers_amendements' => $derniersAmendements,
        ]);
    }

    /**
     * Nettoie une chaîne pour s'assurer qu'elle est en UTF-8 valide
     */
    private function cleanUtf8(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Convertir en UTF-8 si nécessaire
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        // Supprimer les caractères non valides
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Remplacer les séquences UTF-8 invalides
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Décoder les entités HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $text;
    }
}

