<?php

namespace App\Http\Requests\Document;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('upload', Document::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:pdf,doc,docx,jpg,jpeg,png,txt,zip'
            ],
            'documentable_type' => ['required', 'string', 'in:App\Models\Topic,App\Models\Post'],
            'documentable_id' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier est obligatoire.',
            'file.file' => 'Le fichier est invalide.',
            'file.max' => 'Le fichier ne peut pas dépasser 10 MB.',
            'file.mimes' => 'Le fichier doit être de type : pdf, doc, docx, jpg, jpeg, png, txt ou zip.',
            'documentable_type.required' => 'Le type de contenu est obligatoire.',
            'documentable_type.in' => 'Le type de contenu est invalide.',
            'documentable_id.required' => 'L\'identifiant du contenu est obligatoire.',
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
            $type = $this->documentable_type;
            $id = $this->documentable_id;
            
            if (class_exists($type)) {
                $exists = $type::find($id);
                if (!$exists) {
                    $validator->errors()->add('documentable_id', 'Le contenu associé n\'existe pas.');
                }
            }
        });
    }
}

