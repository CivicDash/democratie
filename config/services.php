<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | data.gouv.fr Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration avec l'API data.gouv.fr
    | API Key optionnelle mais recommandée pour augmenter les limites de taux
    | Documentation: https://www.data.gouv.fr/fr/apidoc/
    |
    */
    'datagouv' => [
        'api_key' => env('DATAGOUV_API_KEY'),
        'cache_ttl' => env('DATAGOUV_CACHE_TTL', 604800), // 7 jours par défaut
    ],

    /*
    |--------------------------------------------------------------------------
    | Légifrance API (PISTE) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration avec l'API Légifrance via PISTE
    | Inscription: https://piste.gouv.fr/registration
    | Documentation: https://api.piste.gouv.fr/dila/legifrance/docs
    |
    */
    'legifrance' => [
        'client_id' => env('LEGIFRANCE_CLIENT_ID'),
        'client_secret' => env('LEGIFRANCE_CLIENT_SECRET'),
        'cache_ttl' => env('LEGIFRANCE_CACHE_TTL', 604800), // 7 jours par défaut
    ],

];
