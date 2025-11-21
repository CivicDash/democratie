<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transforme senateurs_mandats_locaux et senateurs_etudes en vues SQL
     */
    public function up(): void
    {
        // === MANDATS LOCAUX ===
        DB::statement("ALTER TABLE IF EXISTS senateurs_mandats_locaux RENAME TO senateurs_mandats_locaux_backup_old");
        
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_mandats_locaux AS
            SELECT 
                ROW_NUMBER() OVER (ORDER BY senid, eludatdeb DESC) AS id,
                senid AS senateur_matricule,
                typmanlib AS type_mandat,
                fonmemlib AS fonction,
                comnom AS collectivite,
                eludatdeb::date AS date_debut,
                eludatfin::date AS date_fin,
                CASE 
                    WHEN eludatfin IS NULL THEN true
                    ELSE false
                END AS actif,
                NOW() AS created_at,
                NOW() AS updated_at
            FROM (
                -- Mandats municipaux (maires)
                SELECT 
                    vm.senid,
                    'Municipal' AS typmanlib,
                    'Maire' AS fonmemlib,
                    com.comnom,
                    vm.eludatdeb,
                    vm.eludatfin
                FROM senat_senateurs_eluvil vm
                JOIN senat_senateurs_com_geo com ON vm.comid = com.comid
                WHERE vm.fonmemid IN (SELECT fonmemid FROM senat_senateurs_fonmem WHERE fonmemlib LIKE '%Maire%')
                
                UNION ALL
                
                -- Mandats départementaux
                SELECT 
                    vd.senid,
                    'Départemental' AS typmanlib,
                    fm.fonmemlib,
                    dpt.dptlib AS comnom,
                    vd.eludatdeb,
                    vd.eludatfin
                FROM senat_senateurs_eludep vd
                LEFT JOIN senat_senateurs_fonmem fm ON vd.fonmemid = fm.fonmemid
                LEFT JOIN senat_senateurs_dpt dpt ON vd.dptid = dpt.dptid
                
                UNION ALL
                
                -- Mandats régionaux  
                SELECT 
                    vr.senid,
                    'Régional' AS typmanlib,
                    fm.fonmemlib,
                    reg.reglib AS comnom,
                    vr.eludatdeb,
                    vr.eludatfin
                FROM senat_senateurs_elureg vr
                LEFT JOIN senat_senateurs_fonmem fm ON vr.fonmemid = fm.fonmemid
                LEFT JOIN senat_senateurs_reg reg ON vr.regid = reg.regid
                
                UNION ALL
                
                -- Mandats métropolitains
                SELECT 
                    vm.senid,
                    'Métropolitain' AS typmanlib,
                    fm.fonmemlib,
                    met.metnom AS comnom,
                    vm.eludatdeb,
                    vm.eludatfin
                FROM senat_senateurs_elumet vm
                LEFT JOIN senat_senateurs_fonmem fm ON vm.fonmemid = fm.fonmemid
                LEFT JOIN senat_senateurs_met met ON vm.metid = met.metid
            ) AS all_mandats
        ");
        
        // === ÉTUDES ===
        DB::statement("ALTER TABLE IF EXISTS senateurs_etudes RENAME TO senateurs_etudes_backup_old");
        
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_etudes AS
            SELECT 
                eta.etaid AS id,
                eta.senid AS senateur_matricule,
                eta.etablib AS etablissement,
                eta.diplib AS diplome,
                eta.nivlib AS niveau,
                eta.domlib AS domaine,
                eta.etaann AS annee,
                eta.etades AS details,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_eta eta
            ORDER BY eta.etaann DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_mandats_locaux");
        DB::statement("DROP VIEW IF EXISTS senateurs_etudes");
        DB::statement("ALTER TABLE IF EXISTS senateurs_mandats_locaux_backup_old RENAME TO senateurs_mandats_locaux");
        DB::statement("ALTER TABLE IF EXISTS senateurs_etudes_backup_old RENAME TO senateurs_etudes");
    }
};

