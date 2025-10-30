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
        Schema::create('datagouv_cache', function (Blueprint $table) {
            $table->id();
            $table->string('dataset_id')->index()->comment('ID du dataset data.gouv.fr');
            $table->string('resource_id')->nullable()->index()->comment('ID de la ressource spécifique');
            $table->string('code_insee', 5)->nullable()->index()->comment('Code INSEE pour données territoriales');
            $table->integer('annee')->nullable()->index()->comment('Année des données');
            $table->string('data_type', 50)->index()->comment('Type de données (budget, election, etc.)');
            $table->json('data')->comment('Données complètes en JSON');
            $table->json('metadata')->nullable()->comment('Métadonnées supplémentaires');
            $table->timestamp('fetched_at')->comment('Date de récupération depuis data.gouv.fr');
            $table->timestamps();
            
            // Index composés pour optimiser les requêtes fréquentes
            $table->index(['dataset_id', 'code_insee', 'annee'], 'idx_dataset_commune_annee');
            $table->index(['data_type', 'code_insee', 'annee'], 'idx_datatype_commune_annee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datagouv_cache');
    }
};

