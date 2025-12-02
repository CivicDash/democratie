<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige les doublons dans les vues sénateurs
 * - Ajoute DISTINCT pour éliminer les doublons
 * - Utilise TRIM() sur les matricules
 * - Ajoute les libellés pour les groupes et commissions
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Corriger la vue senateurs_mandats avec DISTINCT
        DB::statement("DROP VIEW IF EXISTS senateurs_mandats CASCADE");
        DB::statement("
            CREATE VIEW senateurs_mandats AS
            SELECT DISTINCT ON (elusen.senmat, elusen.eludatdeb, elusen.dptnum)
                elusen.eluid AS id,
                TRIM(elusen.senmat) AS senateur_matricule,
                elusen.eludatdeb::date AS date_debut,
                elusen.eludatfin::date AS date_fin,
                CASE 
                    WHEN elusen.eludatfin IS NULL THEN true
                    ELSE false
                END AS actif,
                LPAD(elusen.dptnum::text, 2, '0') AS departement_code,
                dpt.dptlib AS departement_nom,
                dpt.dptlib AS circonscription,
                NULL::integer AS circonscription_numero,
                typman.typmanlib AS type_mandat,
                elusen.eluanndeb AS annee_debut,
                elusen.eluannfin AS annee_fin,
                NULL::integer AS numero_mandat,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_elusen elusen
            LEFT JOIN senat_senateurs_dpt dpt ON elusen.dptnum = dpt.dptnum
            LEFT JOIN senat_senateurs_typman typman ON elusen.typmancod = typman.typmancod
            WHERE elusen.typmancod = 'SENAT'
            ORDER BY elusen.senmat, elusen.eludatdeb DESC NULLS LAST, elusen.dptnum
        ");

        // 2. Corriger la vue senateurs_commissions avec DISTINCT
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

        // 3. Corriger la vue senateurs_historique_groupes avec DISTINCT et libellés
        DB::statement("DROP VIEW IF EXISTS senateurs_historique_groupes CASCADE");
        DB::statement("
            CREATE VIEW senateurs_historique_groupes AS
            SELECT DISTINCT ON (mg.senmat, mg.orgcod, mg.memgrpsendatent)
                mg.memgrpsenid AS id,
                TRIM(mg.senmat) AS senateur_matricule,
                COALESCE(grp.grppollic, mg.orgcod) AS groupe_nom,
                mg.orgcod AS groupe_code,
                mg.memgrpsendatent::date AS date_debut,
                mg.memgrpsendatsor::date AS date_fin,
                CASE 
                    WHEN mg.memgrpsendatsor IS NULL THEN true
                    ELSE false
                END AS actif,
                'Membre' AS type_appartenance,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_memgrpsen mg
            LEFT JOIN senat_senateurs_grppol grp ON mg.orgcod = grp.grppolcod
            ORDER BY mg.senmat, mg.orgcod, mg.memgrpsendatent DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        // Restaurer les vues originales
        DB::statement("DROP VIEW IF EXISTS senateurs_mandats CASCADE");
        DB::statement("
            CREATE VIEW senateurs_mandats AS
            SELECT 
                elusen.eluid AS id,
                elusen.senmat AS senateur_matricule,
                elusen.eludatdeb::date AS date_debut,
                elusen.eludatfin::date AS date_fin,
                CASE 
                    WHEN elusen.eludatfin IS NULL THEN true
                    ELSE false
                END AS actif,
                LPAD(elusen.dptnum::text, 2, '0') AS departement_code,
                dpt.dptlib AS departement_nom,
                NULL AS circonscription_numero,
                typman.typmanlib AS type_mandat,
                elusen.eluanndeb AS annee_debut,
                elusen.eluannfin AS annee_fin,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_elusen elusen
            LEFT JOIN senat_senateurs_dpt dpt ON elusen.dptnum = dpt.dptnum
            LEFT JOIN senat_senateurs_typman typman ON elusen.typmancod = typman.typmancod
            WHERE elusen.typmancod = 'SENAT'
            ORDER BY elusen.eludatdeb DESC NULLS LAST
        ");

        DB::statement("DROP VIEW IF EXISTS senateurs_commissions CASCADE");
        DB::statement("
            CREATE VIEW senateurs_commissions AS
            SELECT 
                mc.memcomid AS id,
                mc.senmat AS senateur_matricule,
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
            ORDER BY mc.memcomdatdeb DESC NULLS LAST
        ");

        DB::statement("DROP VIEW IF EXISTS senateurs_historique_groupes CASCADE");
        DB::statement("
            CREATE VIEW senateurs_historique_groupes AS
            SELECT 
                mg.memgrpsenid AS id,
                mg.senmat AS senateur_matricule,
                mg.orgcod AS groupe_nom,
                mg.orgcod AS groupe_code,
                mg.memgrpsendatent::date AS date_debut,
                mg.memgrpsendatsor::date AS date_fin,
                CASE 
                    WHEN mg.memgrpsendatsor IS NULL THEN true
                    ELSE false
                END AS actif,
                'Membre' AS type_appartenance,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_memgrpsen mg
            ORDER BY mg.memgrpsendatent DESC NULLS LAST
        ");
    }
};

