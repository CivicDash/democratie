<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('amendements_senat', function (Blueprint $table) {
            $table->string('uid', 50)->primary()->comment('Ex: AMELI1720308S0B0001');
            
            // Références
            $table->string('texte_ref', 50)->nullable()->index()->comment('Référence du texte législatif');
            $table->string('auteur_senateur_matricule', 10)->nullable()->index();
            $table->integer('legislature')->index()->comment('Ex: 2024');
            
            // Identification
            $table->string('numero', 20)->index()->comment('Ex: 1, 2, 308');
            $table->string('numero_long', 100)->nullable()->comment('Numéro complet');
            $table->string('subdiv_type', 50)->nullable()->comment('Type de subdivision (article, annexe, etc.)');
            $table->string('subdiv_titre', 255)->nullable()->comment('Titre de la subdivision');
            $table->text('subdiv_mult')->nullable()->comment('Multiples subdivisions (JSON)');
            
            // Auteur
            $table->string('auteur_type', 50)->nullable()->comment('SENATEUR, GOUVERNEMENT, COMMISSION');
            $table->string('auteur_nom', 255)->nullable();
            $table->string('auteur_groupe_sigle', 20)->nullable();
            
            // Cosignataires (JSON array)
            $table->json('cosignataires')->nullable();
            $table->integer('nombre_cosignataires')->default(0);
            
            // Contenu
            $table->text('dispositif')->nullable();
            $table->text('expose')->nullable()->comment('Exposé sommaire');
            
            // Sort
            $table->string('sort_code', 20)->nullable()->index()->comment('ADOPTE, REJETE, RETIRE, TOMBE, etc.');
            $table->string('sort_libelle', 100)->nullable();
            $table->date('date_depot')->nullable()->index();
            $table->date('date_sort')->nullable();
            
            // Liens
            $table->text('url_senat')->nullable()->comment('URL vers data.senat.fr');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('auteur_senateur_matricule')->references('matricule')->on('senateurs')->onDelete('set null');
            
            // Index composites
            $table->index(['legislature', 'sort_code']);
            $table->index(['auteur_senateur_matricule', 'legislature']);
            $table->index(['legislature', 'date_depot']);
        });

        // Full-text search
        DB::statement('CREATE INDEX amendements_senat_dispositif_fulltext ON amendements_senat USING gin(to_tsvector(\'french\', COALESCE(dispositif, \'\')))');
        DB::statement('CREATE INDEX amendements_senat_expose_fulltext ON amendements_senat USING gin(to_tsvector(\'french\', COALESCE(expose, \'\')))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amendements_senat');
    }
};

