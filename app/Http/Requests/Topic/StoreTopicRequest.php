<?php

namespace App\Http\Requests\Topic;

use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Topic::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'min:10'],
            'description' => ['required', 'string', 'min:50'],
            'type' => ['required', Rule::in(['debate', 'bill', 'referendum'])],
            'status' => ['sometimes', Rule::in(['draft', 'open'])],
            'scope' => ['required', Rule::in(['national', 'region', 'dept'])],
            'region_id' => ['required_if:scope,region', 'nullable', 'exists:territories_regions,id'],
            'department_id' => ['required_if:scope,dept', 'nullable', 'exists:territories_departments,id'],
            
            // Pour les bills, seuls les législateurs peuvent
            'type.bill' => Rule::requiredIf(function () {
                return $this->input('type') === 'bill' 
                    && !$this->user()->hasPermissionTo('topics.bill');
            }),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du topic est obligatoire.',
            'title.min' => 'Le titre doit contenir au moins :min caractères.',
            'description.required' => 'La description du topic est obligatoire.',
            'description.min' => 'La description doit contenir au moins :min caractères.',
            'type.in' => 'Le type de topic doit être : debate, bill ou referendum.',
            'scope.in' => 'Le scope doit être : national, region ou dept.',
            'region_id.required_if' => 'La région est obligatoire pour un scope régional.',
            'department_id.required_if' => 'Le département est obligatoire pour un scope départemental.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'description' => 'description',
            'type' => 'type',
            'scope' => 'portée',
            'region_id' => 'région',
            'department_id' => 'département',
        ];
    }
}

