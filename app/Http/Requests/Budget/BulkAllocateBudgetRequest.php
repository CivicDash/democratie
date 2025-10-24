<?php

namespace App\Http\Requests\Budget;

use App\Models\Sector;
use App\Models\UserAllocation;
use Illuminate\Foundation\Http\FormRequest;

class BulkAllocateBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', UserAllocation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'allocations' => ['required', 'array'],
            'allocations.*' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'allocations.required' => 'Les allocations sont obligatoires.',
            'allocations.*.numeric' => 'Chaque pourcentage doit être un nombre.',
            'allocations.*.min' => 'Chaque pourcentage doit être positif.',
            'allocations.*.max' => 'Chaque pourcentage ne peut pas dépasser 100%.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allocations = $this->allocations ?? [];
            
            // Vérifier que le total = 100%
            $total = array_sum($allocations);
            if (abs($total - 100.0) > 0.01) {
                $validator->errors()->add(
                    'allocations',
                    "Le total des allocations doit être égal à 100% (actuel: {$total}%)."
                );
            }
            
            // Vérifier les contraintes de chaque secteur
            foreach ($allocations as $sectorId => $percent) {
                $sector = Sector::find($sectorId);
                
                if (!$sector) {
                    $validator->errors()->add(
                        "allocations.{$sectorId}",
                        "Le secteur {$sectorId} n'existe pas."
                    );
                    continue;
                }
                
                if ($percent < $sector->min_allocation_percent) {
                    $validator->errors()->add(
                        "allocations.{$sectorId}",
                        "Le pourcentage minimum pour {$sector->name} est {$sector->min_allocation_percent}%."
                    );
                }
                
                if ($percent > $sector->max_allocation_percent) {
                    $validator->errors()->add(
                        "allocations.{$sectorId}",
                        "Le pourcentage maximum pour {$sector->name} est {$sector->max_allocation_percent}%."
                    );
                }
            }
        });
    }
}

