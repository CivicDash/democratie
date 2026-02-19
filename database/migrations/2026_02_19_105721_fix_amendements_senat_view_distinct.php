<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix amendements_senat view: add DISTINCT ON to prevent row multiplication
 * when multiple senators share the same (nomuse, prenomuse) in Cas 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        $hasSenatAmeliAmd = Schema::hasTable('senat_ameli_amd');
        $hasSenatAmeliAmdsen = Schema::hasTable('senat_ameli_amdsen');
        $hasSenAmeli = Schema::hasTable('sen_ameli');
        $hasSenatSenateursSen = Schema::hasTable('senat_senateurs_sen');

        DB::statement("DROP VIEW IF EXISTS amendements_senat CASCADE");

        if ($hasSenatAmeliAmd && $hasSenatAmeliAmdsen && $hasSenAmeli) {
            DB::statement("
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
            ");
            return;
        }

        if ($hasSenatAmeliAmd && $hasSenatAmeliAmdsen && $hasSenatSenateursSen) {
            DB::statement("
                CREATE VIEW amendements_senat AS
                SELECT DISTINCT ON (amd.id, TRIM(sen.senmat))
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
                ORDER BY amd.id, TRIM(sen.senmat), amd.datdep DESC NULLS LAST
            ");
            return;
        }

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
        DB::statement("DROP VIEW IF EXISTS amendements_senat CASCADE");
    }
};
