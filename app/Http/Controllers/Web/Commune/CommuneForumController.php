<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommunePage;
use App\Models\Topic;
use Inertia\Inertia;
use Inertia\Response;

class CommuneForumController extends Controller
{
    public function index(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if (! $page->forum_actif) {
            abort(404, 'Forum non actif pour cette commune.');
        }

        $topics = Topic::where('commune_code_insee', $codeInsee)
            ->where('status', 'published')
            ->with(['author:id,name', 'posts' => fn ($q) => $q->selectRaw('topic_id, COUNT(*) as count')->groupBy('topic_id')])
            ->withCount('posts')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->through(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'slug' => $t->slug,
                'type' => $t->type,
                'status' => $t->status,
                'author' => $t->author?->name,
                'posts_count' => $t->posts_count,
                'created_at' => $t->created_at->format('d/m/Y'),
                'updated_at' => $t->updated_at->diffForHumans(),
            ]);

        return Inertia::render('Commune/Forum', [
            'ville' => [
                'nom' => $page->ville->nom,
                'code_insee' => $codeInsee,
                'slug' => $page->ville->slug,
            ],
            'page' => [
                'statut' => $page->statut,
                'couleur_primaire' => $page->couleur_primaire,
            ],
            'topics' => $topics,
        ]);
    }
}
