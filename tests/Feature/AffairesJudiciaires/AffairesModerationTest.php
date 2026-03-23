<?php

use App\Models\AffaireJudiciaire;
use App\Models\User;

test('un admin peut voir la file de modération', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    AffaireJudiciaire::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.affaires.index'))
        ->assertOk()
        ->assertInertia(fn ($page) =>
            $page->component('Admin/AffairesJudiciaires/Index')
                ->has('affaires.data', 3)
                ->has('counts')
                ->has('health_metrics')
        );
});

test('un admin peut voir le détail d\'une affaire', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.affaires.show', $affaire))
        ->assertOk()
        ->assertInertia(fn ($page) =>
            $page->component('Admin/AffairesJudiciaires/Show')
                ->has('affaire')
                ->has('types_affaire')
                ->has('categories')
        );
});

test('un admin peut prendre en charge une affaire', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.affaires.prendre', $affaire))
        ->assertRedirect();

    expect($affaire->fresh()->statut_validation)->toBe('en_review');
});

test('un admin peut rejeter une affaire avec motif', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->enReview()->create();

    $this->actingAs($admin)
        ->put(route('admin.affaires.rejeter', $affaire), ['motif' => 'Faux positif évident'])
        ->assertRedirect();

    expect($affaire->fresh()->statut_validation)->toBe('rejete');
    expect($affaire->fresh()->affiche_publiquement)->toBe(false);
});

test('un admin peut demander un complément', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->enReview()->create();

    $this->actingAs($admin)
        ->put(route('admin.affaires.completer', $affaire), ['commentaire' => 'Besoin de la date du jugement'])
        ->assertRedirect();

    expect($affaire->fresh()->statut_validation)->toBe('a_completer');
});

test('un admin peut archiver une affaire', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->valide()->create();

    $this->actingAs($admin)
        ->put(route('admin.affaires.archiver', $affaire), ['motif' => 'Prescrit'])
        ->assertRedirect();

    expect($affaire->fresh()->statut_validation)->toBe('archive');
    expect($affaire->fresh()->affiche_publiquement)->toBe(false);
});

test('la validation exige au moins une source non basse', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->enReview()->create();

    $this->actingAs($admin)
        ->put(route('admin.affaires.valider', $affaire), [
            'titre' => 'Test affaire',
            'type_affaire' => 'corruption',
            'categorie' => 'probite',
            'statut_judiciaire' => 'condamne_definitif',
            'sources' => [
                ['url' => 'https://example.com/article', 'media' => 'Blog random', 'type_source' => 'article_presse', 'fiabilite' => 'basse'],
            ],
        ])
        ->assertSessionHasErrors('sources');
});

test('la validation fonctionne avec une source haute fiabilité', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->enReview()->create();

    $this->actingAs($admin)
        ->put(route('admin.affaires.valider', $affaire), [
            'titre' => 'Condamnation pour corruption',
            'type_affaire' => 'corruption',
            'categorie' => 'probite',
            'statut_judiciaire' => 'condamne_definitif',
            'date_mise_en_examen' => '2024-01-15',
            'sources' => [
                ['url' => 'https://www.legifrance.gouv.fr/decision/xxx', 'media' => 'Légifrance', 'type_source' => 'decision_justice', 'fiabilite' => 'haute'],
            ],
        ])
        ->assertRedirect();

    expect($affaire->fresh()->statut_validation)->toBe('valide');
    expect($affaire->fresh()->affiche_publiquement)->toBe(true);
});

test('un utilisateur non-admin ne peut pas accéder à la modération', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.affaires.index'))
        ->assertStatus(403);
});

test('chaque action de workflow génère un log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $affaire = AffaireJudiciaire::factory()->create();

    $affaire->prendreEnCharge($admin);
    $affaire->rejeter($admin, 'Faux positif');

    expect($affaire->moderationLogs()->count())->toBe(2);
    expect($affaire->moderationLogs()->latest('id')->first()->action)->toBe('rejet');
});
