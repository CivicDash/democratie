<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senateurs_questions', function (Blueprint $table) {
            $table->id();
            $table->string('senateur_matricule', 10)->index();
            $table->string('numero', 20)->index()->comment('Numéro de la question');
            $table->string('type', 20)->default('Orale')->comment('Orale, Écrite');
            
            // Question
            $table->text('texte_question')->nullable();
            $table->string('ministre_destinataire')->nullable();
            $table->date('date_question')->nullable()->index();
            
            // Réponse
            $table->text('texte_reponse')->nullable();
            $table->date('date_reponse')->nullable()->index();
            $table->boolean('a_reponse')->default(false)->index();
            
            // Thématique
            $table->string('theme')->nullable()->index();
            $table->string('sous_theme')->nullable();
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('senateur_matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['senateur_matricule', 'numero'], 'senateurs_questions_unique');
            
            // Index composites
            $table->index(['senateur_matricule', 'date_question']);
            $table->index(['theme', 'date_question']);
        });

        // Full-text search sur le texte de la question
        DB::statement('CREATE INDEX senateurs_questions_texte_question_fulltext ON senateurs_questions USING gin(to_tsvector(\'french\', COALESCE(texte_question, \'\')))');
        DB::statement('CREATE INDEX senateurs_questions_texte_reponse_fulltext ON senateurs_questions USING gin(to_tsvector(\'french\', COALESCE(texte_reponse, \'\')))');
    }

    public function down(): void
    {
        Schema::dropIfExists('senateurs_questions');
    }
};

