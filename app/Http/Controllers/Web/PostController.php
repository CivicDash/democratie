<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Topic;
use App\Services\TopicService;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Requests\Post\VotePostRequest;

class PostController extends Controller
{
    public function __construct(
        protected TopicService $topicService
    ) {}

    /**
     * Créer une réponse
     */
    public function store(StorePostRequest $request, Topic $topic)
    {
        $post = $this->topicService->createPost(
            $topic,
            $request->user(),
            $request->validated()
        );

        return back()->with('success', 'Réponse ajoutée avec succès !');
    }

    /**
     * Mettre à jour une réponse
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->topicService->updatePost($post, $request->validated());

        return back()->with('success', 'Réponse mise à jour avec succès !');
    }

    /**
     * Supprimer une réponse
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $this->topicService->deletePost($post);

        return back()->with('success', 'Réponse supprimée avec succès.');
    }

    /**
     * Voter sur une réponse
     */
    public function vote(VotePostRequest $request, Post $post)
    {
        $this->topicService->votePost(
            $post,
            $request->user(),
            $request->validated()['vote_type']
        );

        return back();
    }
}

