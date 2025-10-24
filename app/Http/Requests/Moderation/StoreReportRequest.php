<?php

namespace App\Http\Requests\Moderation;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Report::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', 'in:App\Models\Post,App\Models\Topic,App\Models\User'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reportable_type.required' => 'Le type de contenu est obligatoire.',
            'reportable_type.in' => 'Le type de contenu est invalide.',
            'reportable_id.required' => 'L\'identifiant du contenu est obligatoire.',
            'reason.required' => 'La raison du signalement est obligatoire.',
            'reason.min' => 'La raison doit contenir au moins :min caractères.',
            'reason.max' => 'La raison ne peut pas dépasser :max caractères.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Vérifier que le contenu existe
            $type = $this->reportable_type;
            $id = $this->reportable_id;
            
            if (class_exists($type)) {
                $exists = $type::find($id);
                if (!$exists) {
                    $validator->errors()->add('reportable_id', 'Le contenu signalé n\'existe pas.');
                }
            }
        });
    }
}

