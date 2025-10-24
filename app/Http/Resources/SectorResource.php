<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectorResource extends JsonResource
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
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            
            // Average allocation (si calculé)
            'average_allocation' => $this->when(
                isset($this->average_allocation),
                fn() => round($this->average_allocation, 2)
            ),
            
            // Participant count (si calculé)
            'participant_count' => $this->when(
                isset($this->participant_count),
                $this->participant_count
            ),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
