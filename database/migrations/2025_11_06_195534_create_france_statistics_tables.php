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
        // ========================================
        // 1. DÉMOGRAPHIE
        // ========================================
        Schema::create('france_demographics', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->bigInteger('population_total');
            $table->json('population_by_age_group'); // {"0-14": 12000000, "15-24": 8000000, ...}
            $table->json('population_by_gender'); // {"male": 33000000, "female": 34000000}
            $table->decimal('birth_rate', 5, 2)->nullable(); // Taux de natalité
            $table->decimal('death_rate', 5, 2)->nullable(); // Taux de mortalité
            $table->decimal('life_expectancy_male', 5, 2)->nullable();
            $table->decimal('life_expectancy_female', 5, 2)->nullable();
            $table->timestamps();
        });

        // ========================================
        // 2. ÉCONOMIE
        // ========================================
        Schema::create('france_economy', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('quarter')->nullable(); // 1, 2, 3, 4 (null = annuel)
            $table->decimal('gdp_billions_euros', 12, 2); // PIB en milliards d'euros
            $table->decimal('gdp_growth_rate', 5, 2)->nullable(); // Croissance en %
            $table->decimal('unemployment_rate', 5, 2)->nullable(); // Taux de chômage en %
            $table->decimal('inflation_rate', 5, 2)->nullable(); // Taux d'inflation en %
            $table->decimal('public_debt_billions_euros', 12, 2)->nullable();
            $table->decimal('public_debt_gdp_percentage', 5, 2)->nullable(); // Dette/PIB en %
            $table->decimal('trade_balance_billions_euros', 12, 2)->nullable(); // Balance commerciale
            $table->decimal('exports_billions_euros', 12, 2)->nullable();
            $table->decimal('imports_billions_euros', 12, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['year', 'quarter']);
        });

        // ========================================
        // 3. FLUX HUMAINS (Immigration/Expatriation)
        // ========================================
        Schema::create('france_migration', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->bigInteger('immigration_total')->nullable(); // Nombre d'immigrants
            $table->bigInteger('emigration_total')->nullable(); // Nombre d'expatriés
            $table->integer('net_migration')->nullable(); // Solde migratoire
            $table->json('immigration_by_origin')->nullable(); // {"EU": 50000, "Africa": 80000, ...}
            $table->json('emigration_by_destination')->nullable();
            $table->bigInteger('asylum_requests')->nullable(); // Demandes d'asile
            $table->bigInteger('asylum_granted')->nullable(); // Asiles accordés
            $table->timestamps();
            
            $table->unique('year');
        });

        // ========================================
        // 4. BUDGET - RECETTES
        // ========================================
        Schema::create('france_budget_revenue', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->decimal('total_billions_euros', 12, 2);
            
            // Détail des recettes fiscales
            $table->decimal('tva_billions_euros', 12, 2)->nullable(); // TVA
            $table->decimal('income_tax_billions_euros', 12, 2)->nullable(); // Impôt sur le revenu
            $table->decimal('corporate_tax_billions_euros', 12, 2)->nullable(); // Impôt sur les sociétés
            $table->decimal('property_tax_billions_euros', 12, 2)->nullable(); // Taxe foncière
            $table->decimal('housing_tax_billions_euros', 12, 2)->nullable(); // Taxe d'habitation
            $table->decimal('fuel_tax_billions_euros', 12, 2)->nullable(); // TICPE (taxe carburants)
            $table->decimal('social_contributions_billions_euros', 12, 2)->nullable(); // Cotisations sociales
            $table->decimal('other_taxes_billions_euros', 12, 2)->nullable(); // Autres taxes
            
            $table->json('detailed_breakdown')->nullable(); // Détails supplémentaires en JSON
            $table->timestamps();
            
            $table->unique('year');
        });

        // ========================================
        // 5. BUDGET - DÉPENSES
        // ========================================
        Schema::create('france_budget_spending', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->decimal('total_billions_euros', 12, 2);
            
            // Grandes catégories de dépenses
            $table->decimal('health_billions_euros', 12, 2)->nullable(); // Santé
            $table->decimal('education_billions_euros', 12, 2)->nullable(); // Éducation
            $table->decimal('security_defense_billions_euros', 12, 2)->nullable(); // Sécurité & Défense
            $table->decimal('justice_billions_euros', 12, 2)->nullable(); // Justice
            $table->decimal('social_welfare_billions_euros', 12, 2)->nullable(); // Aide sociale
            $table->decimal('unemployment_billions_euros', 12, 2)->nullable(); // Chômage
            $table->decimal('pensions_billions_euros', 12, 2)->nullable(); // Retraites
            $table->decimal('business_subsidies_billions_euros', 12, 2)->nullable(); // Subventions entreprises
            $table->decimal('infrastructure_billions_euros', 12, 2)->nullable(); // Infrastructures
            $table->decimal('environment_billions_euros', 12, 2)->nullable(); // Environnement
            $table->decimal('culture_billions_euros', 12, 2)->nullable(); // Culture
            $table->decimal('debt_interest_billions_euros', 12, 2)->nullable(); // Intérêts de la dette
            $table->decimal('other_spending_billions_euros', 12, 2)->nullable(); // Autres dépenses
            
            $table->json('detailed_breakdown')->nullable();
            $table->timestamps();
            
            $table->unique('year');
        });

        // ========================================
        // 6. RECETTES PERDUES (Fraude, Évasion)
        // ========================================
        Schema::create('france_lost_revenue', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            
            // Fraude fiscale
            $table->decimal('vat_fraud_billions_euros', 12, 2)->nullable(); // Fraude à la TVA
            $table->decimal('income_tax_fraud_billions_euros', 12, 2)->nullable(); // Fraude impôt revenu
            $table->decimal('corporate_tax_fraud_billions_euros', 12, 2)->nullable(); // Fraude IS
            $table->decimal('social_fraud_billions_euros', 12, 2)->nullable(); // Fraude sociale
            
            // Évasion et optimisation fiscale
            $table->decimal('tax_evasion_billions_euros', 12, 2)->nullable(); // Évasion fiscale
            $table->decimal('tax_optimization_billions_euros', 12, 2)->nullable(); // Optimisation fiscale
            $table->decimal('offshore_billions_euros', 12, 2)->nullable(); // Paradis fiscaux
            
            // Total estimé
            $table->decimal('total_lost_billions_euros', 12, 2)->nullable();
            
            $table->text('sources')->nullable(); // Sources des estimations
            $table->text('notes')->nullable(); // Notes méthodologiques
            $table->timestamps();
            
            $table->unique('year');
        });

        // ========================================
        // 7. DONNÉES RÉGIONALES (pour carte interactive)
        // ========================================
        Schema::create('france_regional_data', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('region_code', 3); // Code INSEE région
            $table->string('region_name', 100);
            
            $table->bigInteger('population')->nullable();
            $table->decimal('unemployment_rate', 5, 2)->nullable();
            $table->decimal('gdp_billions_euros', 12, 2)->nullable();
            $table->decimal('median_income_euros', 10, 2)->nullable(); // Revenu médian
            $table->decimal('poverty_rate', 5, 2)->nullable(); // Taux de pauvreté
            
            $table->json('additional_data')->nullable(); // Données supplémentaires
            $table->timestamps();
            
            $table->unique(['year', 'region_code']);
        });

        // ========================================
        // 8. DONNÉES DÉPARTEMENTALES
        // ========================================
        Schema::create('france_departmental_data', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('department_code', 3); // Code département (01, 02, ..., 2A, 2B)
            $table->string('department_name', 100);
            $table->string('region_code', 3);
            
            $table->bigInteger('population')->nullable();
            $table->decimal('unemployment_rate', 5, 2)->nullable();
            $table->decimal('median_income_euros', 10, 2)->nullable();
            $table->decimal('poverty_rate', 5, 2)->nullable();
            
            $table->json('additional_data')->nullable();
            $table->timestamps();
            
            $table->unique(['year', 'department_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('france_departmental_data');
        Schema::dropIfExists('france_regional_data');
        Schema::dropIfExists('france_lost_revenue');
        Schema::dropIfExists('france_budget_spending');
        Schema::dropIfExists('france_budget_revenue');
        Schema::dropIfExists('france_migration');
        Schema::dropIfExists('france_economy');
        Schema::dropIfExists('france_demographics');
    }
};
