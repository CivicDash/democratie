<?php

namespace App\Http\Requests\Moderation;

use App\Models\Sanction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSanctionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $targetUser = $this->route('user');
        return $this->user()->can('create', [Sanction::class, $targetUser]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['warning', 'mute', 'ban'])],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de sanction est obligatoire.',
            'type.in' => 'Le type de sanction doit être : warning, mute ou ban.',
            'reason.required' => 'La raison de la sanction est obligatoire.',
            'reason.min' => 'La raison doit contenir au moins :min caractères.',
            'reason.max' => 'La raison ne peut pas dépasser :max caractères.',
            'duration_days.min' => 'La durée doit être d\'au moins 1 jour.',
            'duration_days.max' => 'La durée ne peut pas dépasser 365 jours.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Un warning ne peut pas avoir de durée
            if ($this->type === 'warning' && $this->duration_days) {
                $validator->errors()->add('duration_days', 'Un avertissement ne peut pas avoir de durée.');
            }
            
            // Un ban permanent (type=ban sans duration) nécessite le rôle admin
            if ($this->type === 'ban' && !$this->duration_days && !$this->user()->hasRole('admin')) {
                $validator->errors()->add('type', 'Seuls les admins peuvent créer des bans permanents.');
            }
        });
    }
}

