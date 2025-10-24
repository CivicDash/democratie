<?php

namespace App\Http\Requests\Topic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBallotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $topic = $this->route('topic');
        return $this->user()->can('createBallot', $topic);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ballot_type' => ['required', Rule::in(['yes_no', 'multiple_choice', 'ranked'])],
            'ballot_options' => ['required_if:ballot_type,multiple_choice,ranked', 'array', 'min:2'],
            'ballot_options.*' => ['string', 'max:255'],
            'voting_opens_at' => ['required', 'date', 'after:now'],
            'voting_deadline_at' => ['required', 'date', 'after:voting_opens_at'],
            'allow_abstention' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ballot_type.required' => 'Le type de scrutin est obligatoire.',
            'ballot_type.in' => 'Le type de scrutin doit être : yes_no, multiple_choice ou ranked.',
            'ballot_options.required_if' => 'Les options de vote sont obligatoires pour ce type de scrutin.',
            'ballot_options.min' => 'Au moins 2 options sont requises.',
            'voting_opens_at.after' => 'La date d\'ouverture doit être dans le futur.',
            'voting_deadline_at.after' => 'La date de clôture doit être après la date d\'ouverture.',
        ];
    }
}

