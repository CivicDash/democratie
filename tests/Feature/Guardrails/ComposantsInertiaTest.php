<?php

use Illuminate\Support\Facades\File;

/**
 * Toute cible Inertia::render() doit avoir son composant Vue.
 *
 * app.blade.php fait @vite("resources/js/Pages/{$component}.vue") : si le fichier
 * n'est pas dans le manifeste, l'exception remonte en 500 sur un chargement direct,
 * et en écran blanc sur une navigation interne. Deux pages publiques — la politique
 * de cookies, liée depuis les CGU, et la fiche élu — sont tombées ainsi sans que
 * personne ne s'en aperçoive.
 *
 * Ce test coûte quelques millisecondes et n'a besoin ni de base ni d'application.
 */
it('ne rend que des composants Vue qui existent', function () {
    $tolerees = config('civicdash.garde_fous.composants_inertia_absents', []);
    $manquants = [];

    foreach ([base_path('app'), base_path('routes')] as $racine) {
        foreach (File::allFiles($racine) as $fichier) {
            if ($fichier->getExtension() !== 'php') {
                continue;
            }

            preg_match_all("/Inertia::render\(\s*'([^']+)'/", $fichier->getContents(), $trouves);

            foreach ($trouves[1] as $composant) {
                if (File::exists(resource_path("js/Pages/{$composant}.vue"))) {
                    continue;
                }
                if (in_array($composant, $tolerees, true)) {
                    continue;
                }
                $manquants[$composant] = $fichier->getRelativePathname();
            }
        }
    }

    expect($manquants)->toBe([], "Composants Vue absents :\n".collect($manquants)
        ->map(fn (string $source, string $page) => "  - {$page}  (rendu par {$source})")
        ->implode("\n"));
});

it('ne tolère aucune dette déjà réglée', function () {
    // Une entrée de la liste de tolérance qui a été écrite doit en sortir, sinon la
    // liste cesse d'être le décompte de ce qui reste à faire.
    $reglees = collect(config('civicdash.garde_fous.composants_inertia_absents', []))
        ->filter(fn (string $page) => File::exists(resource_path("js/Pages/{$page}.vue")))
        ->values();

    expect($reglees->all())->toBe([],
        "Ces composants existent désormais : retirez-les de config/civicdash.php.\n  ".$reglees->implode("\n  "));
});
