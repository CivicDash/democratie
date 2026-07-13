<?php

use App\Models\Argument;
use App\Models\CandidatPresidentielle;
use App\Models\MesureScrutinLien;
use App\Models\ProgrammeMesure;
use App\Models\User;

it('ne publie que les candidats valides ET affichés', function () {
    CandidatPresidentielle::factory()->create();            // detecte
    CandidatPresidentielle::factory()->create(['statut_validation' => 'valide', 'affiche_publiquement' => false]);
    CandidatPresidentielle::factory()->publie()->create();  // valide + affiché

    expect(CandidatPresidentielle::publie()->count())->toBe(1);
});

it('même règle de publication pour les mesures', function () {
    ProgrammeMesure::factory()->count(2)->create();       // detecte
    ProgrammeMesure::factory()->publie()->create();

    expect(ProgrammeMesure::publie()->count())->toBe(1);
});

it('un lien mesure↔scrutin sans explication n’est jamais publiable', function () {
    // même "publié" au sens statut, un lien sans explication rédigée doit être exclu
    $sansExplication = MesureScrutinLien::factory()->publie()->create(['explication' => null]);
    MesureScrutinLien::factory()->publie()->create(['explication' => 'Vote personnel contre l’article 3, lu comme contradictoire.']);

    expect(MesureScrutinLien::publie()->count())->toBe(1)
        ->and($sansExplication->estPubliable())->toBeFalse();
});

it('un argument « contre » exige une double validation', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    $contre = Argument::factory()->contre()->create([
        'statut_validation' => 'valide',
        'valide_par' => $u1->id,
        'double_valide_par' => null,
    ]);
    expect($contre->estValide())->toBeFalse();

    $contre->update(['double_valide_par' => $u2->id]);
    expect($contre->refresh()->estValide())->toBeTrue();

    // un argument "pour" n'exige qu'une validation simple
    $pour = Argument::factory()->pour()->create(['statut_validation' => 'valide', 'valide_par' => $u1->id]);
    expect($pour->estValide())->toBeTrue();
});
