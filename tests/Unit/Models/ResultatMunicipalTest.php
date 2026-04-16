<?php

use App\Models\ResultatMunicipal;

it('has correct casts', function () {
    $model = new ResultatMunicipal;
    $casts = $model->getCasts();

    expect($casts)->toHaveKeys([
        'tour', 'inscrits', 'abstentions', 'votants',
        'blancs', 'nuls', 'exprimes',
    ]);
});

it('returns correct statut libelle', function () {
    $model = new ResultatMunicipal(['statut_commune' => 'elu_t1']);
    expect($model->statut_libelle)->toBe('Élu au 1er tour');

    $model2 = new ResultatMunicipal(['statut_commune' => 'second_tour']);
    expect($model2->statut_libelle)->toBe('Second tour');
});

it('formats participation correctly', function () {
    $model = new ResultatMunicipal(['taux_participation' => 65.43]);
    expect($model->taux_participation_formate)->toContain('65');
    expect($model->taux_participation_formate)->toContain('%');
});

it('has fillable fields', function () {
    $model = new ResultatMunicipal;
    expect($model->getFillable())->toContain('code_commune');
    expect($model->getFillable())->toContain('tour');
    expect($model->getFillable())->toContain('inscrits');
    expect($model->getFillable())->toContain('statut_commune');
});
