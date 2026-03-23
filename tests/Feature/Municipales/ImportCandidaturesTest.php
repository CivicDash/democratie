<?php

use App\Models\ListeElectorale;
use App\Models\CandidatMunicipal;

it('imports candidatures from csv file', function () {
    $fixtureFile = base_path('tests/Fixtures/candidatures_t1_sample.csv');

    $this->artisan('municipales:import-candidatures', [
        'tour' => 1,
        '--file' => $fixtureFile,
    ])->assertSuccessful();

    expect(ListeElectorale::where('source', 'datagouv')->count())->toBeGreaterThanOrEqual(5);
    expect(CandidatMunicipal::where('source', 'datagouv')->count())->toBeGreaterThanOrEqual(10);

    $dijonListe = ListeElectorale::where('commune_code_insee', '21231')
        ->where('numero_panneau', 1)
        ->where('source', 'datagouv')
        ->first();

    expect($dijonListe)->not->toBeNull();
    expect($dijonListe->nom_liste)->toContain('Dijon');
    expect($dijonListe->statut)->toBe('officiel');
    expect($dijonListe->tour)->toBe(1);
});

it('is idempotent on reimport', function () {
    $fixtureFile = base_path('tests/Fixtures/candidatures_t1_sample.csv');

    $this->artisan('municipales:import-candidatures', [
        'tour' => 1,
        '--file' => $fixtureFile,
    ])->assertSuccessful();

    $count1 = ListeElectorale::where('source', 'datagouv')->count();

    $this->artisan('municipales:import-candidatures', [
        'tour' => 1,
        '--file' => $fixtureFile,
    ])->assertSuccessful();

    $count2 = ListeElectorale::where('source', 'datagouv')->count();

    expect($count2)->toBe($count1);
});
