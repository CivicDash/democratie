<?php

namespace Database\Factories;

use App\Models\PersonnePolitique;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PersonnePolitiqueFactory extends Factory
{
    protected $model = PersonnePolitique::class;

    public function definition(): array
    {
        $prenom = $this->faker->firstName();
        $nom = $this->faker->lastName();

        return [
            'slug' => Str::slug($prenom.' '.$nom).'-'.$this->faker->unique()->numberBetween(1, 999999),
            'civilite' => $this->faker->randomElement(['M.', 'Mme']),
            'prenom' => $prenom,
            'nom' => $nom,
            'parti_politique' => $this->faker->randomElement(['LR', 'PS', 'RN', 'RE', 'LFI', 'EELV', 'Horizons']),
            'nuance_politique' => $this->faker->randomElement(['GAU', 'CEN', 'DR', 'ECO', 'EXD', 'EXG']),
        ];
    }
}
