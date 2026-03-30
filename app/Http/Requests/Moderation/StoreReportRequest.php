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
     * Raisons de signalement valides
     */
    public const VALID_REASONS = [
        'spam',
        'harassment',
        'hate_speech',
        'violence',
        'misinformation',
        'inappropriate',
        'off_topic',
        'impersonation',
        'copyright',
        'personal_data',
        'other',
    ];

    /**
     * Mapping des types courts vers les classes complètes
     */
    public const TYPE_MAPPING = [
        'topic' => 'App\Models\Topic',
        'post' => 'App\Models\Post',
        'comment' => 'App\Models\Post',
        'user' => 'App\Models\User',
    ];

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir les types courts en classes complètes
        $type = $this->reportable_type;
        if (isset(self::TYPE_MAPPING[$type])) {
            $this->merge([
                'reportable_type' => self::TYPE_MAPPING[$type],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $validReasons = implode(',', self::VALID_REASONS);

        return [
            'reportable_type' => ['required', 'string', 'in:App\Models\Post,App\Models\Topic,App\Models\User'],
            'reportable_id' => ['required'],
            'reason' => ['required', 'string', 'in:'.$validReasons],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'reason.in' => 'La raison du signalement est invalide.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
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
                if (! $exists) {
                    $validator->errors()->add('reportable_id', 'Le contenu signalé n\'existe pas.');
                }
            }
        });
    }
}
