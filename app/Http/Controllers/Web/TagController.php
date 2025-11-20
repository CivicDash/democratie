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
        $tags = Tag::orderBy('name')->get();
        $popularTags = Tag::popular(10)->get();

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
            'popularTags' => $popularTags,
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

        // Scrutins associés
        $scrutins = $tag->scrutins()
            ->orderBy('date_scrutin', 'desc')
            ->paginate(20);

        // Dossiers associés
        $dossiers = $tag->dossiersLegislatifs()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Topics associés
        $topics = $tag->topics()
            ->with('user')
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Tags/Show', [
            'tag' => [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'name' => $tag->name,
                'color' => $tag->color,
                'icon' => $tag->icon,
                'description' => $tag->description,
                'usage_count' => $tag->usage_count,
            ],
            'scrutins' => [
                'data' => $scrutins->map(fn($s) => [
                    'uid' => $s->uid,
                    'numero' => $s->numero,
                    'titre' => $s->titre,
                    'date' => $s->date_scrutin?->format('d/m/Y'),
                    'nombre_pour' => $s->pour,
                    'nombre_contre' => $s->contre,
                    'nombre_abstention' => $s->abstentions,
                    'resultat_libelle' => $s->resultat_libelle,
                ]),
                'total' => $scrutins->total(),
            ],
            'dossiers' => [
                'data' => $dossiers->map(fn($d) => [
                    'uid' => $d->uid,
                    'titre' => $d->titre,
                    'titre_court' => $d->titre_court,
                    'legislature' => $d->legislature,
                ]),
                'total' => $dossiers->total(),
            ],
            'topics' => [
                'data' => $topics->map(fn($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'user_name' => $t->user->name ?? 'Anonyme',
                    'created_at' => $t->created_at->diffForHumans(),
                    'comments_count' => $t->comments_count,
                    'votes_count' => $t->ballot_type ? $t->votes()->count() : null,
                ]),
                'total' => $topics->total(),
            ],
        ]);
    }
}

