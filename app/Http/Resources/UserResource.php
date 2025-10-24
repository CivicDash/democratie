<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->when($this->shouldShowEmail($request), $this->email),
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'created_at' => $this->created_at?->toISOString(),
            'is_online' => $this->when(
                $this->last_seen_at !== null,
                $this->last_seen_at?->gt(now()->subMinutes(5))
            ),
        ];
    }

    /**
     * Déterminer si l'email doit être affiché
     */
    protected function shouldShowEmail(Request $request): bool
    {
        // Email visible uniquement pour l'utilisateur lui-même ou les admins
        return $request->user()?->id === $this->id 
            || $request->user()?->hasRole('admin');
    }
}
