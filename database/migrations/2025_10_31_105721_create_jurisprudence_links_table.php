<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurisprudence_links', function (Blueprint $table) {
            $table->id();
            
            // Relation avec référence juridique
            $table->foreignId('legal_reference_id')
                ->constrained('legal_references')
                ->onDelete('cascade');
            
            // Identifiants Légifrance
            $table->string('legifrance_juri_id', 255)->comment('ID Légifrance de la décision');
            $table->string('external_url', 500)->nullable()->comment('URL vers Légifrance');
            
            // Informations juridiction
            $table->string('jurisdiction', 100)->comment('Ex: CE, Cass.Civ, Cass.Crim, etc.');
            $table->date('date_decision')->comment('Date de la décision');
            $table->string('decision_number', 100)->nullable()->comment('Numéro de décision');
            
            // Contenu
            $table->text('title')->comment('Titre de la décision');
            $table->text('summary')->nullable()->comment('Résumé de la décision');
            $table->longText('full_text')->nullable()->comment('Texte intégral (optionnel)');
            
            // Classification
            $table->json('themes')->nullable()->comment('Thèmes juridiques');
            $table->json('keywords')->nullable()->comment('Mots-clés');
            
            // Pertinence
            $table->integer('relevance_score')->default(0)->comment('Score de pertinence 0-100');
            $table->integer('citation_count')->default(0)->comment('Nombre de citations');
            
            // Formation de jugement
            $table->string('formation', 100)->nullable()->comment('Formation de jugement');
            $table->enum('decision_type', ['arret', 'jugement', 'ordonnance', 'avis', 'autre'])
                ->default('autre')
                ->comment('Type de décision');
            
            $table->timestamps();
            
            // Index
            $table->index('legal_reference_id');
            $table->index('legifrance_juri_id');
            $table->index(['jurisdiction', 'date_decision']);
            $table->index('relevance_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurisprudence_links');
    }
};
