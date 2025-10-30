<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\PropositionLoi;
use App\Models\VotePropositionLoi;
use App\Models\Ballot;
use App\Models\UserAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Affiche le dashboard principal
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 🔥 SUJETS TENDANCES (5 derniers topics populaires)
        $trendingTopics = Topic::with(['author:id,name', 'territory'])
            ->withCount(['posts', 'views'])
            ->where('status', 'published')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get()
            ->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'titre' => $topic->title,
                    'auteur' => $topic->author->name ?? 'Anonyme',
                    'type' => $topic->type,
                    'scope' => $topic->scope,
                    'territoire' => $topic->territory?->name ?? 'National',
                    'nb_posts' => $topic->posts_count,
                    'nb_vues' => $topic->views_count,
                    'created_at' => $topic->created_at->diffForHumans(),
                ];
            });

        // 🏛️ PROPOSITIONS DE LOI TENDANCES (par score de vote)
        $propositionsLegislatives = PropositionLoi::orderByDesc('score_vote')
            ->limit(5)
            ->get()
            ->map(function ($prop) {
                $stats = VotePropositionLoi::getPropositionStats($prop->id);
                return [
                    'id' => $prop->id,
                    'numero' => $prop->numero,
                    'titre' => $prop->titre,
                    'source' => $prop->source,
                    'statut' => $prop->statut,
                    'date_depot' => $prop->date_depot?->format('d/m/Y'),
                    'votes_stats' => $stats,
                ];
            });

        // 🗳️ VOTES EN COURS (ballots actifs)
        $votesEnCours = Ballot::with(['topic:id,title'])
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->orderBy('end_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($ballot) use ($user) {
                $hasVoted = $ballot->votes()->where('user_id', $user->id)->exists();
                $results = $ballot->getResults();
                
                return [
                    'id' => $ballot->id,
                    'topic_id' => $ballot->topic_id,
                    'topic_titre' => $ballot->topic->title ?? 'Sans titre',
                    'question' => $ballot->question,
                    'type' => $ballot->type,
                    'fin' => $ballot->end_date->diffForHumans(),
                    'fin_date' => $ballot->end_date->format('d/m/Y H:i'),
                    'a_vote' => $hasVoted,
                    'total_votes' => $results['total_votes'] ?? 0,
                ];
            });

        // 💰 BUDGET - Statistiques utilisateur
        $budgetStats = [
            'has_allocated' => false,
            'total_allocated' => 0,
            'nb_sectors' => 0,
            'top_sector' => null,
        ];

        if ($user) {
            $allocations = UserAllocation::where('user_id', $user->id)
                ->with('sector:id,name')
                ->get();

            if ($allocations->isNotEmpty()) {
                $budgetStats['has_allocated'] = true;
                $budgetStats['total_allocated'] = $allocations->sum('amount');
                $budgetStats['nb_sectors'] = $allocations->count();
                
                $topAllocation = $allocations->sortByDesc('amount')->first();
                $budgetStats['top_sector'] = [
                    'name' => $topAllocation->sector->name ?? 'Inconnu',
                    'amount' => $topAllocation->amount,
                    'percentage' => $budgetStats['total_allocated'] > 0 
                        ? round(($topAllocation->amount / $budgetStats['total_allocated']) * 100, 1) 
                        : 0,
                ];
            }
        }

        // 📊 STATISTIQUES GLOBALES
        $globalStats = [
            'total_topics' => Topic::where('status', 'published')->count(),
            'total_votes' => Ballot::where('status', 'active')->sum(
                DB::raw('(SELECT COUNT(*) FROM ballot_votes WHERE ballot_id = ballots.id)')
            ),
            'total_propositions' => PropositionLoi::count(),
            'total_users_allocated' => UserAllocation::distinct('user_id')->count('user_id'),
        ];

        // 🎯 ACTIVITÉ RÉCENTE DE L'UTILISATEUR
        $userActivity = [
            'derniers_topics' => Topic::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(['id', 'title', 'created_at'])
                ->map(fn($t) => [
                    'id' => $t->id,
                    'titre' => $t->title,
                    'date' => $t->created_at->diffForHumans(),
                ]),
            'derniers_votes_loi' => VotePropositionLoi::where('user_id', $user->id)
                ->with('proposition:id,numero,titre')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get()
                ->map(fn($v) => [
                    'id' => $v->proposition->id,
                    'numero' => $v->proposition->numero,
                    'titre' => $v->proposition->titre,
                    'type_vote' => $v->type_vote,
                    'date' => $v->created_at->diffForHumans(),
                ]),
        ];

        return Inertia::render('Dashboard', [
            'trendingTopics' => $trendingTopics,
            'propositionsLegislatives' => $propositionsLegislatives,
            'votesEnCours' => $votesEnCours,
            'budgetStats' => $budgetStats,
            'globalStats' => $globalStats,
            'userActivity' => $userActivity,
        ]);
    }
}

