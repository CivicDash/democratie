<?php

use App\Models\Argument;
use App\Models\ArgumentMesureLien;
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

it('relie un fait autonome à une mesure via une liaison portant le sens', function () {
    $mesure = ProgrammeMesure::factory()->create();

    $arg = Argument::factory()->create();
    ArgumentSource::factory()->count(2)->create(['argument_id' => $arg->id]);
    ArgumentMesureLien::factory()->pour()->create(['argument_id' => $arg->id, 'mesure_id' => $mesure->id]);
    ArgumentMesureLien::factory()->contre()->create(['mesure_id' => $mesure->id]);
    MesureScrutinLien::factory()->create(['mesure_id' => $mesure->id]);

    expect($mesure->argumentsPour()->count())->toBe(1)
        ->and($mesure->argumentsContre()->count())->toBe(1)
        ->and($arg->sources)->toHaveCount(2)
        ->and($mesure->scrutinLiens)->toHaveCount(1);
});

it('réutilise un même fait sur plusieurs mesures, dans des sens opposés', function () {
    $arg = Argument::factory()->create();
    $mesureA = ProgrammeMesure::factory()->create();
    $mesureB = ProgrammeMesure::factory()->create();

    ArgumentMesureLien::factory()->pour()->create(['argument_id' => $arg->id, 'mesure_id' => $mesureA->id]);
    ArgumentMesureLien::factory()->contre()->create(['argument_id' => $arg->id, 'mesure_id' => $mesureB->id]);

    expect($arg->liens()->count())->toBe(2)
        ->and($mesureA->argumentsPour()->count())->toBe(1)
        ->and($mesureB->argumentsContre()->count())->toBe(1);
});

it('expose le parcours et les candidatures sur la personne politique', function () {
    $personne = PersonnePolitique::factory()->create();
    CandidatPresidentielle::factory()->create(['personne_politique_id' => $personne->id]);
    ParcoursEvenement::factory()->count(3)->create(['personne_politique_id' => $personne->id]);

    expect($personne->candidaturesPresidentielle)->toHaveCount(1)
        ->and($personne->parcoursEvenements)->toHaveCount(3);
});

it('cascade mesures et liaisons quand le candidat est supprimé, mais garde les faits autonomes', function () {
    $candidat = CandidatPresidentielle::factory()->create();
    $mesure = ProgrammeMesure::factory()->create(['candidat_id' => $candidat->id]);
    $arg = Argument::factory()->create();
    ArgumentMesureLien::factory()->create(['argument_id' => $arg->id, 'mesure_id' => $mesure->id]);

    $candidat->forceDelete();

    // La mesure et sa liaison disparaissent (cascade DB), mais le fait reste réutilisable.
    expect(ProgrammeMesure::withTrashed()->count())->toBe(0)
        ->and(ArgumentMesureLien::withTrashed()->count())->toBe(0)
        ->and(Argument::withTrashed()->count())->toBe(1);
});
