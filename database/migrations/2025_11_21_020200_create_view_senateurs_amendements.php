<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vue pour les amendements des sénateurs
     * Map la base AMELI vers une structure compatible
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_senateurs_amendements AS
            SELECT 
                -- ID unique
                amd.id AS uid,
                
                -- Lien vers le sénateur auteur
                amdsen.senateur_id::text AS senateur_matricule,
                
                -- Identification amendement
                amd.numero AS numero,
                amd.numero_long AS numero_long,
                
                -- Texte législatif associé
                txt.id AS texte_ref,
                txt.titre AS texte_titre,
                
                -- Subdivision
                sub.article AS article,
                sub.alinea AS alinea,
                
                -- Contenu
                amd.dispositif AS dispositif,
                amd.objet AS expose,
                
                -- Auteur
                CASE 
                    WHEN amd.auteur_type = 'S' THEN 'SENATEUR'
                    WHEN amd.auteur_type = 'G' THEN 'GOUVERNEMENT'
                    WHEN amd.auteur_type = 'C' THEN 'COMMISSION'
                    ELSE amd.auteur_type
                END AS auteur_type,
                
                amd.auteur_nom AS auteur_nom,
                
                -- Groupe du sénateur
                amdsen.groupe_id AS auteur_groupe_id,
                
                -- Sort de l'amendement
                sor.code AS sort_code,
                sor.libelle AS sort_libelle,
                
                -- Avis
                avicom.avis AS avis_commission,
                avigvt.avis AS avis_gouvernement,
                
                -- Dates
                amd.date_depot AS date_depot,
                amd.date_sort AS date_sort,
                
                -- Séance
                sea.date AS date_seance,
                
                -- Timestamps
                amd.created_at AS created_at
                
            FROM senat_ameli_amd amd
            LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amendement_id
            LEFT JOIN senat_ameli_txt_ameli txt ON amd.texte_id = txt.id
            LEFT JOIN senat_ameli_sub sub ON amd.subdivision_id = sub.id
            LEFT JOIN senat_ameli_sor sor ON amd.sort_id = sor.id
            LEFT JOIN senat_ameli_avicom avicom ON amd.id = avicom.amendement_id
            LEFT JOIN senat_ameli_avigvt avigvt ON amd.id = avigvt.amendement_id
            LEFT JOIN senat_ameli_sea sea ON amd.seance_id = sea.id
            WHERE amdsen.senateur_id IS NOT NULL
            ORDER BY amd.date_depot DESC NULLS LAST
        ");
        
        $this->info('✅ Vue v_senateurs_amendements créée');
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_senateurs_amendements");
    }
};

