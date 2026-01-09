<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\PropositionLoi;
use App\Models\VotePropositionLoi;
use App\Models\Loi;
use App\Models\CitizenLawStats;
use App\Models\UserAllocation;
use App\Models\TopicBallot;
use App\Models\GroupeParlementaire;
use App\Models\VoteLegislatif;
use App\Models\DeputeSenateur;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\ScrutinAN;
use App\Models\OrganeAN;
use App\Models\DashboardStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Affiche le dashboard principal
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 🔥 SUJETS TENDANCES (5 derniers topics populaires) - Cache 5 min
        $trendingTopics = Cache::remember('dashboard_trending_topics', 300, function () {
            return Topic::with(['author:id,name'])
                ->withCount('posts')
                ->whereIn('status', ['open', 'published'])
                ->orderByDesc('topics.views_count')
                ->orderByDesc('topics.created_at')
                ->limit(5)
                ->get()
                ->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'slug' => $topic->slug,
                        'titre' => $topic->title,
                        'auteur' => $topic->author->name ?? 'Anonyme',
                        'type' => $topic->idea_type ?? $topic->type,
                        'scope' => $topic->scope,
                        'territoire' => $topic->territory?->name ?? 'National',
                        'nb_posts' => $topic->posts_count,
                        'nb_vues' => $topic->views_count ?? 0,
                        'created_at' => $topic->created_at->diffForHumans(),
                    ];
                });
        });

        // 🏛️ LOIS TENDANCES (vraies lois avec votes citoyens) - Cache 10 min
        $propositionsLegislatives = Cache::remember('dashboard_lois_tendances', 600, function () {
            // Récupérer les lois les plus votées par les citoyens
            $loisStats = CitizenLawStats::orderByDesc('total_votes')
                ->limit(10)
                ->get();
            
            $loisCodes = $loisStats->pluck('loi_cod')->toArray();
            
            // Récupérer les détails des lois
            $lois = Loi::whereIn('loicod', $loisCodes)->get()->keyBy('loicod');
            
            $result = $loisStats->take(5)->map(function ($stats) use ($lois) {
                $loi = $lois[$stats->loi_cod] ?? null;
                if (!$loi) return null;
                
                // Déterminer la source (AN ou Sénat) selon le code de la loi
                $source = str_starts_with($stats->loi_cod, 'a') ? 'assemblee' : 'senat';
                
                return [
                    'id' => $stats->id,
                    'loicod' => trim($stats->loi_cod),
                    'numero' => $loi->numero ?? substr(trim($stats->loi_cod), -4),
                    'titre' => $loi->loitit ?: $loi->loiint ?: 'Loi ' . trim($stats->loi_cod),
                    'source' => $source,
                    'statut' => $loi->etat?->etaloilib ?? 'En cours',
                    'date_depot' => null,
                    'votes_stats' => [
                        'upvotes' => $stats->votes_pour,
                        'downvotes' => $stats->votes_contre,
                        'score' => $stats->score ?? ($stats->votes_pour - $stats->votes_contre),
                        'pourcentage_pour' => $stats->total_votes > 0 
                            ? round(($stats->votes_pour / $stats->total_votes) * 100) 
                            : 50,
                    ],
                ];
            })->filter()->values();
            
            // Si pas assez de lois votées, compléter avec des lois récentes importantes
            if ($result->count() < 5) {
                $existingCodes = $result->pluck('loicod')->toArray();
                
                $loisRecentes = Loi::whereNotNull('loitit')
                    ->whereNotIn('loicod', $existingCodes)
                    ->whereHas('etat', fn($q) => $q->whereIn('etaloicod', ['PROMULGUE', 'ADOPDEF', 'ENCOURS']))
                    ->orderByDesc('date_loi')
                    ->limit(5 - $result->count())
                    ->get()
                    ->map(function ($loi) {
                        $source = str_starts_with($loi->loicod, 'a') ? 'assemblee' : 'senat';
                        
                        // Récupérer les stats de vote citoyen si elles existent
                        $stats = CitizenLawStats::where('loi_cod', $loi->loicod)->first();
                        
                        return [
                            'id' => trim($loi->loicod),
                            'loicod' => trim($loi->loicod),
                            'numero' => $loi->numero ?? substr(trim($loi->loicod), -4),
                            'titre' => $loi->loitit ?: $loi->loiint,
                            'source' => $source,
                            'statut' => $loi->etat?->etaloilib ?? 'En cours',
                            'date_depot' => null,
                            'votes_stats' => [
                                'upvotes' => $stats?->votes_pour ?? 0,
                                'downvotes' => $stats?->votes_contre ?? 0,
                                'score' => $stats?->score ?? 0,
                                'pourcentage_pour' => $stats && $stats->total_votes > 0 
                                    ? round(($stats->votes_pour / $stats->total_votes) * 100) 
                                    : 50,
                            ],
                        ];
                    });
                
                $result = $result->concat($loisRecentes);
            }
            
            return $result->take(5);
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
                    'topic_slug' => $topic->slug,
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

        // 📊 STATISTIQUES GLOBALES - Cache 10 min
        $globalStats = Cache::remember('dashboard_global_stats', 600, function () {
            return [
                'total_topics' => Topic::whereIn('status', ['open', 'published'])->count(),
                'total_votes' => TopicBallot::count(),
                'total_propositions' => PropositionLoi::count(),
                'total_users_allocated' => UserAllocation::distinct('user_id')->count('user_id'),
            ];
        });

        // 🎯 ACTIVITÉ RÉCENTE DE L'UTILISATEUR
        $userActivity = [
            'derniers_topics' => $user ? Topic::where('author_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(['id', 'slug', 'title', 'created_at'])
                ->map(fn($t) => [
                    'id' => $t->id,
                    'slug' => $t->slug,
                    'titre' => $t->title,
                    'date' => $t->created_at->diffForHumans(),
                ]) : collect([]),
            'derniers_votes_loi' => $user ? VotePropositionLoi::where('user_id', $user->id)
                ->with('proposition:id,numero,titre')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get()
                ->map(fn($v) => [
                    'id' => $v->proposition?->id,
                    'numero' => $v->proposition?->numero,
                    'titre' => $v->proposition?->titre,
                    'type_vote' => $v->type_vote,
                    'date' => $v->created_at->diffForHumans(),
                ]) : collect([]),
        ];

        // 🏛️ GROUPES PARLEMENTAIRES (top 5 par nombre de députés) - Cache 1h
        $groupesParlementaires = Cache::remember('dashboard_groupes_parl', 3600, function () {
            return GroupeParlementaire::where('source', 'assemblee')
                ->where('actif', true)
                ->orderByDesc('nombre_membres')
                ->limit(5)
                ->get()
                ->map(fn($groupe) => [
                    'id' => $groupe->id,
                    'nom' => $groupe->nom,
                    'sigle' => $groupe->sigle,
                    'couleur' => $groupe->couleur_hex ?? '#6B7280',
                    'nb_deputes' => $groupe->nombre_membres,
                ]);
        });

        // 📊 STATISTIQUES PRÉ-CALCULÉES (table dashboard_stats)
        // Ces stats sont mises à jour quotidiennement via: php artisan dashboard:calculate-stats
        $derniersScrutins = collect(DashboardStat::get('derniers_scrutins', []))->take(5);
        $topDeputes = collect(DashboardStat::get('top_deputes', []))->take(5);
        $topSenateurs = collect(DashboardStat::get('top_senateurs', []))->take(5);
        $groupesActifs = collect(DashboardStat::get('groupes_actifs', []))->take(5);

        // 📅 PROCHAINES RÉUNIONS (live - cache 30 min)
        $prochainesReunions = Cache::remember('dashboard_prochaines_reunions', 1800, function () {
            return \App\Models\ReunionAN::with('organe:uid,libelle,libelle_abrege')
                ->aVenir()
                ->orderBy('date_debut')
                ->limit(5)
                ->get()
                ->map(fn($r) => [
                    'uid' => $r->uid,
                    'titre' => $r->titre_odj ?? $r->organe_nom ?? 'Réunion',
                    'type' => $r->type_reunion,
                    'emoji' => $r->emoji_type,
                    'date' => $r->date_debut?->format('d/m H:i'),
                    'date_relative' => $r->date_debut?->diffForHumans(),
                    'organe' => $r->organe?->libelle_abrege ?? $r->organe?->libelle,
                    'organe_couleur' => '#6B7280', // Couleur par défaut
                    'etat' => $r->etat,
                ]);
        });

        // Fallback pour votes législatifs si pas de scrutins
        $votesLegislatifs = $derniersScrutins->isEmpty() 
            ? VoteLegislatif::with('proposition:id,numero,titre')
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
                ])
            : collect([]);

        // 📍 MES REPRÉSENTANTS (député + sénateurs si localisation configurée)
        $mesRepresentants = [
            'hasLocation' => false,
            'depute' => null,
            'senateurs' => [],
        ];

        $profile = $user?->profile;
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
            'derniersScrutins' => $derniersScrutins,
            'topDeputes' => $topDeputes,
            'topSenateurs' => $topSenateurs,
            'groupesActifs' => $groupesActifs,
            'prochainesReunions' => $prochainesReunions,
        ]);
    }
}

