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
        Schema::create('maires', function (Blueprint $table) {
            $table->id();
            
            // Identification
            $table->string('uid', 50)->unique()->comment('Identifiant unique du maire');
            
            // Informations personnelles
            $table->string('nom');
            $table->string('prenom');
            $table->string('nom_complet')->nullable()->comment('Nom complet calculé');
            $table->string('civilite', 10)->nullable();
            $table->date('date_naissance')->nullable();
            
            // Localisation
            $table->string('code_commune', 5)->index()->comment('Code INSEE de la commune');
            $table->string('nom_commune', 150)->index();
            $table->string('code_departement', 3)->index();
            $table->string('nom_departement', 100);
            $table->string('code_region', 2)->nullable()->index();
            $table->string('nom_region', 100)->nullable();
            
            // Profession
            $table->string('profession', 150)->nullable();
            $table->string('categorie_socio_pro', 10)->nullable()->comment('Code catégorie socio-professionnelle');
            
            // Mandat
            $table->date('debut_mandat')->nullable();
            $table->date('debut_fonction')->nullable()->comment('Date de début en tant que maire');
            $table->date('fin_mandat')->nullable();
            $table->boolean('en_exercice')->default(true);
            
            // Informations complémentaires
            $table->string('photo_url')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('site_web')->nullable();
            $table->text('adresse_mairie')->nullable();
            
            // Population de la commune (pour tri/stats)
            $table->integer('population_commune')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['code_commune', 'en_exercice'], 'idx_commune_exercice');
            $table->index(['code_departement', 'en_exercice'], 'idx_dept_exercice');
            $table->fullText(['nom', 'prenom', 'nom_commune'], 'fulltext_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maires');
    }
};

