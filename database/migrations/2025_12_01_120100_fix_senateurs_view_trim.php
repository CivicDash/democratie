<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige la vue senateurs en utilisant TRIM() pour le matricule
     * 
     * Problème : senmat est de type character(6) avec padding d'espaces
     * Solution : Utiliser TRIM() pour enlever les espaces
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW senateurs AS
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
    }

    public function down(): void
    {
        // Revenir à la version précédente
        DB::statement("
            CREATE OR REPLACE VIEW senateurs AS
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
    }
};


