<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            
            // Reporter (anonyme pour les non-modérateurs)
            'reporter' => $this->when(
                $request->user()?->hasAnyRole(['moderator', 'admin']),
                new UserResource($this->whenLoaded('reporter'))
            ),
            
            // Assignee
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            
            // Reportable (polymorphic)
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reportable' => $this->when(
                $this->relationLoaded('reportable'),
                function () {
                    return match ($this->reportable_type) {
                        'App\\Models\\Topic' => new TopicResource($this->reportable),
                        'App\\Models\\Post' => new PostResource($this->reportable),
                        'App\\Models\\User' => new UserResource($this->reportable),
                        default => null,
                    };
                }
            ),
            
            // Resolution
            'resolution_note' => $this->when(
                $this->status === 'resolved',
                $this->resolution_note
            ),
            'resolved_at' => $this->resolved_at?->toISOString(),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Links
            'links' => [
                'assign' => route('api.moderation.reports.assign', $this->id),
                'resolve' => route('api.moderation.reports.resolve', $this->id),
                'reject' => route('api.moderation.reports.reject', $this->id),
            ],
        ];
    }
}
