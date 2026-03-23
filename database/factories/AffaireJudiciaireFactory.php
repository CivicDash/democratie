<?php

namespace Database\Factories;

use App\Models\AffaireJudiciaire;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AffaireJudiciaireFactory extends Factory
{
    protected $model = AffaireJudiciaire::class;

    public function definition(): array
    {
        $types = AffaireJudiciaire::TYPES_AFFAIRE();
        $categories = AffaireJudiciaire::CATEGORIES();

        return [
            'uuid' => Str::uuid(),
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'parti_politique' => $this->faker->randomElement(['LR', 'PS', 'RN', 'LREM', 'LFI', 'EELV', 'PCF']),
            'fonction_au_moment' => $this->faker->randomElement(['Député', 'Sénateur', 'Ministre']),
            'titre' => $this->faker->sentence(8),
            'description' => $this->faker->paragraph(),
            'type_affaire' => $this->faker->randomElement($types),
            'categorie' => $this->faker->randomElement($categories),
            'statut_judiciaire' => 'en_cours',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'source_detection' => $this->faker->randomElement(['wikidata', 'wikipedia_nlp', 'hatvp', 'manuel']),
            'detecte_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'detection_confidence' => $this->faker->randomFloat(2, 0.30, 1.00),
        ];
    }

    public function valide(): static
    {
        return $this->state(fn () => [
            'statut_validation' => 'valide',
            'affiche_publiquement' => true,
            'valide_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'valide_par' => 1,
        ]);
    }

    public function condamne(): static
    {
        return $this->valide()->state(fn () => [
            'statut_judiciaire' => $this->faker->randomElement([
                'condamne_premiere_instance', 'condamne_appel', 'condamne_definitif',
            ]),
            'date_condamnation_definitive' => $this->faker->dateTimeBetween('-5 years', '-1 month'),
            'peine_prison_mois' => $this->faker->optional(0.6)->numberBetween(1, 60),
            'peine_prison_avec_sursis' => $this->faker->boolean(70),
            'peine_amende_euros' => $this->faker->optional(0.5)->randomFloat(2, 1000, 500000),
            'peine_ineligibilite_mois' => $this->faker->optional(0.3)->numberBetween(6, 60),
        ]);
    }

    public function relaxe(): static
    {
        return $this->valide()->state(fn () => [
            'statut_judiciaire' => 'relaxe',
        ]);
    }

    public function enReview(): static
    {
        return $this->state(fn () => [
            'statut_validation' => 'en_review',
        ]);
    }

    public function pourDepute(string $uid): static
    {
        return $this->state(fn () => [
            'acteur_an_uid' => $uid,
            'fonction_au_moment' => 'Député',
        ]);
    }

    public function pourSenateur(string $matricule): static
    {
        return $this->state(fn () => [
            'senateur_matricule' => $matricule,
            'fonction_au_moment' => 'Sénateur',
        ]);
    }
}
