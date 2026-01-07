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
        Schema::create('amendements_parlementaires', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('depute_senateur_id')
                ->constrained('deputes_senateurs')
                ->onDelete('cascade');
            
            $table->string('numero', 100)->index(); // ex: "1234 rect", "AS25"
            $table->string('numero_long', 255)->nullable(); // Numéro complet avec session
            $table->date('date_depot')->index();
            
            $table->string('legislature', 20)->nullable();
            $table->string('session', 50)->nullable();
            
            $table->text('titre')->nullable();
            $table->text('expose')->nullable(); // Exposé des motifs
            $table->text('dispositif')->nullable(); // Texte de l'amendement
            
            $table->string('sort', 50)->nullable(); // adopte/rejete/retire/tombe/non-vote
            $table->string('sujet', 255)->nullable();
            
            // Lien vers proposition/texte de loi
            $table->unsignedBigInteger('proposition_loi_id')->nullable();
            $table->string('texte_loi_reference', 255)->nullable(); // Ex: "PLF 2024"
            
            // URLs
            $table->text('url_nosdeputes')->nullable();
            $table->text('url_assemblee')->nullable();
            
            // Co-signataires (JSON)
            $table->json('cosignataires')->nullable();
            
            // Métadonnées
            $table->integer('nombre_cosignataires')->default(0);
            $table->string('groupe_politique', 100)->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['depute_senateur_id', 'date_depot'], 'idx_depute_date_depot');
            $table->index(['sort', 'date_depot'], 'idx_sort_date');
            $table->index('texte_loi_reference');
            
            // Index full-text pour recherche
            $table->index('numero', 'idx_numero_amendement');
            
            // Unique constraint sur numero + legislature
            $table->unique(['numero_long', 'legislature'], 'unique_amendement_legislature');
        });

        // Index full-text PostgreSQL
        DB::statement('CREATE INDEX idx_amendements_fulltext ON amendements_parlementaires USING gin(to_tsvector(\'french\', COALESCE(titre, \'\') || \' \' || COALESCE(expose, \'\') || \' \' || COALESCE(dispositif, \'\')))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amendements_parlementaires');
    }
};

