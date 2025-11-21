<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vue pour les sénateurs complets
     * Map les tables SQL natives (avec leurs vrais noms de colonnes) vers une structure Laravel-friendly
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_senateurs_complets AS
            SELECT 
                -- Identité
                sen.senmat AS matricule,
                CASE 
                    WHEN qua.quacod = 'M' THEN 'M.'
                    WHEN qua.quacod = 'F' THEN 'Mme'
                    ELSE qua.quacod
                END AS civilite,
                sen.sennomuse AS nom_usuel,
                sen.senprenomuse AS prenom_usuel,
                
                -- État
                sen.etasencod AS etat,
                
                -- Dates
                sen.sendatnai::date AS date_naissance,
                sen.sendatdec::date AS date_deces,
                
                -- Groupe politique actuel (depuis colonnes dénormalisées)
                sen.sengrppolliccou AS groupe_politique,
                sen.sentypappcou AS type_appartenance_groupe,
                
                -- Commission permanente actuelle (depuis colonnes dénormalisées)
                sen.sencomliccou AS commission_permanente,
                
                -- Circonscription (département pour les sénateurs)
                sen.sencircou AS circonscription,
                
                -- Fonction au bureau du Sénat
                sen.senburliccou AS fonction_bureau_senat,
                
                -- Email
                sen.senema AS email,
                
                -- Profession
                sen.pcscod AS pcs_insee,
                sen.catprocod AS categorie_socio_pro,
                sen.sendespro AS description_profession,
                
                -- Timestamps
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_sen sen
            LEFT JOIN senat_senateurs_qua qua ON sen.quacod = qua.quacod
            WHERE sen.etasencod = 'AC' -- Sénateurs actifs uniquement
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_senateurs_complets");
    }
};

