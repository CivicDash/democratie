<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Légifrance (PISTE)
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'accès à l'API Légifrance via la plateforme PISTE
    | https://piste.gouv.fr
    |
    */

    'client_id' => env('LEGIFRANCE_CLIENT_ID'),
    'client_secret' => env('LEGIFRANCE_CLIENT_SECRET'),

    'oauth_url' => env('LEGIFRANCE_OAUTH_URL', 'https://oauth.piste.gouv.fr/api/oauth/token'),
    'api_url' => env('LEGIFRANCE_API_URL', 'https://api.piste.gouv.fr/dila/legifrance/lf-engine-app'),

    // Durée de cache du token OAuth (en minutes)
    'token_cache_ttl' => 50, // Token valide 1h, on le renouvelle à 50min

    // Durée de cache des réponses API (en minutes)
    'response_cache_ttl' => 60 * 24, // 24h

    // Timeout des requêtes (en secondes)
    'timeout' => 30,
];
