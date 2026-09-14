<?php

namespace Database\Factories;

use App\Models\AffaireJudiciaire;
use App\Models\AffaireSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffaireSource>
 */
class AffaireSourceFactory extends Factory
{
    protected $model = AffaireSource::class;

    public function definition(): array
    {
        $media = $this->faker->randomElement(['Le Monde', 'AFP', 'Mediapart', 'Ouest-France', 'La Croix']);

        return [
            'affaire_id' => AffaireJudiciaire::factory(),
            'type_source' => 'article_presse',
            'titre' => $this->faker->sentence(6),
            'url' => $this->faker->unique()->url(),
            'media' => $media,
            'date_publication' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'auteur' => $this->faker->name(),
            'extrait' => $this->faker->paragraph(),
            'archive_url' => null,
            'fiabilite' => 'haute',
            'verifie_par' => null,
            'verifie_at' => null,
            'commentaire_verification' => null,
        ];
    }
}
