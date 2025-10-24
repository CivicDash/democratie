<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAllocationResource extends JsonResource
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
            'user_id' => $this->user_id,
            'sector' => new SectorResource($this->whenLoaded('sector')),
            'sector_id' => $this->sector_id,
            'percentage' => round($this->percentage, 2),
            
            // Comparison with average (si fourni)
            'difference_from_average' => $this->when(
                isset($this->difference_from_average),
                fn() => round($this->difference_from_average, 2)
            ),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
