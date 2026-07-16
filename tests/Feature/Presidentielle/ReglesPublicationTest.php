<?php

use App\Models\ArgumentMesureLien;
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

it('une liaison « contre » exige une double validation', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    // Liaison contre validée une fois, reliée à une mesure, dotée d'une note : pas encore valide.
    $contre = ArgumentMesureLien::factory()->contre()->create([
        'statut_validation' => 'valide',
        'note_contextuelle' => 'ce fait joue contre cette mesure',
        'valide_par' => $u1->id,
        'double_valide_par' => null,
    ]);
    expect($contre->estValide())->toBeFalse();

    $contre->update(['double_valide_par' => $u2->id]);
    expect($contre->refresh()->estValide())->toBeTrue();

    // Une liaison "pour" n'exige qu'une validation simple.
    $pour = ArgumentMesureLien::factory()->pour()->create([
        'statut_validation' => 'valide',
        'note_contextuelle' => 'ce fait joue pour cette mesure',
        'valide_par' => $u1->id,
    ]);
    expect($pour->estValide())->toBeTrue();
});
