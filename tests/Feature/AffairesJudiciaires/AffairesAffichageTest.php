<?php

use App\Models\AffaireJudiciaire;
use App\Models\User;

test('les affaires non validées ne sont pas retournées par le scope publiques', function () {
    AffaireJudiciaire::factory()->count(3)->create(); // detecte
    AffaireJudiciaire::factory()->enReview()->count(2)->create();
    AffaireJudiciaire::factory()->valide()->count(1)->create();

    expect(AffaireJudiciaire::publiques()->count())->toBe(1);
});

test('la page transparence est accessible publiquement', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transparence.affaires'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Transparence/AffairesJudiciaires')
        );
});

test('la page notre démarche est accessible publiquement', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transparence.demarche'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Transparence/NotreDemarche')
            ->has('stats')
        );
});

test('seules les affaires publiques comptent dans les stats', function () {
    AffaireJudiciaire::factory()->count(5)->create(); // detecte
    AffaireJudiciaire::factory()->valide()->count(2)->create();
    AffaireJudiciaire::factory()->state(['statut_validation' => 'rejete'])->count(3)->create();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transparence.demarche'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Transparence/NotreDemarche')
            ->where('stats.total_validees', 2)
            ->where('stats.total_rejetees', 3)
        );
});

test('les scopes forDepute et forSenateur filtrent correctement', function () {
    AffaireJudiciaire::factory()->valide()->state(['acteur_an_uid' => 'PA12345'])->count(2)->create();
    AffaireJudiciaire::factory()->valide()->state(['senateur_matricule' => 'SEN001'])->count(1)->create();
    AffaireJudiciaire::factory()->valide()->count(3)->create();

    expect(AffaireJudiciaire::publiques()->forDepute('PA12345')->count())->toBe(2);
    expect(AffaireJudiciaire::publiques()->forSenateur('SEN001')->count())->toBe(1);
});
