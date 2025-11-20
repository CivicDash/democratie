<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PropositionLoi;
use App\Models\ScrutinAN;
use App\Models\AmendementAN;
use App\Models\DossierLegislatifAN;
use App\Models\TexteLegislatifAN;
use App\Models\VoteIndividuelAN;
use App\Services\GroupeParlementaireService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for legislation pages (web/Inertia)
 */
class LegislationController extends Controller
{
    /**
     * Display list of legislative propositions
     * 
     * GET /legislation
     */
    public function index(Request $request): Response
    {
        $query = PropositionLoi::query()
            ->with(['votesCitoyens'])
            ->withCount(['votesCitoyens as upvotes' => function ($query) {
                $query->where('type_vote', 'pour');
            }])
            ->withCount(['votesCitoyens as downvotes' => function ($query) {
                $query->where('type_vote', 'contre');
            }]);

        // Filtres
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('theme')) {
            $query->where('theme', 'LIKE', '%' . $request->input('theme') . '%');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'LIKE', '%' . $search . '%')
                  ->orWhere('resume', 'LIKE', '%' . $search . '%');
            });
        }

        // Tri par défaut : date de dépôt décroissante
        $query->orderBy('date_depot', 'desc');

        // Pagination
        $propositions = $query->paginate(15)->through(function ($proposition) {
            return [
                'id' => $proposition->id,
                'numero' => $proposition->numero,
                'titre' => $proposition->titre,
                'resume' => $proposition->resume ? substr($proposition->resume, 0, 250) . '...' : null,
                'source' => $proposition->source,
                'statut' => $proposition->statut,
                'theme' => $proposition->theme,
                'date_depot' => $proposition->date_depot?->format('d/m/Y'),
                'nb_amendements' => $proposition->nb_amendements,
                'upvotes' => $proposition->upvotes ?? 0,
                'downvotes' => $proposition->downvotes ?? 0,
                'score' => ($proposition->upvotes ?? 0) - ($proposition->downvotes ?? 0),
            ];
        });

        // Propositions tendances (par score)
        $trending = PropositionLoi::query()
            ->withCount(['votesCitoyens as upvotes' => function ($query) {
                $query->where('type_vote', 'pour');
            }])
            ->withCount(['votesCitoyens as downvotes' => function ($query) {
                $query->where('type_vote', 'contre');
            }])
            ->whereIn('statut', ['discussion', 'vote'])
            ->orderByRaw('(SELECT COUNT(*) FROM votes_propositions_loi WHERE proposition_loi_id = propositions_loi.id AND type_vote = \'pour\') - (SELECT COUNT(*) FROM votes_propositions_loi WHERE proposition_loi_id = propositions_loi.id AND type_vote = \'contre\') DESC')
            ->limit(3)
            ->get()
            ->map(function ($proposition) {
                return [
                    'id' => $proposition->id,
                    'numero' => $proposition->numero,
                    'titre' => $proposition->titre,
                    'source' => $proposition->source,
                    'upvotes' => $proposition->upvotes ?? 0,
                    'downvotes' => $proposition->downvotes ?? 0,
                    'score' => ($proposition->upvotes ?? 0) - ($proposition->downvotes ?? 0),
                ];
            });

        return Inertia::render('Legislation/Index', [
            'propositions' => $propositions,
            'trending' => $trending,
            'filters' => [
                'source' => $request->input('source'),
                'statut' => $request->input('statut'),
                'theme' => $request->input('theme'),
                'search' => $request->input('search'),
            ],
        ]);
    }

    /**
     * Display a single legislative proposition
     * 
     * GET /legislation/{proposition}
     */
    public function show(PropositionLoi $proposition): Response
    {
        $proposition->load([
            'amendements' => function ($query) {
                $query->orderBy('date_depot', 'desc')->limit(20);
            },
            'votes' => function ($query) {
                $query->orderBy('date_vote', 'desc')->limit(10);
            },
            'votesCitoyens',
        ]);

        return Inertia::render('Legislation/Show', [
            'proposition' => [
                'id' => $proposition->id,
                'numero' => $proposition->numero,
                'titre' => $proposition->titre,
                'resume' => $proposition->resume,
                'texte' => $proposition->texte,
                'source' => $proposition->source,
                'statut' => $proposition->statut,
                'theme' => $proposition->theme,
                'auteurs' => $proposition->auteurs,
                'date_depot' => $proposition->date_depot,
                'date_discussion' => $proposition->date_discussion,
                'url_dossier' => $proposition->url_dossier,
                'nb_amendements' => $proposition->amendements->count(),
                'nb_signataires' => $proposition->nb_signataires,
            ],
            'amendements' => $proposition->amendements->map(function ($amendement) {
                return [
                    'id' => $amendement->id,
                    'numero' => $amendement->numero,
                    'auteur' => $amendement->auteur,
                    'texte' => $amendement->texte,
                    'statut' => $amendement->statut,
                    'date_depot' => $amendement->date_depot,
                ];
            }),
            'votes' => $proposition->votes->map(function ($vote) {
                return [
                    'id' => $vote->id,
                    'libelle' => $vote->libelle,
                    'date' => $vote->date,
                    'pour' => $vote->pour,
                    'contre' => $vote->contre,
                    'abstentions' => $vote->abstentions,
                ];
            }),
            'similar' => [], // À implémenter avec findSimilar API si besoin
        ]);
    }

    /**
     * Liste des scrutins
     * 
     * GET /legislation/scrutins
     */
    public function scrutinsIndex(Request $request): Response
    {
        $legislature = $request->input('legislature', 17);
        
        $query = ScrutinAN::query()
            ->where('legislature', $legislature)
            ->orderBy('date_scrutin', 'desc')
            ->orderBy('numero', 'desc');

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'ILIKE', "%{$search}%")
                  ->orWhere('objet', 'ILIKE', "%{$search}%");
            });
        }

        $scrutins = $query->paginate(30)->withQueryString();

        // Statistiques - calculer depuis les votes réels
        $allScrutins = ScrutinAN::where('legislature', $legislature)->get();
        $total = $allScrutins->count();
        
        // Compter adoptés/rejetés en calculant depuis pour/contre
        $adoptes = 0;
        $rejetes = 0;
        
        foreach ($allScrutins as $scrutin) {
            // Si resultat_code existe, l'utiliser
            if ($scrutin->resultat_code === 'adopté') {
                $adoptes++;
            } elseif ($scrutin->resultat_code === 'rejeté') {
                $rejetes++;
            } 
            // Sinon, calculer depuis les votes
            elseif ($scrutin->pour > $scrutin->contre) {
                $adoptes++;
            } elseif ($scrutin->contre > $scrutin->pour) {
                $rejetes++;
            }
        }

        $stats = [
            'total' => $total,
            'adoptes' => $adoptes,
            'rejetes' => $rejetes,
            'taux_adoption' => $total > 0 ? round(($adoptes / $total) * 100, 1) : 0,
        ];

        // Transformer les données
        $scrutinsData = $scrutins->through(function($s) {
            // Déterminer le résultat si non défini
            $resultat = $s->resultat_libelle;
            if (!$resultat) {
                if ($s->pour > $s->contre) {
                    $resultat = 'Adopté';
                } elseif ($s->contre > $s->pour) {
                    $resultat = 'Rejeté';
                } else {
                    $resultat = 'Égalité';
                }
            }
            
            return [
                'uid' => $s->uid,
                'numero' => $s->numero,
                'titre' => $s->titre,
                'objet' => $s->objet,
                'date' => $s->date_scrutin?->format('d/m/Y'),
                'pour' => $s->pour,
                'contre' => $s->contre,
                'abstentions' => $s->abstentions,
                'resultat_code' => $s->resultat_code,
                'resultat_libelle' => $resultat,
            ];
        });

        return Inertia::render('Legislation/ScrutinsIndex', [
            'scrutins' => $scrutinsData,
            'stats' => $stats,
            'filters' => $request->only(['search', 'legislature']),
        ]);
    }

    /**
     * Comparaison vote AN vs vote citoyen
     * 
     * GET /legislation/scrutins/{uid}/comparaison
     */
    public function comparaisonVote(string $uid): Response
    {
        $scrutin = ScrutinAN::findOrFail($uid);

        // Chercher un ballot citoyen lié à ce scrutin
        $ballot = null;
        $ballotData = null;
        
        // Chercher via un topic lié au scrutin
        $topic = \App\Models\Topic::where('scrutin_an_uid', $uid)
            ->where('ballot_type', '!=', null)
            ->first();

        if ($topic) {
            // Récupérer les résultats du vote citoyen
            $ballotService = app(\App\Services\BallotService::class);
            $results = $ballotService->getResults($topic);
            
            $ballotData = [
                'topic_id' => $topic->id,
                'votes_count' => $results['total_votes'] ?? 0,
                'pour_count' => $results['pour'] ?? 0,
                'contre_count' => $results['contre'] ?? 0,
                'abstention_count' => $results['abstention'] ?? 0,
            ];
        }

        // Calcul de la concordance (si ballot existe)
        $concordance = [
            'score' => 0,
            'message' => 'Aucun vote citoyen disponible',
        ];

        if ($ballotData && $ballotData['votes_count'] > 0) {
            // Calculer le score de concordance
            $anPourPercent = ($scrutin->pour / max($scrutin->nombre_votants, 1)) * 100;
            $citoyenPourPercent = ($ballotData['pour_count'] / max($ballotData['votes_count'], 1)) * 100;
            
            $anContrePercent = ($scrutin->contre / max($scrutin->nombre_votants, 1)) * 100;
            $citoyenContrePercent = ($ballotData['contre_count'] / max($ballotData['votes_count'], 1)) * 100;
            
            // Score = 100 - moyenne des écarts absolus
            $ecartPour = abs($anPourPercent - $citoyenPourPercent);
            $ecartContre = abs($anContrePercent - $citoyenContrePercent);
            $ecartMoyen = ($ecartPour + $ecartContre) / 2;
            
            $score = max(0, 100 - $ecartMoyen);
            
            $concordance = [
                'score' => round($score, 1),
                'message' => $score >= 80 
                    ? 'Forte concordance entre l\'AN et les citoyens' 
                    : ($score >= 60 
                        ? 'Concordance modérée' 
                        : 'Divergence significative'),
            ];
        }

        return Inertia::render('Legislation/ComparaisonVote', [
            'scrutin' => [
                'uid' => $scrutin->uid,
                'numero' => $scrutin->numero,
                'titre' => $scrutin->titre,
                'objet' => $scrutin->objet,
                'date' => $scrutin->date_scrutin?->format('d/m/Y'),
                'nombre_pour' => $scrutin->pour,
                'nombre_contre' => $scrutin->contre,
                'nombre_abstention' => $scrutin->abstentions,
                'nombre_votants' => $scrutin->nombre_votants,
                'resultat_libelle' => $scrutin->resultat_libelle,
            ],
            'ballot' => $ballotData,
            'concordance' => $concordance,
            'stats' => [
                'an_participation' => $scrutin->nombre_votants,
                'citoyen_participation' => $ballotData['votes_count'] ?? 0,
            ],
        ]);
    }

    /**
     * Afficher un scrutin détaillé
     * 
     * GET /legislation/scrutins/{uid}
     */
    public function showScrutin(string $uid): Response
    {
        $scrutin = ScrutinAN::findOrFail($uid);
        $groupeService = app(GroupeParlementaireService::class);

        // Récupérer TOUS les votes individuels
        $allVotes = VoteIndividuelAN::where('scrutin_ref', $uid)
            ->with(['acteur', 'groupe'])
            ->get();

        // Calculer les totaux réels depuis les votes individuels
        $totalPour = $allVotes->where('position', 'pour')->count();
        $totalContre = $allVotes->where('position', 'contre')->count();
        $totalAbstention = $allVotes->where('position', 'abstention')->count();
        $totalVotants = $allVotes->count();

        // Votes par groupe
        $votesParGroupe = $allVotes
            ->groupBy('groupe_ref')
            ->map(function ($votes, $groupeRef) use ($groupeService) {
                $groupe = $votes->first()->groupe;
                $sigle = $groupe?->libelleAbrev ?? 'NI';
                
                return [
                    'sigle' => $sigle,
                    'nom' => $groupe?->libelle ?? 'Non-inscrits',
                    'couleur' => $groupeService->getCouleurGroupe($sigle),
                    'total_votes' => $votes->count(),
                    'pour' => $votes->where('position', 'pour')->count(),
                    'contre' => $votes->where('position', 'contre')->count(),
                    'abstention' => $votes->where('position', 'abstention')->count(),
                ];
            })
            ->values();

        // TOUS les députés ayant voté (pas de limite)
        $deputesAyantVote = $allVotes->map(fn($vote) => [
            'uid' => $vote->acteur->uid,
            'nom_complet' => $vote->acteur->prenom . ' ' . $vote->acteur->nom,
            'photo_url' => $vote->acteur->photo_url,
            'position' => $vote->position,
        ]);

        return Inertia::render('Legislation/ScrutinShow', [
            'scrutin' => [
                'uid' => $scrutin->uid,
                'numero' => $scrutin->numero,
                'titre' => $scrutin->titre,
                'objet' => $scrutin->objet,
                'date' => $scrutin->date_scrutin?->format('d/m/Y'),
                'moment_scrutin' => $scrutin->moment_scrutin,
                // Utiliser les totaux calculés depuis les votes individuels
                'nombre_pour' => $totalPour,
                'nombre_contre' => $totalContre,
                'nombre_abstention' => $totalAbstention,
                'nombre_votants' => $totalVotants,
                'suffrages_exprimes' => $totalPour + $totalContre,
                'legislature' => $scrutin->legislature,
                'resultat_libelle' => $scrutin->resultat_libelle,
                // Pourcentages
                'pour_percent' => $totalVotants > 0 
                    ? round(($totalPour / $totalVotants) * 100, 1) 
                    : 0,
                'contre_percent' => $totalVotants > 0 
                    ? round(($totalContre / $totalVotants) * 100, 1) 
                    : 0,
                'abstention_percent' => $totalVotants > 0 
                    ? round(($totalAbstention / $totalVotants) * 100, 1) 
                    : 0,
                'participation_percent' => $totalVotants > 0 
                    ? round(($totalVotants / 577) * 100, 1) // 577 députés
                    : 0,
            ],
            'votes_par_groupe' => $votesParGroupe,
            'deputes_ayant_vote' => $deputesAyantVote,
        ]);
    }

    /**
     * Afficher un amendement détaillé
     * 
     * GET /legislation/amendements/{uid}
     */
    public function showAmendement(string $uid): Response
    {
        $amendement = AmendementAN::with([
            'acteur',
            'dossier',
            'texte',
        ])->findOrFail($uid);

        // Co-signataires (si disponibles dans JSON)
        $coSignataires = [];
        if ($amendement->co_signataires && is_array($amendement->co_signataires)) {
            $coSignataires = collect($amendement->co_signataires)->map(fn($cs) => [
                'uid' => $cs['uid'] ?? null,
                'nom_complet' => $cs['nom_complet'] ?? $cs['nom'] ?? 'Inconnu',
                'photo_url' => $cs['photo_url'] ?? null,
            ]);
        }

        return Inertia::render('Legislation/AmendementShow', [
            'amendement' => [
                'uid' => $amendement->uid,
                'numero' => $amendement->numero,
                'sort' => $amendement->sort,
                'dispositif' => $amendement->dispositif,
                'expose_sommaire' => $amendement->expose_sommaire,
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'date_sort' => $amendement->date_sort?->format('d/m/Y'),
                'auteur' => $amendement->acteur ? [
                    'uid' => $amendement->acteur->uid,
                    'nom_complet' => $amendement->acteur->nom_complet,
                    'photo_url' => $amendement->acteur->photo_wikipedia_url,
                    'groupe' => $amendement->acteur->groupe_politique_actuel ? [
                        'nom' => $amendement->acteur->groupe_politique_actuel->libelle,
                        'sigle' => $amendement->acteur->groupe_politique_actuel->libelle_abrege,
                    ] : null,
                ] : null,
                'dossier' => $amendement->dossier ? [
                    'uid' => $amendement->dossier->uid,
                    'titre' => $amendement->dossier->titre,
                    'titre_court' => $amendement->dossier->titre_court,
                ] : null,
                'texte' => $amendement->texte ? [
                    'uid' => $amendement->texte->uid,
                    'titre' => $amendement->texte->titre,
                    'titre_court' => $amendement->texte->titre_court,
                ] : null,
                'co_signataires' => $coSignataires,
            ],
        ]);
    }

    /**
     * Afficher un dossier législatif
     * 
     * GET /legislation/dossiers/{uid}
     */
    public function showDossier(string $uid): Response
    {
        $dossier = DossierLegislatifAN::with([
            'textes' => fn($q) => $q->orderBy('date_depot', 'desc'),
        ])->findOrFail($uid);

        // Chercher le dossier Sénat correspondant (s'il existe)
        $dossierSenat = \App\Models\DossierLegislatifSenat::where('dossier_an_uid', $uid)
            ->first();

        // Récupérer les scrutins liés via les textes
        $textesUids = $dossier->textes->pluck('uid');
        $scrutins = ScrutinAN::whereIn('texte_ref', $textesUids)
            ->orderBy('date_scrutin', 'desc')
            ->get();

        // Amendements (limités pour performance)
        $amendements = AmendementAN::whereIn('texte_legislatif_ref', $textesUids)
            ->with('auteurActeur')
            ->orderBy('date_depot', 'desc')
            ->limit(20)
            ->get();

        // Stats complètes
        $stats = [
            'textes' => $dossier->textes->count(),
            'scrutins' => $scrutins->count(),
            'amendements' => AmendementAN::whereIn('texte_legislatif_ref', $textesUids)->count(),
            'votes_deputes' => VoteIndividuelAN::whereIn('scrutin_ref', $scrutins->pluck('uid'))->count(),
        ];

        // Timeline bicamérale
        $timeline = [];

        // Étapes AN
        if ($dossier->date_depot) {
            $timeline[] = [
                'date' => $dossier->date_depot->format('Y-m-d'),
                'date_display' => $dossier->date_depot->format('d/m/Y'),
                'chambre' => 'AN',
                'etape' => 'Dépôt à l\'Assemblée Nationale',
                'icon' => '🏛️',
                'color' => 'blue',
            ];
        }

        // Étapes Sénat (si lié)
        if ($dossierSenat) {
            if ($dossierSenat->date_depot) {
                $timeline[] = [
                    'date' => $dossierSenat->date_depot->format('Y-m-d'),
                    'date_display' => $dossierSenat->date_depot->format('d/m/Y'),
                    'chambre' => 'Sénat',
                    'etape' => 'Dépôt au Sénat',
                    'icon' => '🏰',
                    'color' => 'purple',
                ];
            }

            if ($dossierSenat->date_adoption_senat) {
                $timeline[] = [
                    'date' => $dossierSenat->date_adoption_senat->format('Y-m-d'),
                    'date_display' => $dossierSenat->date_adoption_senat->format('d/m/Y'),
                    'chambre' => 'Sénat',
                    'etape' => 'Adoption par le Sénat',
                    'icon' => '✅',
                    'color' => 'green',
                ];
            }

            if ($dossierSenat->date_promulgation) {
                $timeline[] = [
                    'date' => $dossierSenat->date_promulgation->format('Y-m-d'),
                    'date_display' => $dossierSenat->date_promulgation->format('d/m/Y'),
                    'chambre' => 'République',
                    'etape' => 'Promulgation',
                    'icon' => '🇫🇷',
                    'color' => 'yellow',
                    'detail' => $dossierSenat->numero_loi ? "Loi n° {$dossierSenat->numero_loi}" : null,
                ];
            }
        }

        // Trier la timeline par date
        usort($timeline, fn($a, $b) => strcmp($a['date'], $b['date']));

        return Inertia::render('Legislation/DossierShow', [
            'dossier' => [
                'uid' => $dossier->uid,
                'titre' => $dossier->titre,
                'titre_court' => $dossier->titre_court,
                'legislature' => $dossier->legislature,
                'date_depot' => $dossier->date_depot?->format('d/m/Y'),
                'etat' => $dossier->etat,
                'etat_libelle' => $dossier->etat_libelle,
                'resume' => $dossier->resume,
                'has_dossier_senat' => !is_null($dossierSenat),
            ],
            'dossierSenat' => $dossierSenat ? [
                'numero' => $dossierSenat->numero_senat,
                'statut' => $dossierSenat->statut,
                'url' => $dossierSenat->url_senat,
            ] : null,
            'timeline' => $timeline,
            'textes' => $dossier->textes->map(fn($texte) => [
                'uid' => $texte->uid,
                'titre' => $texte->titre,
                'titre_court' => $texte->titre_court,
                'type' => $texte->type_libelle,
                'date_depot' => $texte->date_depot?->format('d/m/Y'),
                'amendements_count' => AmendementAN::where('texte_legislatif_ref', $texte->uid)->count(),
            ]),
            'scrutins' => $scrutins->map(fn($scrutin) => [
                'uid' => $scrutin->uid,
                'numero' => $scrutin->numero,
                'titre' => $scrutin->titre,
                'date' => $scrutin->date_scrutin?->format('d/m/Y'),
                'pour' => $scrutin->pour,
                'contre' => $scrutin->contre,
                'abstentions' => $scrutin->abstentions,
                'resultat_libelle' => $scrutin->resultat_libelle,
            ]),
            'amendements' => $amendements->map(fn($amendement) => [
                'uid' => $amendement->uid,
                'numero_long' => $amendement->numero_long,
                'dispositif' => $amendement->dispositif,
                'etat' => $amendement->etat_code,
                'etat_libelle' => $amendement->etat_libelle,
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'auteur' => $amendement->auteurActeur ? [
                    'uid' => $amendement->auteurActeur->uid,
                    'nom_complet' => $amendement->auteurActeur->nom_complet,
                ] : null,
            ]),
            'stats' => $stats,
        ]);
    }

    /**
     * Afficher un texte législatif
     * 
     * GET /legislation/textes/{uid}
     */
    public function showTexte(string $uid): Response
    {
        $texte = TexteLegislatifAN::with([
            'dossier',
            'amendements' => fn($q) => $q->orderBy('date_depot', 'desc')->with('acteur'),
        ])->findOrFail($uid);

        $stats = [
            'amendements_count' => $texte->amendements->count(),
            'amendements_adoptes_count' => $texte->amendements()->adoptes()->count(),
            'taux_adoption' => $texte->amendements->count() > 0 
                ? round(($texte->amendements()->adoptes()->count() / $texte->amendements->count()) * 100, 1)
                : 0,
        ];

        return Inertia::render('Legislation/TexteShow', [
            'texte' => [
                'uid' => $texte->uid,
                'titre' => $texte->titre,
                'titre_court' => $texte->titre_court,
                'type' => $texte->type,
                'date_depot' => $texte->date_depot?->format('d/m/Y'),
                'legislature' => $texte->legislature,
                'dossier' => $texte->dossier ? [
                    'uid' => $texte->dossier->uid,
                    'titre' => $texte->dossier->titre,
                    'titre_court' => $texte->dossier->titre_court,
                ] : null,
            ],
            'amendements' => $texte->amendements->map(fn($amendement) => [
                'uid' => $amendement->uid,
                'numero' => $amendement->numero,
                'sort' => $amendement->sort,
                'dispositif' => $amendement->dispositif,
                'date_depot' => $amendement->date_depot?->format('d/m/Y'),
                'auteur' => $amendement->acteur ? [
                    'uid' => $amendement->acteur->uid,
                    'nom_complet' => $amendement->acteur->nom_complet,
                ] : null,
                'co_signataires_count' => $amendement->co_signataires ? count($amendement->co_signataires) : 0,
            ]),
            'statistiques' => $stats,
        ]);
    }
}

