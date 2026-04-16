<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables pour la participation citoyenne :
 * - Votes sur les lois
 * - Idées/Propositions citoyennes
 * - Commentaires et débats
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // VOTES CITOYENS SUR LES LOIS
        // =====================================================================
        Schema::create('citizen_law_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('loi_cod', 50);           // Référence senat_dosleg_loi
            $table->integer('legislature')->nullable();
            $table->smallInteger('vote');             // -1 = contre, 1 = pour
            $table->timestamps();

            $table->unique(['user_id', 'loi_cod', 'legislature'], 'citizen_law_votes_unique');
            $table->index('loi_cod');
            $table->index(['loi_cod', 'vote']);
        });

        // Stats pré-calculées pour les votes citoyens sur les lois
        Schema::create('citizen_law_stats', function (Blueprint $table) {
            $table->id();
            $table->string('loi_cod', 50)->unique();
            $table->integer('legislature')->nullable();
            $table->integer('votes_pour')->default(0);
            $table->integer('votes_contre')->default(0);
            $table->integer('total_votes')->default(0);
            $table->float('pct_pour')->default(0);
            $table->float('pct_contre')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        // =====================================================================
        // IDÉES CITOYENNES
        // =====================================================================
        Schema::create('citizen_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Contenu
            $table->string('title', 255);
            $table->text('description');
            $table->string('slug', 300)->unique();

            // Niveau géographique
            $table->string('scope', 20)->default('national'); // national, regional, departemental, communal
            $table->string('region_code', 10)->nullable();
            $table->string('departement_code', 5)->nullable();
            $table->string('commune_code', 10)->nullable();

            // Liaison optionnelle avec une loi
            $table->string('loi_cod', 50)->nullable();
            $table->integer('legislature')->nullable();

            // Thématique
            $table->foreignId('tag_id')->nullable()->constrained('tags')->nullOnDelete();

            // Statistiques pré-calculées (updated via triggers ou commande)
            $table->integer('votes_pour')->default(0);
            $table->integer('votes_contre')->default(0);
            $table->integer('score')->default(0); // pour - contre
            $table->integer('comments_count')->default(0);
            $table->integer('views_count')->default(0);

            // Modération
            $table->string('status', 20)->default('pending'); // draft, pending, published, rejected, archived
            $table->text('rejection_reason')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('status');
            $table->index('scope');
            $table->index('tag_id');
            $table->index(['status', 'published_at']);
            $table->index(['scope', 'status']);
            $table->index('score');
        });

        // =====================================================================
        // VOTES SUR LES IDÉES
        // =====================================================================
        Schema::create('citizen_idea_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('idea_id')->constrained('citizen_ideas')->onDelete('cascade');
            $table->smallInteger('vote'); // -1 = contre, 1 = pour
            $table->timestamps();

            $table->unique(['user_id', 'idea_id']);
            $table->index(['idea_id', 'vote']);
        });

        // =====================================================================
        // COMMENTAIRES SUR LES IDÉES
        // =====================================================================
        Schema::create('citizen_idea_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('citizen_ideas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('citizen_idea_comments')->onDelete('cascade');

            $table->text('content');

            // Votes sur les commentaires
            $table->integer('votes_pour')->default(0);
            $table->integer('votes_contre')->default(0);
            $table->integer('score')->default(0);

            // Modération
            $table->boolean('is_hidden')->default(false);
            $table->string('hidden_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['idea_id', 'created_at']);
            $table->index('parent_id');
        });

        // =====================================================================
        // VOTES SUR LES COMMENTAIRES
        // =====================================================================
        Schema::create('citizen_comment_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('comment_id')->constrained('citizen_idea_comments')->onDelete('cascade');
            $table->smallInteger('vote'); // -1 = contre, 1 = pour
            $table->timestamps();

            $table->unique(['user_id', 'comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_comment_votes');
        Schema::dropIfExists('citizen_idea_comments');
        Schema::dropIfExists('citizen_idea_votes');
        Schema::dropIfExists('citizen_ideas');
        Schema::dropIfExists('citizen_law_stats');
        Schema::dropIfExists('citizen_law_votes');
    }
};
