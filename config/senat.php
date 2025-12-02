<?php

/**
 * Configuration des sources de données du Sénat
 * 
 * Documentation : https://data.senat.fr/
 */

return [
    /*
    |--------------------------------------------------------------------------
    | URLs de Base
    |--------------------------------------------------------------------------
    */
    'base_url' => 'https://data.senat.fr/data',
    'akn_base_url' => 'https://www.senat.fr/akomantoso',

    /*
    |--------------------------------------------------------------------------
    | Bases SQL PostgreSQL
    |--------------------------------------------------------------------------
    |
    | Chaque base définit :
    | - url : URL du fichier ZIP
    | - description : Description de la base
    | - table_prefix : Préfixe pour les tables importées
    | - priority : Ordre d'import (1 = premier)
    | - tables : Tables principales de la base
    |
    */
    'databases' => [
        'senateurs' => [
            'url' => 'https://data.senat.fr/data/senateurs/export_sens.zip',
            'description' => 'Sénateurs (Profils complets, votes, scrutins)',
            'table_prefix' => 'senat_senateurs_',
            'priority' => 1,
            'tables' => [
                'sen' => 'Sénateurs',
                'qua' => 'Civilités',
                'votes' => 'Votes individuels',
                'scr' => 'Scrutins',
                'typscr' => 'Types de scrutins',
                'grppol' => 'Groupes politiques',
                'com' => 'Commissions',
                'man' => 'Mandats',
            ],
        ],
        
        'dosleg' => [
            'url' => 'https://data.senat.fr/data/dosleg/dosleg.zip',
            'description' => 'Dossiers Législatifs (DOSLEG)',
            'table_prefix' => 'senat_dosleg_',
            'priority' => 2,
            'tables' => [
                'dos' => 'Dossiers',
                'tex' => 'Textes',
                'typtex' => 'Types de textes',
                'eta' => 'Étapes',
                'typeta' => 'Types d\'étapes',
                'rap' => 'Rapports',
                'amd' => 'Amendements (résumé)',
                'autdos' => 'Auteurs',
            ],
        ],
        
        'questions' => [
            'url' => 'https://data.senat.fr/data/questions/questions.zip',
            'description' => 'Questions au Gouvernement',
            'table_prefix' => 'senat_questions_',
            'priority' => 3,
            'tables' => [
                'que' => 'Questions',
                'rep' => 'Réponses',
                'typque' => 'Types de questions',
                'min' => 'Ministères',
                'the' => 'Thèmes',
                'quethe' => 'Liens question-thème',
            ],
        ],
        
        'debats' => [
            'url' => 'https://data.senat.fr/data/debats/debats.zip',
            'description' => 'Comptes rendus des débats',
            'table_prefix' => 'senat_debats_',
            'priority' => 4,
            'tables' => [
                'sea' => 'Séances',
                'int' => 'Interventions',
                'ora' => 'Orateurs',
            ],
        ],
        
        'ameli' => [
            'url' => 'https://data.senat.fr/data/ameli/ameli.zip',
            'description' => 'Amendements (Base AMELI)',
            'table_prefix' => 'senat_ameli_',
            'priority' => 5,
            'tables' => [
                'amd' => 'Amendements',
                'amdsen' => 'Auteurs sénateurs',
                'sor' => 'Sorts (résultats)',
                'tex' => 'Textes législatifs',
                'art' => 'Articles',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Flux XML Akoma Ntoso
    |--------------------------------------------------------------------------
    */
    'akoma_ntoso' => [
        'depots' => [
            'url' => 'https://www.senat.fr/akomantoso/depots.xml',
            'description' => 'Textes déposés récemment',
        ],
        'adoptions' => [
            'url' => 'https://www.senat.fr/akomantoso/adoptions.xml',
            'description' => 'Textes adoptés',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Codes des Sorts d'Amendements
    |--------------------------------------------------------------------------
    */
    'sorts_amendements' => [
        'adoptes' => ['A', 'AM', 'AB'],
        'rejetes' => ['RJS', 'RJ', 'RJB'],
        'retires' => ['R', 'RET'],
        'tombes' => ['S'],
        'non_soutenus' => ['N'],
        'sans_objet' => ['SO'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Codes des Positions de Vote
    |--------------------------------------------------------------------------
    */
    'positions_vote' => [
        'P' => 'pour',
        'C' => 'contre',
        'A' => 'abstention',
        'NV' => 'non_votant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de Questions
    |--------------------------------------------------------------------------
    */
    'types_questions' => [
        'QE' => 'Question écrite',
        'QO' => 'Question orale',
        'QOG' => 'Question orale avec débat',
        'QAG' => 'Question d\'actualité au Gouvernement',
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de Textes
    |--------------------------------------------------------------------------
    */
    'types_textes' => [
        'ppl' => 'Proposition de loi',
        'pjl' => 'Projet de loi',
        'ppr' => 'Proposition de résolution',
        'pjr' => 'Projet de résolution',
        'plf' => 'Projet de loi de finances',
        'plfss' => 'Projet de loi de financement de la sécurité sociale',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stockage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'path' => storage_path('app/senat-data'),
        'zip_path' => storage_path('app/senat-data/zip'),
        'sql_path' => storage_path('app/senat-data/sql'),
        'xml_path' => storage_path('app/senat-data/xml'),
        'cache_path' => storage_path('app/senat-data/cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'duration' => 86400, // 24 heures en secondes
        'check_modified' => true, // Vérifier la date de modification
    ],

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */
    'import' => [
        'timeout' => 600, // 10 minutes pour les gros fichiers
        'memory_limit' => '1G', // Limite mémoire pour les imports SQL
        'use_psql' => true, // Utiliser psql directement (recommandé)
        'batch_size' => 5000, // Taille des lots pour les inserts
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'senat-sync',
        'path' => storage_path('logs/senat-sync.log'),
    ],
];

