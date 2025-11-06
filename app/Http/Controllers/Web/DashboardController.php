<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\PropositionLoi;
use App\Models\VotePropositionLoi;
use App\Models\UserAllocation;
use App\Models\TopicBallot;
use App\Models\GroupeParlementaire;
use App\Models\VoteLegislatif;
use App\Models\DeputeSenateur;
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
        $trendingTopics = Topic::with(['author:id,name'])
            ->withCount(['posts', 'views'])
            ->whereIn('status', ['open', 'published']) // Inclure open ET published
            ->orderByDesc('views_count')
            ->orderByDesc('created_at') // Tri secondaire par date si même nb de vues
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

        // 🗳️ VOTES EN COURS (topics avec scrutin actif)
        $votesEnCours = Topic::where('has_ballot', true)
            ->where('voting_opens_at', '<=', now())
            ->where('voting_deadline_at', '>', now())
            ->where('status', 'open')
            ->orderBy('voting_deadline_at', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($topic) use ($user) {
                $totalVotes = $topic->ballots()->count();
                $hasVoted = false;
                
                if ($user) {
                    // Vérifier si l'utilisateur a un token consommé pour ce topic
                    $hasVoted = $topic->ballotTokens()
                        ->where('user_id', $user->id)
                        ->where('consumed', true)
                        ->exists();
                }
                
                return [
                    'id' => $topic->id,
                    'topic_id' => $topic->id,
                    'topic_titre' => $topic->title,
                    'question' => $topic->title,
                    'type' => $topic->ballot_type ?? 'yes_no',
                    'fin' => $topic->voting_deadline_at->diffForHumans(),
                    'fin_date' => $topic->voting_deadline_at->format('d/m/Y H:i'),
                    'a_vote' => $hasVoted,
                    'total_votes' => $totalVotes,
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
            'total_topics' => Topic::whereIn('status', ['open', 'published'])->count(),
            'total_votes' => TopicBallot::count(), // Total des bulletins de vote émis
            'total_propositions' => PropositionLoi::count(),
            'total_users_allocated' => UserAllocation::distinct('user_id')->count('user_id'),
        ];

        // 🎯 ACTIVITÉ RÉCENTE DE L'UTILISATEUR
        $userActivity = [
            'derniers_topics' => Topic::where('author_id', $user->id)
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

        // 🏛️ GROUPES PARLEMENTAIRES (top 5 par nombre de députés)
        $groupesParlementaires = GroupeParlementaire::where('source', 'assemblee')
            ->where('actif', true)
            ->withCount('deputes')
            ->orderByDesc('deputes_count')
            ->orderByDesc('nombre_membres') // Fallback si pas de députés liés
            ->limit(5)
            ->get()
            ->map(fn($groupe) => [
                'id' => $groupe->id,
                'nom' => $groupe->nom,
                'sigle' => $groupe->sigle,
                'couleur' => $groupe->couleur_hex ?? '#6B7280',
                'nb_deputes' => $groupe->deputes_count > 0 ? $groupe->deputes_count : $groupe->nombre_membres,
            ]);

        // 📊 VOTES LÉGISLATIFS RÉCENTS (5 derniers)
        $votesLegislatifs = VoteLegislatif::with('proposition:id,numero,titre')
            ->orderByDesc('date_vote')
            ->limit(5)
            ->get()
            ->map(fn($vote) => [
                'id' => $vote->id,
                'titre' => $vote->titre,
                'proposition_numero' => $vote->proposition?->numero,
                'proposition_titre' => $vote->proposition?->titre,
                'date' => $vote->date_vote->format('d/m/Y'),
                'pour' => $vote->pour,
                'contre' => $vote->contre,
                'abstention' => $vote->abstention,
                'resultat' => $vote->pour > $vote->contre ? 'adopté' : 'rejeté',
            ]);

        // 📍 MES REPRÉSENTANTS (député + sénateurs si localisation configurée)
        $mesRepresentants = [
            'hasLocation' => false,
            'depute' => null,
            'senateurs' => [],
        ];

        $profile = $user->profile;
        if ($profile && $profile->circonscription) {
            $mesRepresentants['hasLocation'] = true;

            // Député de la circonscription
            $depute = DeputeSenateur::deputes()
                ->enExercice()
                ->where('circonscription', $profile->circonscription)
                ->with(['groupeParlementaire'])
                ->first();

            if ($depute) {
                $mesRepresentants['depute'] = [
                    'id' => $depute->id,
                    'nom_complet' => $depute->nom_complet,
                    'photo_url' => $depute->photo_url,
                    'groupe_sigle' => $depute->groupe_sigle,
                    'groupe_couleur' => $depute->groupeParlementaire?->couleur_hex ?? '#6B7280',
                    'circonscription' => $depute->circonscription,
                ];
            }

            // Sénateurs du département
            $deptCode = substr($profile->circonscription, 0, 2);
            $senateurs = DeputeSenateur::senateurs()
                ->enExercice()
                ->where('circonscription', 'like', $deptCode . '%')
                ->with(['groupeParlementaire'])
                ->limit(3)
                ->get();

            $mesRepresentants['senateurs'] = $senateurs->map(fn($senateur) => [
                'id' => $senateur->id,
                'nom_complet' => $senateur->nom_complet,
                'photo_url' => $senateur->photo_url,
                'groupe_sigle' => $senateur->groupe_sigle,
                'groupe_couleur' => $senateur->groupeParlementaire?->couleur_hex ?? '#6B7280',
            ])->toArray();
        }

        return Inertia::render('Dashboard', [
            'trendingTopics' => $trendingTopics,
            'propositionsLegislatives' => $propositionsLegislatives,
            'votesEnCours' => $votesEnCours,
            'budgetStats' => $budgetStats,
            'globalStats' => $globalStats,
            'userActivity' => $userActivity,
            'groupesParlementaires' => $groupesParlementaires,
            'votesLegislatifs' => $votesLegislatifs,
            'mesRepresentants' => $mesRepresentants,
        ]);
    }
}

