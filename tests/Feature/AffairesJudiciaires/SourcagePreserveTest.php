<?php

use App\Models\AffaireJudiciaire;
use App\Models\AffaireSource;
use App\Models\User;

/**
 * Le sourçage d'une fiche judiciaire nominative ne doit pas se perdre.
 *
 * La validation détruisait toutes les sources avant de recréer celles du formulaire,
 * sans SoftDeletes ni journal : sur une fiche publiée nommant une personne et une
 * infraction, la trace de qui avait vérifié quoi disparaissait à chaque passage.
 */
function chargeValidation(AffaireJudiciaire $affaire, array $sources): array
{
    return [
        'titre' => $affaire->titre,
        'type_affaire' => $affaire->type_affaire,
        'categorie' => $affaire->categorie,
        'statut_judiciaire' => $affaire->statut_judiciaire,
        'date_mise_en_examen' => '2024-01-15',
        'sources' => $sources,
    ];
}

it('conserve les sources et leur vérificateur d\'une validation à l\'autre', function () {
    $premier = User::factory()->create();
    $premier->assignRole('admin');
    $second = User::factory()->create();
    $second->assignRole('admin');

    $affaire = AffaireJudiciaire::factory()->create();
    $source = AffaireSource::factory()->create([
        'affaire_id' => $affaire->id,
        'url' => 'https://exemple.fr/enquete',
        'media' => 'Le Monde',
        'type_source' => 'article_presse',
        'fiabilite' => 'haute',
        'verifie_par' => $premier->id,
    ]);

    $charge = fn () => chargeValidation($affaire, [[
        'id' => $source->id,
        'url' => 'https://exemple.fr/enquete',
        'media' => 'Le Monde',
        'type_source' => 'article_presse',
        'fiabilite' => 'haute',
    ]]);

    // Un second modérateur revalide sans rien changer à la source.
    $this->actingAs($second)
        ->put(route('admin.affaires.valider', $affaire), $charge())
        ->assertRedirect();

    $source->refresh();

    expect($source->exists)->toBeTrue('La source a été détruite par la validation.')
        ->and($source->verifie_par)->toBe($premier->id,
            'La vérification a été réattribuée alors que la source n\'a pas changé.');

    expect($affaire->sources()->count())->toBe(1);
});

it('journalise le retrait d\'une source au lieu de l\'effacer sans trace', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $affaire = AffaireJudiciaire::factory()->create();
    $retiree = AffaireSource::factory()->create(['affaire_id' => $affaire->id]);

    $this->actingAs($admin)
        ->put(route('admin.affaires.valider', $affaire), chargeValidation($affaire, [[
            'url' => 'https://exemple.fr/nouvelle-source',
            'media' => 'AFP',
            'type_source' => 'article_presse',
            'fiabilite' => 'haute',
        ]]))
        ->assertRedirect();

    expect($affaire->sources()->count())->toBe(1)
        ->and(AffaireSource::withTrashed()->find($retiree->id))->not->toBeNull(
            'La source retirée doit rester consultable en base.')
        ->and($affaire->moderationLogs()->where('action', 'sources_modifiees')->exists())->toBeTrue(
            'Le retrait d\'une source doit laisser une trace.');
});
