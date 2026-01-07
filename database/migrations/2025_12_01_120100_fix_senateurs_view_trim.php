<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige la vue senateurs en utilisant TRIM() pour le matricule
     * 
     * Problème : senmat est de type character(6) avec padding d'espaces
     * Solution : Utiliser TRIM() pour enlever les espaces
     * 
     * Note: PostgreSQL ne permet pas de changer le type d'une colonne avec CREATE OR REPLACE VIEW
     * Il faut d'abord DROP les vues puis les recréer dans l'ordre
     */
    public function up(): void
    {
        // 1. Supprimer les vues dans l'ordre inverse des dépendances
        DB::statement("DROP VIEW IF EXISTS votes_senat CASCADE");
        DB::statement("DROP VIEW IF EXISTS scrutins_senat CASCADE");
        DB::statement("DROP VIEW IF EXISTS senateurs_votes CASCADE");
        DB::statement("DROP VIEW IF EXISTS senateurs_scrutins CASCADE");
        DB::statement("DROP VIEW IF EXISTS amendements_senat CASCADE");
        DB::statement("DROP VIEW IF EXISTS senateurs CASCADE");
        
        // 2. Recréer la vue senateurs avec TRIM
        DB::statement("
            CREATE VIEW senateurs AS
            SELECT 
                -- ID et identité (TRIM pour enlever les espaces du character(6))
                TRIM(sen.senmat) AS id,
                TRIM(sen.senmat) AS matricule,
                CASE 
                    WHEN qua.qualib = 'Monsieur' THEN 'M.'
                    WHEN qua.qualib = 'Madame' THEN 'Mme'
                    ELSE COALESCE(qua.qualib, 'M.')
                END AS civilite,
                sen.sennomuse AS nom,
                sen.sennomuse AS nom_usuel,
                sen.senprenomuse AS prenom,
                sen.senprenomuse AS prenom_usuel,
                
                -- État
                CASE 
                    WHEN sen.etasencod = 'ACTIF' THEN 'ACTIF'
                    ELSE 'ANCIEN'
                END AS etat,
                
                -- Dates
                sen.sendatnai::date AS date_naissance,
                sen.sendatdec::date AS date_deces,
                
                -- Groupe politique actuel
                COALESCE(sen.sengrppolliccou, 'Non inscrit') AS groupe_politique,
                sen.sengrppolcodcou AS groupe_politique_code,
                sen.sentypappcou AS type_appartenance_groupe,
                
                -- Commission permanente actuelle
                sen.sencomliccou AS commission_permanente,
                sen.sencomcodcou AS commission_permanente_code,
                
                -- Circonscription
                LPAD(sen.sencirnumcou::text, 2, '0') AS departement_code,
                sen.sencircou AS circonscription,
                
                -- Fonction au bureau du Sénat
                sen.senburliccou AS fonction_bureau_senat,
                
                -- Contact
                sen.senema AS email,
                
                -- Profession
                sen.pcscod AS pcs_insee,
                sen.catprocod AS categorie_socio_pro,
                sen.sendespro AS description_profession,
                sen.sendespro AS profession,
                
                -- Wikipedia (sera enrichi plus tard)
                sen.sendaiurl AS wikipedia_url,
                NULL::text AS wikipedia_photo,
                NULL::text AS photo_wikipedia_url,
                NULL::text AS wikipedia_extract,
                NULL::timestamp AS wikipedia_last_sync,
                
                -- Timestamps Laravel
                sen.syscredat AS created_at,
                sen.sysmajdat AS updated_at
                
            FROM senat_senateurs_sen sen
            LEFT JOIN senat_senateurs_qua qua ON sen.quacod = qua.quacod
        ");

        // 3. Recréer senateurs_votes avec TRIM
        DB::statement("
            CREATE VIEW senateurs_votes AS
            SELECT 
                v.votesid AS id,
                TRIM(v.senmat) AS senateur_matricule,
                v.scrid AS scrutin_id,
                scr.scrdat::date AS date_vote,
                scr.scrint AS intitule,
                scr.scrintext AS intitule_complet,
                CASE 
                    WHEN v.posvotcod = 'P' THEN 'pour'
                    WHEN v.posvotcod = 'C' THEN 'contre'
                    WHEN v.posvotcod = 'A' THEN 'abstention'
                    WHEN v.posvotcod = 'NV' THEN 'non_votant'
                    ELSE v.posvotcod
                END AS position,
                CASE 
                    WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
                    WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
                    ELSE 'Égalité'
                END AS resultat_scrutin,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_votes v
            LEFT JOIN senat_senateurs_scr scr ON v.scrid = scr.scrid
            WHERE v.senmat IS NOT NULL
            ORDER BY scr.scrdat DESC NULLS LAST
        ");

        // 4. Recréer senateurs_scrutins
        DB::statement("
            CREATE VIEW senateurs_scrutins AS
            SELECT 
                scr.scrid AS id,
                scr.sesann AS session_annee,
                scr.scrnum AS numero,
                scr.typscrcod AS type_scrutin_code,
                typscr.typscrlib AS type_scrutin,
                scr.scrdat::date AS date_scrutin,
                scr.scrint AS intitule,
                scr.scrintext AS intitule_complet,
                scr.scrpou AS pour,
                scr.scrcon AS contre,
                scr.scrvot AS votants,
                scr.scrsuf AS suffrages_exprimes,
                scr.scrmaj AS majorite_requise,
                CASE 
                    WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
                    WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
                    WHEN scr.scrpou = scr.scrcon THEN 'Égalité'
                    ELSE 'Non déterminé'
                END AS resultat,
                scr.syscredat AS created_at,
                scr.sysmajdat AS updated_at
                
            FROM senat_senateurs_scr scr
            LEFT JOIN senat_senateurs_typscr typscr ON scr.typscrcod = typscr.typscrcod
            ORDER BY scr.scrdat DESC NULLS LAST, scr.scrnum DESC
        ");

        // 5. Recréer les alias
        DB::statement("
            CREATE VIEW votes_senat AS
            SELECT * FROM senateurs_votes
        ");
        
        DB::statement("
            CREATE VIEW scrutins_senat AS
            SELECT * FROM senateurs_scrutins
        ");

        // 6. Recréer amendements_senat
        // Vérifier si sen_ameli existe
        $senAmeliExists = Schema::hasTable('sen_ameli');
        
        if ($senAmeliExists) {
            DB::statement("
                CREATE VIEW amendements_senat AS
                SELECT 
                    amd.id AS id,
                    TRIM(sen_ameli.mat) AS senateur_matricule,
                    amd.num AS numero,
                    amd.typ AS type_amendement,
                    amd.dis AS dispositif,
                    amd.obj AS expose,
                    amd.datdep::date AS date_depot,
                    sor.lib AS sort_libelle,
                    sor.cod AS sort_code,
                    amdsen.nomuse AS auteur_nom,
                    amdsen.prenomuse AS auteur_prenom,
                    amdsen.grpid AS auteur_groupe_id,
                    NOW() AS created_at,
                    NOW() AS updated_at
                    
                FROM senat_ameli_amd amd
                LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
                LEFT JOIN sen_ameli ON amdsen.senid = sen_ameli.entid
                LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
                WHERE amdsen.senid IS NOT NULL
                ORDER BY amd.datdep DESC NULLS LAST
            ");
        } else {
            // Fallback : jointure par nom/prénom
            DB::statement("
                CREATE VIEW amendements_senat AS
                SELECT 
                    amd.id AS id,
                    TRIM(sen.senmat) AS senateur_matricule,
                    amd.num AS numero,
                    amd.typ AS type_amendement,
                    amd.dis AS dispositif,
                    amd.obj AS expose,
                    amd.datdep::date AS date_depot,
                    sor.lib AS sort_libelle,
                    sor.cod AS sort_code,
                    amdsen.nomuse AS auteur_nom,
                    amdsen.prenomuse AS auteur_prenom,
                    amdsen.grpid AS auteur_groupe_id,
                    NOW() AS created_at,
                    NOW() AS updated_at
                    
                FROM senat_ameli_amd amd
                LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
                LEFT JOIN senat_senateurs_sen sen 
                    ON UPPER(TRIM(amdsen.nomuse)) = UPPER(TRIM(sen.sennomuse))
                    AND UPPER(TRIM(amdsen.prenomuse)) = UPPER(TRIM(sen.senprenomuse))
                LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
                WHERE amdsen.senid IS NOT NULL
                ORDER BY amd.datdep DESC NULLS LAST
            ");
        }
    }

    public function down(): void
    {
        // Supprimer et recréer les vues sans TRIM (version originale)
        DB::statement("DROP VIEW IF EXISTS votes_senat CASCADE");
        DB::statement("DROP VIEW IF EXISTS scrutins_senat CASCADE");
        DB::statement("DROP VIEW IF EXISTS senateurs_votes CASCADE");
        DB::statement("DROP VIEW IF EXISTS senateurs_scrutins CASCADE");
        DB::statement("DROP VIEW IF EXISTS amendements_senat CASCADE");
        DB::statement("DROP VIEW IF EXISTS senateurs CASCADE");
        
        // Recréer senateurs sans TRIM
        DB::statement("
            CREATE VIEW senateurs AS
            SELECT 
                sen.senmat AS id,
                sen.senmat AS matricule,
                CASE 
                    WHEN qua.qualib = 'Monsieur' THEN 'M.'
                    WHEN qua.qualib = 'Madame' THEN 'Mme'
                    ELSE COALESCE(qua.qualib, 'M.')
                END AS civilite,
                sen.sennomuse AS nom_usuel,
                sen.senprenomuse AS prenom_usuel,
                sen.etasencod AS etat,
                sen.sendatnai::date AS date_naissance,
                sen.sendatdec::date AS date_deces,
                COALESCE(sen.sengrppolliccou, 'Non inscrit') AS groupe_politique,
                sen.sengrppolcodcou AS groupe_politique_code,
                sen.sentypappcou AS type_appartenance_groupe,
                sen.sencomliccou AS commission_permanente,
                sen.sencomcodcou AS commission_permanente_code,
                LPAD(sen.sencirnumcou::text, 2, '0') AS departement_code,
                sen.sencircou AS circonscription,
                sen.senburliccou AS fonction_bureau_senat,
                sen.senema AS email,
                sen.pcscod AS pcs_insee,
                sen.catprocod AS categorie_socio_pro,
                sen.sendespro AS description_profession,
                NULL::text AS wikipedia_url,
                NULL::text AS photo_wikipedia_url,
                NULL::text AS wikipedia_extract,
                NULL::timestamp AS wikipedia_last_sync,
                NOW() AS created_at,
                NOW() AS updated_at
            FROM senat_senateurs_sen sen
            LEFT JOIN senat_senateurs_qua qua ON sen.quacod = qua.quacod
        ");

        // Recréer senateurs_votes sans TRIM
        DB::statement("
            CREATE VIEW senateurs_votes AS
            SELECT 
                v.votesid AS id,
                v.senmat AS senateur_matricule,
                v.scrid AS scrutin_id,
                scr.scrdat::date AS date_vote,
                scr.scrint AS intitule,
                scr.scrintext AS intitule_complet,
                CASE 
                    WHEN v.posvotcod = 'P' THEN 'pour'
                    WHEN v.posvotcod = 'C' THEN 'contre'
                    WHEN v.posvotcod = 'A' THEN 'abstention'
                    WHEN v.posvotcod = 'NV' THEN 'non_votant'
                    ELSE v.posvotcod
                END AS position,
                CASE 
                    WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
                    WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
                    ELSE 'Égalité'
                END AS resultat_scrutin,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_votes v
            LEFT JOIN senat_senateurs_scr scr ON v.scrid = scr.scrid
            WHERE v.senmat IS NOT NULL
            ORDER BY scr.scrdat DESC NULLS LAST
        ");

        // Recréer senateurs_scrutins
        DB::statement("
            CREATE VIEW senateurs_scrutins AS
            SELECT 
                scr.scrid AS id,
                scr.sesann AS session_annee,
                scr.scrnum AS numero,
                scr.typscrcod AS type_scrutin_code,
                typscr.typscrlib AS type_scrutin,
                scr.scrdat::date AS date_scrutin,
                scr.scrint AS intitule,
                scr.scrintext AS intitule_complet,
                scr.scrpou AS pour,
                scr.scrcon AS contre,
                scr.scrvot AS votants,
                scr.scrsuf AS suffrages_exprimes,
                scr.scrmaj AS majorite_requise,
                CASE 
                    WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
                    WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
                    WHEN scr.scrpou = scr.scrcon THEN 'Égalité'
                    ELSE 'Non déterminé'
                END AS resultat,
                scr.syscredat AS created_at,
                scr.sysmajdat AS updated_at
                
            FROM senat_senateurs_scr scr
            LEFT JOIN senat_senateurs_typscr typscr ON scr.typscrcod = typscr.typscrcod
            ORDER BY scr.scrdat DESC NULLS LAST, scr.scrnum DESC
        ");

        // Recréer les alias
        DB::statement("CREATE VIEW votes_senat AS SELECT * FROM senateurs_votes");
        DB::statement("CREATE VIEW scrutins_senat AS SELECT * FROM senateurs_scrutins");
    }
};
