<?php

namespace Database\Factories;

use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => QuizQuestion::factory(),
            'libelle' => 'Réduire le dispositif',
            'ordre' => 0,
        ];
    }
}
