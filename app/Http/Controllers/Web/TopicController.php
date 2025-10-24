<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\TerritoryRegion;
use App\Models\TerritoryDepartment;
use App\Services\TopicService;
use App\Http\Requests\Topic\StoreTopicRequest;
use App\Http\Requests\Topic\UpdateTopicRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function __construct(
        protected TopicService $topicService
    ) {}

    /**
     * Liste des topics avec filtres
     */
    public function index(Request $request): Response
    {
        $query = Topic::with(['author', 'region', 'department'])
            ->withCount(['posts', 'ballots']);

        // Filtres
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Tri par défaut : plus récents
        $topics = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Topics/Index', [
            'topics' => $topics,
            'filters' => $request->only(['search', 'scope', 'type']),
        ]);
    }

    /**
     * Topics populaires
     */
    public function trending(): Response
    {
        $topics = Topic::with(['author', 'region', 'department'])
            ->withCount(['posts', 'ballots'])
            ->where('created_at', '>=', now()->subWeek())
            ->orderByDesc('posts_count')
            ->orderByDesc('ballots_count')
            ->paginate(20);

        return Inertia::render('Topics/Index', [
            'topics' => $topics,
            'filters' => ['trending' => true],
        ]);
    }

    /**
     * Détails d'un topic
     */
    public function show(Topic $topic): Response
    {
        $topic->load(['author', 'region', 'department']);
        $topic->loadCount('ballots');

        $posts = $topic->posts()
            ->with('author')
            ->withVoteScore()
            ->orderByDesc('is_pinned')
            ->orderByDesc('is_solution')
            ->orderByDesc('vote_score')
            ->get();

        return Inertia::render('Topics/Show', [
            'topic' => $topic,
            'posts' => $posts,
            'can' => [
                'update' => auth()->check() && auth()->user()->can('update', $topic),
                'delete' => auth()->check() && auth()->user()->can('delete', $topic),
                'reply' => auth()->check() && auth()->user()->can('reply', $topic),
            ],
        ]);
    }

    /**
     * Formulaire de création
     */
    public function create(): Response
    {
        $this->authorize('create', Topic::class);

        return Inertia::render('Topics/Create', [
            'regions' => TerritoryRegion::orderBy('name')->get(),
            'departments' => TerritoryDepartment::with('region')->orderBy('name')->get(),
        ]);
    }

    /**
     * Créer un topic
     */
    public function store(StoreTopicRequest $request)
    {
        $topic = $this->topicService->createTopic(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('topics.show', $topic)
            ->with('success', 'Sujet créé avec succès !');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Topic $topic): Response
    {
        $this->authorize('update', $topic);

        return Inertia::render('Topics/Edit', [
            'topic' => $topic->load(['region', 'department']),
            'regions' => TerritoryRegion::orderBy('name')->get(),
            'departments' => TerritoryDepartment::with('region')->orderBy('name')->get(),
        ]);
    }

    /**
     * Mettre à jour un topic
     */
    public function update(UpdateTopicRequest $request, Topic $topic)
    {
        $this->topicService->updateTopic($topic, $request->validated());

        return redirect()->route('topics.show', $topic)
            ->with('success', 'Sujet mis à jour avec succès !');
    }

    /**
     * Supprimer un topic
     */
    public function destroy(Topic $topic)
    {
        $this->authorize('delete', $topic);

        $this->topicService->deleteTopic($topic);

        return redirect()->route('topics.index')
            ->with('success', 'Sujet supprimé avec succès.');
    }
}

