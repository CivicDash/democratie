<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recrée la vue amendements_senat qui a été supprimée
 * par la migration fix_senateurs_view_distinct
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vérifier quelles tables AMELI existent
        $hasSenatAmeliAmd = Schema::hasTable('senat_ameli_amd');
        $hasSenatAmeliAmdsen = Schema::hasTable('senat_ameli_amdsen');
        $hasSenatAmeliSor = Schema::hasTable('senat_ameli_sor');
        $hasSenAmeli = Schema::hasTable('sen_ameli');
        $hasSenatSenateursSen = Schema::hasTable('senat_senateurs_sen');

        // Log pour debug
        DB::statement("DO $$ BEGIN RAISE NOTICE 'Tables check: senat_ameli_amd=%, senat_ameli_amdsen=%, sen_ameli=%, senat_senateurs_sen=%', 
            '$hasSenatAmeliAmd', '$hasSenatAmeliAmdsen', '$hasSenAmeli', '$hasSenatSenateursSen'; END $$;");

        // Supprimer la vue si elle existe
        DB::statement('DROP VIEW IF EXISTS amendements_senat CASCADE');

        // Cas 1: On a sen_ameli (table de correspondance ID -> matricule)
        if ($hasSenatAmeliAmd && $hasSenatAmeliAmdsen && $hasSenAmeli) {
            DB::statement('
                CREATE VIEW amendements_senat AS
                SELECT 
                    amd.id AS id,
                    TRIM(sen_ameli.mat) AS senateur_matricule,
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
                LEFT JOIN sen_ameli ON amdsen.senid = sen_ameli.entid
                LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
                WHERE amdsen.senid IS NOT NULL AND TRIM(sen_ameli.mat) IS NOT NULL
                ORDER BY amd.datdep DESC NULLS LAST
            ');

            return;
        }

        // Cas 2: On a les tables AMELI mais pas sen_ameli, on fait une correspondance par nom
        if ($hasSenatAmeliAmd && $hasSenatAmeliAmdsen && $hasSenatSenateursSen) {
            DB::statement('
                CREATE VIEW amendements_senat AS
                SELECT 
                    amd.id AS id,
                    TRIM(sen.senmat) AS senateur_matricule,
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
                LEFT JOIN senat_senateurs_sen sen ON (
                    UPPER(TRIM(amdsen.nomuse)) = UPPER(TRIM(sen.sennomuse))
                    AND UPPER(TRIM(amdsen.prenomuse)) = UPPER(TRIM(sen.senprenomuse))
                )
                WHERE amdsen.senid IS NOT NULL
                ORDER BY amd.datdep DESC NULLS LAST
            ');

            return;
        }

        // Cas 3: Pas de tables AMELI, créer une vue vide pour éviter les erreurs
        DB::statement("
            CREATE VIEW amendements_senat AS
            SELECT 
                0::integer AS id,
                ''::text AS senateur_matricule,
                ''::text AS numero,
                ''::text AS type_amendement,
                ''::text AS dispositif,
                ''::text AS expose,
                NULL::date AS date_depot,
                ''::text AS sort_libelle,
                ''::text AS sort_code,
                ''::text AS auteur_nom,
                ''::text AS auteur_prenom,
                0::integer AS auteur_groupe_id,
                NOW() AS created_at,
                NOW() AS updated_at
            WHERE false
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS amendements_senat CASCADE');
    }
};
