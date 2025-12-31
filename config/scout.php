<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    */

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => false,

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            // Députés (ActeurAN)
            'acteurs_an' => [
                'searchableAttributes' => [
                    'nom_complet',
                    'prenom',
                    'nom',
                    'profession',
                    'groupe_politique',
                    'circonscription',
                ],
                'filterableAttributes' => [
                    'groupe_politique_sigle',
                    'legislature',
                    'est_depute_actif',
                    'region',
                    'departement',
                ],
                'sortableAttributes' => [
                    'nom',
                    'prenom',
                ],
                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 4,
                        'twoTypos' => 8,
                    ],
                ],
            ],
            // Sénateurs
            'senateurs' => [
                'searchableAttributes' => [
                    'nom_complet',
                    'prenom_usuel',
                    'nom_usuel',
                    'groupe_politique',
                    'circonscription',
                    'description_profession',
                ],
                'filterableAttributes' => [
                    'groupe_politique',
                    'etat',
                    'commission_permanente',
                    'circonscription',
                ],
                'sortableAttributes' => [
                    'nom_usuel',
                    'prenom_usuel',
                ],
                'typoTolerance' => [
                    'enabled' => true,
                ],
            ],
            // Lois
            'lois' => [
                'searchableAttributes' => [
                    'loitit',
                    'loiint',
                    'numero',
                    'motclef',
                ],
                'filterableAttributes' => [
                    'etaloicod',
                    'typloicod',
                    'annee',
                    'chambre_origine',
                ],
                'sortableAttributes' => [
                    'date_loi',
                    'loidatjo',
                ],
                'typoTolerance' => [
                    'enabled' => true,
                ],
            ],
            // Topics (Idées citoyennes)
            'topics_index' => [
                'searchableAttributes' => [
                    'title',
                    'description',
                    'author_name',
                ],
                'filterableAttributes' => [
                    'type',
                    'status',
                    'scope',
                    'idea_type',
                    'region_id',
                    'department_id',
                ],
                'sortableAttributes' => [
                    'created_at',
                    'published_at',
                    'votes_count',
                    'comments_count',
                ],
                'typoTolerance' => [
                    'enabled' => true,
                ],
            ],
            // Maires
            'maires' => [
                'searchableAttributes' => [
                    'nom_complet',
                    'prenom',
                    'nom',
                    'commune_nom',
                    'departement_nom',
                ],
                'filterableAttributes' => [
                    'departement_code',
                    'region_code',
                    'mandat_actif',
                ],
                'sortableAttributes' => [
                    'nom',
                    'commune_nom',
                ],
                'typoTolerance' => [
                    'enabled' => true,
                ],
            ],
            // Scrutins
            'scrutins_an' => [
                'searchableAttributes' => [
                    'libelle',
                    'titre',
                    'sort',
                ],
                'filterableAttributes' => [
                    'legislature',
                    'annee',
                    'sort',
                    'mode_scrutin',
                ],
                'sortableAttributes' => [
                    'date_scrutin',
                    'numero',
                ],
                'typoTolerance' => [
                    'enabled' => true,
                ],
            ],
        ],
    ],

];
