<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            
            // Author
            'author' => new UserResource($this->whenLoaded('author')),
            
            // Topic
            'topic_id' => $this->topic_id,
            'topic' => new TopicResource($this->whenLoaded('topic')),
            
            // Parent (pour les réponses)
            'parent_id' => $this->parent_id,
            'parent' => new PostResource($this->whenLoaded('parent')),
            
            // Replies (si chargées)
            'replies' => PostResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->when(isset($this->replies_count), $this->replies_count),
            
            // Vote score
            'vote_score' => $this->when(isset($this->vote_score), (int) $this->vote_score),
            'user_vote' => $this->when(
                $request->user(),
                function () use ($request) {
                    $vote = $this->votes()
                        ->where('user_id', $request->user()->id)
                        ->first();
                    return $vote?->vote_type;
                }
            ),
            
            // Status
            'is_pinned' => (bool) $this->is_pinned,
            'is_solution' => (bool) $this->is_solution,
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Links
            'links' => [
                'self' => route('api.posts.show', $this->id),
                'vote' => route('api.posts.vote', $this->id),
            ],
        ];
    }
}
