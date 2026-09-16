<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * La fiche utilisateur de l'administration est l'écran qui a produit les trois symptômes
 * d'origine — mot de passe inopérant, changement non validé, numéro d'adhérent qui ne
 * reste pas — puis, le 15/09/2026, une modale blanche en passant un compte en « membre
 * association ».
 *
 * Ces tests couvrent le parcours complet, pas seulement le contrôleur : requête HTTP avec
 * les middlewares (donc ConvertEmptyStringsToNull, dont dépendent les trois champs de date
 * envoyés à vide par le formulaire), puis rendu de la page vers laquelle on redirige. La
 * modale blanche d'Inertia apparaît quand cette seconde étape échoue, pas la première :
 * vérifier l'enregistrement sans suivre la redirection ne l'aurait pas vue.
 */
beforeEach(function () {
    // Sans manifeste Vite, app.blade.php lève avant d'atteindre le composant : le test
    // échouerait pour la mauvaise raison. Un dépôt fraîchement cloné affichait cinq
    // échecs fantômes là où il n'y avait qu'un `npm run build` manquant.
    if (! file_exists(public_path('build/manifest.json'))) {
        test()->markTestSkipped('Manifeste Vite absent : lancer `npm run build` avant ce test.');
    }

    foreach (['admin', 'moderator', 'citizen', 'legislator'] as $r) {
        Role::findOrCreate($r, 'web');
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

/** Charge utile exacte du formulaire : les dates et le numéro partent à vide. */
function chargeFiche(User $u, array $ecrasements = []): array
{
    return array_merge([
        'name' => $u->name,
        'email' => $u->email,
        'password' => '',
        'role' => 'moderator',
        'elu_type' => '',
        'elu_ref' => '',
        'is_verified_elu' => false,
        'is_association_member' => false,
        'member_type' => 'adherent',
        'member_since' => '',
        'member_until' => '',
        'member_number' => '',
    ], $ecrasements);
}

it('passe un compte en membre association et affiche la fiche ensuite', function () {
    $cible = User::factory()->create();
    $cible->assignRole('moderator');

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $cible->id), chargeFiche($cible, [
            'is_association_member' => true,
        ]))
        ->assertRedirect(route('admin.users.show', $cible->id));

    // La modale blanche se produit ici : réponse HTML au lieu d'une réponse Inertia.
    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $cible->id))
        ->assertOk();

    $cible->refresh();
    expect($cible->is_association_member)->toBeTrue()
        // `member_since` part à vide et doit devenir la date du jour, pas une chaîne vide :
        // la colonne est de type `date` et PostgreSQL refuse '' — c'est une 500.
        ->and($cible->member_since)->not->toBeNull()
        ->and($cible->member_until)->toBeNull()
        // Dolibarr reste la source de vérité du numéro : rien de généré maison.
        ->and($cible->member_number)->toBeNull();
});

it('enregistre réellement le mot de passe saisi par un administrateur', function () {
    $cible = User::factory()->create(['password' => Hash::make('ancien-mot-de-passe')]);
    $cible->assignRole('moderator');

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $cible->id), chargeFiche($cible, [
            'password' => 'Nouveau-Mot-De-Passe-2027!',
        ]))
        ->assertRedirect();

    expect(Hash::check('Nouveau-Mot-De-Passe-2027!', $cible->fresh()->password))->toBeTrue();
});

it('retire les champs d\'adhésion quand la case est décochée', function () {
    $cible = User::factory()->create([
        'is_association_member' => true,
        'member_type' => 'adherent',
        'member_since' => now()->subYear()->toDateString(),
        'member_number' => 'MEM2601-0003',
    ]);
    $cible->assignRole('moderator');

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $cible->id), chargeFiche($cible))
        ->assertRedirect();

    $cible->refresh();
    expect($cible->is_association_member)->toBeFalse()
        ->and($cible->member_number)->toBeNull()
        ->and($cible->member_since)->toBeNull();
});
