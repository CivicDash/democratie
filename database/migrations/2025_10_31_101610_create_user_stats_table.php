<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Niveau & Expérience
            $table->integer('level')->default(1)->comment('Niveau actuel (1-100)');
            $table->integer('xp')->default(0)->comment('Points d\'expérience');
            $table->integer('xp_to_next_level')->default(100)->comment('XP nécessaire pour level up');
            
            // Streak
            $table->integer('current_streak')->default(0)->comment('Jours consécutifs actuel');
            $table->integer('longest_streak')->default(0)->comment('Record de jours consécutifs');
            $table->date('last_activity_date')->nullable()->comment('Dernière activité');
            
            // Statistiques d\'actions
            $table->integer('total_votes')->default(0)->comment('Nombre total de votes');
            $table->integer('total_topics_created')->default(0)->comment('Sujets créés');
            $table->integer('total_posts_created')->default(0)->comment('Posts créés');
            $table->integer('total_comments')->default(0)->comment('Commentaires');
            $table->integer('total_proposals_followed')->default(0)->comment('Propositions suivies');
            $table->integer('total_legislative_votes')->default(0)->comment('Votes sur propositions de loi');
            $table->integer('total_budget_allocations')->default(0)->comment('Allocations budget');
            
            // Engagement social
            $table->integer('upvotes_received')->default(0)->comment('Upvotes reçus');
            $table->integer('downvotes_received')->default(0)->comment('Downvotes reçus');
            $table->integer('reputation_score')->default(0)->comment('Score de réputation');
            
            // Achievements
            $table->integer('total_achievements')->default(0)->comment('Nombre de badges débloqués');
            $table->integer('rare_achievements')->default(0)->comment('Badges rares');
            $table->integer('epic_achievements')->default(0)->comment('Badges épiques');
            $table->integer('legendary_achievements')->default(0)->comment('Badges légendaires');
            
            // Classement
            $table->integer('global_rank')->nullable()->comment('Rang global');
            $table->integer('category_rank')->nullable()->comment('Rang par catégorie');
            
            $table->timestamps();
            
            // Index pour classements
            $table->index(['level', 'xp']);
            $table->index(['reputation_score']);
            $table->index(['global_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
