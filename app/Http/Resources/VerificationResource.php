<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
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
            'is_valid' => (bool) $this->is_valid,
            'comment' => $this->comment,
            
            // Verifier
            'verifier' => new UserResource($this->whenLoaded('verifier')),
            
            // Document
            'document' => new DocumentResource($this->whenLoaded('document')),
            
            // Timestamp
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
