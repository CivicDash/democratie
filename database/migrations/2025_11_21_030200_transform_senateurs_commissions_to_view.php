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
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_commissions");
        DB::statement("ALTER TABLE IF EXISTS senateurs_commissions_backup_old RENAME TO senateurs_commissions");
    }
};

