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

it('ne décale aucune date quand on revalide la fiche telle que l\'écran l\'a reçue', function () {
    // Les dates partaient vers l'écran en UTC (« 2017-06-29T22:00:00Z » pour le 30/06
    // à Paris) et revenaient telles quelles : chaque validation reculait toutes les
    // dates d'un jour, et marquait toutes les sources comme modifiées.
    config(['app.timezone' => 'Europe/Paris']);
    date_default_timezone_set('Europe/Paris');

    $premier = User::factory()->create();
    $premier->assignRole('admin');
    $second = User::factory()->create();
    $second->assignRole('admin');

    $affaire = AffaireJudiciaire::factory()->create([
        'date_mise_en_examen' => '2017-06-30',
        'date_jugement_premiere_instance' => '2025-03-31',
        'date_jugement_appel' => '2026-07-07',
    ]);
    $source = AffaireSource::factory()->create([
        'affaire_id' => $affaire->id, 'url' => 'https://exemple.fr/arret', 'media' => 'franceinfo',
        'type_source' => 'article_presse', 'fiabilite' => 'moyenne',
        'date_publication' => '2026-07-07', 'verifie_par' => $premier->id,
    ]);

    // Ce que reçoit l'écran (props Inertia), renvoyé sans retouche.
    $recu = json_decode(json_encode($affaire->fresh()->load('sources')), true);
    $this->actingAs($second)->put(route('admin.affaires.valider', $affaire), [
        'titre' => $recu['titre'], 'type_affaire' => $recu['type_affaire'], 'categorie' => $recu['categorie'],
        'statut_judiciaire' => $recu['statut_judiciaire'],
        'date_mise_en_examen' => $recu['date_mise_en_examen'],
        'date_jugement_premiere_instance' => $recu['date_jugement_premiere_instance'],
        'date_jugement_appel' => $recu['date_jugement_appel'],
        'sources' => [collect($recu['sources'][0])->only(['id', 'url', 'media', 'type_source', 'fiabilite', 'date_publication'])->all()],
    ])->assertRedirect();

    $affaire->refresh();
    expect($affaire->date_mise_en_examen->toDateString())->toBe('2017-06-30')
        ->and($affaire->date_jugement_premiere_instance->toDateString())->toBe('2025-03-31')
        ->and($affaire->date_jugement_appel->toDateString())->toBe('2026-07-07')
        ->and($source->fresh()->date_publication->toDateString())->toBe('2026-07-07')
        ->and($source->fresh()->verifie_par)->toBe($premier->id);
});
