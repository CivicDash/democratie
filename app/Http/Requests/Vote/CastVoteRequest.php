<?php

namespace App\Http\Requests\Vote;

use Illuminate\Foundation\Http\FormRequest;

class CastVoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Note: L'autorisation réelle est vérifiée par le token, pas par l'utilisateur.
     */
    public function authorize(): bool
    {
        // Tout utilisateur authentifié peut tenter de voter (le token vérifiera)
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:128'], // SHA512
            'vote' => ['required', 'array'],
            'vote.choice' => ['required', 'string'], // La validation précise dépend du type de scrutin
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Le token de vote est obligatoire.',
            'token.size' => 'Le token de vote est invalide.',
            'vote.required' => 'Le vote est obligatoire.',
            'vote.choice.required' => 'Le choix de vote est obligatoire.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $topic = $this->route('topic');
            
            // Valider le choix selon le type de ballot
            if ($topic->ballot_type === 'yes_no') {
                if (!in_array($this->input('vote.choice'), ['yes', 'no', 'abstain'])) {
                    $validator->errors()->add('vote.choice', 'Le choix doit être : yes, no ou abstain.');
                }
            } elseif ($topic->ballot_type === 'multiple_choice') {
                $validOptions = json_decode($topic->ballot_options, true) ?? [];
                if (!in_array($this->input('vote.choice'), $validOptions)) {
                    $validator->errors()->add('vote.choice', 'Le choix n\'est pas valide pour ce scrutin.');
                }
            }
        });
    }
}

