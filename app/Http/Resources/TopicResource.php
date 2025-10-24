<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'scope' => $this->scope,
            
            // Author
            'author' => new UserResource($this->whenLoaded('author')),
            
            // Territory
            'region' => new TerritoryRegionResource($this->whenLoaded('region')),
            'department' => new TerritoryDepartmentResource($this->whenLoaded('department')),
            
            // Ballot info
            'ballot_type' => $this->ballot_type,
            'ballot_options' => $this->when($this->ballot_type === 'multiple', $this->ballot_options),
            'ballot_ends_at' => $this->ballot_ends_at?->toISOString(),
            
            // Status
            'is_open' => !$this->closed_at && !$this->archived_at,
            'is_closed' => (bool) $this->closed_at,
            'is_archived' => (bool) $this->archived_at,
            'closed_at' => $this->closed_at?->toISOString(),
            'archived_at' => $this->archived_at?->toISOString(),
            
            // Counts
            'posts_count' => $this->when(isset($this->posts_count), $this->posts_count),
            'ballots_count' => $this->when(isset($this->ballots_count), $this->ballots_count),
            
            // Posts (si chargés)
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Links
            'links' => [
                'self' => route('api.topics.show', $this->id),
                'posts' => route('api.topics.posts.index', $this->id),
                'vote' => $this->when(
                    $this->ballot_type,
                    route('api.topics.vote.results', $this->id)
                ),
            ],
        ];
    }
}
