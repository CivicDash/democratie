<?php

namespace Database\Factories;

use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\ProgrammeTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

class IngestionPropositionFactory extends Factory
{
    protected $model = IngestionProposition::class;

    public function definition(): array
    {
        return [
            'document_id' => IngestionDocument::factory(),
            'candidat_id' => CandidatPresidentielle::factory(),
            'theme_id' => ProgrammeTheme::factory(),
            'type' => 'mesure',
            'resume_propose' => 'Instaurer la mesure '.$this->faker->words(3, true).'.',
            'citation_verbatim' => 'Nous instaurerons cette mesure dès le premier jour du mandat.',
            'timestamp_ou_paragraphe' => '00:12:30',
            'source_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'confiance' => 0.9,
            'verbatim_verifie' => true,
            'statut' => 'detecte',
        ];
    }

    /** Une personnalité de l'équipe de campagne, aux côtés du candidat. */
    public function equipe(string $nom = 'Éric Exemple', ?string $role = 'député, président de la commission des finances'): static
    {
        return $this->state(fn () => ['locuteur' => $nom, 'role_locuteur' => $role, 'qualite_locuteur' => 'equipe_campagne']);
    }

    /** Un responsable de parti : jamais une mesure du candidat. */
    public function parti(string $nom = 'Paul Parti', ?string $role = 'président du parti'): static
    {
        return $this->state(fn () => ['locuteur' => $nom, 'role_locuteur' => $role, 'qualite_locuteur' => 'parti']);
    }
}
