<?php

use App\Support\AuditEcritures;

/**
 * Aucune route d'administration ne doit valider une clé qui n'ira pas en base.
 *
 * C'est le contrôle dont l'absence a produit cinq fois la même panne : douze champs
 * d'adhésion, cinq champs de suspension, quarante-cinq champs de statistiques,
 * l'édition d'un sénateur et celle d'un député — tous perdus en silence derrière un
 * message de succès.
 */
it('ne valide que des clés qui existent en colonne et sont assignables', function () {
    $divergents = collect(AuditEcritures::analyser())
        ->where('statut', 'divergent');

    $rapport = $divergents->map(function (array $l) {
        $details = [];
        if ($l['inconnues']) {
            $details[] = 'colonnes inexistantes : '.implode(', ', $l['inconnues']);
        }
        if ($l['non_fillable']) {
            $details[] = 'hors $fillable : '.implode(', ', $l['non_fillable']);
        }

        return sprintf("  - %s → %s\n      %s", $l['route'], class_basename($l['modele']), implode("\n      ", $details));
    })->implode("\n");

    expect($divergents->values()->all())->toBe([],
        "Ces écritures ne peuvent pas aboutir :\n{$rapport}\n\n".
        "Une clé validée mais absente des colonnes est jetée en silence, et l'écran affiche « succès ».");
});

it('garde un angle mort mesurable', function () {
    // L'analyse est statique : elle ne sait pas lire toutes les formes d'écriture.
    // Ce qu'elle ne comprend pas est compté plutôt que passé sous silence — si ce
    // nombre grimpe, c'est que du code neuf échappe au contrôle.
    $indetermines = collect(AuditEcritures::analyser())->where('statut', 'indetermine')->count();

    expect($indetermines)->toBeLessThanOrEqual(40,
        "{$indetermines} routes d'écriture dont l'analyse ne sait pas déduire le modèle. ".
        'Passer par un FormRequest, ou écrire $modele->update($validated), les rend lisibles.');
});
