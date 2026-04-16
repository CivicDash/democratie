<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communes')) {
            Schema::create('communes', function (Blueprint $table) {
                $table->id();
                $table->string('code_insee', 5)->unique(); // Code INSEE officiel
                $table->string('nom', 150);
                $table->string('slug', 200)->unique();
                $table->string('code_postal', 5)->nullable()->index();
                $table->string('codes_postaux', 500)->nullable(); // Multiples CP séparés par virgule
                $table->string('departement_code', 3)->index();
                $table->string('departement_nom', 100)->nullable();
                $table->string('region_code', 2)->nullable();
                $table->string('region_nom', 100)->nullable();
                $table->integer('population')->nullable();
                $table->decimal('superficie', 10, 2)->nullable(); // km²
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();

                // Liens vers les représentants
                $table->string('circonscription_code', 10)->nullable()->index(); // Ex: "01-01" pour Ain 1ère
                $table->string('epci_siren', 15)->nullable(); // Intercommunalité
                $table->string('epci_nom', 200)->nullable();

                // Caractéristiques
                $table->boolean('est_chef_lieu_departement')->default(false);
                $table->boolean('est_chef_lieu_region')->default(false);
                $table->boolean('zone_montagne')->default(false);
                $table->boolean('zone_rurale')->default(false);
                $table->boolean('outre_mer')->default(false);

                $table->timestamps();

                $table->index(['departement_code', 'nom']);
            });
        }

        // Table pour les budgets des communes (OFGL)
        if (! Schema::hasTable('commune_budgets')) {
            Schema::create('commune_budgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commune_id')->constrained()->onDelete('cascade');
                $table->integer('annee')->index();

                // Fonctionnement
                $table->decimal('recettes_fonctionnement', 15, 2)->nullable();
                $table->decimal('depenses_fonctionnement', 15, 2)->nullable();

                // Investissement
                $table->decimal('recettes_investissement', 15, 2)->nullable();
                $table->decimal('depenses_investissement', 15, 2)->nullable();

                // Dette
                $table->decimal('encours_dette', 15, 2)->nullable();
                $table->decimal('annuite_dette', 15, 2)->nullable();

                // Ratios
                $table->decimal('capacite_autofinancement', 15, 2)->nullable();
                $table->decimal('euros_par_habitant', 10, 2)->nullable();

                $table->string('source', 50)->default('ofgl');
                $table->timestamps();

                $table->unique(['commune_id', 'annee']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_budgets');
        Schema::dropIfExists('communes');
    }
};
