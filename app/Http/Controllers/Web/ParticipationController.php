<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\TopicElu;
use App\Models\TopicVote;
use App\Models\TerritoryRegion;
use App\Models\TerritoryDepartment;
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
    public function ideasStore(Request $request)
    {
        $validated = $request->validate([
            'idea_type' => ['required', 'in:proposal,question,debate,petition,interpellation'],
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
        ]);

        // Créer le topic
        $topic = Topic::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'idea_type' => $validated['idea_type'],
            'type' => 'debate', // Type legacy
            'scope' => $validated['scope'],
            'region_id' => $validated['region_id'],
            'department_id' => $validated['department_id'],
            'loi_cod' => $validated['loi_cod'],
            'author_id' => auth()->id(),
            'status' => 'published', // Auto-publish pour l'instant
            'published_at' => now(),
        ]);

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
        }

        // Redirection
        if ($topic->loi_cod) {
            return redirect()->route('lois.show', trim($topic->loi_cod))
                ->with('success', 'Votre contribution a été publiée !');
        }

        return redirect()->route('topics.show', $topic->slug ?: $topic->id)
            ->with('success', 'Votre contribution a été publiée !');
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
}
