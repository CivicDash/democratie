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
        Schema::create('amendements_an', function (Blueprint $table) {
            $table->string('uid', 50)->primary()->comment('UID amendement (ex: AMANR5L17PO838901B0689P0D1N000007)');
            $table->string('texte_legislatif_ref', 30)->nullable()->index();
            $table->string('examen_ref', 50)->nullable();
            $table->integer('legislature')->index();
            $table->string('numero_long', 20)->nullable();
            $table->integer('numero_ordre_depot')->nullable();
            $table->string('prefixe_organe_examen', 20)->nullable()->comment('AN, CION_LOIS, etc.');
            
            // Auteur
            $table->string('auteur_type', 50)->comment('Député, Gouvernement');
            $table->string('auteur_acteur_ref', 20)->nullable()->index();
            $table->string('auteur_groupe_ref', 20)->nullable()->index();
            $table->text('auteur_libelle')->nullable();
            
            // Cosignataires
            $table->json('cosignataires_acteur_refs')->nullable();
            $table->integer('nombre_cosignataires')->default(0);
            
            // Article visé
            $table->string('article_designation', 100)->nullable();
            $table->string('article_designation_courte', 50)->nullable();
            $table->string('division_titre', 255)->nullable();
            $table->string('division_type', 20)->nullable()->comment('ARTICLE, ANNEXE');
            
            // Contenu
            $table->text('cartouche_informatif')->nullable();
            $table->longText('dispositif')->nullable();
            $table->longText('expose')->nullable();
            
            // Cycle de vie
            $table->date('date_depot')->nullable()->index();
            $table->date('date_publication')->nullable();
            $table->boolean('soumis_article_40')->default(false);
            $table->string('etat_code', 20)->nullable()->index()->comment('ADO, REJ, IRR45, etc.');
            $table->string('etat_libelle', 100)->nullable();
            $table->string('sous_etat_code', 20)->nullable();
            $table->string('sous_etat_libelle', 100)->nullable();
            $table->date('date_sort')->nullable();
            $table->string('sort_code', 20)->nullable();
            $table->string('sort_libelle', 100)->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('texte_legislatif_ref')->references('uid')->on('textes_legislatifs_an')->onDelete('set null');
            $table->foreign('auteur_acteur_ref')->references('uid')->on('acteurs_an')->onDelete('set null');
            $table->foreign('auteur_groupe_ref')->references('uid')->on('organes_an')->onDelete('set null');
            
            // Index composites
            $table->index(['legislature', 'etat_code']);
            $table->index(['auteur_acteur_ref', 'legislature']);
            $table->index(['legislature', 'date_depot']);
            $table->index(['texte_legislatif_ref', 'numero_ordre_depot']);
        });

        // Full-text search sur dispositif et exposé
        DB::statement('CREATE INDEX amendements_an_dispositif_fulltext ON amendements_an USING gin(to_tsvector(\'french\', COALESCE(dispositif, \'\')))');
        DB::statement('CREATE INDEX amendements_an_expose_fulltext ON amendements_an USING gin(to_tsvector(\'french\', COALESCE(expose, \'\')))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amendements_an');
    }
};

