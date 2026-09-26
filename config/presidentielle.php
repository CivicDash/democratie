<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Destinataires des notifications de signalement
    |--------------------------------------------------------------------------
    | Adresses prévenues par email à chaque nouveau signalement citoyen
    | (« Signaler une erreur »). Surchargeable via PRESIDENTIELLE_SIGNALEMENT_MAILS
    | (liste séparée par des virgules). Vide => aucune notification envoyée.
    */
    'signalement_notify' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'PRESIDENTIELLE_SIGNALEMENT_MAILS',
            'secretaire@civis-consilium.eu,president@civis-consilium.eu'
        ))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Domaines exclus des sources de « Ce qu'on entend »
    |--------------------------------------------------------------------------
    | Exclusions du cadre éditorial (« CNews / groupe Bolloré »). Une fiche qui cite une
    | source de ces domaines n'est pas publiable. Un sous-domaine est exclu avec son
    | domaine. Liste à valider par l'équipe éditoriale : le périmètre du groupe varie
    | (Prisma Media, notamment, n'y figure pas ici).
    */
    'sources_exclues' => [
        'cnews.fr',
        'europe1.fr',
        'lejdd.fr',
        'parismatch.com',
    ],
];
