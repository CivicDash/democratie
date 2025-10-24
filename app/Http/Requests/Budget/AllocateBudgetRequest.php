<?php

namespace App\Http\Requests\Budget;

use App\Models\Sector;
use App\Models\UserAllocation;
use Illuminate\Foundation\Http\FormRequest;

class AllocateBudgetRequest extends FormRequest
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
            'sector_id' => ['required', 'exists:sectors,id'],
            'allocated_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sector_id.required' => 'Le secteur est obligatoire.',
            'sector_id.exists' => 'Le secteur sélectionné n\'existe pas.',
            'allocated_percent.required' => 'Le pourcentage d\'allocation est obligatoire.',
            'allocated_percent.numeric' => 'Le pourcentage doit être un nombre.',
            'allocated_percent.min' => 'Le pourcentage doit être positif.',
            'allocated_percent.max' => 'Le pourcentage ne peut pas dépasser 100%.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sector = Sector::find($this->sector_id);
            
            if ($sector) {
                $percent = $this->allocated_percent;
                
                // Vérifier les contraintes min/max du secteur
                if ($percent < $sector->min_allocation_percent) {
                    $validator->errors()->add(
                        'allocated_percent',
                        "Le pourcentage minimum pour {$sector->name} est {$sector->min_allocation_percent}%."
                    );
                }
                
                if ($percent > $sector->max_allocation_percent) {
                    $validator->errors()->add(
                        'allocated_percent',
                        "Le pourcentage maximum pour {$sector->name} est {$sector->max_allocation_percent}%."
                    );
                }
                
                // Vérifier que le total ne dépasse pas 100%
                $currentTotal = UserAllocation::where('user_id', $this->user()->id)
                    ->where('sector_id', '!=', $this->sector_id)
                    ->sum('allocated_percent');
                
                if ($currentTotal + $percent > 100) {
                    $validator->errors()->add(
                        'allocated_percent',
                        "Le total des allocations ne peut pas dépasser 100% (actuel: {$currentTotal}%)."
                    );
                }
            }
        });
    }
}

