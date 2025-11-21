<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transforme senateurs_questions en vue SQL
     */
    public function up(): void
    {
        // Vérifier si les tables questions existent
        $tablesExist = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name IN ('senat_questions_tam_questions', 'senat_questions_tam_reponses')
        ");
        
        if ($tablesExist[0]->count < 2) {
            $this->warn('⚠️  Tables questions du Sénat non trouvées, vue non créée');
            return;
        }
        
        DB::statement("ALTER TABLE IF EXISTS senateurs_questions RENAME TO senateurs_questions_backup_old");
        
        DB::statement("
            CREATE OR REPLACE VIEW senateurs_questions AS
            SELECT 
                q.queid AS id,
                q.senid AS senateur_matricule,
                natq.natquelib AS type_question,
                q.quenum AS numero_question,
                q.queobj AS objet,
                q.quetxtque AS texte_question,
                r.reptxtrep AS texte_reponse,
                min.minlib AS ministere_destinataire,
                q.quedatjodep::date AS date_depot,
                q.quedatjotran::date AS date_transmission,
                r.repdatjorep::date AS date_reponse,
                q.quedatclo::date AS date_cloture,
                etatq.etatquelib AS etat,
                sortq.sortquelib AS sort,
                CASE 
                    WHEN r.repdatjorep IS NOT NULL AND q.quedatjodep IS NOT NULL
                    THEN EXTRACT(DAY FROM (r.repdatjorep - q.quedatjodep))
                    ELSE NULL
                END AS delai_reponse_jours,
                the.thelib AS theme,
                NOW() AS created_at,
                NOW() AS updated_at
                
            FROM senat_questions_tam_questions q
            LEFT JOIN senat_questions_tam_reponses r ON q.queid = r.queid
            LEFT JOIN senat_questions_naturequestion natq ON q.natqueid = natq.natqueid
            LEFT JOIN senat_questions_etatquestion etatq ON q.etatqueid = etatq.etatqueid
            LEFT JOIN senat_questions_sortquestion sortq ON q.sortqueid = sortq.sortqueid
            LEFT JOIN senat_questions_tam_ministeres min ON q.minid = min.minid
            LEFT JOIN senat_questions_the the ON q.theid = the.theid
            ORDER BY q.quedatjodep DESC NULLS LAST
        ");
        
        $this->info('✅ Vue senateurs_questions créée');
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS senateurs_questions");
        DB::statement("ALTER TABLE IF EXISTS senateurs_questions_backup_old RENAME TO senateurs_questions");
    }
};

