<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vue pour les scrutins du Sénat
     * Map les scrutins pour avoir une structure cohérente avec ScrutinAN
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_scrutins_senat AS
            SELECT 
                -- ID unique
                scr.id::text AS uid,
                
                -- Numéro et identification
                scr.numero AS numero,
                scr.scrdat AS date_scrutin,
                
                -- Intitulé
                scr.scrint AS intitule,
                scr.scrobj AS objet,
                
                -- Type de scrutin
                typscr.code AS type_code,
                typscr.libelle AS type_libelle,
                
                -- Résultats agrégés
                scr.pour AS pour,
                scr.contre AS contre,
                scr.abstentions AS abstentions,
                scr.non_votants AS non_votants,
                scr.nombre_votants AS nombre_votants,
                scr.suffrages_exprimes AS suffrages_exprimes,
                
                -- Résultat global
                scr.resultat_code AS resultat_code,
                scr.resultat_libelle AS resultat_libelle,
                
                -- Session
                ses.libelle AS session,
                ses.annee AS annee_session,
                
                -- Texte législatif associé (si disponible)
                txt.titre AS texte_titre,
                txt.numero AS texte_numero,
                
                -- Timestamps
                scr.syscredat AS created_at,
                scr.sysmajdat AS updated_at
                
            FROM scr
            LEFT JOIN typscr ON scr.type_id = typscr.id
            LEFT JOIN ses ON scr.session_id = ses.id
            LEFT JOIN texte txt ON scr.texte_id = txt.id
            ORDER BY scr.scrdat DESC NULLS LAST
        ");
        
        $this->info('✅ Vue v_scrutins_senat créée');
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_scrutins_senat");
    }
};

