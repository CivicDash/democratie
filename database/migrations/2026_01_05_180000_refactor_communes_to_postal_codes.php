<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour unifier le système de localisation :
 * - Enrichir french_postal_codes avec les colonnes manquantes
 * - Supprimer la table communes (doublon)
 * - Créer commune_budgets liée par insee_code
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Enrichir french_postal_codes avec les colonnes manquantes
        Schema::table('french_postal_codes', function (Blueprint $table) {
            // EPCI (Intercommunalité)
            if (! Schema::hasColumn('french_postal_codes', 'epci_code')) {
                $table->string('epci_code', 15)->nullable()->after('insee_code');
            }
            if (! Schema::hasColumn('french_postal_codes', 'epci_nom')) {
                $table->string('epci_nom', 200)->nullable()->after('epci_code');
            }

            // Superficie en km²
            if (! Schema::hasColumn('french_postal_codes', 'superficie')) {
                $table->decimal('superficie', 10, 2)->nullable()->after('population');
            }

            // Caractéristiques géographiques
            if (! Schema::hasColumn('french_postal_codes', 'est_chef_lieu_dep')) {
                $table->boolean('est_chef_lieu_dep')->default(false)->after('superficie');
            }
            if (! Schema::hasColumn('french_postal_codes', 'est_chef_lieu_region')) {
                $table->boolean('est_chef_lieu_region')->default(false)->after('est_chef_lieu_dep');
            }
            if (! Schema::hasColumn('french_postal_codes', 'zone_montagne')) {
                $table->boolean('zone_montagne')->default(false)->after('est_chef_lieu_region');
            }
            if (! Schema::hasColumn('french_postal_codes', 'zone_rurale')) {
                $table->boolean('zone_rurale')->default(false)->after('zone_montagne');
            }
            if (! Schema::hasColumn('french_postal_codes', 'outre_mer')) {
                $table->boolean('outre_mer')->default(false)->after('zone_rurale');
            }

            // Index pour les nouvelles colonnes
            $table->index('epci_code');
        });

        // 2. Supprimer l'ancienne table commune_budgets si elle existe
        Schema::dropIfExists('commune_budgets');

        // 3. Supprimer la table communes (doublon)
        Schema::dropIfExists('communes');

        // 4. Créer la nouvelle table commune_budgets liée par insee_code
        Schema::create('commune_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('insee_code', 5)->index(); // Lien avec french_postal_codes.insee_code
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

            // Détails recettes
            $table->decimal('impots_locaux', 15, 2)->nullable();
            $table->decimal('dotations_subventions', 15, 2)->nullable();

            // Détails dépenses
            $table->decimal('charges_personnel', 15, 2)->nullable();
            $table->decimal('achats_services', 15, 2)->nullable();

            // Soldes
            $table->decimal('epargne_brute', 15, 2)->nullable();

            // Population au moment du budget
            $table->integer('population')->nullable();

            $table->string('source', 50)->default('ofgl');
            $table->timestamps();

            $table->unique(['insee_code', 'annee']);

            // Index pour les requêtes fréquentes
            $table->index(['annee', 'recettes_fonctionnement']);
        });
    }

    public function down(): void
    {
        // Supprimer commune_budgets
        Schema::dropIfExists('commune_budgets');

        // Retirer les colonnes ajoutées à french_postal_codes
        Schema::table('french_postal_codes', function (Blueprint $table) {
            $table->dropIndex(['epci_code']);
            $table->dropColumn([
                'epci_code',
                'epci_nom',
                'superficie',
                'est_chef_lieu_dep',
                'est_chef_lieu_region',
                'zone_montagne',
                'zone_rurale',
                'outre_mer',
            ]);
        });

        // Note: Ne pas recréer la table communes dans le rollback
        // car elle était un doublon
    }
};
