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

it('une liaison est valide après une seule validation, quel que soit le sens', function () {
    $u1 = User::factory()->create();

    // Liaison contre validée une fois, reliée à une mesure, dotée d'une note : valide.
    $contre = ArgumentMesureLien::factory()->contre()->create([
        'statut_validation' => 'valide',
        'note_contextuelle' => 'ce fait joue contre cette mesure',
        'valide_par' => $u1->id,
        'double_valide_par' => null,
    ]);
    expect($contre->estValide())->toBeTrue();

    // Sans note contextuelle, elle ne l'est pas.
    $contre->update(['note_contextuelle' => null]);
    expect($contre->refresh()->estValide())->toBeFalse();

    // Une liaison "pour" : même règle.
    $pour = ArgumentMesureLien::factory()->pour()->create([
        'statut_validation' => 'valide',
        'note_contextuelle' => 'ce fait joue pour cette mesure',
        'valide_par' => $u1->id,
    ]);
    expect($pour->estValide())->toBeTrue();
});
