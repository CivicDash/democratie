<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vue pour les votes des sénateurs
     * Map les scrutins et votes individuels du Sénat
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_senateurs_votes AS
            SELECT 
                -- ID unique pour la vue
                v.id AS id,
                
                -- Lien vers le sénateur
                v.senateur_id::text AS senateur_matricule,
                
                -- Lien vers le scrutin
                v.scrutin_id AS scrutin_id,
                
                -- Date et détails du scrutin
                scr.scrdat AS date_vote,
                scr.scrint AS intitule,
                scr.scrobj AS objet,
                
                -- Position du sénateur
                CASE 
                    WHEN v.posvot = 'P' THEN 'pour'
                    WHEN v.posvot = 'C' THEN 'contre'
                    WHEN v.posvot = 'A' THEN 'abstention'
                    WHEN v.posvot = 'NV' THEN 'non_votant'
                    ELSE v.posvot
                END AS position,
                
                -- Résultat du scrutin
                scr.resultat AS resultat_scrutin,
                
                -- Groupe du sénateur au moment du vote
                (
                    SELECT libgrp.libelle
                    FROM senat_senateurs_memgrpsen msg
                    JOIN senat_senateurs_grpsenami grp ON msg.groupe_id = grp.id
                    LEFT JOIN senat_senateurs_libgrpsen libgrp ON grp.id = libgrp.groupe_id
                    WHERE msg.senateur_id = v.senateur_id
                    AND msg.memgrpsendatent <= scr.scrdat
                    AND (msg.memgrpsendatsor IS NULL OR msg.memgrpsendatsor >= scr.scrdat)
                    ORDER BY msg.memgrpsendatent DESC
                    LIMIT 1
                ) AS groupe_politique,
                
                -- Type de scrutin
                scr.typscr AS type_scrutin,
                
                -- Timestamps
                scr.syscredat AS created_at
                
            FROM senat_senateurs_votes v
            JOIN senat_senateurs_scr scr ON v.scrutin_id = scr.id
            WHERE v.senateur_id IS NOT NULL
            ORDER BY scr.scrdat DESC, v.senateur_id
        ");
        
        $this->info('✅ Vue v_senateurs_votes créée');
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_senateurs_votes");
    }
};

