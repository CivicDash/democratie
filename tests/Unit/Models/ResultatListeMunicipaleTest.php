<?php

use App\Models\ResultatListeMunicipale;

it('has correct casts', function () {
    $model = new ResultatListeMunicipale;
    $casts = $model->getCasts();

    expect($casts)->toHaveKeys([
        'voix', 'pourcentage_exprimes', 'elu',
        'sieges_obtenus',
    ]);
});

it('computes tete de liste nom complet', function () {
    $model = new ResultatListeMunicipale([
        'tete_de_liste_prenom' => 'Marie',
        'tete_de_liste_nom' => 'Dupont',
    ]);
    expect($model->tete_de_liste_nom_complet)->toBe('Marie Dupont');
});

it('formats pourcentage correctly', function () {
    $model = new ResultatListeMunicipale(['pourcentage_exprimes' => 54.32]);
    expect($model->pourcentage_formate)->toContain('54');
    expect($model->pourcentage_formate)->toContain('%');
});

it('formats voix correctly', function () {
    $model = new ResultatListeMunicipale(['voix' => 15000]);
    $formatted = $model->voix_formate;
    expect($formatted)->toContain('15');
});

it('has fillable fields', function () {
    $model = new ResultatListeMunicipale;
    expect($model->getFillable())->toContain('voix');
    expect($model->getFillable())->toContain('pourcentage_exprimes');
    expect($model->getFillable())->toContain('elu');
    expect($model->getFillable())->toContain('resultat_commune_id');
});
