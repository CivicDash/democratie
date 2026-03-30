<?php

/**
 * Configuration des sources de données HATVP
 *
 * Haute Autorité pour la Transparence de la Vie Publique
 * https://www.hatvp.fr/
 */

return [
    /*
    |--------------------------------------------------------------------------
    | URLs de Base
    |--------------------------------------------------------------------------
    */
    'base_url' => 'https://www.hatvp.fr/livraison',
    'fiche_url' => 'https://www.hatvp.fr/fiche-nominative/',

    /*
    |--------------------------------------------------------------------------
    | Sources de Données
    |--------------------------------------------------------------------------
    */
    'sources' => [
        'declarations' => [
            'url' => 'https://www.hatvp.fr/livraison/merge/declarations.xml',
            'description' => 'Export complet de toutes les déclarations',
            'format' => 'xml',
        ],
        'dossiers' => [
            'url' => 'https://www.hatvp.fr/livraison/dossiers/',
            'description' => 'Déclarations individuelles',
            'format' => 'xml',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de Déclarations
    |--------------------------------------------------------------------------
    */
    'types_declarations' => [
        // Déclarations d'intérêts et d'activités
        'DIA' => 'Déclaration d\'intérêts et d\'activités modificative',
        'DIAC' => 'Déclaration d\'intérêts et d\'activités de fin de mandat',
        'DIAI' => 'Déclaration initiale d\'intérêts et d\'activités',

        // Déclarations de situation patrimoniale
        'DSP' => 'Déclaration de situation patrimoniale modificative',
        'DSPC' => 'Déclaration de situation patrimoniale de fin de mandat',
        'DSPI' => 'Déclaration initiale de situation patrimoniale',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catégories de Mandats
    |--------------------------------------------------------------------------
    */
    'categories_mandats' => [
        'PAR' => 'Député ou sénateur',
        'GOV' => 'Membre du Gouvernement',
        'EUR' => 'Député européen',
        'LOC' => 'Élu local',
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de Mandats
    |--------------------------------------------------------------------------
    */
    'types_mandats' => [
        'senateur' => 'Sénateur',
        'depute' => 'Député',
        'depute-europeen' => 'Député européen',
        'ministre' => 'Membre du Gouvernement',
        'maire' => 'Maire',
        'president-conseil-departemental' => 'Président de conseil départemental',
        'president-conseil-regional' => 'Président de conseil régional',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filtres pour Parlementaires
    |--------------------------------------------------------------------------
    |
    | Types de mandats à importer pour le projet
    |
    */
    'filtres_parlementaires' => [
        'senateur',
        'depute',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sections de la Déclaration d'Intérêts (DIA)
    |--------------------------------------------------------------------------
    */
    'sections_interets' => [
        'activConsultantDto' => [
            'label' => 'Activités de consultant',
            'table' => 'hatvp_activites_consultant',
        ],
        'activProfCinqDerniereDto' => [
            'label' => 'Activités professionnelles (5 dernières années)',
            'table' => 'hatvp_activites_professionnelles',
        ],
        'activProfConjointDto' => [
            'label' => 'Activités professionnelles du conjoint',
            'table' => 'hatvp_activites_conjoint',
        ],
        'fonctionBenevoleDto' => [
            'label' => 'Fonctions bénévoles',
            'table' => 'hatvp_fonctions_benevoles',
        ],
        'mandatElectifDto' => [
            'label' => 'Fonctions et mandats électifs',
            'table' => 'hatvp_mandats_electifs',
        ],
        'participationDirigeantDto' => [
            'label' => 'Participations aux organes dirigeants',
            'table' => 'hatvp_participations_dirigeantes',
        ],
        'participationFinanciereDto' => [
            'label' => 'Participations financières directes',
            'table' => 'hatvp_participations_financieres',
        ],
        'activCollaborateursDto' => [
            'label' => 'Collaborateurs parlementaires',
            'table' => 'hatvp_collaborateurs',
        ],
        'observationInteretDto' => [
            'label' => 'Observations',
            'table' => null, // Stocké dans la déclaration
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sections de la Déclaration de Patrimoine (DSP)
    |--------------------------------------------------------------------------
    */
    'sections_patrimoine' => [
        'immeubleDto' => [
            'label' => 'Immeubles bâtis et non bâtis',
            'table' => 'hatvp_immeubles',
        ],
        'sciDto' => [
            'label' => 'Parts de SCI',
            'table' => 'hatvp_sci',
        ],
        'valeursNonEnBourseDto' => [
            'label' => 'Valeurs mobilières non cotées',
            'table' => 'hatvp_valeurs_non_cotees',
        ],
        'valeursEnBourseDto' => [
            'label' => 'Valeurs mobilières cotées',
            'table' => 'hatvp_valeurs_cotees',
        ],
        'assuranceVieDto' => [
            'label' => 'Assurances vie',
            'table' => 'hatvp_assurances_vie',
        ],
        'comptesBancaireDto' => [
            'label' => 'Comptes bancaires et épargne',
            'table' => 'hatvp_comptes_bancaires',
        ],
        'bienDiverDto' => [
            'label' => 'Biens mobiliers divers (> 10 000€)',
            'table' => 'hatvp_biens_divers',
        ],
        'vehiculeDto' => [
            'label' => 'Véhicules à moteur',
            'table' => 'hatvp_vehicules',
        ],
        'fondDto' => [
            'label' => 'Fonds de commerce',
            'table' => 'hatvp_fonds_commerce',
        ],
        'autreBienDto' => [
            'label' => 'Autres biens (> 10 000€)',
            'table' => 'hatvp_autres_biens',
        ],
        'bienEtrangerDto' => [
            'label' => 'Biens à l\'étranger',
            'table' => 'hatvp_biens_etrangers',
        ],
        'passifDto' => [
            'label' => 'Passif (dettes)',
            'table' => 'hatvp_passif',
        ],
        'revenuMandatDto' => [
            'label' => 'Revenus annuels',
            'table' => 'hatvp_revenus',
        ],
        'evenementMajeurDto' => [
            'label' => 'Événements majeurs',
            'table' => 'hatvp_evenements_majeurs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stockage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'path' => storage_path('app/hatvp-data'),
        'xml_path' => storage_path('app/hatvp-data/xml'),
        'cache_path' => storage_path('app/hatvp-data/cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'duration' => 86400, // 24 heures
        'check_modified' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */
    'import' => [
        'timeout' => 300, // 5 minutes pour le gros fichier
        'batch_size' => 100,
        'skip_non_published' => true, // Ignorer les [Données non publiées]
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'hatvp-sync',
    ],
];
