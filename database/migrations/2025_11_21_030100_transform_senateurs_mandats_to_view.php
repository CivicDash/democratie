<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transforme senateurs_mandats en vue SQL qui pointe vers les données brutes
     */
    public function up(): void
    {
        // Renommer la table actuelle comme backup
        DB::statement("ALTER TABLE IF EXISTS senateurs_mandats RENAME TO senateurs_mandats_backup_old");
        
        // Créer une vue qui mappe les mandats sénatoriaux
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_mandats AS
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
            WHERE elusen.typmancod = 'SENAT' -- SENAT = Mandat sénatorial
            ORDER BY elusen.eludatdeb DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_mandats");
        DB::statement("ALTER TABLE IF EXISTS senateurs_mandats_backup_old RENAME TO senateurs_mandats");
    }
};

