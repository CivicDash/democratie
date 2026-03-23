<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AffaireJudiciaire;
use App\Models\EluFollower;
use App\Models\HatvpDeclaration;
use App\Models\Maire;
use App\Models\MaireMandat;
use App\Models\ResultatMunicipal;
use App\Models\TopicElu;
use App\Models\Topic;
use App\Models\TerritoryDepartment;
use App\Services\NotificationService;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $sort = $request->input('sort', 'recent');
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
                    'name' => $interpellation->topic->author->display_name,
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
                'user' => $p->user ? ['id' => $p->user->id, 'name' => $p->user->display_name] : null,
            ]),
        ]);
    }

    /**
     * Répondre à une interpellation
     */
    public function respond(Request $request, TopicElu $interpellation, ContentModerationService $moderationService)
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

        // Modération du contenu de la réponse
        $moderation = $moderationService->fullModerate(
            $validated['response_content'],
            $user->id,
            $interpellation,
            [
                'moderate_words' => true,
                'sanitize_images' => true,
                'sanitize_links' => true,
                'parse_references' => false,
            ]
        );

        if ($moderation['blocked']) {
            return back()->withErrors([
                'response_content' => 'Le contenu contient des propos interdits. Merci de reformuler.',
            ])->withInput();
        }

        $interpellation->update([
            'response_content' => $moderation['content'],
            'response_status' => 'answered',
            'answered_at' => now(),
        ]);

        // Notifier l'auteur de l'interpellation
        $topic = $interpellation->topic;
        if ($topic && $topic->author) {
            $eluName = $interpellation->elu_nom ?? 'Un élu';
            app(NotificationService::class)->notify(
                $topic->author,
                'response',
                "✅ Réponse à votre interpellation !",
                "{$eluName} a répondu à votre interpellation \"{$topic->title}\"",
                route('participation.ideas.show', $topic->slug ?? $topic->id),
                '✅',
                [
                    'topic_id' => $topic->id,
                    'topic_elu_id' => $interpellation->id,
                    'elu_type' => $interpellation->elu_type,
                    'elu_id' => $interpellation->elu_id,
                ]
            );
        }

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
        if ($type === 'maire') {
            return $this->renderMaireProfile($ref);
        }

        $eluData = match ($type) {
            'depute' => \App\Models\ActeurAN::find($ref),
            'senateur' => \App\Models\Senateur::where('matricule', $ref)->first(),
            default => null,
        };

        if (!$eluData) {
            abort(404, 'Élu non trouvé.');
        }

        $userAccount = \App\Models\User::where('elu_type', $type)
            ->where('elu_ref', $ref)
            ->where('is_verified_elu', true)
            ->where('is_public_profile', true)
            ->first();

        $interpellations = TopicElu::where('elu_type', $type)
            ->where('elu_id', $ref)
            ->where('is_interpellation', true)
            ->with(['topic:id,slug,title,idea_type,votes_pour,votes_contre,published_at', 'topic.author:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($i) => $this->formatInterpellation($i));

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
     * Index des maires
     */
    public function maires(Request $request): Response
    {
        $query = $request->input('q', '');
        $departement = $request->input('departement');
        $nuance = $request->input('nuance');

        $mairesQuery = Maire::enExercice();

        if ($query) {
            $mairesQuery->search($query);
        }
        if ($departement) {
            $mairesQuery->byDepartement($departement);
        }
        if ($nuance) {
            $mairesQuery->where('nuance_politique', $nuance);
        }

        $maires = $mairesQuery
            ->orderByDesc('population_commune')
            ->paginate(50)
            ->withQueryString()
            ->through(fn(Maire $m) => [
                'id' => $m->id,
                'uid' => $m->uid,
                'nom_complet' => $m->nom_complet,
                'civilite' => $m->civilite,
                'photo' => $m->photo,
                'commune' => $m->nom_commune,
                'code_commune' => $m->code_commune,
                'departement' => $m->nom_departement,
                'code_departement' => $m->code_departement,
                'population' => $m->population_commune,
                'nuance' => $m->nuance_politique ? [
                    'code' => $m->nuance_politique,
                    'libelle' => $m->nuance_libelle,
                    'couleur' => $m->nuance_couleur,
                ] : null,
                'debut_mandat' => $m->debut_mandat?->format('d/m/Y'),
                'reelu' => $m->reelu,
                'url' => route('elus.public-profile', ['type' => 'maire', 'ref' => $m->id]),
            ]);

        $totalMaires = Maire::enExercice()->count();

        $departements = TerritoryDepartment::orderBy('code')
            ->get(['code', 'name'])
            ->map(fn($d) => ['code' => $d->code, 'nom' => $d->name]);

        $nuances = Maire::enExercice()
            ->whereNotNull('nuance_politique')
            ->selectRaw('nuance_politique, COUNT(*) as total')
            ->groupBy('nuance_politique')
            ->orderByDesc('total')
            ->get()
            ->map(fn($n) => [
                'code' => $n->nuance_politique,
                'libelle' => (new Maire(['nuance_politique' => $n->nuance_politique]))->nuance_libelle,
                'total' => $n->total,
            ]);

        return Inertia::render('Elus/Maires/Index', [
            'maires' => $maires,
            'totalMaires' => $totalMaires,
            'filters' => compact('query', 'departement', 'nuance'),
            'departements' => $departements,
            'nuances' => $nuances,
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
            'maire' => redirect()->route('elus.public-profile', ['type' => 'maire', 'ref' => $user->elu_ref]),
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

    private function renderMaireProfile(string $ref): Response
    {
        $maire = Maire::with([
            'ville',
            'mandats',
            'personnePolitique.postes.gouvernement',
        ])->findOrFail($ref);

        $affaires = $maire->toutesAffairesPubliques()
            ->with('sources')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'description' => $a->description,
                'type_affaire' => $a->type_affaire,
                'type_affaire_libelle' => $a->type_affaire_libelle,
                'categorie' => $a->categorie,
                'statut_judiciaire' => $a->statut_judiciaire,
                'statut_libelle' => $a->statut_judiciaire_libelle,
                'statut_couleur' => $a->statut_judiciaire_couleur,
                'date_condamnation' => $a->date_condamnation_definitive?->format('d/m/Y'),
                'date_mise_en_examen' => $a->date_mise_en_examen?->format('d/m/Y'),
                'peine_resume' => $a->peine_resume,
                'juridiction' => $a->juridiction,
                'sources' => $a->sources->map(fn($s) => [
                    'media' => $s->media,
                    'url' => $s->url,
                    'date' => $s->date_publication?->format('d/m/Y'),
                    'type' => $s->type_source,
                ]),
            ]);

        $hatvpData = ['declarations' => [], 'summary' => null];
        if ($maire->est_soumis_hatvp) {
            $hatvpData = Cache::remember("hatvp_maire_{$maire->id}", 3600, function () use ($maire) {
                $declarations = $maire->declarationsHatvp()
                    ->with([
                        'mandatsElectifs.remunerations',
                        'activitesProfessionnelles.remunerations',
                        'participationsDirigeantes.remunerations',
                        'collaborateurs',
                        'fonctionsBenevoles',
                    ])
                    ->get();

                $formatted = $declarations->map(fn($d) => [
                    'uuid' => $d->uuid,
                    'type' => $d->type_declaration,
                    'type_label' => $d->type_declaration_label,
                    'date_depot' => $d->date_depot?->format('d/m/Y'),
                    'type_mandat' => $d->type_mandat,
                    'url' => $maire->url_hatvp ?? 'https://www.hatvp.fr/fiche-nominative/?declarant='
                             . urlencode(strtolower($maire->nom) . '-' . strtolower($maire->prenom)),
                ]);

                $summary = null;
                $latest = $declarations->first();
                if ($latest) {
                    $mandatsElectifs = $latest->mandatsElectifs ?? collect();
                    $activitesPro = $latest->activitesProfessionnelles ?? collect();
                    $participations = $latest->participationsDirigeantes ?? collect();

                    $totalRemunerations = 0;
                    foreach ([$mandatsElectifs, $activitesPro, $participations] as $group) {
                        foreach ($group as $item) {
                            foreach ($item->remunerations ?? [] as $rem) {
                                $totalRemunerations += (float) ($rem->montant_brut ?? 0);
                            }
                        }
                    }

                    $summary = [
                        'nb_mandats_electifs' => $mandatsElectifs->count(),
                        'nb_activites_pro' => $activitesPro->count(),
                        'nb_participations' => $participations->count(),
                        'nb_collaborateurs' => ($latest->collaborateurs ?? collect())->count(),
                        'total_remunerations_brut' => $totalRemunerations,
                        'derniere_declaration' => $latest->date_depot?->format('d/m/Y'),
                    ];
                }

                return ['declarations' => $formatted->toArray(), 'summary' => $summary];
            });
        }

        $resultatsElection = $maire->resultatsElection()
            ->with('listes')
            ->get()
            ->map(fn($r) => [
                'tour' => (int) $r->tour,
                'inscrits' => (int) $r->inscrits,
                'votants' => (int) $r->votants,
                'taux_participation' => (float) $r->taux_participation,
                'exprimes' => (int) ($r->exprimes ?? 0),
                'listes' => $r->listes->map(fn($l) => [
                    'nom_liste' => $l->nom_liste,
                    'tete_de_liste' => trim(($l->tete_de_liste_prenom ?? '') . ' ' . ($l->tete_de_liste_nom ?? '')),
                    'nuance' => $l->nuance_liste,
                    'voix' => (int) $l->voix,
                    'pourcentage' => (float) ($l->pourcentage_exprimes ?? 0),
                    'elu' => (bool) $l->elu,
                    'sieges' => (int) ($l->sieges_obtenus ?? 0),
                ]),
            ]);

        $mandats = $maire->mandats->map(fn($m) => [
            'id' => $m->id,
            'date_debut' => $m->date_debut?->format('d/m/Y'),
            'date_fin' => $m->date_fin?->format('d/m/Y'),
            'periode' => $m->periode,
            'duree' => $m->duree_formate,
            'nuance_politique' => $m->nuance_politique,
            'parti' => $m->parti,
            'score_election' => $m->score_election_pct,
            'tour_election' => $m->tour_election,
            'est_actuel' => (bool) $m->est_actuel,
        ]);

        $elusRattaches = $this->getElusCommune($maire);

        $isFollowed = Auth::check() && EluFollower::where('user_id', Auth::id())
            ->where('elu_type', 'maire')
            ->where('elu_id', (string) $maire->id)
            ->exists();

        return Inertia::render('Elus/Maires/Show', [
            'maire' => [
                'id' => $maire->id,
                'uid' => $maire->uid,
                'nom_complet' => $maire->nom_complet,
                'civilite' => $maire->civilite,
                'prenom' => $maire->prenom,
                'nom' => $maire->nom,
                'photo' => $maire->photo,
                'date_naissance' => $maire->date_naissance?->format('d/m/Y'),
                'age' => $maire->age,
                'lieu_naissance' => $maire->lieu_naissance,
                'profession' => $maire->profession,
                'formation' => $maire->formation,
                'nuance' => $maire->nuance_politique ? [
                    'code' => $maire->nuance_politique,
                    'libelle' => $maire->nuance_libelle,
                    'couleur' => $maire->nuance_couleur,
                ] : null,
                'mandat' => [
                    'debut' => $maire->debut_mandat?->format('d/m/Y'),
                    'debut_fonction' => $maire->debut_fonction?->format('d/m/Y'),
                    'mandature' => $maire->mandature,
                    'duree' => $maire->duree_mandat,
                    'reelu' => $maire->reelu,
                    'score' => $maire->score_election_pct,
                    'tour' => $maire->tour_election,
                ],
                'commune' => [
                    'code' => $maire->code_commune,
                    'nom' => $maire->nom_commune,
                    'departement' => $maire->nom_departement,
                    'code_departement' => $maire->code_departement,
                    'region' => $maire->nom_region,
                    'population' => $maire->population_commune,
                    'ville_slug' => $maire->ville?->slug,
                ],
                'wikipedia' => [
                    'url' => $maire->wikipedia_url,
                    'extract' => $maire->wikipedia_extract,
                ],
                'contact' => array_filter([
                    'email' => $maire->email,
                    'telephone' => $maire->telephone,
                    'site_web' => $maire->site_web,
                    'adresse_mairie' => $maire->adresse_mairie,
                ]),
                'reseaux_sociaux' => array_filter([
                    'twitter' => $maire->twitter_url,
                    'facebook' => $maire->facebook_url,
                    'instagram' => $maire->instagram_url,
                    'linkedin' => $maire->linkedin_url,
                ]),
                'est_soumis_hatvp' => $maire->est_soumis_hatvp,
                'est_fiche_riche' => $maire->est_fiche_riche,
                'est_aussi_depute' => $maire->personnePolitique?->uid_an !== null,
                'est_aussi_senateur' => $maire->personnePolitique?->uid_senat !== null,
                'postes_gouvernement' => $maire->personnePolitique?->postes
                    ?->map(fn($p) => $p->fonction . ' (' . ($p->gouvernement?->nom_complet ?? 'Gouvernement') . ')')
                    ->toArray() ?? [],
                'is_followed' => $isFollowed,
            ],
            'mandats_historiques' => $mandats,
            'resultats_election' => $resultatsElection,
            'affaires_judiciaires' => $affaires,
            'declarations_hatvp' => $hatvpData['declarations'],
            'hatvp_summary' => $hatvpData['summary'],
            'elus_rattaches' => $elusRattaches,
            'budget_commune' => $maire->ville?->budgets?->take(5)->map(fn($b) => [
                'annee' => $b->annee,
                'recettes' => $b->recettes_fonctionnement ?? 0,
                'depenses' => $b->depenses_fonctionnement ?? 0,
                'dette' => $b->encours_dette ?? 0,
            ]) ?? [],
        ]);
    }

    private function getElusCommune(Maire $maire): array
    {
        $elus = [];
        $deptCode = $maire->code_departement;

        $deputeUids = \App\Models\DeputeCirconscription::where('num_departement', $deptCode)
            ->whereNull('date_fin')
            ->pluck('acteur_uid')
            ->unique();

        if ($deputeUids->isNotEmpty()) {
            $deputes = \App\Models\ActeurAN::whereIn('uid', $deputeUids)
                ->limit(10)
                ->get();

            foreach ($deputes as $d) {
                $groupe = $d->groupe_politique_actuel;
                $elus[] = [
                    'type' => 'depute',
                    'nom' => $d->nom_complet,
                    'photo' => $d->photo_url,
                    'detail' => $groupe?->libelle_abrege ?? $groupe?->libelle ?? null,
                    'url' => route('representants.deputes.show', $d->uid),
                ];
            }
        }

        $senateurs = \App\Models\Senateur::actifs()
            ->where('circonscription', 'ILIKE', '%' . ($maire->nom_departement ?? '') . '%')
            ->limit(10)
            ->get();

        foreach ($senateurs as $s) {
            $elus[] = [
                'type' => 'senateur',
                'nom' => $s->nom_complet,
                'photo' => $s->photo_url,
                'detail' => $s->groupe_politique,
                'url' => route('representants.senateurs.show', $s->matricule),
            ];
        }

        return $elus;
    }

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
                    'name' => $i->topic->author->display_name,
                ] : null,
            ] : null,
        ];

        if ($full) {
            $data['response_content'] = $i->response_content;
        }

        return $data;
    }
}
