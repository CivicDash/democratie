<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transforme la table senateurs en vue SQL qui pointe vers les données brutes importées
     */
    public function up(): void
    {
        // 1. Renommer la table actuelle comme backup
        DB::statement("ALTER TABLE IF EXISTS senateurs RENAME TO senateurs_backup_old");
        
        // 2. Créer une vue avec le même nom qui mappe les données brutes
        DB::statement("
            CREATE OR REPLACE VIEW senateurs AS
            SELECT 
                -- ID et identité
                sen.senmat AS id,
                sen.senmat AS matricule,
                CASE 
                    WHEN qua.qualib = 'Monsieur' THEN 'M.'
                    WHEN qua.qualib = 'Madame' THEN 'Mme'
                    ELSE COALESCE(qua.qualib, 'M.')
                END AS civilite,
                sen.sennomuse AS nom_usuel,
                sen.senprenomuse AS prenom_usuel,
                
                -- État (AC = Actif, AN = Ancien)
                CASE 
                    WHEN sen.etasencod = 'AC' THEN 'ACTIF'
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
                
                -- Champs Laravel (pour compatibilité)
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

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs");
        DB::statement("ALTER TABLE IF EXISTS senateurs_backup_old RENAME TO senateurs");
    }
};

