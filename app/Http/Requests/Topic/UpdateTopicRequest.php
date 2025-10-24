<?php

namespace App\Http\Requests\Topic;

use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTopicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $topic = $this->route('topic');
        return $this->user()->can('update', $topic);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255', 'min:10'],
            'description' => ['sometimes', 'string', 'min:50'],
            'type' => ['sometimes', Rule::in(['debate', 'bill', 'referendum'])],
            'status' => ['sometimes', Rule::in(['draft', 'open', 'closed', 'archived'])],
            'scope' => ['sometimes', Rule::in(['national', 'region', 'dept'])],
            'region_id' => ['required_if:scope,region', 'nullable', 'exists:territories_regions,id'],
            'department_id' => ['required_if:scope,dept', 'nullable', 'exists:territories_departments,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Le titre doit contenir au moins :min caractères.',
            'description.min' => 'La description doit contenir au moins :min caractères.',
            'type.in' => 'Le type de topic doit être : debate, bill ou referendum.',
            'status.in' => 'Le statut doit être : draft, open, closed ou archived.',
            'scope.in' => 'Le scope doit être : national, region ou dept.',
        ];
    }
}

