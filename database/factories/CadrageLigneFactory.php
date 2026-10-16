<?php

namespace Database\Factories;

use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use Illuminate\Database\Eloquent\Factories\Factory;

class CadrageLigneFactory extends Factory
{
    protected $model = CadrageLigne::class;

    public function definition(): array
    {
        return [
            'cadrage_id' => CadrageBudgetaire::factory(),
            'rubrique' => 'recettes_nouvelles',
            'montant' => 106.4,
            'unite' => 'md_eur',
            'portee' => 'annuelle',
            'annee' => 2027,
            'valeur_texte' => '106,4 Md€',
            'citation' => 'Au total, 106,4 milliards d\'euros de recettes nouvelles.',
            'reperage' => '00:54:06',
            'verification' => 'a_verifier',
        ];
    }

    public function verifiee(): static
    {
        return $this->state(fn () => ['verification' => 'verifie', 'verifie_at' => now()]);
    }

    /** Ligne de la référence officielle : sourcée par un producteur public. */
    public function deReference(): static
    {
        return $this->state(fn () => [
            'rubrique' => 'solde_vise',
            'montant' => 4.7,
            'unite' => 'pct_pib',
            'portee' => null,
            'annee' => 2027,
            'valeur_texte' => '4,7 % du PIB',
            'citation' => 'Le déficit public atteindrait 4,7 % du PIB en 2027.',
            'reperage' => 'p. 9',
            'source_producteur' => 'Ministère de l\'Économie et des Finances',
            'source_titre' => 'Projet de loi de finances pour 2027',
            'source_url' => 'https://www.budget.gouv.fr/plf-2027',
            'source_date' => '2026-10-01',
        ]);
    }
}
