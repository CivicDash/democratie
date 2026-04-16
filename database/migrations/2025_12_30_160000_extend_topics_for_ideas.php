<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Étendre la table topics pour le système unifié d'idées citoyennes :
 * - Types de contributions (idée, question, débat, pétition, interpellation)
 * - Statistiques de votes
 * - Liaisons avec élus
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // EXTENSION TABLE TOPICS
        // =====================================================================
        Schema::table('topics', function (Blueprint $table) {
            // Type de contribution
            $table->string('idea_type', 30)->default('debate')->after('type');
            // Types: proposal, question, debate, petition, interpellation

            // Statistiques de votes (pré-calculées)
            $table->integer('votes_pour')->default(0)->after('ballot_options');
            $table->integer('votes_contre')->default(0)->after('votes_pour');
            $table->integer('score')->default(0)->after('votes_contre'); // Wilson score pour trending
            $table->integer('views_count')->default(0)->after('score');

            // Slug pour URLs propres
            $table->string('slug', 300)->nullable()->after('title');

            // Date de publication
            $table->timestamp('published_at')->nullable()->after('status');

            // Raison de rejet si modéré
            $table->text('rejection_reason')->nullable()->after('published_at');

            // Index pour performances
            $table->index('idea_type');
            $table->index('score');
            $table->index('published_at');
            $table->index(['idea_type', 'status']);
            $table->index(['scope', 'idea_type', 'status']);
        });

        // =====================================================================
        // TABLE LIAISONS TOPICS <-> ÉLUS
        // =====================================================================
        Schema::create('topic_elus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');

            // Type d'élu
            $table->string('elu_type', 20); // depute, senateur, maire
            $table->string('elu_id', 50);   // uid AN, id sénateur, id maire

            // Est-ce une interpellation directe ?
            $table->boolean('is_interpellation')->default(false);

            // Statut de la réponse de l'élu
            $table->string('response_status', 20)->default('pending');
            // pending, viewed, answered, declined

            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->text('response_content')->nullable();

            $table->timestamps();

            // Index
            $table->index(['elu_type', 'elu_id']);
            $table->index('is_interpellation');
            $table->index('response_status');
            $table->unique(['topic_id', 'elu_type', 'elu_id']);
        });

        // =====================================================================
        // TABLE VOTES SUR TOPICS (si pas déjà existante)
        // =====================================================================
        if (! Schema::hasTable('topic_votes')) {
            Schema::create('topic_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('topic_id')->constrained()->onDelete('cascade');
                $table->smallInteger('vote'); // -1 = contre, 1 = pour
                $table->timestamps();

                $table->unique(['user_id', 'topic_id']);
                $table->index(['topic_id', 'vote']);
            });
        }

        // =====================================================================
        // TABLE TAGS MULTIPLES POUR TOPICS
        // =====================================================================
        if (! Schema::hasTable('topic_tags')) {
            Schema::create('topic_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('topic_id')->constrained()->onDelete('cascade');
                $table->foreignId('tag_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                $table->unique(['topic_id', 'tag_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_tags');
        Schema::dropIfExists('topic_votes');
        Schema::dropIfExists('topic_elus');

        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['scope', 'idea_type', 'status']);
            $table->dropIndex(['idea_type', 'status']);
            $table->dropColumn([
                'idea_type',
                'votes_pour',
                'votes_contre',
                'score',
                'views_count',
                'slug',
                'published_at',
                'rejection_reason',
            ]);
        });
    }
};
