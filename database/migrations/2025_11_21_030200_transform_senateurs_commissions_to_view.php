<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transforme senateurs_commissions en vue SQL qui pointe vers les données brutes
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE IF EXISTS senateurs_commissions RENAME TO senateurs_commissions_backup_old");
        
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_commissions AS
            SELECT 
                mc.memcomid AS id,
                mc.senid AS senateur_matricule,
                com.comlib AS commission_nom,
                com.comcod AS commission_code,
                typorg.typorglib AS type_organe,
                mc.memcomdatdeb::date AS date_debut,
                mc.memcomdatfin::date AS date_fin,
                CASE 
                    WHEN mc.memcomdatfin IS NULL THEN true
                    ELSE false
                END AS actif,
                fonmemcom.fonmemcomlib AS fonction,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_memcom mc
            JOIN senat_senateurs_com com ON mc.comid = com.comid
            LEFT JOIN senat_senateurs_typorg typorg ON com.typorgid = typorg.typorgid
            LEFT JOIN senat_senateurs_fonmemcom fonmemcom ON mc.fonmemcomid = fonmemcom.fonmemcomid
            ORDER BY mc.memcomdatdeb DESC
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_commissions");
        DB::statement("ALTER TABLE IF EXISTS senateurs_commissions_backup_old RENAME TO senateurs_commissions");
    }
};

