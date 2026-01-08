<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les tables de rémunérations pour les différentes activités HATVP
 * et les colonnes manquantes pour les revenus par année
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rémunérations des activités professionnelles
        Schema::create('hatvp_remunerations_activites_pro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activite_id')->constrained('hatvp_activites_professionnelles')->onDelete('cascade');
            $table->integer('annee');
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('brut_net', 10)->nullable();
            $table->timestamps();
            
            $table->index(['activite_id', 'annee']);
        });

        // Rémunérations des activités de consultant
        Schema::create('hatvp_remunerations_consultant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activite_id')->constrained('hatvp_activites_consultant')->onDelete('cascade');
            $table->integer('annee');
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('brut_net', 10)->nullable();
            $table->timestamps();
            
            $table->index(['activite_id', 'annee']);
        });

        // Rémunérations des participations dirigeantes
        Schema::create('hatvp_remunerations_dirigeant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained('hatvp_participations_dirigeantes')->onDelete('cascade');
            $table->integer('annee');
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('brut_net', 10)->nullable();
            $table->timestamps();
            
            $table->index(['participation_id', 'annee']);
        });

        // Ajouter une colonne remuneration aux participations financières
        Schema::table('hatvp_participations_financieres', function (Blueprint $table) {
            $table->decimal('remuneration', 12, 2)->nullable()->after('nombre_parts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hatvp_participations_financieres', function (Blueprint $table) {
            $table->dropColumn('remuneration');
        });
        
        Schema::dropIfExists('hatvp_remunerations_dirigeant');
        Schema::dropIfExists('hatvp_remunerations_consultant');
        Schema::dropIfExists('hatvp_remunerations_activites_pro');
    }
};

