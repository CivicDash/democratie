<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('followable_type'); // Topic, PropositionLoi, Post, etc.
            $table->unsignedBigInteger('followable_id');
            $table->json('notification_settings')->nullable(); // Préférences spécifiques
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            // Index polymorphique
            $table->index(['followable_type', 'followable_id']);
            $table->index(['user_id', 'followable_type']);
            
            // Empêcher les doublons
            $table->unique(['user_id', 'followable_type', 'followable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_follows');
    }
};
