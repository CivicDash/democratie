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
        // Ajouter des indicateurs économiques et sociaux supplémentaires
        
        // Table démographie - Ajouter salaire médian
        Schema::table('france_demographics', function (Blueprint $table) {
            $table->decimal('median_salary_euros', 10, 2)->nullable()->after('life_expectancy_female');
        });

        // Table économie - Ajouter PIB par habitant, inflation détaillée
        Schema::table('france_economy', function (Blueprint $table) {
            $table->decimal('gdp_per_capita_euros', 10, 2)->nullable()->after('imports_billions_euros');
            $table->decimal('food_inflation_rate', 5, 2)->nullable()->after('inflation_rate');
            $table->decimal('energy_inflation_rate', 5, 2)->nullable()->after('food_inflation_rate');
            $table->decimal('services_inflation_rate', 5, 2)->nullable()->after('energy_inflation_rate');
        });

        // Nouvelle table pour les indicateurs de qualité de vie
        Schema::create('france_quality_of_life', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            
            // Indicateur de Développement Humain (IDH)
            $table->decimal('hdi_score', 5, 4)->nullable(); // Échelle 0-1
            $table->integer('hdi_world_rank')->nullable();
            $table->decimal('hdi_life_expectancy', 5, 2)->nullable();
            $table->decimal('hdi_education_index', 5, 4)->nullable();
            $table->decimal('hdi_income_index', 5, 4)->nullable();
            
            // Bonheur National Brut (BNB) / Indice de bien-être
            $table->decimal('happiness_score', 5, 3)->nullable(); // Échelle 0-10 (World Happiness Report)
            $table->integer('happiness_world_rank')->nullable();
            $table->decimal('life_satisfaction', 5, 2)->nullable(); // Satisfaction de vie (0-10)
            $table->decimal('work_life_balance', 5, 2)->nullable(); // Équilibre vie pro/perso (0-10)
            $table->decimal('social_connections', 5, 2)->nullable(); // Liens sociaux (0-10)
            
            // Indice Big Mac (parité pouvoir d'achat)
            $table->decimal('big_mac_price_euros', 5, 2)->nullable();
            $table->decimal('big_mac_index', 5, 2)->nullable(); // % de surévaluation/sous-évaluation vs USD
            $table->decimal('big_mac_ppp_rate', 5, 3)->nullable(); // Taux de change PPA implicite
            
            // Autres indicateurs de bien-être
            $table->decimal('gini_coefficient', 5, 3)->nullable(); // Coefficient de Gini (inégalités)
            $table->decimal('disposable_income_euros', 10, 2)->nullable(); // Revenu disponible moyen
            $table->decimal('housing_cost_percentage', 5, 2)->nullable(); // % revenu pour logement
            $table->decimal('life_expectancy_at_birth', 5, 2)->nullable();
            
            $table->text('sources')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('france_quality_of_life');
        
        Schema::table('france_economy', function (Blueprint $table) {
            $table->dropColumn([
                'gdp_per_capita_euros',
                'food_inflation_rate',
                'energy_inflation_rate',
                'services_inflation_rate'
            ]);
        });
        
        Schema::table('france_demographics', function (Blueprint $table) {
            $table->dropColumn('median_salary_euros');
        });
    }
};
