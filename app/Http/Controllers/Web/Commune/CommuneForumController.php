<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommunePage;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CommuneForumController extends Controller
{
    public const CATEGORIES_FORUM = [
        'urbanisme' => 'Urbanisme',
        'securite' => 'Securite',
        'environnement' => 'Environnement',
        'transport' => 'Transport',
        'culture' => 'Culture & Loisirs',
        'education' => 'Education',
        'social' => 'Social & Solidarite',
        'vie_locale' => 'Vie locale',
        'autre' => 'Autre',
    ];

    public function index(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if (! $page->forum_actif) {
            abort(404, 'Forum non actif pour cette commune.');
        }

        $categorie = request('categorie');

        $epingles = Topic::where('commune_code_insee', $codeInsee)
            ->where('status', 'published')
            ->where('is_pinned', true)
            ->with('author:id,name')
            ->withCount('posts')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => $this->formatTopic($t));

        $query = Topic::where('commune_code_insee', $codeInsee)
            ->where('status', 'published')
            ->where('is_pinned', false)
            ->with('author:id,name')
            ->withCount('posts');

        if ($categorie && array_key_exists($categorie, self::CATEGORIES_FORUM)) {
            $query->where('forum_categorie', $categorie);
        }

        $topics = $query->orderByDesc('updated_at')
            ->paginate(20)
            ->through(fn ($t) => $this->formatTopic($t));

        return Inertia::render('Commune/Forum', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'page' => ['statut' => $page->statut, 'couleur_primaire' => $page->couleur_primaire],
            'topics' => $topics,
            'epingles' => $epingles,
            'categories' => self::CATEGORIES_FORUM,
            'categorie_active' => $categorie,
            'est_admin' => $page->estAdministrePar(auth()->user() ?? new \App\Models\User),
        ]);
    }

    public function create(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        return Inertia::render('Commune/ForumCreate', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'categories' => self::CATEGORIES_FORUM,
        ]);
    }

    public function store(Request $request, string $codeInsee)
    {
        $page = CommunePage::where('code_insee', $codeInsee)->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'forum_categorie' => 'nullable|string|in:'.implode(',', array_keys(self::CATEGORIES_FORUM)),
        ]);

        $topic = Topic::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']).'-'.Str::random(6),
            'description' => $validated['description'],
            'user_id' => $request->user()->id,
            'commune_code_insee' => $codeInsee,
            'forum_categorie' => $validated['forum_categorie'] ?? 'autre',
            'status' => 'published',
            'published_at' => now(),
            'type' => 'debate',
        ]);

        return redirect()->route('commune.forum', $codeInsee)
            ->with('success', 'Sujet cree avec succes.');
    }

    public function epingler(string $codeInsee, int $topicId)
    {
        $page = CommunePage::where('code_insee', $codeInsee)->firstOrFail();
        if (! $page->estAdministrePar(auth()->user()) && ! auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $topic = Topic::where('commune_code_insee', $codeInsee)->findOrFail($topicId);
        $topic->update(['is_pinned' => ! $topic->is_pinned]);

        return back()->with('success', $topic->is_pinned ? 'Sujet epingle.' : 'Sujet desepingle.');
    }

    private function formatTopic(Topic $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'slug' => $t->slug,
            'type' => $t->type,
            'status' => $t->status,
            'author' => $t->author?->name,
            'posts_count' => $t->posts_count,
            'is_pinned' => $t->is_pinned,
            'forum_categorie' => $t->forum_categorie,
            'forum_categorie_label' => self::CATEGORIES_FORUM[$t->forum_categorie] ?? null,
            'created_at' => $t->created_at->format('d/m/Y'),
            'updated_at' => $t->updated_at->diffForHumans(),
        ];
    }
}
