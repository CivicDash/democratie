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
        Schema::create('propositions_loi', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20)->comment('assemblee ou senat');
            $table->integer('legislature')->default(17)->comment('Numéro de législature');
            $table->string('numero', 20)->comment('Numéro de la proposition');
            $table->string('titre')->comment('Titre de la proposition');
            $table->text('resume')->nullable()->comment('Résumé/exposé des motifs');
            $table->text('texte_integral')->nullable()->comment('Texte complet');
            
            $table->string('statut', 50)->default('en_cours')->comment('Statut: en_cours, adoptee, rejetee, promulguee');
            $table->string('theme', 100)->nullable()->comment('Thème/catégorie');
            
            $table->date('date_depot')->nullable()->comment('Date de dépôt');
            $table->date('date_adoption')->nullable()->comment('Date d\'adoption');
            $table->date('date_promulgation')->nullable()->comment('Date de promulgation');
            
            $table->json('auteurs')->nullable()->comment('Liste des auteurs (députés/sénateurs)');
            $table->json('etapes')->nullable()->comment('Étapes du processus législatif');
            $table->json('votes_resultats')->nullable()->comment('Résultats des votes');
            
            $table->string('url_externe')->nullable()->comment('URL sur le site officiel');
            $table->string('url_pdf')->nullable()->comment('URL du PDF');
            
            $table->timestamp('fetched_at')->nullable()->comment('Date de récupération des données');
            $table->timestamps();
            
            // Index
            $table->unique(['source', 'legislature', 'numero'], 'unique_proposition');
            $table->index(['source', 'statut'], 'idx_source_statut');
            $table->index(['theme'], 'idx_theme');
            $table->index(['date_depot'], 'idx_date_depot');
            $table->fullText(['titre', 'resume'], 'fulltext_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propositions_loi');
    }
};

