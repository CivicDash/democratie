<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $document = $this->route('document');
        return $this->user()->can('verify', $document);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['verified', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Le statut de vérification est obligatoire.',
            'status.in' => 'Le statut doit être : verified ou rejected.',
            'notes.max' => 'Les notes ne peuvent pas dépasser :max caractères.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Si rejected, des notes sont obligatoires
            if ($this->status === 'rejected' && empty($this->notes)) {
                $validator->errors()->add('notes', 'Des notes sont obligatoires lors du rejet d\'un document.');
            }
        });
    }
}

