<?php

namespace App\Http\Requests;

use App\Models\AffaireJudiciaire;
use App\Models\AffaireSource;
use Illuminate\Foundation\Http\FormRequest;

class ValiderAffaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:500',
            'type_affaire' => 'required|in:' . implode(',', AffaireJudiciaire::TYPES_AFFAIRE()),
            'categorie' => 'required|in:' . implode(',', AffaireJudiciaire::CATEGORIES()),
            'statut_judiciaire' => 'required|in:' . implode(',', AffaireJudiciaire::STATUTS_JUDICIAIRES()),
            'description' => 'nullable|string|max:5000',
            'date_faits' => 'nullable|date',
            'date_mise_en_examen' => 'nullable|date|required_without_all:date_jugement_premiere_instance,date_condamnation_definitive',
            'date_jugement_premiere_instance' => 'nullable|date',
            'date_jugement_appel' => 'nullable|date',
            'date_jugement_cassation' => 'nullable|date',
            'date_condamnation_definitive' => 'nullable|date',
            'peine_prison_mois' => 'nullable|integer|min:0',
            'peine_prison_avec_sursis' => 'nullable|boolean',
            'peine_amende_euros' => 'nullable|numeric|min:0',
            'peine_ineligibilite_mois' => 'nullable|integer|min:0',
            'peine_complementaire' => 'nullable|string|max:2000',
            'juridiction' => 'nullable|string|max:255',
            'numero_dossier' => 'nullable|string|max:100',
            'lien_decision_justice' => 'nullable|url|max:500',
            'commentaire_validation' => 'nullable|string|max:2000',
            'sources' => 'required|array|min:1',
            'sources.*.url' => 'required|url',
            'sources.*.media' => 'required|string|max:200',
            'sources.*.type_source' => 'required|in:' . implode(',', AffaireSource::TYPES_SOURCE()),
            'sources.*.fiabilite' => 'required|in:haute,moyenne,basse',
            'sources.*.titre' => 'nullable|string|max:500',
            'sources.*.date_publication' => 'nullable|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $sources = collect($this->input('sources', []));
            $hasReliable = $sources->contains(fn ($s) =>
                in_array($s['fiabilite'] ?? '', ['haute', 'moyenne'])
            );
            if (!$hasReliable) {
                $v->errors()->add('sources',
                    'Au moins une source de fiabilité "haute" ou "moyenne" est requise pour valider.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre de l\'affaire est obligatoire.',
            'type_affaire.required' => 'Le type d\'affaire est obligatoire.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'statut_judiciaire.required' => 'Le statut judiciaire est obligatoire.',
            'sources.required' => 'Au moins une source est requise.',
            'sources.min' => 'Au moins une source est requise.',
            'sources.*.url.required' => 'L\'URL de la source est obligatoire.',
            'sources.*.url.url' => 'L\'URL de la source doit être valide.',
            'date_mise_en_examen.required_without_all' => 'Au moins une date (mise en examen, jugement ou condamnation) est requise.',
        ];
    }
}
