<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pour stocker les textes du Journal Officiel
     * Source : exports DILA (data.gouv.fr)
     */
    public function up(): void
    {
        Schema::create('textes_jo', function (Blueprint $table) {
            $table->id();
            $table->string('jorf_id', 50)->unique(); // JORFTEXT000053176659
            $table->string('eli_url')->nullable(); // URL ELI Légifrance
            $table->string('nor', 20)->nullable()->index(); // PRMD2535384A
            $table->string('nature', 50)->index(); // LOI, DECRET, ARRETE, ORDONNANCE
            $table->string('numero')->nullable(); // 2025-1079
            $table->text('titre');
            $table->text('titre_court')->nullable();
            $table->date('date_signature')->nullable();
            $table->date('date_publication')->index();
            $table->string('num_parution_jo', 20)->nullable(); // 0304
            
            // Contenu (on stocke uniquement le résumé, pas tout le texte pour économiser)
            $table->text('visa')->nullable(); // Texte des visas
            $table->integer('nb_articles')->default(0);
            
            // Lien vers nos tables existantes
            $table->string('loi_loicod')->nullable()->index(); // FK vers senat_dosleg_loi
            
            $table->timestamps();
        });

        // Table pour les articles (optionnelle, peut être activée plus tard)
        Schema::create('articles_jo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('texte_jo_id')->constrained('textes_jo')->onDelete('cascade');
            $table->string('jorf_article_id', 50)->unique(); // JORFARTI000053176665
            $table->string('numero', 20)->nullable(); // 1, 2, 3...
            $table->string('type', 50)->nullable(); // AUTONOME, etc.
            $table->text('contenu'); // Texte de l'article
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles_jo');
        Schema::dropIfExists('textes_jo');
    }
};

