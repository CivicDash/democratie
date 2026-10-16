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

/**
 * Une décision du juge de l'élection n'est ni pénale ni une condamnation : elle sort à
 * part, sans bandeau de présomption, avec sa durée et son terme.
 */
function exportAffairesAvec(array $champs): array
{
    $p = PersonnePolitique::factory()->create();
    CandidatPresidentielle::factory()->create([
        'personne_politique_id' => $p->id, 'election' => '2027',
        'statut_validation' => 'valide', 'affiche_publiquement' => true,
    ]);
    AffaireJudiciaire::factory()->valide()->create(array_merge(['personne_politique_id' => $p->id], $champs));

    return app(PresidentielleExporter::class)->build('2027')['candidats'][$p->slug]['affaires'];
}

it('exporte une sanction électorale à part des affaires pénales, avec son terme', function () {
    $this->travelTo('2026-10-16');
    $affaires = exportAffairesAvec([
        'statut_judiciaire' => 'sanction_electorale',
        'type_affaire' => 'compte_campagne_rejete',
        'date_condamnation_definitive' => '2025-04-17',
        'peine_ineligibilite_mois' => 18,
    ]);

    expect($affaires['publiees'])->toBe([])
        ->and($affaires['sanctions_electorales'])->toHaveCount(1);
    $s = $affaires['sanctions_electorales'][0];
    expect($s['date_decision'])->toBe('2025-04-17')
        ->and($s['duree_mois'])->toBe(18)
        ->and($s['fin'])->toBe('2026-10-17')
        ->and($s['echue'])->toBeFalse()
        ->and($s['statut_label'])->toContain('non pénale')
        ->and($s)->not->toHaveKey('presomption_innocence');
});

it('dit la sanction échue à partir du jour de son terme', function () {
    $this->travelTo('2026-10-17 08:00');
    $s = exportAffairesAvec([
        'statut_judiciaire' => 'sanction_electorale',
        'date_condamnation_definitive' => '2025-04-17',
        'peine_ineligibilite_mois' => 18,
    ])['sanctions_electorales'][0];

    expect($s['echue'])->toBeTrue();
});

it('laisse le terme vide quand la date ou la durée manque', function () {
    $s = exportAffairesAvec([
        'statut_judiciaire' => 'sanction_electorale',
        'date_condamnation_definitive' => '2025-04-17',
        'peine_ineligibilite_mois' => null,
    ])['sanctions_electorales'][0];

    expect($s['fin'])->toBeNull()->and($s['echue'])->toBeNull();
});

it('garde les affaires pénales dans la liste habituelle, avec la présomption', function () {
    $affaires = exportAffairesAvec(['statut_judiciaire' => 'condamne_premiere_instance']);

    expect($affaires['sanctions_electorales'])->toBe([])
        ->and($affaires['publiees'][0]['presomption_innocence'])->toBeTrue();
});
