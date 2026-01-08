<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Les domaines ministériels sont des catégories permanentes (ex: Intérieur, Justice, Économie...)
     * qui regroupent les différentes appellations de ministères à travers les gouvernements.
     */
    public function up(): void
    {
        // Table des domaines ministériels (catégories permanentes)
        Schema::create('domaines_ministeriels', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 255);           // Ex: "Intérieur", "Justice", "Économie"
            $table->string('slug', 100)->unique();
            $table->string('sigle', 20)->nullable(); // Ex: "MINT", "MJ", "MEFSIN"
            $table->text('description')->nullable();
            
            // Données Wikipedia
            $table->string('wikipedia_url', 500)->nullable();
            $table->text('wikipedia_extract')->nullable();
            
            // Coordonnées officielles
            $table->string('site_web', 500)->nullable();
            $table->string('adresse', 500)->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('email', 255)->nullable();
            
            // Visuels
            $table->string('couleur', 10)->default('#6b7280');
            $table->string('icone', 50)->nullable();
            $table->string('logo_url', 500)->nullable();
            
            // Dates historiques
            $table->date('date_creation')->nullable(); // Date de création du ministère
            
            $table->integer('ordre')->default(99);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Ajouter la colonne domaine_ministeriel_id sur ministeres
        Schema::table('ministeres', function (Blueprint $table) {
            $table->foreignId('domaine_ministeriel_id')
                ->nullable()
                ->after('gouvernement_id')
                ->constrained('domaines_ministeriels')
                ->nullOnDelete();
        });

        // Ajouter aussi sur postes_ministeriels pour liaison directe
        Schema::table('postes_ministeriels', function (Blueprint $table) {
            $table->foreignId('domaine_ministeriel_id')
                ->nullable()
                ->after('ministere_id')
                ->constrained('domaines_ministeriels')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postes_ministeriels', function (Blueprint $table) {
            $table->dropForeign(['domaine_ministeriel_id']);
            $table->dropColumn('domaine_ministeriel_id');
        });

        Schema::table('ministeres', function (Blueprint $table) {
            $table->dropForeign(['domaine_ministeriel_id']);
            $table->dropColumn('domaine_ministeriel_id');
        });

        Schema::dropIfExists('domaines_ministeriels');
    }
};
