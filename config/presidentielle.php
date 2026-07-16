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
];
