<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crée une vue pour les amendements du Sénat à partir des données AMELI
     */
    public function up(): void
    {
        // Vérifier si les tables AMELI existent
        $tablesExist = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name IN ('senat_ameli_amd', 'senat_ameli_amdsen')
        ");
        
        if ($tablesExist[0]->count < 2) {
            return; // Tables non importées, skip
        }
        
        DB::statement("
            CREATE OR REPLACE VIEW amendements_senat AS
            SELECT 
                amd.id AS id,
                amdsen.senid AS senateur_matricule,
                amd.num AS numero,
                amd.typ AS type_amendement,
                amd.dis AS dispositif,
                amd.obj AS expose,
                amd.datdep::date AS date_depot,
                sor.sorlib AS sort_libelle,
                amd.sorid AS sort_code,
                avi_com.avilib AS avis_commission,
                avi_gvt.avilib AS avis_gouvernement,
                txt.txtnom AS texte_nom,
                sub.subart AS article,
                sub.subalin AS alinea,
                amdsen.nomuse AS auteur_nom,
                amdsen.prenomuse AS auteur_prenom,
                amdsen.grpid AS auteur_groupe_id,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_ameli_amd amd
            LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
            LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.sorid
            LEFT JOIN senat_ameli_avicom avi_com ON amd.avcid = avi_com.avicod
            LEFT JOIN senat_ameli_avigvt avi_gvt ON amd.avgid = avi_gvt.avgcod
            LEFT JOIN senat_ameli_txt txt ON amd.txtid = txt.txtid
            LEFT JOIN senat_ameli_sub sub ON amd.subid = sub.subid
            WHERE amdsen.senid IS NOT NULL
            ORDER BY amd.datdep DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS amendements_senat");
    }
};

