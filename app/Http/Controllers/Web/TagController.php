<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Liste de tous les tags
     * 
     * GET /tags
     */
    public function index(): Response
    {
        $tags = Tag::validated()
            ->orderBy('nom')
            ->get();

        $popularTags = Tag::validated()
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        return Inertia::render('Tags/Index', [
            'tags' => $tags->map(fn($t) => $this->formatTag($t)),
            'popularTags' => $popularTags->map(fn($t) => $this->formatTag($t)),
        ]);
    }

    /**
     * Afficher un tag et son contenu
     * 
     * GET /tags/{slug}
     */
    public function show(string $slug): Response
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        // Lois associées
        $lois = $tag->lois()
            ->with('etat')
            ->orderByDesc('loidatjo')
            ->limit(20)
            ->get();

        // Topics associés
        $topics = $tag->topics()
            ->with('user')
            ->withCount('posts')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Textes JO associés
        $textesJo = $tag->textesJo()
            ->orderByDesc('date_publication')
            ->limit(20)
            ->get();

        return Inertia::render('Tags/Show', [
            'tag' => $this->formatTag($tag),
            'lois' => [
                'data' => $lois->map(fn($l) => [
                    'loicod' => $l->loicod,
                    'titre' => $l->titre_court,
                    'numero' => $l->numero,
                    'date_jo' => $l->loidatjo?->format('d/m/Y'),
                    'etat' => $l->etat_libelle,
                    'etat_couleur' => $l->etat_couleur,
                ]),
                'total' => $tag->lois()->count(),
            ],
            'topics' => [
                'data' => $topics->map(fn($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'user_name' => $t->user->display_name ?? 'Anonyme',
                    'created_at' => $t->created_at->diffForHumans(),
                    'posts_count' => $t->posts_count ?? 0,
                ]),
                'total' => $tag->topics()->count(),
            ],
            'textesJo' => [
                'data' => $textesJo->map(fn($t) => [
                    'id' => $t->id,
                    'jorf_id' => $t->jorf_id,
                    'titre' => $t->titre_court,
                    'nature' => $t->nature,
                    'date_publication' => $t->date_publication?->format('d/m/Y'),
                    'legifrance_url' => $t->legifrance_url,
                ]),
                'total' => $tag->textesJo()->count(),
            ],
        ]);
    }

    /**
     * Format un tag pour le frontend
     */
    protected function formatTag(Tag $tag): array
    {
        return [
            'id' => $tag->id,
            'slug' => $tag->slug,
            'nom' => $tag->nom,
            'couleur' => $tag->couleur,
            'icone' => $tag->icone,
            'description' => $tag->description,
            'type' => $tag->type,
            'source' => $tag->source,
            'usage_count' => $tag->usage_count,
            'validated' => $tag->validated,
        ];
    }
}
