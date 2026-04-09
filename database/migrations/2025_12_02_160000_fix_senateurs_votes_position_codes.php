<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les codes de position de vote dans la vue senateurs_votes
     *
     * Les codes réels sont numériques (0, 2, 3, 4, 5) et non alphabétiques (P, C, A, NV)
     * Source : table senat_senateurs_posvot
     *
     * Correspondance :
     * - 0 = pour
     * - 2 = contre
     * - 3 = abstention
     * - 4 = non-votant
     * - 5 = n'a pas souhaité prendre part au vote (traité comme non_votant)
     *
     * Ajout également du code dossier législatif (dossier_code) via senat_dosleg_scr
     */
    public function up(): void
    {
        if (! Schema::hasTable('senat_senateurs_votes')) {
            return;
        }

        // 1. Supprimer les vues dépendantes
        DB::statement('DROP VIEW IF EXISTS votes_senat CASCADE');
        DB::statement('DROP VIEW IF EXISTS senateurs_votes CASCADE');

        // 2. Recréer senateurs_votes avec les bons codes et le dossier_code
        DB::statement("
            CREATE VIEW senateurs_votes AS
            SELECT 
                v.votesid AS id,
                TRIM(v.senmat) AS senateur_matricule,
                v.scrid AS scrutin_id,
                scr.scrdat::date AS date_vote,
                scr.scrint AS intitule,
                scr.scrintext AS intitule_complet,
                ds.code AS dossier_code,
                CASE 
                    WHEN v.posvotcod = '0' THEN 'pour'
                    WHEN v.posvotcod = '2' THEN 'contre'
                    WHEN v.posvotcod = '3' THEN 'abstention'
                    WHEN v.posvotcod = '4' THEN 'non_votant'
                    WHEN v.posvotcod = '5' THEN 'non_votant'
                    ELSE v.posvotcod
                END AS position,
                CASE 
                    WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
                    WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
                    ELSE 'Égalité'
                END AS resultat_scrutin,
                scr.scrpou AS pour,
                scr.scrcon AS contre,
                scr.scrvot AS votants,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_votes v
            LEFT JOIN senat_senateurs_scr scr ON v.scrid = scr.scrid
            LEFT JOIN senat_dosleg_scr ds ON scr.sesann = ds.sesann AND scr.scrnum = ds.scrnum
            WHERE v.senmat IS NOT NULL
            ORDER BY scr.scrdat DESC NULLS LAST
        ");

        // 3. Recréer l'alias
        DB::statement('
            CREATE VIEW votes_senat AS
            SELECT * FROM senateurs_votes
        ');
    }

    public function down(): void
    {
        // Revenir aux anciens codes (P, C, A, NV)
        DB::statement('DROP VIEW IF EXISTS votes_senat CASCADE');
        DB::statement('DROP VIEW IF EXISTS senateurs_votes CASCADE');

        DB::statement("
            CREATE VIEW senateurs_votes AS
            SELECT 
                v.votesid AS id,
                TRIM(v.senmat) AS senateur_matricule,
                v.scrid AS scrutin_id,
                scr.scrdat::date AS date_vote,
                scr.scrint AS intitule,
                scr.scrintext AS intitule_complet,
                CASE 
                    WHEN v.posvotcod = 'P' THEN 'pour'
                    WHEN v.posvotcod = 'C' THEN 'contre'
                    WHEN v.posvotcod = 'A' THEN 'abstention'
                    WHEN v.posvotcod = 'NV' THEN 'non_votant'
                    ELSE v.posvotcod
                END AS position,
                CASE 
                    WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
                    WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
                    ELSE 'Égalité'
                END AS resultat_scrutin,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_votes v
            LEFT JOIN senat_senateurs_scr scr ON v.scrid = scr.scrid
            WHERE v.senmat IS NOT NULL
            ORDER BY scr.scrdat DESC NULLS LAST
        ");

        DB::statement('
            CREATE VIEW votes_senat AS
            SELECT * FROM senateurs_votes
        ');
    }
};
