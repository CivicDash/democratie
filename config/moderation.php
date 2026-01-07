<?php

/**
 * Configuration de la modération de contenu CivicDash
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Domaines Whitelistés
    |--------------------------------------------------------------------------
    |
    | Liste des domaines dont les liens sont autorisés dans le contenu utilisateur.
    | Les patterns avec *.domain.tld matchent tous les sous-domaines.
    |
    */
    'whitelisted_domains' => [
        // Sites gouvernementaux français
        '*.gouv.fr',
        'gouv.fr',
        'legifrance.gouv.fr',
        'assemblee-nationale.fr',
        'senat.fr',
        'elysee.fr',
        'conseil-constitutionnel.fr',
        'conseil-etat.fr',
        'ccomptes.fr',
        'service-public.fr',
        'vie-publique.fr',
        'data.gouv.fr',
        'france.fr',
        'hatvp.fr',
        
        // Statistiques officielles
        'insee.fr',
        '*.insee.fr',
        
        // Institutions européennes
        'europa.eu',
        '*.europa.eu',
        'europarl.europa.eu',
        
        // Presse officielle
        'journal-officiel.gouv.fr',
        
        // Open data
        'data.senat.fr',
        'data.assemblee-nationale.fr',
        
        //ONG
        'www.msf.fr/',
        'www.amnesty.fr/',
        'www.croix-rouge.fr/',
        'rsf.org',
        'hrw.org',
        'oxfamfrance.org',
        'actioncontrelafaim.org',
        'wwf.fr',
        'wwf.org',
        'greenpeace.fr',
        'greenpeace.org',
        'emmaus-france.org/',
        'carefrance.org',
        'fne.asso.fr',
        'handicap-international.fr',
        'acted.org',

        // Références internes CivicDash
        '*.objectif2027.fr',
        'objectif2027.fr',
        '*.civis-consilium.eu',
        'civis-consilium.eu',
        'localhost',
    ],

    /*
    |--------------------------------------------------------------------------
    | Patterns de Références Internes
    |--------------------------------------------------------------------------
    |
    | Formats de mentions internes reconnus dans le contenu.
    | Exemple: @loi:2024-123, @depute:PA12345, @senateur:M12345
    |
    */
    'reference_patterns' => [
        'loi' => [
            'pattern' => '/@loi:([A-Za-z0-9\-_]+)/i',
            'route' => 'lois.show',
            'description' => 'Référence à une loi',
            'example' => '@loi:2024-123',
        ],
        'depute' => [
            'pattern' => '/@depute:([A-Za-z0-9]+)/i',
            'route' => 'representants.deputes.show',
            'description' => 'Référence à un député',
            'example' => '@depute:PA123456',
        ],
        'senateur' => [
            'pattern' => '/@senateur:([A-Za-z0-9]+)/i',
            'route' => 'representants.senateurs.show',
            'description' => 'Référence à un sénateur',
            'example' => '@senateur:M12345',
        ],
        'maire' => [
            'pattern' => '/@maire:([0-9]+)/i',
            'route' => 'representants.maires.show',
            'description' => 'Référence à un maire',
            'example' => '@maire:12345',
        ],
        'scrutin' => [
            'pattern' => '/@scrutin:([A-Za-z0-9\-]+)/i',
            'route' => 'scrutins.show',
            'description' => 'Référence à un scrutin',
            'example' => '@scrutin:VTANR5L17V123',
        ],
        'amendement' => [
            'pattern' => '/@amendement:([A-Za-z0-9\-]+)/i',
            'route' => 'amendements.show',
            'description' => 'Référence à un amendement',
            'example' => '@amendement:AN123-456',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Options de Modération par Défaut
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'moderate_words' => true,      // Remplacer les mots bannis
        'sanitize_images' => true,     // Supprimer TOUTES les images (sécurité)
        'sanitize_links' => true,      // Supprimer les liens non autorisés
        'parse_references' => true,    // Parser les références internes
        'log_actions' => true,         // Logger les actions de modération
    ],

    /*
    |--------------------------------------------------------------------------
    | Formats de Mise en Forme Autorisés
    |--------------------------------------------------------------------------
    |
    | Syntaxe Markdown simplifiée autorisée dans les contenus utilisateur.
    |
    */
    'allowed_formats' => [
        'bold' => true,       // **gras**
        'italic' => true,     // *italique*
        'underline' => true,  // __souligné__
        'strike' => true,     // ~~barré~~
        'quote' => true,      // > citation
        'list' => true,       // - liste
        'link' => true,       // [texte](url) - domaines autorisés uniquement
        'mention' => true,    // @type:id
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensions d'Images Bloquées
    |--------------------------------------------------------------------------
    */
    'blocked_image_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'tiff', 'heic',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catégories de Mots Bannis
    |--------------------------------------------------------------------------
    */
    'word_categories' => [
        'insulte' => [
            'label' => 'Insulte',
            'icon' => '🤬',
            'action' => 'replace', // replace ou block
        ],
        'racisme' => [
            'label' => 'Racisme',
            'icon' => '🚫',
            'action' => 'block',
        ],
        'violence' => [
            'label' => 'Violence',
            'icon' => '⚠️',
            'action' => 'block',
        ],
        'spam' => [
            'label' => 'Spam',
            'icon' => '📧',
            'action' => 'replace',
        ],
        'extremisme' => [
            'label' => 'Extrémisme',
            'icon' => '🚨',
            'action' => 'block',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Niveaux de Sévérité
    |--------------------------------------------------------------------------
    */
    'severity_levels' => [
        'low' => [
            'label' => 'Faible',
            'color' => 'green',
            'action' => 'replace',
        ],
        'medium' => [
            'label' => 'Moyen',
            'color' => 'yellow',
            'action' => 'replace',
        ],
        'high' => [
            'label' => 'Élevé',
            'color' => 'red',
            'action' => 'block',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ttl' => 3600, // 1 heure
        'prefix' => 'moderation_',
    ],
];
