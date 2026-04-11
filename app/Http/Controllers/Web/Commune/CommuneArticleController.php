<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommuneArticle;
use App\Models\CommunePage;
use App\Models\CommuneReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CommuneArticleController extends Controller
{
    public function index(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if (! $page->actus_actives) {
            abort(404, 'Actualites non activees pour cette commune.');
        }

        $categorie = request('categorie');
        $query = $page->articles()->publies()->recents();

        if ($categorie) {
            $query->parCategorie($categorie);
        }

        $articles = $query->paginate(12)->through(fn ($a) => [
            'id' => $a->id,
            'titre' => $a->titre,
            'slug' => $a->slug,
            'extrait' => $a->extrait_auto,
            'categorie' => $a->categorie,
            'categorie_label' => $a->categorie_labell,
            'image_url' => $a->image_url,
            'publie_at' => $a->publie_at?->format('d/m/Y'),
            'vues_count' => $a->vues_count,
        ]);

        $epingles = $page->articles()->publies()->epingles()->recents()->limit(3)->get()->map(fn ($a) => [
            'id' => $a->id,
            'titre' => $a->titre,
            'slug' => $a->slug,
            'extrait' => $a->extrait_auto,
            'image_url' => $a->image_url,
            'publie_at' => $a->publie_at?->format('d/m/Y'),
        ]);

        return Inertia::render('Commune/Actualites', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'page' => ['statut' => $page->statut, 'couleur_primaire' => $page->couleur_primaire],
            'articles' => $articles,
            'epingles' => $epingles,
            'categories' => CommuneArticle::CATEGORIES,
            'categorie_active' => $categorie,
            'seo' => [
                'title' => "Actualites - {$page->ville->nom} - Hub Citoyen",
                'description' => "Toutes les actualites municipales de {$page->ville->nom} ({$page->ville->departement_nom}).",
                'image' => $page->image_couverture_url ?? $page->ville->blason_url,
                'url' => url()->current(),
                'type' => 'website',
            ],
        ]);
    }

    public function show(string $codeInsee, string $slug): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();
        $article = $page->articles()->publies()->where('slug', $slug)->firstOrFail();
        $article->incrementerVues();

        $articlesRecents = $page->articles()
            ->publies()
            ->recents()
            ->where('id', '!=', $article->id)
            ->limit(4)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'slug' => $a->slug,
                'extrait' => $a->extrait_auto,
                'image_url' => $a->image_url,
                'publie_at' => $a->publie_at?->format('d/m/Y'),
            ]);

        $extrait = $article->extrait ?? \Illuminate\Support\Str::limit(strip_tags($article->contenu), 160);

        $commentaires = $article->commentaires()
            ->visibles()
            ->racines()
            ->with(['user:id,name', 'reponses' => fn ($q) => $q->visibles()->with('user:id,name')])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'contenu' => $c->contenu,
                'user' => $c->user ? ['name' => $c->user->name] : null,
                'user_id' => $c->user_id,
                'created_at' => $c->created_at->toISOString(),
                'reponses' => $c->reponses->map(fn ($r) => [
                    'id' => $r->id,
                    'contenu' => $r->contenu,
                    'user' => $r->user ? ['name' => $r->user->name] : null,
                    'user_id' => $r->user_id,
                    'created_at' => $r->created_at->toISOString(),
                ]),
            ]);

        $reactionCounts = [];
        foreach (CommuneReaction::TYPES as $type) {
            $count = $article->reactions()->where('type', $type)->count();
            if ($count > 0) {
                $reactionCounts[$type] = $count;
            }
        }

        $userReaction = null;
        if ($user = auth()->user()) {
            $userReaction = $article->reactions()->where('user_id', $user->id)->value('type');
        }

        return Inertia::render('Commune/ArticleShow', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'article' => [
                'id' => $article->id,
                'titre' => $article->titre,
                'slug' => $article->slug,
                'contenu' => $article->contenu,
                'categorie' => $article->categorie,
                'categorie_label' => $article->categorie_labell,
                'image_url' => $article->image_url,
                'publie_at' => $article->publie_at?->format('d/m/Y a H:i'),
                'publie_at_iso' => $article->publie_at?->toISOString(),
                'vues_count' => $article->vues_count,
                'auteur' => $article->auteur?->name,
                'extrait' => $extrait,
            ],
            'commentaires' => $commentaires,
            'reactions' => $reactionCounts,
            'user_reaction' => $userReaction,
            'articles_recents' => $articlesRecents,
            'seo' => [
                'title' => "{$article->titre} - {$page->ville->nom} - Hub Citoyen",
                'description' => $extrait,
                'image' => $article->image_url ?? $page->image_couverture_url ?? $page->ville->blason_url,
                'url' => url()->current(),
                'type' => 'article',
            ],
        ]);
    }

    // ========================================================================
    // ADMIN CRUD
    // ========================================================================

    public function adminIndex(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee);

        $articles = $page->articles()
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'slug' => $a->slug,
                'categorie' => $a->categorie,
                'publie' => $a->publie,
                'epingle' => $a->epingle,
                'publie_at' => $a->publie_at?->format('d/m/Y'),
                'vues_count' => $a->vues_count,
            ]);

        return Inertia::render('Commune/Admin/Articles', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee],
            'articles' => $articles,
        ]);
    }

    public function create(string $codeInsee): Response
    {
        $this->resolveAdminPage($codeInsee);

        return Inertia::render('Commune/Admin/ArticleForm', [
            'code_insee' => $codeInsee,
            'categories' => CommuneArticle::CATEGORIES,
            'article' => null,
        ]);
    }

    public function store(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee);

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'extrait' => 'nullable|string|max:500',
            'categorie' => 'required|string|in:'.implode(',', array_keys(CommuneArticle::CATEGORIES)),
            'epingle' => 'boolean',
            'publie' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("communes/{$codeInsee}/articles", 'public');
        }

        $article = $page->articles()->create([
            'auteur_id' => $request->user()->id,
            'titre' => $validated['titre'],
            'slug' => Str::slug($validated['titre']),
            'contenu' => $validated['contenu'],
            'extrait' => $validated['extrait'] ?? null,
            'categorie' => $validated['categorie'],
            'epingle' => $validated['epingle'] ?? false,
            'publie' => $validated['publie'] ?? false,
            'publie_at' => ($validated['publie'] ?? false) ? now() : null,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('commune.admin.articles', $codeInsee)
            ->with('success', 'Article cree avec succes.');
    }

    public function edit(string $codeInsee, string $slug): Response
    {
        $page = $this->resolveAdminPage($codeInsee);
        $article = $page->articles()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Commune/Admin/ArticleForm', [
            'code_insee' => $codeInsee,
            'categories' => CommuneArticle::CATEGORIES,
            'article' => [
                'id' => $article->id,
                'titre' => $article->titre,
                'slug' => $article->slug,
                'contenu' => $article->contenu,
                'extrait' => $article->extrait,
                'categorie' => $article->categorie,
                'epingle' => $article->epingle,
                'publie' => $article->publie,
                'image_url' => $article->image_url,
            ],
        ]);
    }

    public function update(Request $request, string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $article = $page->articles()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'extrait' => 'nullable|string|max:500',
            'categorie' => 'required|string|in:'.implode(',', array_keys(CommuneArticle::CATEGORIES)),
            'epingle' => 'boolean',
            'publie' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store("communes/{$codeInsee}/articles", 'public');
        }

        $wasDraft = ! $article->publie;
        $article->update(array_filter([
            'titre' => $validated['titre'],
            'contenu' => $validated['contenu'],
            'extrait' => $validated['extrait'] ?? null,
            'categorie' => $validated['categorie'],
            'epingle' => $validated['epingle'] ?? false,
            'publie' => $validated['publie'] ?? false,
            'publie_at' => ($validated['publie'] ?? false) && $wasDraft ? now() : $article->publie_at,
            'image_path' => $validated['image_path'] ?? $article->image_path,
        ]));

        return redirect()->route('commune.admin.articles', $codeInsee)
            ->with('success', 'Article mis a jour.');
    }

    public function destroy(string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $page->articles()->where('slug', $slug)->firstOrFail()->delete();

        return redirect()->route('commune.admin.articles', $codeInsee)
            ->with('success', 'Article supprime.');
    }

    public function publier(string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $article = $page->articles()->where('slug', $slug)->firstOrFail();
        $article->publier();

        return back()->with('success', 'Article publie.');
    }

    private function resolveAdminPage(string $codeInsee): CommunePage
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if (! $page->estAdministrePar(auth()->user())) {
            abort(403, 'Vous n\'avez pas acces a l\'administration de cette commune.');
        }

        return $page;
    }
}
