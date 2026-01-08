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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // new_thematique, new_groupe, new_vote, new_legislation, etc.
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('icon')->nullable(); // emoji ou classe icône
            $table->string('link')->nullable(); // URL de l'élément concerné
            $table->json('data')->nullable(); // Données additionnelles (ID, stats, etc.)
            $table->timestamp('read_at')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->timestamps();
            
            // Index pour performances
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
