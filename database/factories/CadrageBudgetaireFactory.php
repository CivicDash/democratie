<?php

namespace Database\Factories;

use App\Models\CadrageBudgetaire;
use App\Models\CandidatPresidentielle;
use Illuminate\Database\Eloquent\Factories\Factory;

class CadrageBudgetaireFactory extends Factory
{
    protected $model = CadrageBudgetaire::class;

    public function definition(): array
    {
        return [
            'election' => '2027',
            'candidat_id' => CandidatPresidentielle::factory(),
            'nature' => 'budget_premiere_annee',
            'titre' => 'Budget de première année',
            'date_source' => '2026-10-14',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ];
    }

    /** Ligne de référence officielle : sans candidat. */
    public function reference(): static
    {
        return $this->state(fn () => [
            'candidat_id' => null,
            'nature' => CadrageBudgetaire::NATURE_REFERENCE,
            'titre' => 'PLF 2027, comptes INSEE, avis du HCFP',
            'date_source' => '2026-10-01',
        ]);
    }

    public function publie(): static
    {
        return $this->state(fn () => ['statut_validation' => 'valide', 'affiche_publiquement' => true]);
    }
}
