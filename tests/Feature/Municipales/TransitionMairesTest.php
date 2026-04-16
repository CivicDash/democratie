<?php

use App\Models\Maire;

it('creates new maires from election results', function () {
    $candidaturesFile = base_path('tests/Fixtures/candidatures_t1_sample.csv');
    $this->artisan('municipales:import-candidatures', [
        'tour' => 1,
        '--file' => $candidaturesFile,
    ])->assertSuccessful();

    $resultatsFile = base_path('tests/Fixtures/resultats_t1_sample.csv');
    $this->artisan('municipales:import-resultats', [
        'tour' => 1,
        '--file' => $resultatsFile,
    ])->assertSuccessful();

    $this->artisan('municipales:transition-maires')
        ->assertSuccessful();

    $nouveauMaire = Maire::where('code_commune', '21231')
        ->where('mandature', '2026-2032')
        ->where('en_exercice', true)
        ->first();

    expect($nouveauMaire)->not->toBeNull();
    expect($nouveauMaire->nom)->toContain('Dupont');
});

it('detects reelections', function () {
    Maire::create([
        'uid' => 'MAIRE-21054',
        'nom' => 'Bernard',
        'prenom' => 'Pierre',
        'code_commune' => '21054',
        'nom_commune' => 'Beaune',
        'code_departement' => '21',
        'nom_departement' => 'Côte-d\'Or',
        'en_exercice' => true,
        'mandature' => '2020-2026',
    ]);

    $candidaturesFile = base_path('tests/Fixtures/candidatures_t1_sample.csv');
    $this->artisan('municipales:import-candidatures', [
        'tour' => 1,
        '--file' => $candidaturesFile,
    ])->assertSuccessful();

    $resultatsFile = base_path('tests/Fixtures/resultats_t1_sample.csv');
    $this->artisan('municipales:import-resultats', [
        'tour' => 1,
        '--file' => $resultatsFile,
    ])->assertSuccessful();

    $this->artisan('municipales:transition-maires')
        ->assertSuccessful();

    $reelu = Maire::where('code_commune', '21054')
        ->where('mandature', '2026-2032')
        ->first();

    expect($reelu)->not->toBeNull();
    expect($reelu->reelu)->toBeTrue();
});

it('runs dry run without writing to database', function () {
    $candidaturesFile = base_path('tests/Fixtures/candidatures_t1_sample.csv');
    $this->artisan('municipales:import-candidatures', [
        'tour' => 1,
        '--file' => $candidaturesFile,
    ])->assertSuccessful();

    $resultatsFile = base_path('tests/Fixtures/resultats_t1_sample.csv');
    $this->artisan('municipales:import-resultats', [
        'tour' => 1,
        '--file' => $resultatsFile,
    ])->assertSuccessful();

    $countBefore = Maire::where('mandature', '2026-2032')->count();

    $this->artisan('municipales:transition-maires', [
        '--dry-run' => true,
    ])->assertSuccessful();

    $countAfter = Maire::where('mandature', '2026-2032')->count();
    expect($countAfter)->toBe($countBefore);
});
