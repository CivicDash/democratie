<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transforme senateurs_historique_groupes en vue SQL
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE IF EXISTS senateurs_historique_groupes RENAME TO senateurs_historique_groupes_backup_old");
        
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_historique_groupes AS
            SELECT 
                mg.memgrpsenid AS id,
                mg.senmat AS senateur_matricule,
                org.evelib AS groupe_nom,
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
            LEFT JOIN senat_senateurs_org org ON mg.orgcod = org.orgcod
            WHERE org.typorgcod = 'GP' -- GP = Groupe politique
            ORDER BY mg.memgrpsendatent DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_historique_groupes");
        DB::statement("ALTER TABLE IF EXISTS senateurs_historique_groupes_backup_old RENAME TO senateurs_historique_groupes");
    }
};

