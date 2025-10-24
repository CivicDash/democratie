<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VotePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');
        return $this->user()->can('vote', $post);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'vote' => ['required', Rule::in(['upvote', 'downvote'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'vote.required' => 'Le type de vote est obligatoire.',
            'vote.in' => 'Le type de vote doit être : upvote ou downvote.',
        ];
    }
}

