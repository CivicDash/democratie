<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommuneArticle;
use App\Models\CommuneCommentaire;
use App\Models\CommuneEvenement;
use App\Models\CommunePage;
use App\Models\CommuneReaction;
use Illuminate\Http\Request;

class CommuneCommentaireController extends Controller
{
    public function store(Request $request, string $codeInsee, string $type, string $id)
    {
        $validated = $request->validate([
            'contenu' => 'required|string|max:2000',
            'parent_id' => 'nullable|uuid|exists:commune_commentaires,id',
        ]);

        $commentable = $this->resolveCommentable($type, $id);

        $commentaire = CommuneCommentaire::create([
            'user_id' => $request->user()->id,
            'commentable_type' => get_class($commentable),
            'commentable_id' => $commentable->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'contenu' => $validated['contenu'],
        ]);

        return back()->with('success', 'Commentaire publie.');
    }

    public function destroy(Request $request, string $codeInsee, string $commentaireId)
    {
        $commentaire = CommuneCommentaire::findOrFail($commentaireId);

        $page = CommunePage::where('code_insee', $codeInsee)->firstOrFail();
        $user = $request->user();

        $isOwner = $commentaire->user_id === $user->id;
        $isAdmin = $page->estAdministrePar($user) || $user->hasRole('admin');

        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }

        $commentaire->delete();

        return back()->with('success', 'Commentaire supprime.');
    }

    public function signaler(Request $request, string $codeInsee, string $commentaireId)
    {
        $commentaire = CommuneCommentaire::findOrFail($commentaireId);
        $commentaire->increment('signalements_count');

        if ($commentaire->signalements_count >= 5) {
            $commentaire->update(['masque' => true, 'masque_raison' => 'Signale par la communaute']);
        }

        return back()->with('success', 'Signalement enregistre.');
    }

    public function react(Request $request, string $codeInsee, string $type, string $id)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:'.implode(',', CommuneReaction::TYPES),
        ]);

        $reactable = $this->resolveCommentable($type, $id);

        $existing = CommuneReaction::where('user_id', $request->user()->id)
            ->where('reactable_type', get_class($reactable))
            ->where('reactable_id', $reactable->id)
            ->where('type', $validated['type'])
            ->first();

        if ($existing) {
            $existing->delete();

            return back();
        }

        CommuneReaction::where('user_id', $request->user()->id)
            ->where('reactable_type', get_class($reactable))
            ->where('reactable_id', $reactable->id)
            ->delete();

        CommuneReaction::create([
            'user_id' => $request->user()->id,
            'reactable_type' => get_class($reactable),
            'reactable_id' => $reactable->id,
            'type' => $validated['type'],
        ]);

        return back();
    }

    private function resolveCommentable(string $type, string $id)
    {
        return match ($type) {
            'article' => CommuneArticle::findOrFail($id),
            'evenement' => CommuneEvenement::findOrFail($id),
            default => abort(404),
        };
    }
}
