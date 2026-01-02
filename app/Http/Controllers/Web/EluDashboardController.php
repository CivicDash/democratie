<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TopicElu;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard pour les élus vérifiés
 * Permet de gérer les interpellations et répondre aux citoyens
 */
class EluDashboardController extends Controller
{
    /**
     * Dashboard élu - Vue d'ensemble
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur est un élu vérifié
        if (!$user->isVerifiedElu()) {
            abort(403, 'Accès réservé aux élus vérifiés.');
        }

        // Statistiques de base
        $total = $this->getInterpellationsQuery($user)->count();
        $pending = $this->getInterpellationsQuery($user)->where('response_status', 'pending')->count();
        $answered = $this->getInterpellationsQuery($user)->where('response_status', 'answered')->count();
        $declined = $this->getInterpellationsQuery($user)->where('response_status', 'declined')->count();
        
        // Calcul du délai moyen de réponse (en jours)
        $avgResponseTime = $this->getInterpellationsQuery($user)
            ->whereNotNull('answered_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (answered_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');
        
        // % de réponse
        $responseRate = $total > 0 ? round(($answered / $total) * 100, 1) : 0;
        
        // Interpellations cette semaine
        $thisWeek = $this->getInterpellationsQuery($user)
            ->where('created_at', '>=', now()->subWeek())
            ->count();
        
        $stats = [
            'total_interpellations' => $total,
            'pending' => $pending,
            'answered' => $answered,
            'declined' => $declined,
            'views' => $this->getInterpellationsQuery($user)->whereNotNull('viewed_at')->count(),
            'avg_response_days' => $avgResponseTime ? round($avgResponseTime, 1) : null,
            'response_rate' => $responseRate,
            'this_week' => $thisWeek,
        ];

        // Dernières interpellations non répondues
        $pendingInterpellations = $this->getInterpellationsQuery($user)
            ->where('response_status', 'pending')
            ->with(['topic:id,slug,title,idea_type,votes_pour,votes_contre,published_at', 'topic.author:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($i) => $this->formatInterpellation($i));

        // Dernières réponses
        $recentResponses = $this->getInterpellationsQuery($user)
            ->where('response_status', 'answered')
            ->with(['topic:id,slug,title,idea_type'])
            ->orderByDesc('answered_at')
            ->limit(5)
            ->get()
            ->map(fn($i) => $this->formatInterpellation($i));

        return Inertia::render('Elu/Dashboard', [
            'stats' => $stats,
            'pendingInterpellations' => $pendingInterpellations,
            'recentResponses' => $recentResponses,
            'eluData' => $user->elu_data ? [
                'nom_complet' => $user->elu_data->nom_complet ?? null,
                'photo_url' => $user->elu_data->photo_url ?? null,
                'type' => $user->elu_type,
            ] : null,
        ]);
    }

    /**
     * Liste des interpellations avec filtres
     */
    public function interpellations(Request $request): Response
    {
        $user = Auth::user();
        
        if (!$user->isVerifiedElu()) {
            abort(403, 'Accès réservé aux élus vérifiés.');
        }

        $query = $this->getInterpellationsQuery($user)
            ->with(['topic:id,slug,title,idea_type,description,votes_pour,votes_contre,published_at', 'topic.author:id,name']);

        // Filtres
        if ($request->filled('status')) {
            $query->where('response_status', $request->status);
        }

        // Tri
        $sort = $request->get('sort', 'recent');
        match ($sort) {
            'popular' => $query->join('topics', 'topics.id', '=', 'topic_elus.topic_id')
                ->orderByDesc('topics.votes_pour'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $interpellations = $query->paginate(20)->withQueryString();

        return Inertia::render('Elu/Interpellations', [
            'interpellations' => $interpellations->through(fn($i) => $this->formatInterpellation($i)),
            'filters' => $request->only(['status', 'sort']),
            'stats' => [
                'total' => $this->getInterpellationsQuery($user)->count(),
                'pending' => $this->getInterpellationsQuery($user)->where('response_status', 'pending')->count(),
                'answered' => $this->getInterpellationsQuery($user)->where('response_status', 'answered')->count(),
            ],
        ]);
    }

    /**
     * Voir une interpellation spécifique
     */
    public function showInterpellation(TopicElu $interpellation): Response
    {
        $user = Auth::user();
        
        if (!$user->isVerifiedElu()) {
            abort(403, 'Accès réservé aux élus vérifiés.');
        }

        // Vérifier que cette interpellation appartient à cet élu
        if ($interpellation->elu_type !== $user->elu_type || $interpellation->elu_id !== $user->elu_ref) {
            abort(403, 'Cette interpellation ne vous est pas adressée.');
        }

        // Marquer comme vue
        if (!$interpellation->viewed_at) {
            $interpellation->update(['viewed_at' => now()]);
        }

        $interpellation->load(['topic.author:id,name', 'topic.topicTags', 'topic.posts.user:id,name']);

        return Inertia::render('Elu/InterpellationShow', [
            'interpellation' => $this->formatInterpellation($interpellation, true),
            'topic' => [
                'id' => $interpellation->topic->id,
                'slug' => $interpellation->topic->slug,
                'title' => $interpellation->topic->title,
                'description' => $interpellation->topic->description,
                'idea_type' => $interpellation->topic->idea_type,
                'idea_type_info' => $interpellation->topic->idea_type_info,
                'scope' => $interpellation->topic->scope,
                'scope_info' => $interpellation->topic->scope_info,
                'votes_pour' => $interpellation->topic->votes_pour,
                'votes_contre' => $interpellation->topic->votes_contre,
                'published_at' => $interpellation->topic->published_at?->toIso8601String(),
                'author' => $interpellation->topic->author ? [
                    'id' => $interpellation->topic->author->id,
                    'name' => $interpellation->topic->author->name,
                ] : null,
                'tags' => $interpellation->topic->topicTags->map(fn($t) => [
                    'id' => $t->id,
                    'nom' => $t->nom,
                    'icone' => $t->icone,
                ]),
                'url' => route('participation.ideas.show', $interpellation->topic->slug),
            ],
            'comments' => $interpellation->topic->posts->map(fn($p) => [
                'id' => $p->id,
                'content' => $p->content,
                'created_at' => $p->created_at->toIso8601String(),
                'user' => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name] : null,
            ]),
        ]);
    }

    /**
     * Répondre à une interpellation
     */
    public function respond(Request $request, TopicElu $interpellation)
    {
        $user = Auth::user();
        
        if (!$user->isVerifiedElu()) {
            abort(403, 'Accès réservé aux élus vérifiés.');
        }

        // Vérifier que cette interpellation appartient à cet élu
        if ($interpellation->elu_type !== $user->elu_type || $interpellation->elu_id !== $user->elu_ref) {
            abort(403, 'Cette interpellation ne vous est pas adressée.');
        }

        $validated = $request->validate([
            'response_content' => ['required', 'string', 'min:50', 'max:10000'],
        ]);

        $interpellation->update([
            'response_content' => $validated['response_content'],
            'response_status' => 'answered',
            'answered_at' => now(),
        ]);

        // TODO: Notifier l'auteur de l'interpellation

        return redirect()->route('elu.interpellations.show', $interpellation)
            ->with('success', 'Votre réponse a été publiée !');
    }

    /**
     * Refuser de répondre à une interpellation
     */
    public function decline(Request $request, TopicElu $interpellation)
    {
        $user = Auth::user();
        
        if (!$user->isVerifiedElu()) {
            abort(403, 'Accès réservé aux élus vérifiés.');
        }

        if ($interpellation->elu_type !== $user->elu_type || $interpellation->elu_id !== $user->elu_ref) {
            abort(403, 'Cette interpellation ne vous est pas adressée.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $interpellation->update([
            'response_status' => 'declined',
            'response_content' => $validated['reason'] ?? 'L\'élu a choisi de ne pas répondre.',
            'answered_at' => now(),
        ]);

        return redirect()->route('elu.interpellations')
            ->with('info', 'Interpellation déclinée.');
    }

    /**
     * Profil public de l'élu
     */
    public function publicProfile(string $type, string $ref): Response
    {
        // Trouver l'élu dans les données parlementaires
        $eluData = match ($type) {
            'depute' => \App\Models\ActeurAN::find($ref),
            'senateur' => \App\Models\Senateur::where('matricule', $ref)->first(),
            'maire' => \App\Models\Maire::find($ref),
            default => null,
        };

        if (!$eluData) {
            abort(404, 'Élu non trouvé.');
        }

        // Trouver le compte utilisateur lié (s'il existe)
        $userAccount = \App\Models\User::where('elu_type', $type)
            ->where('elu_ref', $ref)
            ->where('is_verified_elu', true)
            ->where('is_public_profile', true)
            ->first();

        // Interpellations publiques
        $interpellations = TopicElu::where('elu_type', $type)
            ->where('elu_id', $ref)
            ->where('is_interpellation', true)
            ->with(['topic:id,slug,title,idea_type,votes_pour,votes_contre,published_at', 'topic.author:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($i) => $this->formatInterpellation($i));

        // Stats
        $stats = [
            'total_interpellations' => TopicElu::where('elu_type', $type)->where('elu_id', $ref)->where('is_interpellation', true)->count(),
            'answered' => TopicElu::where('elu_type', $type)->where('elu_id', $ref)->where('response_status', 'answered')->count(),
        ];

        return Inertia::render('Elu/PublicProfile', [
            'elu' => [
                'type' => $type,
                'ref' => $ref,
                'nom_complet' => $eluData->nom_complet ?? trim(($eluData->prenom ?? '') . ' ' . ($eluData->nom ?? '')),
                'photo_url' => $eluData->photo_url ?? null,
                'groupe' => $eluData->groupe_politique ?? $eluData->groupe_politique_actuel?->libelle ?? null,
                'circonscription' => $eluData->circonscription ?? null,
            ],
            'userAccount' => $userAccount ? [
                'id' => $userAccount->id,
                'bio' => $userAccount->elu_bio,
                'twitter' => $userAccount->twitter_handle,
                'facebook' => $userAccount->facebook_url,
                'website' => $userAccount->website_url,
                'verified_at' => $userAccount->verified_at?->toIso8601String(),
            ] : null,
            'interpellations' => $interpellations,
            'stats' => $stats,
        ]);
    }

    /**
     * Page "Ma fiche" - Lien vers le profil public de l'élu
     */
    public function maFiche()
    {
        $user = Auth::user();
        
        if (!$user->isVerifiedElu()) {
            abort(403, 'Accès réservé aux élus vérifiés.');
        }

        // Rediriger vers le profil public correspondant
        return match ($user->elu_type) {
            'depute' => redirect()->route('representants.deputes.show', $user->elu_ref),
            'senateur' => redirect()->route('representants.senateurs.show', $user->elu_ref),
            'maire' => redirect()->route('collectivites.maires.show', $user->elu_ref),
            default => redirect()->route('elu.dashboard')->with('error', 'Type d\'élu non reconnu'),
        };
    }

    /**
     * Statistiques détaillées pour l'élu
     */
    public function stats(): Response
    {
        $user = Auth::user();
        
        if (!$user->isVerifiedElu()) {
            abort(403, 'Accès réservé aux élus vérifiés.');
        }

        // Statistiques globales
        $total = $this->getInterpellationsQuery($user)->count();
        $answered = $this->getInterpellationsQuery($user)->where('response_status', 'answered')->count();
        $declined = $this->getInterpellationsQuery($user)->where('response_status', 'declined')->count();
        $pending = $this->getInterpellationsQuery($user)->where('response_status', 'pending')->count();
        
        // Délai moyen de réponse
        $avgResponseTime = $this->getInterpellationsQuery($user)
            ->whereNotNull('answered_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (answered_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');
        
        // Évolution par mois (12 derniers mois)
        $evolutionByMonth = $this->getInterpellationsQuery($user)
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as mois, COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN response_status = 'answered' THEN 1 ELSE 0 END) as repondues")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();
        
        // Thématiques des interpellations (top 10)
        $topThemes = $this->getInterpellationsQuery($user)
            ->join('topics', 'topics.id', '=', 'topic_elus.topic_id')
            ->leftJoin('topic_tag', 'topic_tag.topic_id', '=', 'topics.id')
            ->leftJoin('tags', 'tags.id', '=', 'topic_tag.tag_id')
            ->whereNotNull('tags.nom')
            ->selectRaw('tags.nom as theme, COUNT(*) as count')
            ->groupBy('tags.nom')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        
        // Temps de réponse par période
        $responseTimeEvolution = $this->getInterpellationsQuery($user)
            ->whereNotNull('answered_at')
            ->where('answered_at', '>=', now()->subMonths(6))
            ->selectRaw("TO_CHAR(answered_at, 'YYYY-MM') as mois")
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (answered_at - created_at)) / 86400) as avg_days')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        return Inertia::render('Elu/Stats', [
            'globalStats' => [
                'total' => $total,
                'answered' => $answered,
                'declined' => $declined,
                'pending' => $pending,
                'response_rate' => $total > 0 ? round(($answered / $total) * 100, 1) : 0,
                'avg_response_days' => $avgResponseTime ? round($avgResponseTime, 1) : null,
            ],
            'evolutionByMonth' => $evolutionByMonth,
            'topThemes' => $topThemes,
            'responseTimeEvolution' => $responseTimeEvolution,
            'eluData' => $user->elu_data ? [
                'nom_complet' => $user->elu_data->nom_complet ?? null,
                'photo_url' => $user->elu_data->photo_url ?? null,
                'type' => $user->elu_type,
            ] : null,
        ]);
    }

    // ========================================================================
    // PRIVATE HELPERS
    // ========================================================================

    private function getInterpellationsQuery($user)
    {
        return TopicElu::where('elu_type', $user->elu_type)
            ->where('elu_id', $user->elu_ref)
            ->where('is_interpellation', true);
    }

    private function formatInterpellation(TopicElu $i, bool $full = false): array
    {
        $data = [
            'id' => $i->id,
            'response_status' => $i->response_status,
            'viewed_at' => $i->viewed_at?->toIso8601String(),
            'answered_at' => $i->answered_at?->toIso8601String(),
            'created_at' => $i->created_at->toIso8601String(),
            'topic' => $i->topic ? [
                'id' => $i->topic->id,
                'slug' => $i->topic->slug,
                'title' => $i->topic->title,
                'idea_type' => $i->topic->idea_type,
                'votes_pour' => $i->topic->votes_pour,
                'votes_contre' => $i->topic->votes_contre,
                'published_at' => $i->topic->published_at?->toIso8601String(),
                'author' => $i->topic->author ? [
                    'id' => $i->topic->author->id,
                    'name' => $i->topic->author->name,
                ] : null,
            ] : null,
        ];

        if ($full) {
            $data['response_content'] = $i->response_content;
        }

        return $data;
    }
}
