<?php

use App\Models\User;
use App\Services\UserSanctionService;

/**
 * Une suspension temporaire doit rester temporaire.
 *
 * `account_status` avait été ajouté au $fillable sans ses cinq compagnons. Comme
 * CheckAccountStatus teste `suspended_until`, toujours NULL, la levée automatique ne
 * se déclenchait jamais : une suspension d'un jour valait bannissement à vie, sans
 * motif affiché à la personne.
 */
it('enregistre l\'échéance et le motif d\'une suspension', function () {
    $moderateur = User::factory()->create();
    $moderateur->assignRole('admin');
    $utilisateur = User::factory()->create();

    app(UserSanctionService::class)->suspend($utilisateur, 3, 'Propos injurieux répétés', $moderateur);

    $utilisateur->refresh();

    expect($utilisateur->account_status)->toBe('suspended')
        ->and($utilisateur->suspended_until)->not->toBeNull('Sans échéance, la levée automatique ne se déclenche jamais.')
        ->and($utilisateur->suspended_until->isFuture())->toBeTrue()
        ->and($utilisateur->suspension_reason)->toBe('Propos injurieux répétés')
        ->and($utilisateur->suspended_by)->toBe($moderateur->id)
        ->and($utilisateur->suspension_count)->toBe(1);
});

it('rend un compte banni incapable de publier', function () {
    $utilisateur = User::factory()->create();

    app(UserSanctionService::class)->ban($utilisateur, 'Compte automatisé');

    $utilisateur->refresh();

    // Le bannissement bloquait la connexion mais laissait isBanned() à false, donc
    // canPost() à true : les deux systèmes de sanction ne se parlaient pas.
    expect($utilisateur->isBanned())->toBeTrue()
        ->and($utilisateur->canPost())->toBeFalse();
});

it('marque comme vérifié le compte créé par un administrateur', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Morgane',
        'email' => 'morgane@exemple.test',
        'password' => 'MotDePasse!2026',
        'role' => 'citizen',
    ])->assertRedirect();

    // Sans cela, la connexion réussissait puis /dashboard — gardé par `verified` —
    // renvoyait vers l'écran de vérification. Vu de l'administrateur, « le mot de
    // passe ne fonctionne pas ».
    expect(User::firstWhere('email', 'morgane@exemple.test')?->hasVerifiedEmail())->toBeTrue();
});
