<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crée une vue pour les dossiers législatifs du Sénat à partir des données DOSLEG
     */
    public function up(): void
    {
        // Vérifier si les tables DOSLEG existent
        $tablesExist = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'senat_dosleg_doc'
        ");
        
        if ($tablesExist[0]->count < 1) {
            return; // Tables non importées, skip
        }
        
        DB::statement("
            CREATE OR REPLACE VIEW dossiers_legislatifs_senat AS
            SELECT 
                doc.docidt AS id,
                doc.docnum AS numero,
                doc.sesann AS session_annee,
                doc.typdoccod AS type_document_code,
                doc.docint AS intitule,
                doc.doctitcou AS titre_court,
                doc.docurl AS url_senat,
                doc.date_depot::date AS date_depot,
                doc.docdat::date AS date_document,
                doc.docdatsea::date AS date_seance,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_dosleg_doc doc
            ORDER BY doc.date_depot DESC NULLS LAST, doc.docnum DESC
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS dossiers_legislatifs_senat");
    }
};

