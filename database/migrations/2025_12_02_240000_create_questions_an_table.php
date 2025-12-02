<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_an', function (Blueprint $table) {
            $table->string('uid', 50)->primary();
            $table->integer('numero');
            $table->smallInteger('legislature')->default(17);
            $table->string('type', 10)->default('QG'); // QG = Question au Gouvernement
            
            // Auteur
            $table->string('acteur_ref', 20)->nullable()->index();
            $table->string('mandat_ref', 20)->nullable();
            $table->string('groupe_ref', 20)->nullable();
            $table->string('groupe_sigle', 20)->nullable();
            $table->string('groupe_nom')->nullable();
            
            // Ministère interrogé
            $table->string('ministere_ref', 20)->nullable();
            $table->string('ministere_sigle', 50)->nullable();
            $table->string('ministere_nom')->nullable();
            
            // Indexation
            $table->string('rubrique')->nullable();
            $table->string('analyse')->nullable();
            
            // Textes
            $table->text('texte_question')->nullable();
            $table->text('texte_reponse')->nullable();
            
            // Dates
            $table->date('date_question')->nullable();
            $table->date('date_reponse')->nullable();
            $table->string('page_jo', 20)->nullable();
            
            // Clôture
            $table->string('code_cloture', 20)->nullable();
            $table->string('libelle_cloture')->nullable();
            $table->date('date_cloture')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('numero');
            $table->index('legislature');
            $table->index('date_question');
            $table->index('date_reponse');
            $table->index(['acteur_ref', 'legislature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_an');
    }
};

