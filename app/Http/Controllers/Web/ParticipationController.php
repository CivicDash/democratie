<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\TopicElu;
use App\Models\TopicVote;
use App\Models\TerritoryRegion;
use App\Models\TerritoryDepartment;
use App\Services\ContentModerationService;
use App\Services\EluNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParticipationController extends Controller
{
    /**
     * Hub principal de la participation citoyenne
     */
    public function hub(): Response
    {
        // Stats globales
        $stats = [
            'total_ideas' => Topic::published()->count(),
            'total_votes' => TopicVote::count(),
            'total_comments' => DB::table('posts')->count(),
            'total_interpellations' => Topic::where('idea_type', 'interpellation')->published()->count(),
            'responses' => TopicElu::where('response_status', 'answered')->count(),
        ];

        // Idées tendances
        $trending = Topic::published()
            ->trending()
            ->with(['author:id,name', 'elus'])
            ->withCount('posts')
            ->take(5)
            ->get();

        // Dernières idées
        $recent = Topic::published()
            ->recent()
            ->with(['author:id,name'])
            ->withCount('posts')
            ->take(5)
            ->get();

        // Interpellations récentes avec réponses
        $interpellations = Topic::where('idea_type', 'interpellation')
            ->published()
            ->whereHas('elus', fn($q) => $q->where('response_status', 'answered'))
            ->with(['author:id,name', 'elus'])
            ->take(3)
            ->get();

        return Inertia::render('Participation/Hub', [
            'stats' => $stats,
            'trending' => $trending,
            'recent' => $recent,
            'interpellations' => $interpellations,
        ]);
    }

    /**
     * Liste des idées citoyennes avec filtres
     */
    public function ideasIndex(Request $request): Response
    {
        $query = Topic::published()
            ->with(['author:id,name', 'elus', 'region:id,name', 'department:id,name,code'])
            ->withCount('posts');

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('idea_type')) {
            $query->where('idea_type', $request->idea_type);
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('topicTags', fn($q) => $q->where('tag_id', $request->tag_id));
        }

        // Tri
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'trending':
                $query->orderByDesc('score')->orderByDesc('votes_pour');
                break;
            case 'popular':
                $query->orderByDesc('votes_pour');
                break;
            case 'controversial':
                // High engagement but polarizing
                $query->orderByRaw('votes_pour + votes_contre DESC')
                      ->orderByRaw('ABS(votes_pour - votes_contre) ASC');
                break;
            case 'recent':
            default:
                $query->orderByDesc('published_at');
        }

        $ideas = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total_ideas' => Topic::published()->count(),
            'total_votes' => TopicVote::count(),
            'total_comments' => DB::table('posts')->count(),
            'total_interpellations' => Topic::where('idea_type', 'interpellation')->published()->count(),
            'responses' => TopicElu::where('response_status', 'answered')->count(),
        ];

        return Inertia::render('Participation/Ideas/Index', [
            'ideas' => $ideas,
            'filters' => $request->only(['search', 'idea_type', 'scope', 'region_id', 'tag_id', 'sort']),
            'tags' => Tag::where('validated', true)->orderBy('nom')->get(['id', 'nom', 'icone']),
            'regions' => TerritoryRegion::orderBy('name')->get(['id', 'name']),
            'stats' => $stats,
        ]);
    }

    /**
     * Formulaire de création d'une idée
     */
    public function ideasCreate(Request $request): Response
    {
        $loiCod = $request->query('loi_cod');
        $loiTitre = $request->query('loi_titre');

        return Inertia::render('Participation/CreateIdea', [
            'regions' => TerritoryRegion::orderBy('name')->get(['id', 'name']),
            'departments' => TerritoryDepartment::with('region:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'region_id']),
            'tags' => Tag::where('validated', true)->orderBy('nom')->get(['id', 'nom', 'icone', 'couleur']),
            'elus' => [
                'deputes' => [], // TODO: Charger les députés
                'senateurs' => [],
                'maires' => [],
            ],
            'loiCod' => $loiCod,
            'loiTitre' => $loiTitre,
        ]);
    }

    /**
     * Enregistrer une nouvelle idée
     */
    public function ideasStore(Request $request, ContentModerationService $moderationService)
    {
        $validated = $request->validate([
            'idea_type' => ['required', 'in:discussion,proposal,question,debate,petition,interpellation,poll'],
            'title' => ['required', 'string', 'min:10', 'max:255'],
            'description' => ['required', 'string', 'min:50'],
            'scope' => ['required', 'in:national,regional,departemental,communal'],
            'region_id' => ['nullable', 'exists:territories_regions,id'],
            'department_id' => ['nullable', 'exists:territories_departments,id'],
            'loi_cod' => ['nullable', 'string', 'max:50'],
            'tag_ids' => ['nullable', 'array', 'max:3'],
            'tag_ids.*' => ['exists:tags,id'],
            'elus' => ['nullable', 'array'],
            'elus.*.type' => ['required', 'in:depute,senateur,maire'],
            'elus.*.id' => ['required', 'string'],
            'is_interpellation' => ['boolean'],
            // Sondages - validation min:2 faite conditionnellement après
            'poll_options' => ['nullable', 'array', 'max:6'],
            'poll_options.*.label' => ['nullable', 'string', 'max:255'],
            'poll_options.*.icon' => ['nullable', 'string', 'max:10'],
            'poll_type' => ['nullable', 'in:single,multiple'],
            'poll_ends_at' => ['nullable', 'date', 'after:today'],
            // Débat
            'debate_mode' => ['boolean'],
        ]);

        // Validation spécifique pour les sondages
        if ($validated['idea_type'] === 'poll') {
            if (empty($validated['poll_options']) || count($validated['poll_options']) < 2) {
                return back()->withErrors([
                    'poll_options' => 'Un sondage doit avoir au moins 2 options de réponse.',
                ])->withInput();
            }
        }

        // =====================================================================
        // MODÉRATION DU CONTENU
        // =====================================================================
        
        // 1. Modérer le titre
        $titleModeration = $moderationService->fullModerate(
            $validated['title'],
            auth()->id(),
            null,
            [
                'moderate_words' => true, 
                'sanitize_images' => true, 
                'sanitize_links' => true, 
                'parse_references' => false
            ]
        );
        
        // Si le titre contient du contenu bloqué (racisme, violence grave)
        if ($titleModeration['blocked']) {
            return back()->withErrors([
                'title' => 'Le titre contient du contenu interdit. Merci de reformuler.',
            ])->withInput();
        }
        
        // 2. Modérer la description
        $descModeration = $moderationService->fullModerate(
            $validated['description'],
            auth()->id(),
            null,
            [
                'moderate_words' => true, 
                'sanitize_images' => true,  // Supprimer TOUTES les images
                'sanitize_links' => true,   // Garder seulement liens officiels
                'parse_references' => false
            ]
        );
        
        if ($descModeration['blocked']) {
            return back()->withErrors([
                'description' => 'Le contenu contient des propos interdits (discours de haine, violence). Merci de reformuler.',
            ])->withInput();
        }
        
        // Utiliser le contenu modéré
        $title = $titleModeration['content'];
        $description = $descModeration['content'];
        
        // Informer l'utilisateur si des liens ont été supprimés
        $warnings = [];
        if (!empty($descModeration['removed_links'])) {
            $warnings[] = count($descModeration['removed_links']) . ' lien(s) externe(s) ont été supprimés. Seuls les liens vers les sites officiels (.gouv.fr, insee.fr, etc.) sont autorisés.';
        }
        if (!empty($titleModeration['word_replacements']) || !empty($descModeration['word_replacements'])) {
            $warnings[] = 'Certains mots inappropriés ont été automatiquement remplacés.';
        }
        
        // Vérification spécifique aux discussions
        if ($validated['idea_type'] === 'discussion') {
            if (Topic::containsMedia($description)) {
                return back()->withErrors([
                    'description' => 'Les discussions ne peuvent pas contenir d\'images ou de médias. Utilisez uniquement du texte.',
                ])->withInput();
            }
            
            // Les discussions doivent avoir au moins une thématique
            if (empty($validated['tag_ids'])) {
                return back()->withErrors([
                    'tag_ids' => 'Les discussions doivent être classées dans au moins une thématique.',
                ])->withInput();
            }
        }

        // Créer le topic
        $topic = Topic::create([
            'title' => $title,
            'description' => $description,
            'idea_type' => $validated['idea_type'],
            'type' => 'debate', // Type legacy
            'scope' => $validated['scope'],
            'region_id' => $validated['region_id'],
            'department_id' => $validated['department_id'],
            'loi_cod' => $validated['loi_cod'],
            'author_id' => auth()->id(),
            'status' => 'published', // Auto-publish pour l'instant
            'published_at' => now(),
            // Sondage
            'poll_type' => $validated['poll_type'] ?? null,
            'poll_ends_at' => $validated['poll_ends_at'] ?? null,
            // Débat
            'debate_mode' => $validated['idea_type'] === 'debate' || ($validated['debate_mode'] ?? false),
        ]);

        // Créer les options de sondage
        if ($validated['idea_type'] === 'poll' && !empty($validated['poll_options'])) {
            foreach ($validated['poll_options'] as $index => $optionData) {
                if (!empty(trim($optionData['label']))) {
                    \App\Models\PollOption::create([
                        'topic_id' => $topic->id,
                        'label' => trim($optionData['label']),
                        'icon' => $optionData['icon'] ?? null,
                        'position' => $index,
                    ]);
                }
            }
        }

        // Attacher les tags
        if (!empty($validated['tag_ids'])) {
            $topic->topicTags()->sync($validated['tag_ids']);
        }

        // Créer les liaisons avec les élus
        if (!empty($validated['elus'])) {
            foreach ($validated['elus'] as $elu) {
                TopicElu::create([
                    'topic_id' => $topic->id,
                    'elu_type' => $elu['type'],
                    'elu_id' => $elu['id'],
                    'is_interpellation' => $validated['is_interpellation'] ?? false,
                ]);
            }

            // Envoyer les notifications aux élus interpellés
            if ($validated['is_interpellation'] ?? false) {
                try {
                    $eluNotificationService = app(EluNotificationService::class);
                    $topic->load('elus');
                    $notifiedCount = $eluNotificationService->notifyAllElusForTopic($topic);
                    
                    if ($notifiedCount > 0) {
                        $warnings[] = "{$notifiedCount} élu(s) ont été notifié(s) de votre interpellation.";
                    }
                } catch (\Exception $e) {
                    \Log::error('Erreur notification élus', ['error' => $e->getMessage()]);
                }
            }
        }

        // Notifier les élus mentionnés dans le contenu (@depute:, @senateur:, etc.)
        try {
            $eluNotificationService = app(EluNotificationService::class);
            $eluNotificationService->notifyMentionsInContent(
                auth()->user(),
                $description,
                route('participation.ideas.show', $topic->slug ?? $topic->id),
                $title
            );
        } catch (\Exception $e) {
            \Log::warning('Erreur notification mentions', ['error' => $e->getMessage()]);
        }

        // Préparer le message de succès avec éventuels warnings
        $successMessage = 'Votre contribution a été publiée !';
        if (!empty($warnings)) {
            $successMessage .= ' Note : ' . implode(' ', $warnings);
        }

        // Toujours rediriger vers la page de détail de l'idée
        return redirect()->route('participation.ideas.show', $topic->slug ?: $topic->id)
            ->with('success', $successMessage);
    }

    /**
     * Voter sur une idée (API)
     */
    public function vote(Request $request, Topic $topic)
    {
        $request->validate([
            'vote' => ['required', 'in:-1,1'],
        ]);

        $vote = TopicVote::castVote(
            auth()->id(),
            $topic->id,
            (int) $request->vote
        );

        $topic->refresh();

        return response()->json([
            'success' => true,
            'vote' => $vote->vote,
            'stats' => [
                'votes_pour' => $topic->votes_pour,
                'votes_contre' => $topic->votes_contre,
                'score' => $topic->votes_pour - $topic->votes_contre,
            ],
        ]);
    }

    /**
     * Retirer son vote
     */
    public function unvote(Request $request, Topic $topic)
    {
        TopicVote::removeVote(auth()->id(), $topic->id);

        $topic->refresh();

        return response()->json([
            'success' => true,
            'vote' => null,
            'stats' => [
                'votes_pour' => $topic->votes_pour,
                'votes_contre' => $topic->votes_contre,
                'score' => $topic->votes_pour - $topic->votes_contre,
            ],
        ]);
    }

    /**
     * Afficher une idée citoyenne
     */
    public function ideasShow(Topic $topic): Response
    {
        // Incrémenter le compteur de vues
        $topic->increment('views_count');

        // Charger les relations
        $topic->load([
            'author:id,name',
            'region:id,name',
            'department:id,name,code',
            'elus',
            'topicTags',
            'loi:loicod,loitit,etaloicod',
        ]);

        // Commentaires avec pagination
        $comments = $topic->posts()
            ->with(['user:id,name', 'votes'])
            ->withCount('votes')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Vérifier si l'utilisateur a voté
        $userVote = null;
        if (auth()->check()) {
            $vote = TopicVote::where('user_id', auth()->id())
                ->where('topic_id', $topic->id)
                ->first();
            $userVote = $vote?->vote;
        }

        // Charger les infos des élus liés
        $elusDetails = $this->loadElusDetails($topic->elus);

        // Idées similaires (même thématique ou même scope)
        $similar = Topic::published()
            ->where('id', '!=', $topic->id)
            ->where(function ($q) use ($topic) {
                if ($topic->idea_type) {
                    $q->where('idea_type', $topic->idea_type);
                }
                if ($topic->scope) {
                    $q->orWhere('scope', $topic->scope);
                }
            })
            ->orderByDesc('score')
            ->limit(3)
            ->get(['id', 'slug', 'title', 'idea_type', 'votes_pour', 'votes_contre']);

        return Inertia::render('Participation/Ideas/Show', [
            'idea' => [
                'id' => $topic->id,
                'slug' => $topic->slug,
                'title' => $topic->title,
                'description' => $topic->description,
                'idea_type' => $topic->idea_type,
                'idea_type_info' => $topic->idea_type_info,
                'scope' => $topic->scope,
                'scope_info' => $topic->scope_info,
                'votes_pour' => $topic->votes_pour,
                'votes_contre' => $topic->votes_contre,
                'score' => $topic->votes_pour - $topic->votes_contre,
                'pct_pour' => $topic->pct_pour,
                'pct_contre' => $topic->pct_contre,
                'total_votes' => $topic->total_votes,
                'views_count' => $topic->views_count,
                'posts_count' => $topic->posts()->count(),
                'published_at' => $topic->published_at?->toIso8601String(),
                'created_at' => $topic->created_at->toIso8601String(),
                'author' => $topic->author ? [
                    'id' => $topic->author->id,
                    'name' => $topic->author->name,
                ] : null,
                'region' => $topic->region ? [
                    'id' => $topic->region->id,
                    'name' => $topic->region->name,
                ] : null,
                'department' => $topic->department ? [
                    'id' => $topic->department->id,
                    'name' => $topic->department->name,
                    'code' => $topic->department->code,
                ] : null,
                'tags' => $topic->topicTags->map(fn($t) => [
                    'id' => $t->id,
                    'nom' => $t->nom,
                    'icone' => $t->icone,
                ]),
                'loi' => $topic->loi ? [
                    'code' => $topic->loi->loicod,
                    'titre' => $topic->loi->loitit,
                    'etat' => $topic->loi->etaloicod,
                ] : null,
                'elus' => $elusDetails,
                // Mode débat
                'debate_mode' => $topic->debate_mode || $topic->idea_type === 'debate',
                'poll_type' => $topic->poll_type,
                'poll_ends_at' => $topic->poll_ends_at?->toIso8601String(),
            ],
            'comments' => $comments->through(fn($post) => [
                'id' => $post->id,
                'content' => $post->content,
                'debate_position' => $post->debate_position,
                'created_at' => $post->created_at->toIso8601String(),
                'user' => $post->user ? [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                ] : null,
                'votes_count' => $post->votes_count ?? 0,
            ]),
            'userVote' => $userVote,
            'similar' => $similar,
        ]);
    }

    /**
     * Ajouter un commentaire sur une idée
     */
    public function addComment(Request $request, Topic $topic, ContentModerationService $moderationService)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:10', 'max:5000'],
            'parent_id' => ['nullable', 'exists:posts,id'],
            'debate_position' => ['nullable', 'in:for,against,neutral'],
        ]);

        // Modérer le contenu
        $moderation = $moderationService->fullModerate(
            $validated['content'],
            auth()->id(),
            null,
            [
                'moderate_words' => true, 
                'sanitize_images' => true,  // Supprimer les images
                'sanitize_links' => true,   // Garder seulement liens officiels
                'parse_references' => true
            ]
        );

        // Si contenu bloqué
        if ($moderation['blocked'] ?? false) {
            return response()->json([
                'success' => false,
                'message' => 'Votre commentaire contient du contenu interdit.',
            ], 422);
        }

        // Déterminer la position pour le mode débat
        $debatePosition = null;
        if ($topic->debate_mode || $topic->idea_type === 'debate') {
            $debatePosition = $validated['debate_position'] ?? null;
        }

        $post = $topic->posts()->create([
            'user_id' => auth()->id(),
            'content' => $moderation['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'debate_position' => $debatePosition,
        ]);

        // Traiter les mentions @utilisateur
        $mentionService = app(\App\Services\MentionService::class);
        $mentionService->processContent($moderation['content'], $post, auth()->user());

        $post->load('user:id,name');

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $post->id,
                'content' => $mentionService->renderMentions($post->content),
                'debate_position' => $post->debate_position,
                'created_at' => $post->created_at->toIso8601String(),
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                ],
            ],
        ]);
    }

    /**
     * Charger les détails des élus liés
     */
    private function loadElusDetails($elus): array
    {
        $details = [];

        foreach ($elus as $elu) {
            $info = [
                'id' => $elu->id,
                'elu_type' => $elu->elu_type,
                'elu_id' => $elu->elu_id,
                'is_interpellation' => $elu->is_interpellation,
                'response_status' => $elu->response_status,
                'response_date' => $elu->response_date?->toIso8601String(),
                'response_content' => $elu->response_content,
                'elu_data' => null,
            ];

            // Charger les données de l'élu selon le type
            switch ($elu->elu_type) {
                case 'depute':
                    $acteur = \App\Models\ActeurAN::find($elu->elu_id);
                    if ($acteur) {
                        $info['elu_data'] = [
                            'nom_complet' => $acteur->nom_complet,
                            'photo_url' => $acteur->photo_url,
                            'groupe' => $acteur->groupe_politique_actuel?->libelle_abrege,
                            'url' => route('representants.deputes.show', $acteur->uid),
                        ];
                    }
                    break;

                case 'senateur':
                    $senateur = \App\Models\Senateur::where('matricule', $elu->elu_id)->first();
                    if ($senateur) {
                        $info['elu_data'] = [
                            'nom_complet' => $senateur->nom_complet,
                            'photo_url' => $senateur->photo_url,
                            'groupe' => $senateur->groupe_politique,
                            'url' => route('representants.senateurs.show', $senateur->matricule),
                        ];
                    }
                    break;

                case 'maire':
                    $maire = \App\Models\Maire::find($elu->elu_id);
                    if ($maire) {
                        $info['elu_data'] = [
                            'nom_complet' => trim("{$maire->prenom} {$maire->nom}"),
                            'photo_url' => null,
                            'commune' => $maire->commune,
                            'url' => null,
                        ];
                    }
                    break;
            }

            $details[] = $info;
        }

        return $details;
    }
}
