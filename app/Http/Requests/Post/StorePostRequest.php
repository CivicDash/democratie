<?php

namespace App\Http\Requests\Post;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $topic = $this->route('topic');
        return $this->user()->can('create', [Post::class, $topic]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:10000'],
            'parent_id' => ['nullable', 'exists:posts,id'],
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
            'parent_id.exists' => 'Le post parent n\'existe pas.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Vérifier que le parent_id appartient au même topic
            if ($this->parent_id) {
                $parentPost = Post::find($this->parent_id);
                $topic = $this->route('topic');
                
                if ($parentPost && $parentPost->topic_id !== $topic->id) {
                    $validator->errors()->add('parent_id', 'Le post parent n\'appartient pas à ce topic.');
                }
            }
        });
    }
}

