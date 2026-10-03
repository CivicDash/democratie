<?php

use App\Models\AffaireJudiciaire;
use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use App\Services\Presidentielle\PresidentielleExporter;

/**
 * La chronologie d'une affaire sort sur la page candidat ; les notes de modération
 * rangées à côté d'elle dans `detection_raw_data` n'en sortent jamais.
 */
function affairePubliee(array $raw): array
{
    $p = PersonnePolitique::factory()->create();
    CandidatPresidentielle::factory()->create([
        'personne_politique_id' => $p->id, 'election' => '2027',
        'statut_validation' => 'valide', 'affiche_publiquement' => true,
    ]);
    AffaireJudiciaire::factory()->valide()->create([
        'personne_politique_id' => $p->id,
        'statut_judiciaire' => 'condamne_appel',
        'detection_raw_data' => $raw,
    ]);

    return app(PresidentielleExporter::class)->build('2027')['candidats'][$p->slug]['affaires']['publiees'][0];
}

it('exporte la chronologie, avec la précision de chaque date et sa source', function () {
    $affaire = affairePubliee(['chronologie' => [
        ['date' => '2015-03', 'fait' => 'Signalement', 'source' => 'https://www.example.org/signalement'],
        ['date' => '2017-06-30', 'fait' => 'Mise en examen'],
        ['date' => '2004', 'fait' => 'Début de la période des faits'],
    ]]);

    expect($affaire['chronologie'])->toBe([
        ['date' => '2015-03', 'fait' => 'Signalement', 'source' => 'https://www.example.org/signalement'],
        ['date' => '2017-06-30', 'fait' => 'Mise en examen', 'source' => null],
        ['date' => '2004', 'fait' => 'Début de la période des faits', 'source' => null],
    ]);
});

it('écarte les étapes sans date lisible ou sans fait', function () {
    $affaire = affairePubliee(['chronologie' => [
        ['date' => 'printemps 2020', 'fait' => 'Date libre'],
        ['date' => '2020-05-15', 'fait' => '   '],
        'pas une étape',
        ['date' => '2021-02-05', 'fait' => 'Mise en examen'],
    ]]);

    expect(array_column($affaire['chronologie'], 'fait'))->toBe(['Mise en examen']);
});

it('ne laisse sortir aucune note de modération', function () {
    $affaire = affairePubliee([
        'chronologie' => [['date' => '2021-02-05', 'fait' => 'Mise en examen']],
        'rappel_statut' => 'NOTE INTERNE',
        'taches_moderation' => ['NOTE INTERNE'],
        'procedures_closes_non_affichees' => ['NOTE INTERNE'],
    ]);

    expect(json_encode($affaire))->not->toContain('NOTE INTERNE');
});

it('exporte une chronologie vide quand la fiche n\'en a pas', function () {
    expect(affairePubliee([])['chronologie'])->toBe([]);
});
