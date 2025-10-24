<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BallotResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'ballot_type' => $this->resource['ballot_type'] ?? null,
            'total_votes' => $this->resource['total_votes'] ?? 0,
            
            // Binary results
            'yes' => $this->when(
                isset($this->resource['yes']),
                $this->resource['yes']
            ),
            'no' => $this->when(
                isset($this->resource['no']),
                $this->resource['no']
            ),
            
            // Multiple choice results
            'choices' => $this->when(
                isset($this->resource['choices']),
                $this->resource['choices']
            ),
            
            // Percentages
            'percentages' => $this->when(
                $this->resource['total_votes'] > 0,
                $this->calculatePercentages()
            ),
            
            // Metadata
            'ballot_ends_at' => $this->resource['ballot_ends_at'] ?? null,
            'is_ended' => $this->when(
                isset($this->resource['ballot_ends_at']),
                fn() => now()->gt($this->resource['ballot_ends_at'])
            ),
        ];
    }

    /**
     * Calculer les pourcentages
     */
    protected function calculatePercentages(): array
    {
        $total = $this->resource['total_votes'];
        
        if ($total === 0) {
            return [];
        }

        if (isset($this->resource['yes'], $this->resource['no'])) {
            // Binary
            return [
                'yes' => round(($this->resource['yes'] / $total) * 100, 2),
                'no' => round(($this->resource['no'] / $total) * 100, 2),
            ];
        }

        if (isset($this->resource['choices'])) {
            // Multiple choice
            $percentages = [];
            foreach ($this->resource['choices'] as $choice => $count) {
                $percentages[$choice] = round(($count / $total) * 100, 2);
            }
            return $percentages;
        }

        return [];
    }
}
