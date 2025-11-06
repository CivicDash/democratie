<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PropositionLoi;
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
}

