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
        Schema::create('topic_ballots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            // ⚠️ PAS de user_id ici pour garantir l'anonymat
            $table->string('encrypted_vote')->comment('Vote chiffré (Laravel Crypt)');
            $table->string('vote_hash', 64)->unique()->comment('Hash du vote pour unicité');
            $table->timestamp('cast_at')->useCurrent();
            
            $table->index('topic_id');
            $table->index('cast_at');
            
            // Note: Ce tableau ne contient AUCUNE référence à l'identité du votant
            // La liaison est supprimée après consommation du token
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_ballots');
    }
};

