<?php

namespace Database\Factories;

use App\Models\ProgrammeTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'election' => '2027',
            'theme_id' => ProgrammeTheme::factory(),
            'format' => 'arbitrage',
            'intitule' => 'Faut-il réduire, maintenir ou rétablir ce dispositif ?',
            'ordre' => 0,
            'statut_validation' => 'valide',
            'affiche_publiquement' => false,
        ];
    }
}
