<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dette connue, tolérée temporairement par les garde-fous
    |--------------------------------------------------------------------------
    |
    | Ces listes existent pour qu'un garde-fou puisse être vert dès son ajout, et
    | pour que la dette restante soit une liste qu'on regarde diminuer plutôt qu'un
    | échec permanent qu'on apprend à ignorer. Une entrée ne se rajoute pas : elle
    | se retire.
    |
    */

    'garde_fous' => [

        /*
         * Cibles Inertia::render() sans composant Vue. Le rendu direct d'une de ces
         * routes produit un 500 (Vite ne trouve pas le fichier dans le manifeste) ;
         * une navigation interne produit un écran blanc.
         */
        'composants_inertia_absents' => [
            // Vide, et à garder vide : chaque entrée ici est un écran qui renvoie un 500
            // au chargement direct et un écran blanc en navigation interne.
        ],

    ],

];
