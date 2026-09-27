<?php

use App\Console\Commands\PresidentielleImportParcours;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;

/**
 * La synchro du parcours ne doit pas défaire le travail des modérateurs.
 *
 * Les déclarations HATVP datent souvent un mandat du début de la période déclarée
 * (01/01/2019 pour un député élu en 2017) ; un modérateur corrige la date. Sans clé
 * d'origine, la synchro suivante ne reconnaissait plus la ligne et la recréait, fausse.
 */
function creerParcours(PersonnePolitique $p, string $type, string $titre, ?string $debut): bool
{
    $methode = new ReflectionMethod(PresidentielleImportParcours::class, 'creer');

    return $methode->invoke(app(PresidentielleImportParcours::class), $p, $type, $titre, null, $debut, null, 'hatvp', null);
}

it('ne recrée pas une ligne déjà importée', function () {
    $p = PersonnePolitique::factory()->create();

    expect(creerParcours($p, 'mandat', 'Député', '2019-01-01'))->toBeTrue()
        ->and(creerParcours($p, 'mandat', 'Député', '2019-01-01'))->toBeFalse()
        ->and(ParcoursEvenement::count())->toBe(1);
});

it('reconnaît une ligne dont un modérateur a corrigé la date', function () {
    $p = PersonnePolitique::factory()->create();
    creerParcours($p, 'mandat', 'Député', '2019-01-01');
    ParcoursEvenement::sole()->update([
        'date_debut' => '2017-06-21',
        'detection_raw_data' => ['cle_import' => 'mandat|Député|2019-01-01'],
    ]);

    expect(creerParcours($p, 'mandat', 'Député', '2019-01-01'))->toBeFalse()
        ->and(ParcoursEvenement::count())->toBe(1);
});

it('ne ressuscite pas une ligne rejetée', function () {
    $p = PersonnePolitique::factory()->create();
    creerParcours($p, 'poste_prive', 'Ministre délégué', '2022-07-01');
    ParcoursEvenement::sole()->delete();

    expect(creerParcours($p, 'poste_prive', 'Ministre délégué', '2022-07-01'))->toBeFalse()
        ->and(ParcoursEvenement::withTrashed()->count())->toBe(1);
});
