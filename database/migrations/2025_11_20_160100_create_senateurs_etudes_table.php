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
        Schema::create('senateurs_etudes', function (Blueprint $table) {
            $table->id();
            $table->string('senateur_matricule', 20);
            $table->string('etablissement')->nullable();
            $table->string('diplome')->nullable();
            $table->string('niveau', 50)->nullable(); // 'BAC', 'BAC+2', 'BAC+3', 'BAC+5', 'DOCTORAT'
            $table->string('domaine')->nullable(); // 'Droit', 'Sciences Politiques', etc.
            $table->integer('annee')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->foreign('senateur_matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
            $table->index('senateur_matricule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senateurs_etudes');
    }
};

