<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables pour les débats en séance publique du Sénat
 * 
 * Source: https://data.senat.fr/data/debats/debats.zip
 * 
 * Structure:
 * - senat_debats: Séances de débat
 * - senat_sections_discussion: Sections de discussion (articles, amendements, etc.)
 * - senat_interventions: Interventions des sénateurs
 * - senat_types_section: Types de sections
 */
return new class extends Migration
{
    public function up(): void
    {
        // Types de sections
        Schema::create('senat_types_section', function (Blueprint $table) {
            $table->string('code', 32)->primary();
            $table->string('libelle', 255)->nullable();
        });

        // Séances de débat
        Schema::create('senat_debats', function (Blueprint $table) {
            $table->timestamp('date_seance')->primary();
            $table->bigInteger('numero')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('libelle_special', 256)->nullable();
            $table->boolean('est_congres')->default(false);
            $table->char('etat_video', 1)->nullable(); // C=CRI, A=Archive
            $table->bigInteger('cpterr')->default(0);
            $table->timestamps();
            
            $table->index('numero');
        });

        // Lectures associées aux débats
        Schema::create('senat_lectures_debats', function (Blueprint $table) {
            $table->string('lecture_id', 15);
            $table->timestamp('date_seance');
            
            $table->primary(['lecture_id', 'date_seance']);
            $table->index('date_seance');
        });

        // Sections de discussion législative
        Schema::create('senat_sections_discussion', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('lecture_id', 255);
            $table->string('type_section', 32);
            $table->timestamp('date_seance');
            $table->string('numero', 512)->nullable(); // Article 1, Amendement 123, etc.
            $table->text('objet')->nullable(); // Description/contexte
            $table->string('url', 255)->nullable();
            $table->bigInteger('ordre')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->timestamps();
            
            $table->index('lecture_id');
            $table->index('date_seance');
            $table->index('type_section');
            $table->index('parent_id');
        });

        // Sections de discussion non-législative (questions, etc.)
        Schema::create('senat_sections_diverses', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('type_section', 32);
            $table->timestamp('date_seance');
            $table->string('objet', 2048)->nullable();
            $table->string('url', 255)->nullable();
            $table->bigInteger('ordre')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->timestamps();
            
            $table->index('date_seance');
            $table->index('type_section');
        });

        // Interventions législatives
        Schema::create('senat_interventions_legislatives', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('auteur_code', 6); // Matricule sénateur
            $table->bigInteger('section_id');
            $table->text('analyse')->nullable(); // Résumé de l'intervention
            $table->string('fonction', 254)->nullable(); // Fonction de l'intervenant
            $table->string('url', 255)->nullable();
            $table->bigInteger('ordre')->nullable();
            $table->timestamps();
            
            $table->index('auteur_code');
            $table->index('section_id');
        });

        // Interventions non-législatives (questions, déclarations, etc.)
        Schema::create('senat_interventions_diverses', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('auteur_code', 6);
            $table->bigInteger('section_id');
            $table->text('analyse')->nullable();
            $table->string('fonction', 254)->nullable();
            $table->string('url', 255)->nullable();
            $table->bigInteger('ordre')->nullable();
            $table->timestamps();
            
            $table->index('auteur_code');
            $table->index('section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senat_interventions_diverses');
        Schema::dropIfExists('senat_interventions_legislatives');
        Schema::dropIfExists('senat_sections_diverses');
        Schema::dropIfExists('senat_sections_discussion');
        Schema::dropIfExists('senat_lectures_debats');
        Schema::dropIfExists('senat_debats');
        Schema::dropIfExists('senat_types_section');
    }
};
