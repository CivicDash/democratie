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
        Schema::create('votes_individuels_an', function (Blueprint $table) {
            $table->id();
            $table->string('scrutin_ref', 30)->index();
            $table->string('acteur_ref', 20)->index();
            $table->string('mandat_ref', 20)->nullable();
            $table->string('groupe_ref', 20)->nullable()->index();
            $table->enum('position', ['pour', 'contre', 'abstention', 'non_votant']);
            $table->enum('position_groupe', ['pour', 'contre', 'abstention', 'mixte'])->nullable();
            $table->string('numero_place', 10)->nullable();
            $table->boolean('par_delegation')->default(false);
            $table->string('cause_non_vote', 50)->nullable()->comment('PAN, PSE, etc.');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('scrutin_ref')->references('uid')->on('scrutins_an')->onDelete('cascade');
            $table->foreign('acteur_ref')->references('uid')->on('acteurs_an')->onDelete('cascade');
            $table->foreign('groupe_ref')->references('uid')->on('organes_an')->onDelete('set null');
            
            // Unique constraint
            $table->unique(['scrutin_ref', 'acteur_ref']);
            
            // Index pour les analyses
            $table->index(['acteur_ref', 'position']);
            $table->index(['groupe_ref', 'position']);
            $table->index(['scrutin_ref', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes_individuels_an');
    }
};

