<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables pour les données INSEE
     * Source : API INSEE / data.gouv.fr
     */
    public function up(): void
    {
        // Données par commune (enrichissement french_postal_codes)
        Schema::create('insee_communes', function (Blueprint $table) {
            $table->id();
            $table->string('code_insee', 10)->unique();
            $table->string('nom');
            $table->string('code_departement', 5)->index();
            $table->string('code_region', 5)->index();
            
            // Population
            $table->integer('population')->nullable();
            $table->integer('population_annee')->nullable();
            $table->decimal('densite', 10, 2)->nullable(); // hab/km²
            $table->decimal('superficie', 10, 2)->nullable(); // km²
            
            // Données économiques
            $table->decimal('revenu_median', 12, 2)->nullable();
            $table->decimal('taux_pauvrete', 5, 2)->nullable();
            $table->decimal('taux_chomage', 5, 2)->nullable();
            
            // Données démographiques
            $table->decimal('part_moins_25', 5, 2)->nullable(); // %
            $table->decimal('part_plus_65', 5, 2)->nullable(); // %
            $table->decimal('taux_natalite', 5, 2)->nullable(); // ‰
            $table->decimal('taux_mortalite', 5, 2)->nullable(); // ‰
            
            // Logement
            $table->decimal('taux_proprietaires', 5, 2)->nullable();
            $table->decimal('taux_logements_vacants', 5, 2)->nullable();
            
            // Coordonnées
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            $table->timestamps();
        });

        // Données par département
        Schema::create('insee_departements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('nom');
            $table->string('code_region', 5)->index();
            $table->string('chef_lieu')->nullable();
            
            // Population
            $table->integer('population')->nullable();
            $table->integer('population_annee')->nullable();
            $table->decimal('densite', 10, 2)->nullable();
            $table->decimal('superficie', 10, 2)->nullable();
            $table->integer('nb_communes')->nullable();
            
            // Économie
            $table->decimal('revenu_median', 12, 2)->nullable();
            $table->decimal('taux_pauvrete', 5, 2)->nullable();
            $table->decimal('taux_chomage', 5, 2)->nullable();
            $table->decimal('pib_par_habitant', 12, 2)->nullable();
            
            // Démographie
            $table->decimal('part_moins_25', 5, 2)->nullable();
            $table->decimal('part_plus_65', 5, 2)->nullable();
            $table->decimal('esperance_vie', 5, 2)->nullable();
            
            // Politique
            $table->integer('nb_deputes')->nullable();
            $table->integer('nb_senateurs')->nullable();
            
            $table->timestamps();
        });

        // Données par région
        Schema::create('insee_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('nom');
            $table->string('chef_lieu')->nullable();
            
            // Population
            $table->integer('population')->nullable();
            $table->integer('population_annee')->nullable();
            $table->decimal('densite', 10, 2)->nullable();
            $table->decimal('superficie', 10, 2)->nullable();
            $table->integer('nb_departements')->nullable();
            $table->integer('nb_communes')->nullable();
            
            // Économie
            $table->decimal('pib', 15, 2)->nullable(); // en millions €
            $table->decimal('pib_par_habitant', 12, 2)->nullable();
            $table->decimal('revenu_median', 12, 2)->nullable();
            $table->decimal('taux_chomage', 5, 2)->nullable();
            $table->decimal('taux_activite', 5, 2)->nullable();
            
            // Démographie
            $table->decimal('part_moins_25', 5, 2)->nullable();
            $table->decimal('part_plus_65', 5, 2)->nullable();
            $table->decimal('solde_migratoire', 8, 2)->nullable();
            
            $table->timestamps();
        });

        // Séries temporelles (évolution annuelle)
        Schema::create('insee_series', function (Blueprint $table) {
            $table->id();
            $table->string('territoire_type', 20); // commune, departement, region, france
            $table->string('territoire_code', 10)->index();
            $table->string('indicateur', 50)->index(); // population, chomage, pib, etc.
            $table->integer('annee')->index();
            $table->decimal('valeur', 15, 4);
            $table->string('unite', 20)->nullable();
            $table->timestamps();
            
            $table->unique(['territoire_type', 'territoire_code', 'indicateur', 'annee'], 'insee_series_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insee_series');
        Schema::dropIfExists('insee_regions');
        Schema::dropIfExists('insee_departements');
        Schema::dropIfExists('insee_communes');
    }
};
