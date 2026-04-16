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
        // Table des mots bannis
        Schema::create('banned_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100)->unique();
            $table->string('category', 50)->default('general'); // insulte, spam, politique_extreme, sexisme, racisme
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_regex')->default(false); // Si le mot est une regex
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('severity');
        });

        // Table des mots de remplacement (gentils/drôles)
        Schema::create('nice_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100);
            $table->string('category', 50)->default('general'); // compliment, emoji, nature, animal, nourriture
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // Table de logs des remplacements (pour stats)
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('moderatable'); // Topic, Post, etc.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50); // word_replaced, content_blocked, user_warned
            $table->string('original_word', 100)->nullable();
            $table->string('replacement_word', 100)->nullable();
            $table->text('context')->nullable(); // Phrase complète pour analyse
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
        Schema::dropIfExists('nice_words');
        Schema::dropIfExists('banned_words');
    }
};
