<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'document_type' => $this->document_type,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'file_hash' => $this->file_hash,
            'source_url' => $this->source_url,
            
            // Verification status
            'verification_status' => $this->verification_status,
            'verified_at' => $this->verified_at?->toISOString(),
            
            // Uploader
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            
            // Verifications
            'verifications' => VerificationResource::collection($this->whenLoaded('verifications')),
            'verifications_count' => $this->when(
                isset($this->verifications_count),
                $this->verifications_count
            ),
            
            // File info
            'file_size_formatted' => $this->when(
                $this->file_size,
                fn() => $this->formatFileSize($this->file_size)
            ),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Links
            'links' => [
                'self' => route('api.documents.show', $this->id),
                'download' => route('api.documents.download', $this->id),
                'verify' => $this->when(
                    $this->verification_status === 'pending',
                    route('api.documents.verify', $this->id)
                ),
            ],
        ];
    }

    /**
     * Formater la taille du fichier
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
