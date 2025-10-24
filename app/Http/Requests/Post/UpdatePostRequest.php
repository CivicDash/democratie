<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');
        return $this->user()->can('update', $post);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Le contenu du post est obligatoire.',
            'content.min' => 'Le post doit contenir au moins :min caractères.',
            'content.max' => 'Le post ne peut pas dépasser :max caractères.',
        ];
    }
}

