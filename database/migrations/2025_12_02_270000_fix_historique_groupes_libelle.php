<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige la vue senateurs_historique_groupes pour afficher
 * le libellé complet du groupe au lieu du code
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('senat_senateurs_memgrpsen')) {
            return;
        }

        // Supprimer la vue existante
        DB::statement('DROP VIEW IF EXISTS senateurs_historique_groupes CASCADE');

        // Jointure avec senat_senateurs_grppol pour avoir le libellé
        // La colonne grppollibcou contient le libellé du groupe
        DB::statement("
            CREATE VIEW senateurs_historique_groupes AS
            SELECT DISTINCT ON (mg.senmat, mg.orgcod, mg.memgrpsendatent)
                mg.memgrpsenid AS id,
                TRIM(mg.senmat) AS senateur_matricule,
                COALESCE(grp.grppollibcou, mg.orgcod) AS groupe_nom,
                mg.orgcod AS groupe_code,
                mg.memgrpsendatent::date AS date_debut,
                mg.memgrpsendatsor::date AS date_fin,
                CASE 
                    WHEN mg.memgrpsendatsor IS NULL THEN true
                    ELSE false
                END AS actif,
                'Membre' AS type_appartenance,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_memgrpsen mg
            LEFT JOIN senat_senateurs_grppol grp ON mg.orgcod = grp.grppolcod
            ORDER BY mg.senmat, mg.orgcod, mg.memgrpsendatent, mg.memgrpsendatent DESC NULLS LAST
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS senateurs_historique_groupes CASCADE');
        DB::statement("
            CREATE VIEW senateurs_historique_groupes AS
            SELECT DISTINCT ON (mg.senmat, mg.orgcod, mg.memgrpsendatent)
                mg.memgrpsenid AS id,
                TRIM(mg.senmat) AS senateur_matricule,
                mg.orgcod AS groupe_nom,
                mg.orgcod AS groupe_code,
                mg.memgrpsendatent::date AS date_debut,
                mg.memgrpsendatsor::date AS date_fin,
                CASE 
                    WHEN mg.memgrpsendatsor IS NULL THEN true
                    ELSE false
                END AS actif,
                'Membre' AS type_appartenance,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_senateurs_memgrpsen mg
            ORDER BY mg.senmat, mg.orgcod, mg.memgrpsendatent DESC NULLS LAST
        ");
    }
};
