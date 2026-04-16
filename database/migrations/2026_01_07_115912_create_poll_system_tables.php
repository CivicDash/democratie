<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Système de sondage amélioré pour les contributions citoyennes
 *
 * Permet de créer des sondages avec plusieurs options de réponse,
 * de suivre les votes individuels et de calculer des statistiques.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ========================================================================
        // TABLE DES OPTIONS DE SONDAGE
        // ========================================================================
        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('label', 255);
            $table->text('description')->nullable(); // Description optionnelle
            $table->string('color', 20)->nullable(); // Couleur pour les graphiques
            $table->string('icon', 10)->nullable(); // Emoji optionnel
            $table->integer('position')->default(0); // Ordre d'affichage
            $table->unsignedInteger('votes_count')->default(0); // Cache du nombre de votes
            $table->timestamps();

            $table->index(['topic_id', 'position']);
        });

        // ========================================================================
        // TABLE DES VOTES DE SONDAGE
        // ========================================================================
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->ipAddress('ip_address')->nullable(); // Pour anti-fraude
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            // Un utilisateur ne peut voter qu'une fois par option
            // (mais peut voter pour plusieurs options si le sondage le permet)
            $table->unique(['poll_option_id', 'user_id']);

            $table->index('user_id');
        });

        // ========================================================================
        // EXTENSION DE LA TABLE TOPICS POUR LES SONDAGES
        // ========================================================================
        Schema::table('topics', function (Blueprint $table) {
            // Type de sondage
            if (! Schema::hasColumn('topics', 'poll_type')) {
                $table->string('poll_type', 30)->nullable()->after('ballot_options');
                // single = une seule réponse, multiple = plusieurs réponses possibles
            }

            // Nombre max de choix (pour type multiple)
            if (! Schema::hasColumn('topics', 'poll_max_choices')) {
                $table->tinyInteger('poll_max_choices')->nullable()->after('poll_type');
            }

            // Afficher les résultats avant de voter ?
            if (! Schema::hasColumn('topics', 'poll_show_results_before_vote')) {
                $table->boolean('poll_show_results_before_vote')->default(false)->after('poll_max_choices');
            }

            // Permettre de changer son vote ?
            if (! Schema::hasColumn('topics', 'poll_allow_change_vote')) {
                $table->boolean('poll_allow_change_vote')->default(true)->after('poll_show_results_before_vote');
            }

            // Date de fin du sondage (optionnel)
            if (! Schema::hasColumn('topics', 'poll_ends_at')) {
                $table->timestamp('poll_ends_at')->nullable()->after('poll_allow_change_vote');
            }

            // Mode débat activé (Pour/Contre sur les commentaires)
            if (! Schema::hasColumn('topics', 'debate_mode')) {
                $table->boolean('debate_mode')->default(false)->after('poll_ends_at');
            }
        });

        // ========================================================================
        // EXTENSION DE LA TABLE POSTS POUR LE MODE DÉBAT
        // ========================================================================
        Schema::table('posts', function (Blueprint $table) {
            // Position dans le débat (pour, contre, neutre)
            if (! Schema::hasColumn('posts', 'debate_position')) {
                $table->string('debate_position', 20)->nullable()->after('content');
                // 'for', 'against', 'neutral'
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'debate_position')) {
                $table->dropColumn('debate_position');
            }
        });

        Schema::table('topics', function (Blueprint $table) {
            $columns = [
                'poll_type',
                'poll_max_choices',
                'poll_show_results_before_vote',
                'poll_allow_change_vote',
                'poll_ends_at',
                'debate_mode',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('topics', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');
    }
};
