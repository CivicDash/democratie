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
        Schema::create('commune_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('code_insee', 5)->comment('Code INSEE de la commune');
            $table->string('nom_commune')->comment('Nom de la commune');
            $table->integer('annee')->comment('Année budgétaire');
            $table->integer('population')->default(0)->comment('Population légale');
            
            // Montants en centimes d'euros (pour éviter les problèmes de précision)
            $table->bigInteger('budget_total')->default(0)->comment('Budget total en centimes');
            $table->bigInteger('recettes_fonctionnement')->default(0)->comment('Recettes de fonctionnement en centimes');
            $table->bigInteger('depenses_fonctionnement')->default(0)->comment('Dépenses de fonctionnement en centimes');
            $table->bigInteger('recettes_investissement')->default(0)->comment('Recettes d\'investissement en centimes');
            $table->bigInteger('depenses_investissement')->default(0)->comment('Dépenses d\'investissement en centimes');
            $table->bigInteger('dette')->default(0)->comment('Dette totale en centimes');
            
            $table->decimal('depenses_par_habitant', 10, 2)->default(0)->comment('Dépenses par habitant en euros');
            
            // Sections budgétaires détaillées (optionnel)
            $table->json('sections')->nullable()->comment('Répartition détaillée du budget par section');
            
            // Métadonnées
            $table->string('source', 100)->default('data.gouv.fr')->comment('Source des données');
            $table->timestamp('fetched_at')->nullable()->comment('Date de récupération des données');
            
            $table->timestamps();
            
            // Index et contraintes
            $table->unique(['code_insee', 'annee'], 'unique_commune_annee');
            $table->index(['nom_commune'], 'idx_nom_commune');
            $table->index(['population'], 'idx_population');
            $table->index(['annee'], 'idx_annee');
            
            // Index pour les recherches fréquentes
            $table->index(['code_insee', 'annee'], 'idx_search_commune');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commune_budgets');
    }
};

