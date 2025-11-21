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
                elusen.elusenid AS id,
                elusen.senid AS senateur_matricule,
                elusen.elusendatent::date AS date_debut,
                elusen.eludatfin::date AS date_fin,
                CASE 
                    WHEN elusen.eludatfin IS NULL THEN true
                    ELSE false
                END AS actif,
                dpt.dptcod AS departement_code,
                dpt.dptlib AS departement_nom,
                elusen.cirnum AS circonscription_numero,
                typman.typmanlib AS type_mandat,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_elusen elusen
            LEFT JOIN senat_senateurs_dpt dpt ON elusen.dptid = dpt.dptid
            LEFT JOIN senat_senateurs_typman typman ON elusen.typmanid = typman.typmanid
            WHERE elusen.typmanid = 2 -- Type 2 = Mandat sénatorial
            ORDER BY elusen.elusendatent DESC
        ");
        
        $this->info('✅ Vue senateurs_mandats créée');
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_mandats");
        DB::statement("ALTER TABLE IF EXISTS senateurs_mandats_backup_old RENAME TO senateurs_mandats");
    }
};

