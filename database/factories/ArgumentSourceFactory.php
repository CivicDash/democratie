<?php

namespace Database\Factories;

use App\Models\Argument;
use App\Models\ArgumentSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArgumentSourceFactory extends Factory
{
    protected $model = ArgumentSource::class;

    public function definition(): array
    {
        return [
            'argument_id' => Argument::factory(),
            'type_source' => $this->faker->randomElement(ArgumentSource::TYPES_SOURCE),
            'titre' => $this->faker->sentence(6),
            'url' => $this->faker->url(),
            'media' => $this->faker->randomElement(['INSEE', 'Cour des comptes', 'Le Monde', 'OFCE']),
            'date_publication' => $this->faker->optional()->dateTimeBetween('-3 years', 'now'),
            'extrait' => $this->faker->text(200),
            'archive_url' => 'https://web.archive.org/web/'.$this->faker->numerify('##############').'/'.$this->faker->url(),
            'fiabilite' => $this->faker->randomElement(['haute', 'moyenne']),
        ];
    }
}
