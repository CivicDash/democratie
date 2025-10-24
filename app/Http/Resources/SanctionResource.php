<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SanctionResource extends JsonResource
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
            'type' => $this->type,
            'reason' => $this->reason,
            
            // User sanctionné (visible uniquement pour modérateurs/admin)
            'user' => $this->when(
                $request->user()?->hasAnyRole(['moderator', 'admin']),
                new UserResource($this->whenLoaded('user'))
            ),
            
            // Moderator
            'moderator' => new UserResource($this->whenLoaded('moderator')),
            
            // Duration
            'starts_at' => $this->starts_at->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            
            // Status
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_revoked' => (bool) $this->revoked_at,
            'revoked_at' => $this->revoked_at?->toISOString(),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            
            // Links
            'links' => [
                'revoke' => $this->when(
                    !$this->revoked_at,
                    route('api.moderation.sanctions.revoke', $this->id)
                ),
            ],
        ];
    }
}
