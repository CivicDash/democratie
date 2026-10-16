<?php

namespace Database\Factories;

use App\Models\IngestionDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class IngestionDocumentFactory extends Factory
{
    protected $model = IngestionDocument::class;

    public function definition(): array
    {
        return [
            'type' => 'video',
            'titre' => 'Prise de parole '.$this->faker->words(3, true),
            'url' => 'https://www.youtube.com/watch?v='.$this->faker->regexify('[A-Za-z0-9]{11}'),
            'date_publication' => '2026-10-14',
            'statut' => 'extrait',
            'porteur_type' => 'candidat',
        ];
    }

    /** Document d'un parti ou d'un groupe, endossement complet (publiable « Autour »). */
    public function porte(string $type = 'groupe_parlementaire'): static
    {
        return $this->state(fn () => [
            'porteur_type' => $type,
            'porteur_libelle' => 'Groupe Exemple à l\'Assemblée nationale',
            'endossement_url' => 'https://exemple.fr/endossement',
            'endossement_date' => '2026-10-06',
            'endossement_note' => 'Présenté le 06/10/2026 en présence du candidat.',
        ]);
    }
}
