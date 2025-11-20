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
     * Afficher un scrutin détaillé
     * 
     * GET /legislation/scrutins/{uid}
     */
    public function showScrutin(string $uid): Response
    {
        $scrutin = ScrutinAN::with(['votesIndividuels.acteur'])
            ->findOrFail($uid);

        $groupeService = app(GroupeParlementaireService::class);

        // Votes par groupe
        $votesParGroupe = VoteIndividuelAN::where('scrutin_ref', $uid)
            ->with(['acteur.mandats.organe'])
            ->get()
            ->groupBy(function ($vote) {
                return $vote->acteur->groupe_politique_actuel?->uid ?? 'NI';
            })
            ->map(function ($votes, $groupeUid) use ($groupeService) {
                $groupe = $votes->first()->acteur->groupe_politique_actuel;
                $sigle = $groupe?->libelle_abrege ?? 'NI';
                
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

        // Députés ayant voté (limité à 50 pour performance)
        $deputesAyantVote = VoteIndividuelAN::where('scrutin_ref', $uid)
            ->with('acteur')
            ->limit(50)
            ->get()
            ->map(fn($vote) => [
                'uid' => $vote->acteur->uid,
                'nom_complet' => $vote->acteur->nom_complet,
                'photo_url' => $vote->acteur->photo_wikipedia_url,
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
                'nombre_pour' => $scrutin->nombre_pour,
                'nombre_contre' => $scrutin->nombre_contre,
                'nombre_abstention' => $scrutin->nombre_abstention,
                'legislature' => $scrutin->legislature,
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
            'amendements' => fn($q) => $q->orderBy('date_depot', 'desc')->limit(20)->with('acteur'),
        ])->findOrFail($uid);

        $stats = [
            'textes_count' => $dossier->textes->count(),
            'amendements_count' => $dossier->amendements()->count(),
            'amendements_adoptes_count' => $dossier->amendements()->adoptes()->count(),
            'taux_adoption' => $dossier->amendements()->count() > 0 
                ? round(($dossier->amendements()->adoptes()->count() / $dossier->amendements()->count()) * 100, 1)
                : 0,
        ];

        return Inertia::render('Legislation/DossierShow', [
            'dossier' => [
                'uid' => $dossier->uid,
                'titre' => $dossier->titre,
                'titre_court' => $dossier->titre_court,
                'legislature' => $dossier->legislature,
            ],
            'textes' => $dossier->textes->map(fn($texte) => [
                'uid' => $texte->uid,
                'titre' => $texte->titre,
                'titre_court' => $texte->titre_court,
                'type' => $texte->type,
                'date_depot' => $texte->date_depot?->format('d/m/Y'),
            ]),
            'amendements' => $dossier->amendements->map(fn($amendement) => [
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

