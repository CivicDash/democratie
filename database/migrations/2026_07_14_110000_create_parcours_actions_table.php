<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * « Actions au pouvoir » (itération 2 §1) : faits objectifs rattachés à une fonction
 * du parcours (loi portée, vote clé, usage du 49.3, rapport parlementaire).
 * Règles de neutralité bloquantes : critères de sélection PUBLICS et MÉCANIQUES
 * (documentés sur /methodologie), explication OBLIGATOIRE avant publication,
 * présentation descriptive sans qualificatif, même profondeur pour tous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcours_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parcours_evenement_id')
                ->constrained('parcours_evenements')->cascadeOnDelete();

            $table->string('type', 30);                    // loi_portee | vote_cle | rapport_parlementaire | usage_493
            $table->string('reference_type', 60)->nullable(); // table source CivicDash (ex. senat_dosleg_loi)
            $table->string('reference_id', 100)->nullable();  // clé dans la table source (ex. loicod)

            $table->string('titre_court', 300);
            $table->text('explication')->nullable();       // OBLIGATOIRE avant publication (contrôlé à l'export)
            $table->date('date_action')->nullable();
            $table->string('source_url', 500)->nullable();

            $table->string('source_detection', 30)->default('mecanique'); // mecanique | manuel
            $table->string('critere', 200)->nullable();    // critère mécanique appliqué (documenté /methodologie)

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();
            $table->integer('ordre')->default(0);

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['parcours_evenement_id', 'type']);
            $table->index('statut_validation');
            $table->unique(['parcours_evenement_id', 'type', 'reference_id'], 'parcours_actions_dedup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcours_actions');
    }
};
