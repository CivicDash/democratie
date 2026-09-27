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
    | domaine. Liste validée par Kévin le 27/09/2026, Prisma Media compris ; le périmètre
    | du groupe varie : la compléter quand un titre change de main.
    */
    'sources_exclues' => [
        // Groupe Bolloré : information.
        'cnews.fr',
        'europe1.fr',
        'lejdd.fr',
        'parismatch.com',
        // Prisma Media (principaux titres).
        'prismamedia.com',
        'capital.fr',
        'geo.fr',
        'caminteresse.fr',
        'businessinsider.fr',
        'hbrfrance.fr',
        'nationalgeographic.fr',
        'femmeactuelle.fr',
        'prima.fr',
        'gala.fr',
        'voici.fr',
        'programme-tv.net',
        'telestar.fr',
        'tele2semaines.fr',
        'cuisineactuelle.fr',
        'neonmag.fr',
    ],
];
