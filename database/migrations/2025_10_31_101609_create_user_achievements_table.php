<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
            
            // Progression
            $table->integer('progress')->default(0)->comment('Progression actuelle');
            $table->integer('progress_target')->comment('Cible pour débloquer');
            $table->boolean('is_unlocked')->default(false)->comment('Badge débloqué');
            $table->timestamp('unlocked_at')->nullable()->comment('Date de déblocage');
            
            // Contexte
            $table->json('unlock_data')->nullable()->comment('Données de déblocage (contexte)');
            $table->boolean('is_notified')->default(false)->comment('Notification envoyée');
            $table->boolean('is_shared')->default(false)->comment('Partagé sur réseaux');
            
            $table->timestamps();
            
            // Index
            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'is_unlocked']);
            $table->index(['user_id', 'unlocked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
