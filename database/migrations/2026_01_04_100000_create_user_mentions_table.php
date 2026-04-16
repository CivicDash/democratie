<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_mentions')) {
            return; // Table déjà créée
        }

        Schema::create('user_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Utilisateur mentionné
            $table->foreignId('mentioned_by')->constrained('users')->onDelete('cascade'); // Auteur de la mention
            $table->morphs('mentionable'); // Topic, Post, ou autre contenu
            $table->boolean('is_read')->default(false);
            $table->timestamp('notified_at')->nullable(); // Email envoyé
            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mentions');
    }
};
