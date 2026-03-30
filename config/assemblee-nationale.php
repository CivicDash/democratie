<?php

/**
 * Configuration des sources de données de l'Assemblée Nationale
 *
 * Documentation : https://data.assemblee-nationale.fr/
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Législature par défaut
    |--------------------------------------------------------------------------
    |
    | Numéro de la législature à utiliser par défaut pour les imports.
    | 16 = 2022-2024 (dissolution)
    | 17 = 2024-présent
    | 18 = future
    |
    */
    'legislature' => env('AN_LEGISLATURE', 17),

    /*
    |--------------------------------------------------------------------------
    | URL de base
    |--------------------------------------------------------------------------
    */
    'base_url' => 'http://data.assemblee-nationale.fr/static/openData/repository',

    /*
    |--------------------------------------------------------------------------
    | Sources de données
    |--------------------------------------------------------------------------
    |
    | Chaque source définit :
    | - path : Chemin relatif avec {legislature} comme placeholder
    | - model : Modèle Eloquent cible
    | - parser : Nom du parser à utiliser
    | - priority : Ordre d'import (1 = premier)
    | - description : Description de la source
    |
    */
    'sources' => [
        'deputes_actifs' => [
            'path' => '{legislature}/amo/deputes_actifs_mandats_actifs_organes/AMO10_deputes_actifs_mandats_actifs_organes.xml.zip',
            'model' => \App\Models\ActeurAN::class,
            'parser' => 'deputes',
            'priority' => 1,
            'description' => 'Députés en exercice avec leurs mandats et organes',
            'root_element' => 'export',
            'item_element' => 'acteur',
        ],

        'tous_acteurs' => [
            'path' => '{legislature}/amo/tous_acteurs_mandats_organes_xi_legislature/AMO30_tous_acteurs_tous_mandats_tous_organes_historique.xml.zip',
            'model' => \App\Models\ActeurAN::class,
            'parser' => 'acteurs',
            'priority' => 2,
            'description' => 'Historique complet de tous les acteurs parlementaires',
            'root_element' => 'export',
            'item_element' => 'acteur',
        ],

        'organes' => [
            'path' => '{legislature}/amo/tous_acteurs_mandats_organes_xi_legislature/AMO30_tous_acteurs_tous_mandats_tous_organes_historique.xml.zip',
            'model' => \App\Models\OrganeAN::class,
            'parser' => 'organes',
            'priority' => 3,
            'description' => 'Organes parlementaires (groupes, commissions, etc.)',
            'root_element' => 'export',
            'item_element' => 'organe',
        ],

        'scrutins' => [
            'path' => '{legislature}/loi/scrutins/Scrutins.xml.zip',
            'model' => \App\Models\ScrutinAN::class,
            'parser' => 'scrutins',
            'priority' => 4,
            'description' => 'Votes publics et résultats des scrutins',
            'root_element' => 'scrutins',
            'item_element' => 'scrutin',
        ],

        'amendements' => [
            'path' => '{legislature}/loi/amendements_div_legis/Amendements.xml.zip',
            'model' => \App\Models\AmendementAN::class,
            'parser' => 'amendements',
            'priority' => 5,
            'description' => 'Tous les amendements déposés',
            'root_element' => 'amendements',
            'item_element' => 'amendement',
            'chunked' => true, // Fichier volumineux, utiliser XMLReader
        ],

        'dossiers' => [
            'path' => '{legislature}/loi/dossiers_legislatifs/Dossiers_Legislatifs.xml.zip',
            'model' => \App\Models\DossierLegislatifAN::class,
            'parser' => 'dossiers',
            'priority' => 6,
            'description' => 'Dossiers législatifs et textes de loi',
            'root_element' => 'dossiersLegislatifs',
            'item_element' => 'dossier',
        ],

        'reunions' => [
            'path' => '{legislature}/vp/reunions/Agenda.xml.zip',
            'model' => \App\Models\ReunionAN::class,
            'parser' => 'reunions',
            'priority' => 7,
            'description' => 'Agenda des réunions parlementaires',
            'root_element' => 'agenda',
            'item_element' => 'reunion',
        ],

        'questions' => [
            'path' => '{legislature}/questions/questions_gouvernement/Questions_gouvernement.xml.zip',
            'model' => \App\Models\QuestionAN::class,
            'parser' => 'questions',
            'priority' => 8,
            'description' => 'Questions au gouvernement',
            'root_element' => 'questions',
            'item_element' => 'question',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stockage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'path' => storage_path('app/an-data'),
        'zip_path' => storage_path('app/an-data/zip'),
        'xml_path' => storage_path('app/an-data/xml'),
        'cache_path' => storage_path('app/an-data/cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'duration' => 3600, // 1 heure en secondes
        'check_etag' => true, // Vérifier le ETag HTTP pour éviter re-téléchargement
    ],

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */
    'import' => [
        'chunk_size' => 1000, // Nombre d'éléments par lot
        'memory_limit' => '512M', // Limite mémoire pour les gros fichiers
        'timeout' => 3600, // Timeout en secondes (1h)
        'retry_attempts' => 3, // Nombre de tentatives en cas d'erreur
        'retry_delay' => 5, // Délai entre les tentatives (secondes)
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'an-sync',
        'path' => storage_path('logs/an-sync.log'),
    ],
];
