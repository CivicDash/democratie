<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service pour gérer les topics et les posts
 */
class TopicService
{
    /**
     * Crée un nouveau topic.
     */
    public function createTopic(User $author, array $data): Topic
    {
        if (!$author->can('create', Topic::class)) {
            throw new RuntimeException('User cannot create topics.');
        }

        return DB::transaction(function () use ($author, $data) {
            $topic = Topic::create([
                'author_id' => $author->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $data['type'] ?? 'debate',
                'status' => $data['status'] ?? 'draft',
                'scope' => $data['scope'] ?? 'national',
                'region_id' => $data['region_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
            ]);

            return $topic;
        });
    }

    /**
     * Met à jour un topic.
     */
    public function updateTopic(Topic $topic, User $user, array $data): Topic
    {
        if (!$user->can('update', $topic)) {
            throw new RuntimeException('User cannot update this topic.');
        }

        $topic->update($data);

        return $topic->fresh();
    }

    /**
     * Ferme un topic.
     */
    public function closeTopic(Topic $topic, User $user): Topic
    {
        if (!$user->can('close', $topic)) {
            throw new RuntimeException('User cannot close this topic.');
        }

        $topic->update(['status' => 'closed']);

        return $topic->fresh();
    }

    /**
     * Archive un topic.
     */
    public function archiveTopic(Topic $topic, User $user): Topic
    {
        if (!$user->can('archive', $topic)) {
            throw new RuntimeException('User cannot archive this topic.');
        }

        $topic->update(['status' => 'archived']);

        return $topic->fresh();
    }

    /**
     * Crée un post dans un topic.
     */
    public function createPost(Topic $topic, User $user, string $content, ?int $parentId = null): Post
    {
        if (!$user->can('create', [Post::class, $topic])) {
            throw new RuntimeException('User cannot post in this topic.');
        }

        return DB::transaction(function () use ($topic, $user, $content, $parentId) {
            $post = Post::create([
                'topic_id' => $topic->id,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'content' => $content,
            ]);

            return $post;
        });
    }

    /**
     * Met à jour un post.
     */
    public function updatePost(Post $post, User $user, string $content): Post
    {
        if (!$user->can('update', $post)) {
            throw new RuntimeException('User cannot update this post.');
        }

        $post->update(['content' => $content]);

        return $post->fresh();
    }

    /**
     * Vote sur un post (upvote/downvote).
     */
    public function voteOnPost(Post $post, User $user, string $voteType): array
    {
        if (!$user->can('vote', $post)) {
            throw new RuntimeException('User cannot vote on this post.');
        }

        if (!in_array($voteType, ['upvote', 'downvote'])) {
            throw new RuntimeException('Invalid vote type.');
        }

        return DB::transaction(function () use ($post, $user, $voteType) {
            // Vérifier si le user a déjà voté
            $existingVote = $post->votes()->where('user_id', $user->id)->first();

            if ($existingVote) {
                // Si même type, on retire le vote
                if ($existingVote->type === $voteType) {
                    $existingVote->delete();
                    
                    if ($voteType === 'upvote') {
                        $post->decrement('upvotes');
                    } else {
                        $post->decrement('downvotes');
                    }

                    return [
                        'action' => 'removed',
                        'vote_type' => $voteType,
                        'score' => $post->fresh()->upvotes - $post->fresh()->downvotes,
                    ];
                } else {
                    // Sinon on change le vote
                    $existingVote->update(['type' => $voteType]);
                    
                    if ($voteType === 'upvote') {
                        $post->increment('upvotes');
                        $post->decrement('downvotes');
                    } else {
                        $post->decrement('upvotes');
                        $post->increment('downvotes');
                    }

                    return [
                        'action' => 'changed',
                        'vote_type' => $voteType,
                        'score' => $post->fresh()->upvotes - $post->fresh()->downvotes,
                    ];
                }
            } else {
                // Créer le vote
                $post->votes()->create([
                    'user_id' => $user->id,
                    'type' => $voteType,
                ]);

                if ($voteType === 'upvote') {
                    $post->increment('upvotes');
                } else {
                    $post->increment('downvotes');
                }

                return [
                    'action' => 'added',
                    'vote_type' => $voteType,
                    'score' => $post->fresh()->upvotes - $post->fresh()->downvotes,
                ];
            }
        });
    }

    /**
     * Obtient les topics populaires (plus de posts/votes).
     */
    public function getTrendingTopics(int $limit = 10, ?int $days = 7): Collection
    {
        $since = $days ? now()->subDays($days) : null;

        return Topic::where('status', 'open')
            ->when($since, fn($query) => $query->where('created_at', '>=', $since))
            ->withCount(['posts' => fn($query) => $query->where('created_at', '>=', $since ?? now()->subYear())])
            ->orderBy('posts_count', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Obtient les posts les mieux notés d'un topic.
     */
    public function getTopPosts(Topic $topic, int $limit = 10): Collection
    {
        return Post::where('topic_id', $topic->id)
            ->where('is_hidden', false)
            ->orderByRaw('(upvotes - downvotes) DESC')
            ->take($limit)
            ->get();
    }

    /**
     * Obtient les réponses d'un post (threading).
     */
    public function getReplies(Post $post): Collection
    {
        return Post::where('parent_id', $post->id)
            ->where('is_hidden', false)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Supprime un post (soft delete possible).
     */
    public function deletePost(Post $post, User $user): bool
    {
        if (!$user->can('delete', $post)) {
            throw new RuntimeException('User cannot delete this post.');
        }

        return DB::transaction(function () use ($post) {
            // Supprimer les réponses aussi
            Post::where('parent_id', $post->id)->delete();
            
            return $post->delete();
        });
    }

    /**
     * Supprime un topic.
     */
    public function deleteTopic(Topic $topic, User $user): bool
    {
        if (!$user->can('delete', $topic)) {
            throw new RuntimeException('User cannot delete this topic.');
        }

        return DB::transaction(function () use ($topic) {
            // Supprimer tous les posts
            Post::where('topic_id', $topic->id)->delete();
            
            return $topic->delete();
        });
    }

    /**
     * Obtient les statistiques d'un topic.
     */
    public function getTopicStats(Topic $topic): array
    {
        $posts = Post::where('topic_id', $topic->id);

        return [
            'total_posts' => $posts->count(),
            'total_participants' => $posts->distinct('user_id')->count(),
            'total_upvotes' => $posts->sum('upvotes'),
            'total_downvotes' => $posts->sum('downvotes'),
            'hidden_posts' => $posts->where('is_hidden', true)->count(),
        ];
    }
}

