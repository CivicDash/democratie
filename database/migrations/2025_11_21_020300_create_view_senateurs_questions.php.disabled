<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vue pour les questions au gouvernement des sénateurs
     * Map la base Questions vers une structure compatible
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_senateurs_questions AS
            SELECT 
                -- ID unique
                q.id AS uid,
                
                -- Lien vers le sénateur
                q.senateur_id::text AS senateur_matricule,
                
                -- Type de question
                natq.code AS type_question_code,
                natq.libelle AS type_question,
                
                -- Législature
                legq.numero AS legislature,
                
                -- Numéro de la question
                q.numero AS numero_question,
                
                -- Objet
                q.objet AS objet,
                
                -- Texte de la question
                q.txtque AS texte_question,
                
                -- Réponse
                r.txtrep AS texte_reponse,
                
                -- Ministère destinataire
                m.libelle AS ministere_destinataire,
                
                -- Dates
                q.datejodepot AS date_depot,
                q.datejotran AS date_transmission,
                q.datesignal AS date_signalement,
                r.datejorep AS date_reponse,
                q.datecloture AS date_cloture,
                
                -- État et sort
                etatq.code AS etat_code,
                etatq.libelle AS etat,
                sortq.code AS sort_code,
                sortq.libelle AS sort,
                
                -- Délai de réponse (en jours)
                CASE 
                    WHEN r.datejorep IS NOT NULL AND q.datejodepot IS NOT NULL
                    THEN EXTRACT(DAY FROM (r.datejorep - q.datejodepot))
                    ELSE NULL
                END AS delai_reponse_jours,
                
                -- Thème
                the.libelle AS theme,
                
                -- URL Sénat (si disponible)
                CONCAT('https://www.senat.fr/questions/', q.numero) AS url_senat,
                
                -- Timestamps
                q.datesynctam AS created_at
                
            FROM senat_questions_tam_questions q
            LEFT JOIN senat_questions_tam_reponses r ON q.id = r.question_id
            LEFT JOIN senat_questions_naturequestion natq ON q.nature_id = natq.id
            LEFT JOIN senat_questions_etatquestion etatq ON q.etat_id = etatq.id
            LEFT JOIN senat_questions_sortquestion sortq ON q.sort_id = sortq.id
            LEFT JOIN senat_questions_legquestion legq ON q.legislature_id = legq.id
            LEFT JOIN senat_questions_tam_ministeres m ON q.ministere_id = m.id
            LEFT JOIN senat_questions_the the ON q.theme_id = the.id
            WHERE q.senateur_id IS NOT NULL
            ORDER BY q.datejodepot DESC NULLS LAST
        ");
        
        $this->info('✅ Vue v_senateurs_questions créée');
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_senateurs_questions");
    }
};

