<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * CORRECTION FINALE : Vue amendements_senat avec jointure via sen_ameli
     *
     * Problème identifié :
     * - senat_ameli_amdsen.senid = ID numérique (ex: 7577)
     * - senat_senateurs_sen.senmat = Matricule (ex: "20110Q")
     * - La table sen_ameli fait le lien : entid (ID numérique) → mat (matricule)
     *
     * Solution :
     * - Joindre via sen_ameli : amdsen.senid → sen_ameli.entid → sen_ameli.mat
     * - Fallback sur nom/prénom si sen_ameli n'existe pas
     */
    public function up(): void
    {
        // Vérifier si les tables existent
        $tablesExist = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name IN ('senat_ameli_amd', 'senat_ameli_amdsen')
        ");

        if ($tablesExist[0]->count < 2) {
            return; // Tables non importées, skip
        }

        // Vérifier si sen_ameli existe
        $senAmeliExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'sen_ameli'
        ");

        if ($senAmeliExists[0]->count > 0) {
            // Utiliser sen_ameli pour la jointure (méthode préférée)
            DB::statement('
                CREATE OR REPLACE VIEW amendements_senat AS
                SELECT 
                    amd.id AS id,
                    TRIM(sen_ameli.mat) AS senateur_matricule,  -- ✅ Via sen_ameli.mat (matricule) avec TRIM
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
                LEFT JOIN sen_ameli ON amdsen.senid = sen_ameli.entid  -- ✅ Jointure via sen_ameli
                LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
                WHERE amdsen.senid IS NOT NULL
                ORDER BY amd.datdep DESC NULLS LAST
            ');
        } else {
            // Fallback : jointure par nom/prénom
            DB::statement('
                CREATE OR REPLACE VIEW amendements_senat AS
                SELECT 
                    amd.id AS id,
                    TRIM(sen.senmat) AS senateur_matricule,  -- Via jointure nom/prénom
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
                LEFT JOIN senat_senateurs_sen sen 
                    ON UPPER(TRIM(amdsen.nomuse)) = UPPER(TRIM(sen.sennomuse))
                    AND UPPER(TRIM(amdsen.prenomuse)) = UPPER(TRIM(sen.senprenomuse))
                LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
                WHERE amdsen.senid IS NOT NULL
                ORDER BY amd.datdep DESC NULLS LAST
            ');
        }
    }

    public function down(): void
    {
        // Revenir à l'ancienne version (avec ID numérique)
        DB::statement('
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
        ');
    }
};
