<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * CORRECTION FINALE : Vue amendements_senat avec jointure via sen_ameli
     * 
     * Problème identifié :
     * - senat_ameli_amdsen.senid = ID numérique (ex: 7496)
     * - senat_senateurs_sen.senmat = Matricule string (ex: "19954N")
     * - Pas de correspondance directe
     * 
     * Solution :
     * - Joindre via sen_ameli : amdsen.senid → sen_ameli.entid → sen_ameli.mat
     */
    public function up(): void
    {
        // Vérifier si les tables existent
        $tablesExist = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name IN ('senat_ameli_amd', 'senat_ameli_amdsen', 'sen_ameli')
        ");
        
        if ($tablesExist[0]->count < 3) {
            return; // Tables non importées, skip
        }
        
        DB::statement("
            CREATE OR REPLACE VIEW amendements_senat AS
            SELECT 
                amd.id AS id,
                sen.mat AS senateur_matricule,  -- ✅ Via sen_ameli.mat (matricule)
                amd.num AS numero,
                amd.typ AS type_amendement,
                amd.dis AS dispositif,
                amd.obj AS expose,
                amd.datdep::date AS date_depot,
                sor.lib AS sort_libelle,
                sor.cod AS sort_code,
                amdsen.nomuse AS auteur_nom,
                amdsen.prenomuse AS auteur_prenom,
                amdsen.grpid AS auteur_groupe_id,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_ameli_amd amd
            LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
            LEFT JOIN sen_ameli sen ON amdsen.senid = sen.entid  -- ✅ Jointure correcte via sen_ameli
            LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
            WHERE amdsen.senid IS NOT NULL AND sen.mat IS NOT NULL
            ORDER BY amd.datdep DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        // Revenir à l'ancienne version (incorrecte)
        DB::statement("
            CREATE OR REPLACE VIEW amendements_senat AS
            SELECT 
                amd.id AS id,
                amdsen.senid::text AS senateur_matricule,
                amd.num AS numero,
                amd.typ AS type_amendement,
                amd.dis AS dispositif,
                amd.obj AS expose,
                amd.datdep::date AS date_depot,
                sor.lib AS sort_libelle,
                sor.cod AS sort_code,
                amdsen.nomuse AS auteur_nom,
                amdsen.prenomuse AS auteur_prenom,
                amdsen.grpid AS auteur_groupe_id,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_ameli_amd amd
            LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
            LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
            WHERE amdsen.senid IS NOT NULL
            ORDER BY amd.datdep DESC NULLS LAST
        ");
    }
};

