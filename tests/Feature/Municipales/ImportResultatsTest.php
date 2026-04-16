<?php

use App\Models\ResultatListeMunicipale;
use App\Models\ResultatMunicipal;

it('imports resultats from csv file', function () {
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

    expect(ResultatMunicipal::count())->toBeGreaterThanOrEqual(2);

    $dijon = ResultatMunicipal::where('code_commune', '21231')->first();
    expect($dijon)->not->toBeNull();
    expect($dijon->inscrits)->toBe(95000);
    expect($dijon->votants)->toBe(57000);
    expect($dijon->tour)->toBe(1);
});

it('determines statut commune correctly', function () {
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

    $dijon = ResultatMunicipal::where('code_commune', '21231')->first();
    expect($dijon->statut_commune)->toBe('elu_t1');

    $beaune = ResultatMunicipal::where('code_commune', '21054')->first();
    expect($beaune->statut_commune)->toBe('elu_t1');
});

it('creates resultat listes with correct votes', function () {
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

    $dijon = ResultatMunicipal::where('code_commune', '21231')->first();
    $listes = ResultatListeMunicipale::where('resultat_commune_id', $dijon->id)->get();

    expect($listes)->toHaveCount(3);

    $gagnante = $listes->where('elu', true)->first();
    expect($gagnante)->not->toBeNull();
    expect($gagnante->voix)->toBe(30190);
});
