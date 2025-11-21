<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crée une vue pour les votes des sénateurs à partir des données SQL brutes
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_votes AS
            SELECT 
                v.votesid AS id,
                v.senmat AS senateur_matricule,
                v.scrid AS scrutin_id,
                scr.scrdat::date AS date_vote,
                scr.scrint AS intitule,
                scr.scrobj AS objet,
                CASE 
                    WHEN v.posvotcod = 'P' THEN 'pour'
                    WHEN v.posvotcod = 'C' THEN 'contre'
                    WHEN v.posvotcod = 'A' THEN 'abstention'
                    WHEN v.posvotcod = 'NV' THEN 'non_votant'
                    ELSE v.posvotcod
                END AS position,
                scr.reslis AS resultat_scrutin,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_votes v
            LEFT JOIN senat_senateurs_scr scr ON v.scrid = scr.scrid
            WHERE v.senmat IS NOT NULL
            ORDER BY scr.scrdat DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_votes");
    }
};

