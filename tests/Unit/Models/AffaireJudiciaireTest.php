<?php

use App\Models\AffaireJudiciaire;
use App\Models\User;

test('TYPES_AFFAIRE retourne 19 types', function () {
    expect(AffaireJudiciaire::TYPES_AFFAIRE())
        ->toBeArray()
        ->toHaveCount(19)
        ->toContain('corruption', 'fraude_fiscale', 'emploi_fictif');
});

test('CATEGORIES retourne 5 catégories', function () {
    expect(AffaireJudiciaire::CATEGORIES())
        ->toBeArray()
        ->toHaveCount(5)
        ->toContain('probite', 'financement', 'personne', 'manquement', 'autre');
});

test('STATUTS_JUDICIAIRES retourne 10 statuts', function () {
    expect(AffaireJudiciaire::STATUTS_JUDICIAIRES())
        ->toBeArray()
        ->toHaveCount(10)
        ->toContain('en_cours', 'condamne_definitif', 'relaxe');
});

test('STATUTS_VALIDATION retourne 7 statuts', function () {
    expect(AffaireJudiciaire::STATUTS_VALIDATION())
        ->toBeArray()
        ->toHaveCount(7)
        ->toContain('detecte', 'en_review', 'valide', 'rejete');
});

test('statut_judiciaire_libelle retourne un libellé lisible', function () {
    $affaire = new AffaireJudiciaire(['statut_judiciaire' => 'condamne_definitif']);
    expect($affaire->statut_judiciaire_libelle)->toBe('Condamné (définitif)');

    $affaire2 = new AffaireJudiciaire(['statut_judiciaire' => 'relaxe']);
    expect($affaire2->statut_judiciaire_libelle)->toBe('Relaxé');
});

test('statut_judiciaire_couleur retourne la bonne couleur', function () {
    expect((new AffaireJudiciaire(['statut_judiciaire' => 'condamne_definitif']))->statut_judiciaire_couleur)->toBe('red');
    expect((new AffaireJudiciaire(['statut_judiciaire' => 'relaxe']))->statut_judiciaire_couleur)->toBe('green');
    expect((new AffaireJudiciaire(['statut_judiciaire' => 'en_cours']))->statut_judiciaire_couleur)->toBe('gray');
});

test('type_affaire_libelle retourne un libellé lisible', function () {
    $affaire = new AffaireJudiciaire(['type_affaire' => 'detournement_fonds']);
    expect($affaire->type_affaire_libelle)->toBe('Détournement de fonds publics');
});

test('peine_resume formate correctement les peines', function () {
    $affaire = new AffaireJudiciaire([
        'peine_prison_mois' => 24,
        'peine_prison_avec_sursis' => true,
        'peine_amende_euros' => 50000,
        'peine_ineligibilite_mois' => 36,
    ]);
    expect($affaire->peine_resume)
        ->toContain('2 an(s) avec sursis')
        ->toContain("50 000 € d'amende")
        ->toContain("36 mois d'inéligibilité");
});

test('peine_resume retourne null si aucune peine', function () {
    $affaire = new AffaireJudiciaire([]);
    expect($affaire->peine_resume)->toBeNull();
});

test('gravite_score est entre 2 et 10', function () {
    $affaire = new AffaireJudiciaire([
        'categorie' => 'probite',
        'peine_prison_mois' => 12,
        'peine_prison_avec_sursis' => false,
        'peine_ineligibilite_mois' => 24,
    ]);
    expect($affaire->gravite_score)->toBe(10);

    $simple = new AffaireJudiciaire(['categorie' => 'autre']);
    expect($simple->gravite_score)->toBe(2);
});

test('uuid est auto-généré à la création', function () {
    $affaire = AffaireJudiciaire::factory()->make();
    expect($affaire->uuid)->toBeNull();

    $affaire->save();
    expect($affaire->uuid)->not()->toBeNull()->toBeString();
});

test('scope publiques ne retourne que les affaires validées et publiques', function () {
    AffaireJudiciaire::factory()->count(3)->create();
    AffaireJudiciaire::factory()->valide()->count(2)->create();

    expect(AffaireJudiciaire::publiques()->count())->toBe(2);
});

test('scope enAttente ne retourne que les détectées', function () {
    AffaireJudiciaire::factory()->count(3)->create();
    AffaireJudiciaire::factory()->valide()->count(2)->create();

    expect(AffaireJudiciaire::enAttente()->count())->toBe(3);
});

test('workflow : prendreEnCharge change le statut', function () {
    $affaire = AffaireJudiciaire::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);

    $affaire->prendreEnCharge($user);

    expect($affaire->fresh()->statut_validation)->toBe('en_review');
    expect($affaire->moderationLogs()->count())->toBe(1);
    expect($affaire->moderationLogs()->first()->action)->toBe('prise_en_charge');
});

test('workflow : rejeter empêche la publication', function () {
    $affaire = AffaireJudiciaire::factory()->enReview()->create();
    $user = User::factory()->create(['role' => 'admin']);

    $affaire->rejeter($user, 'Faux positif');

    $fresh = $affaire->fresh();
    expect($fresh->statut_validation)->toBe('rejete');
    expect($fresh->affiche_publiquement)->toBe(false);
});

test('workflow : valider active la publication', function () {
    $affaire = AffaireJudiciaire::factory()->enReview()->create();
    $user = User::factory()->create(['role' => 'admin']);

    $affaire->valider($user, 'Sources confirmées');

    $fresh = $affaire->fresh();
    expect($fresh->statut_validation)->toBe('valide');
    expect($fresh->affiche_publiquement)->toBe(true);
    expect($fresh->valide_par)->toBe($user->id);
    expect($fresh->valide_at)->not()->toBeNull();
});
