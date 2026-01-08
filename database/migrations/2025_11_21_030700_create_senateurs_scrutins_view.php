<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crée une vue pour les scrutins du Sénat à partir des données SQL brutes
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_scrutins AS
            SELECT 
                scr.scrid AS id,
                scr.sesann AS session_annee,
                scr.scrnum AS numero,
                scr.typscrcod AS type_scrutin_code,
                typscr.typscrlib AS type_scrutin,
                scr.scrdat::date AS date_scrutin,
                scr.scrint AS intitule,
                scr.scrintext AS intitule_complet,
                scr.scrpou AS pour,
                scr.scrcon AS contre,
                scr.scrvot AS votants,
                scr.scrsuf AS suffrages_exprimes,
                scr.scrmaj AS majorite_requise,
                CASE 
                    WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
                    WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
                    WHEN scr.scrpou = scr.scrcon THEN 'Égalité'
                    ELSE 'Non déterminé'
                END AS resultat,
                scr.syscredat AS created_at,
                scr.sysmajdat AS updated_at
                
            FROM senat_senateurs_scr scr
            LEFT JOIN senat_senateurs_typscr typscr ON scr.typscrcod = typscr.typscrcod
            ORDER BY scr.scrdat DESC NULLS LAST, scr.scrnum DESC
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_scrutins");
    }
};

