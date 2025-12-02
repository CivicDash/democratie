<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige la vue senateurs_commissions pour afficher
 * le libellé complet de la commission au lieu du code
 */
return new class extends Migration
{
    public function up(): void
    {
        // Supprimer la vue existante
        DB::statement("DROP VIEW IF EXISTS senateurs_commissions CASCADE");

        // Recréer avec jointure sur senat_senateurs_com pour avoir le libellé
        DB::statement("
            CREATE VIEW senateurs_commissions AS
            SELECT DISTINCT ON (mc.senmat, mc.orgcod, mc.memcomdatdeb)
                mc.memcomid AS id,
                TRIM(mc.senmat) AS senateur_matricule,
                mc.orgcod AS commission_code,
                COALESCE(com.comlib, mc.orgcod) AS commission_nom,
                mc.memcomdatdeb::date AS date_debut,
                mc.memcomdatfin::date AS date_fin,
                CASE 
                    WHEN mc.memcomdatfin IS NULL THEN true
                    ELSE false
                END AS actif,
                COALESCE(mc.memcomtitsup, 'Membre') AS fonction,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_memcom mc
            LEFT JOIN senat_senateurs_com com ON mc.orgcod = com.comcod
            ORDER BY mc.senmat, mc.orgcod, mc.memcomdatdeb, mc.memcomdatdeb DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_commissions CASCADE");
        DB::statement("
            CREATE VIEW senateurs_commissions AS
            SELECT DISTINCT ON (mc.senmat, mc.orgcod, mc.memcomdatdeb)
                mc.memcomid AS id,
                TRIM(mc.senmat) AS senateur_matricule,
                mc.orgcod AS commission_code,
                mc.orgcod AS commission_nom,
                mc.memcomdatdeb::date AS date_debut,
                mc.memcomdatfin::date AS date_fin,
                CASE 
                    WHEN mc.memcomdatfin IS NULL THEN true
                    ELSE false
                END AS actif,
                mc.memcomtitsup AS fonction,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_memcom mc
            ORDER BY mc.senmat, mc.orgcod, mc.memcomdatdeb DESC NULLS LAST
        ");
    }
};

