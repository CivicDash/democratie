<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Système Villes : entité centrale avec stats pré-calculées et historique maires
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Table principale des villes (agrégation par code INSEE)
        Schema::create('villes', function (Blueprint $table) {
            $table->id();
            $table->string('code_insee', 5)->unique();
            $table->string('nom', 150);
            $table->string('slug', 200)->unique();

            // Codes postaux (peut y en avoir plusieurs)
            $table->string('code_postal_principal', 5)->nullable();
            $table->json('codes_postaux')->nullable(); // ["75001", "75002", ...]

            // Géographie
            $table->string('departement_code', 3)->index();
            $table->string('departement_nom', 100)->nullable();
            $table->string('region_code', 3)->nullable();
            $table->string('region_nom', 100)->nullable();
            $table->string('circonscription', 10)->nullable()->index(); // "75-01"

            // EPCI (Intercommunalité)
            $table->string('epci_code', 15)->nullable()->index();
            $table->string('epci_nom', 200)->nullable();

            // Coordonnées
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Caractéristiques
            $table->boolean('est_prefecture')->default(false);
            $table->boolean('est_sous_prefecture')->default(false);
            $table->boolean('est_chef_lieu_region')->default(false);
            $table->boolean('arrondissement_municipal')->default(false); // Paris, Lyon, Marseille
            $table->string('ville_parent_insee', 5)->nullable(); // Pour arrondissements

            // Données démographiques actuelles
            $table->integer('population')->nullable();
            $table->decimal('superficie_km2', 10, 2)->nullable();
            $table->decimal('densite', 10, 2)->nullable(); // hab/km²

            // Lien vers le maire actuel
            $table->foreignId('maire_actuel_id')->nullable()->constrained('maires')->nullOnDelete();

            $table->timestamps();

            // Index pour recherche
            $table->fullText(['nom']);
            $table->index(['departement_code', 'nom']);
        });

        // 2. Historique démographique des villes
        Schema::create('villes_population', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ville_id')->constrained()->onDelete('cascade');
            $table->integer('annee')->index();
            $table->integer('population');
            $table->integer('population_municipale')->nullable(); // Sans doubles comptes
            $table->integer('population_comptee_a_part')->nullable();
            $table->string('source', 50)->default('insee');
            $table->timestamps();

            $table->unique(['ville_id', 'annee']);
        });

        // 3. Statistiques pré-calculées des villes
        Schema::create('villes_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ville_id')->constrained()->onDelete('cascade');
            $table->integer('annee')->nullable(); // null = stats actuelles

            // Démographie
            $table->integer('population')->nullable();
            $table->decimal('densite', 10, 2)->nullable();
            $table->decimal('evolution_population_5ans_pct', 6, 2)->nullable();
            $table->decimal('age_moyen', 4, 1)->nullable();

            // Économie (si données OFGL disponibles)
            $table->decimal('budget_fonctionnement', 15, 2)->nullable();
            $table->decimal('budget_investissement', 15, 2)->nullable();
            $table->decimal('dette_totale', 15, 2)->nullable();
            $table->decimal('dette_par_habitant', 10, 2)->nullable();
            $table->decimal('taux_endettement_pct', 6, 2)->nullable();
            $table->decimal('capacite_autofinancement', 15, 2)->nullable();

            // Fiscalité
            $table->decimal('taxe_habitation', 6, 2)->nullable();
            $table->decimal('taxe_fonciere', 6, 2)->nullable();

            // Représentation politique
            $table->integer('nb_maires_historique')->nullable();
            $table->integer('duree_moyenne_mandat_mois')->nullable();

            // Scores
            $table->integer('score_dynamisme')->nullable(); // 0-100
            $table->integer('score_sante_financiere')->nullable(); // 0-100

            $table->string('source', 50)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['ville_id', 'annee']);
        });

        // 4. Historique des maires (mandats)
        Schema::create('maires_mandats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ville_id')->constrained()->onDelete('cascade');
            $table->foreignId('maire_id')->nullable()->constrained()->nullOnDelete();

            // Identité (si maire_id null = données historiques sans fiche maire)
            $table->string('nom', 100)->nullable();
            $table->string('prenom', 100)->nullable();
            $table->string('sexe', 1)->nullable();

            // Mandat
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('type_mandat', 50)->nullable(); // "election", "interim", "remplacement"
            $table->string('cause_fin', 100)->nullable(); // "fin_mandat", "demission", "deces", "revocation"

            // Élection
            $table->integer('annee_election')->nullable();
            $table->string('nuance_politique', 20)->nullable();
            $table->string('parti', 100)->nullable();
            $table->decimal('score_election_pct', 5, 2)->nullable();
            $table->integer('tour_election')->nullable(); // 1 ou 2

            // Lien avec mandature
            $table->string('mandature', 20)->nullable(); // "2020-2026", "2014-2020"

            $table->boolean('est_actuel')->default(false);
            $table->timestamps();

            $table->index(['ville_id', 'est_actuel']);
            $table->index(['ville_id', 'date_debut']);
        });

        // 5. Ajouter colonnes à la table maires existante
        if (Schema::hasTable('maires')) {
            Schema::table('maires', function (Blueprint $table) {
                if (! Schema::hasColumn('maires', 'ville_id')) {
                    $table->foreignId('ville_id')->nullable()->after('id');
                }
            });
        }
    }

    public function down(): void
    {
        // Retirer FK de maires
        if (Schema::hasTable('maires') && Schema::hasColumn('maires', 'ville_id')) {
            Schema::table('maires', function (Blueprint $table) {
                $table->dropColumn('ville_id');
            });
        }

        Schema::dropIfExists('maires_mandats');
        Schema::dropIfExists('villes_stats');
        Schema::dropIfExists('villes_population');
        Schema::dropIfExists('villes');
    }
};
