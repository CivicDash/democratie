<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'region' => new TerritoryRegionResource($this->whenLoaded('region')),
            'department' => new TerritoryDepartmentResource($this->whenLoaded('department')),
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toISOString(),
        ];
    }
}
