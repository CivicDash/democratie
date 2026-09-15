<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questions du quiz thématique.
 *
 * Le quiz existant dérivait ses questions des mesures marquées « mise en avant » : une
 * question valait pour UN candidat, et l'accord lui était crédité. Trois biais que ce
 * schéma corrige :
 *   - le nombre de questions par candidat suivait notre volume de dépouillement ;
 *   - une position défendue par plusieurs candidats n'en créditait qu'un ;
 *   - l'énoncé était le verbatim d'un camp, donc sa formulation persuasive.
 *
 * Ici une question porte un intitulé neutre, et les OPTIONS sont les positions réellement
 * défendues, chacune rattachée à une ou plusieurs mesures publiées — ce sont les mesures
 * qui portent les candidats. Une position commune à trois candidats les crédite tous.
 *
 * Deux formats, car tous les sujets ne s'y prêtent pas de la même façon :
 *   - `arbitrage` : plusieurs candidats s'opposent sur un même choix. Au moins deux options.
 *   - `accord`    : une mesure isolée, sur laquelle on se dit d'accord ou non. Une option.
 *
 * Aucune pondération entre les deux n'est nécessaire : l'écran de résultat ne produit pas
 * de score, il rend les positions retenues et qui les porte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('election', 10)->default('2027');
            $table->foreignId('theme_id')->constrained('programme_themes')->cascadeOnDelete();
            // Une controverse porte déjà un intitulé neutre passé en modération : quand la
            // question en dérive, on garde le lien plutôt que de recopier.
            $table->foreignId('controverse_id')->nullable()->constrained('controverses')->nullOnDelete();
            $table->string('format', 20)->default('arbitrage');
            $table->string('intitule', 500);
            $table->text('precision_contexte')->nullable();
            $table->integer('ordre')->default(0);

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['election', 'affiche_publiquement']);
            $table->index('statut_validation');
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->string('libelle', 300);
            $table->integer('ordre')->default(0);
            $table->timestamps();

            $table->index('question_id');
        });

        // Une option peut recouvrir plusieurs mesures : deux candidats formulent la même
        // position avec des mots différents. C'est précisément le cas que l'ancien modèle
        // ne savait pas représenter.
        Schema::create('quiz_option_mesure', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('quiz_options')->cascadeOnDelete();
            $table->foreignId('mesure_id')->constrained('programme_mesures')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['option_id', 'mesure_id']);
            $table->index('mesure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_option_mesure');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
    }
};
