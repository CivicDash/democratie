<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Helpers — domaine présidentielle
|--------------------------------------------------------------------------
*/

/**
 * Crée un candidat PUBLIÉ complet et conforme (mesure sourcée + argument « pour »
 * ET « contre » validés et sourcés). Retourne [candidat, theme, mesure].
 */
function candidatPubliePublic(array $overrides = []): array
{
    $theme = \App\Models\ProgrammeTheme::factory()->create(['slug' => 'theme-'.uniqid(), 'actif' => true]);
    $personne = \App\Models\PersonnePolitique::factory()->create(['slug' => 'cand-'.uniqid()]);
    $candidat = \App\Models\CandidatPresidentielle::factory()->publie()
        ->create(['personne_politique_id' => $personne->id] + $overrides);

    $mesure = \App\Models\ProgrammeMesure::factory()->publie()->create([
        'candidat_id' => $candidat->id,
        'theme_id' => $theme->id,
        'source_officielle_url' => 'https://exemple.fr/programme#m1',
        'est_mise_en_avant' => true,
    ]);

    foreach (['pour', 'contre'] as $sens) {
        lierArgumentPublie($mesure, $sens);
    }

    return [$candidat, $theme, $mesure];
}

/**
 * Crée un fait (argument) publié + une source fiable, et le relie à la mesure par une
 * liaison publiée dans le sens donné (une liaison « contre » est doublement validée).
 */
function lierArgumentPublie(\App\Models\ProgrammeMesure $mesure, string $sens): \App\Models\ArgumentMesureLien
{
    $arg = \App\Models\Argument::factory()->publie()->create();
    \App\Models\ArgumentSource::factory()->create(['argument_id' => $arg->id, 'fiabilite' => 'haute']);

    return \App\Models\ArgumentMesureLien::factory()->{$sens}()->publie()->create([
        'argument_id' => $arg->id,
        'mesure_id' => $mesure->id,
    ]);
}
