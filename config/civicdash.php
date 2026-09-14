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
            // Module de modération citoyenne — écrans à écrire.
            'Moderation/ReportDetail',
            'Moderation/Sanctions',
            'Moderation/SanctionDetail',
            'Moderation/PriorityReports',
            'Moderation/Stats',

            // Administration.
            'Admin/Moderation/PhotoHistory',
            'Admin/Gouvernement/Ministeres',
            'Admin/Gouvernement/Personnes',

            // Public ou semi-public.
            'Documents/Show',
            'Documents/Pending',
            'Documents/Stats',
            'Elections/Municipales/ShowCandidat',

            // Écrans hérités, rendus par du code non routé ou en sursis.
            'Topics/Index',
            'Topics/Show',
            'Topics/Create',
            'Topics/Edit',
            'Vote/Results',
            'Budget/Sectors',
            'BudgetEtat/Mission',
            'Gouvernement/Historique',
            'Legislation/Lois/Statistiques',
            'Parlement/Calendrier/Semaine',
        ],

    ],

];
