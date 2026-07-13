<?php

use App\Models\Argument;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\MesureScrutinLien;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;

it('lie un candidat à sa personne politique et à ses mesures', function () {
    $candidat = CandidatPresidentielle::factory()->create();

    expect($candidat->uuid)->not->toBeNull()
        ->and($candidat->personnePolitique)->toBeInstanceOf(PersonnePolitique::class);

    $mesure = ProgrammeMesure::factory()->create(['candidat_id' => $candidat->id]);

    expect($candidat->refresh()->mesures)->toHaveCount(1)
        ->and($mesure->theme)->toBeInstanceOf(ProgrammeTheme::class)
        ->and($mesure->candidat->is($candidat))->toBeTrue();
});

it('rattache arguments pour/contre, sources et liens de scrutin à une mesure', function () {
    $mesure = ProgrammeMesure::factory()->create();

    Argument::factory()->pour()->create(['mesure_id' => $mesure->id]);
    $contre = Argument::factory()->contre()->create(['mesure_id' => $mesure->id]);
    ArgumentSource::factory()->count(2)->create(['argument_id' => $contre->id]);
    MesureScrutinLien::factory()->create(['mesure_id' => $mesure->id]);

    expect($mesure->argumentsPour()->count())->toBe(1)
        ->and($mesure->argumentsContre()->count())->toBe(1)
        ->and($contre->sources)->toHaveCount(2)
        ->and($mesure->scrutinLiens)->toHaveCount(1);
});

it('expose le parcours et les candidatures sur la personne politique', function () {
    $personne = PersonnePolitique::factory()->create();
    CandidatPresidentielle::factory()->create(['personne_politique_id' => $personne->id]);
    ParcoursEvenement::factory()->count(3)->create(['personne_politique_id' => $personne->id]);

    expect($personne->candidaturesPresidentielle)->toHaveCount(1)
        ->and($personne->parcoursEvenements)->toHaveCount(3);
});

it('supprime en cascade les mesures et arguments quand le candidat est supprimé', function () {
    $candidat = CandidatPresidentielle::factory()->create();
    $mesure = ProgrammeMesure::factory()->create(['candidat_id' => $candidat->id]);
    Argument::factory()->create(['mesure_id' => $mesure->id]);

    $candidat->forceDelete();

    expect(ProgrammeMesure::withTrashed()->count())->toBe(0)
        ->and(Argument::withTrashed()->count())->toBe(0);
});
