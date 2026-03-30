<?php

namespace App\Http\Requests;

use App\Models\AffaireSource;
use Illuminate\Foundation\Http\FormRequest;

class AjouterSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'type_source' => 'required|in:'.implode(',', AffaireSource::TYPES_SOURCE()),
            'url' => 'required|url|max:1000',
            'titre' => 'nullable|string|max:500',
            'media' => 'nullable|string|max:200',
            'date_publication' => 'nullable|date',
            'auteur' => 'nullable|string|max:200',
            'extrait' => 'nullable|string|max:5000',
            'archive_url' => 'nullable|url|max:1000',
            'fiabilite' => 'required|in:haute,moyenne,basse',
        ];
    }
}
