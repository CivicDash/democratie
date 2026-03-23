<?php

use App\Models\User;
use App\Models\ResultatMunicipal;
use App\Models\ResultatListeMunicipale;
use App\Models\StatsElectionMunicipale;

it('renders resultats index page', function () {
    $user = User::factory()->create();

    StatsElectionMunicipale::create([
        'annee' => 2026,
        'scope' => 'national',
        'scope_code' => null,
        'data' => [
            'participation' => ['t1' => ['taux' => 60.5]],
            'communes' => ['total' => 100, 'elues_t1' => 80, 'second_tour' => 20],
        ],
        'calculated_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('elections.municipales.resultats.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Elections/Municipales/Resultats'));
});

it('renders resultat commune page', function () {
    $user = User::factory()->create();

    ResultatMunicipal::create([
        'code_commune' => '21231',
        'nom_commune' => 'Dijon',
        'code_departement' => '21',
        'tour' => 1,
        'inscrits' => 95000,
        'abstentions' => 38000,
        'taux_abstention' => 40.00,
        'votants' => 57000,
        'taux_participation' => 60.00,
        'blancs' => 800,
        'nuls' => 400,
        'exprimes' => 55800,
        'statut_commune' => 'elu_t1',
    ]);

    $response = $this->actingAs($user)->get(route('elections.municipales.resultats.commune', '21231'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Elections/Municipales/ResultatCommune')
        ->has('commune')
        ->has('resultats')
    );
});

it('renders statistiques page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('elections.municipales.resultats.statistiques'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Elections/Municipales/Statistiques'));
});

it('renders transition maires page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('elections.municipales.resultats.transition'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Elections/Municipales/TransitionMaires'));
});

it('requires authentication for resultats pages', function () {
    $response = $this->get(route('elections.municipales.resultats.index'));
    $response->assertRedirect(route('login'));
});
