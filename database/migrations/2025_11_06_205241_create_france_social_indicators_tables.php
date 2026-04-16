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
        // 1. ÉDUCATION & COMPÉTENCES
        // ========================================
        Schema::create('france_education', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();

            // Illettrisme et compétences de base
            $table->decimal('illiteracy_rate', 5, 2)->nullable(); // Taux d'illettrisme (%)
            $table->decimal('numeracy_rate', 5, 2)->nullable(); // Taux d'innumérisme (%)

            // Niveau de diplôme (% de la population 25-64 ans)
            $table->decimal('no_diploma_percentage', 5, 2)->nullable();
            $table->decimal('brevet_percentage', 5, 2)->nullable();
            $table->decimal('cap_bep_percentage', 5, 2)->nullable();
            $table->decimal('bac_percentage', 5, 2)->nullable();
            $table->decimal('bac_plus_2_percentage', 5, 2)->nullable();
            $table->decimal('bac_plus_3_percentage', 5, 2)->nullable();
            $table->decimal('bac_plus_5_percentage', 5, 2)->nullable();
            $table->decimal('bac_plus_8_percentage', 5, 2)->nullable(); // Doctorat

            // Scolarisation et réussite
            $table->decimal('school_enrollment_rate', 5, 2)->nullable(); // Taux de scolarisation 3-18 ans
            $table->decimal('bac_success_rate', 5, 2)->nullable(); // Taux de réussite au Bac
            $table->decimal('dropout_rate', 5, 2)->nullable(); // Décrochage scolaire
            $table->decimal('neet_rate', 5, 2)->nullable(); // NEET 15-29 ans (ni emploi, ni études, ni formation)

            // Enseignement supérieur
            $table->integer('university_students')->nullable(); // Nombre d'étudiants
            $table->decimal('higher_education_access_rate', 5, 2)->nullable(); // Taux d'accès

            $table->text('sources')->nullable();
            $table->timestamps();
        });

        // ========================================
        // 2. SANTÉ
        // ========================================
        Schema::create('france_health', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();

            // Accès aux soins
            $table->decimal('doctors_per_100k', 8, 2)->nullable(); // Médecins pour 100k habitants
            $table->decimal('nurses_per_100k', 8, 2)->nullable(); // Infirmiers pour 100k habitants
            $table->decimal('hospital_beds_per_1k', 5, 2)->nullable(); // Lits d'hôpital pour 1000 habitants
            $table->decimal('medical_desert_population_percentage', 5, 2)->nullable(); // % pop en désert médical

            // Dépenses de santé
            $table->decimal('health_spending_per_capita_euros', 10, 2)->nullable();
            $table->decimal('health_spending_gdp_percentage', 5, 2)->nullable();
            $table->decimal('out_of_pocket_health_spending_percentage', 5, 2)->nullable(); // Reste à charge

            // Vaccination et prévention
            $table->decimal('vaccination_rate_children', 5, 2)->nullable(); // Couverture vaccinale enfants
            $table->decimal('flu_vaccination_rate_elderly', 5, 2)->nullable(); // Grippe 65+
            $table->decimal('cancer_screening_rate', 5, 2)->nullable(); // Dépistage cancers

            // Santé mentale
            $table->decimal('depression_rate', 5, 2)->nullable(); // % population
            $table->decimal('psychiatrists_per_100k', 5, 2)->nullable();
            $table->integer('suicide_rate_per_100k')->nullable();

            // Addictions
            $table->decimal('smoking_rate', 5, 2)->nullable(); // % fumeurs quotidiens
            $table->decimal('alcohol_consumption_liters', 5, 2)->nullable(); // Litres/an/habitant

            $table->text('sources')->nullable();
            $table->timestamps();
        });

        // ========================================
        // 3. LOGEMENT
        // ========================================
        Schema::create('france_housing', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();

            // Propriété et location
            $table->decimal('homeownership_rate', 5, 2)->nullable(); // Taux de propriétaires
            $table->decimal('rental_rate', 5, 2)->nullable(); // Taux de locataires
            $table->decimal('social_housing_rate', 5, 2)->nullable(); // % logements sociaux

            // Prix et accessibilité
            $table->decimal('average_price_per_sqm_euros', 10, 2)->nullable(); // Prix moyen m² France
            $table->decimal('paris_price_per_sqm_euros', 10, 2)->nullable(); // Prix m² Paris
            $table->decimal('rent_to_income_ratio', 5, 2)->nullable(); // Taux d'effort locatif (%)

            // Mal-logement et précarité
            $table->integer('homeless_people')->nullable(); // Personnes sans domicile
            $table->integer('poorly_housed_people')->nullable(); // Mal-logés
            $table->decimal('overcrowding_rate', 5, 2)->nullable(); // Surpeuplement
            $table->decimal('energy_poverty_rate', 5, 2)->nullable(); // Précarité énergétique

            // Construction
            $table->integer('new_housing_units')->nullable(); // Logements neufs construits
            $table->decimal('vacant_housing_rate', 5, 2)->nullable(); // Logements vacants

            $table->text('sources')->nullable();
            $table->timestamps();
        });

        // ========================================
        // 4. ENVIRONNEMENT
        // ========================================
        Schema::create('france_environment', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();

            // Émissions et climat
            $table->decimal('co2_emissions_per_capita_tons', 8, 2)->nullable();
            $table->decimal('total_co2_emissions_mt', 10, 2)->nullable(); // Mégatonnes
            $table->decimal('renewable_energy_percentage', 5, 2)->nullable(); // % énergies renouvelables
            $table->decimal('nuclear_energy_percentage', 5, 2)->nullable();

            // Qualité de l'air
            $table->integer('pollution_days')->nullable(); // Jours de pollution/an
            $table->decimal('pm25_concentration', 8, 2)->nullable(); // Particules fines µg/m³
            $table->integer('air_quality_deaths')->nullable(); // Décès liés pollution air

            // Déchets et recyclage
            $table->decimal('waste_per_capita_kg', 8, 2)->nullable(); // Déchets kg/hab/an
            $table->decimal('recycling_rate', 5, 2)->nullable(); // Taux de recyclage
            $table->decimal('plastic_recycling_rate', 5, 2)->nullable();

            // Biodiversité et espaces naturels
            $table->decimal('protected_areas_percentage', 5, 2)->nullable(); // % territoire protégé
            $table->decimal('forest_coverage_percentage', 5, 2)->nullable(); // Couverture forestière
            $table->integer('endangered_species')->nullable(); // Espèces menacées

            // Eau
            $table->decimal('water_quality_index', 5, 2)->nullable(); // Qualité eau potable
            $table->decimal('water_consumption_per_capita_m3', 8, 2)->nullable();

            $table->text('sources')->nullable();
            $table->timestamps();
        });

        // ========================================
        // 5. SÉCURITÉ & JUSTICE
        // ========================================
        Schema::create('france_security', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();

            // Criminalité générale
            $table->decimal('crime_rate_per_1000', 8, 2)->nullable(); // Crimes pour 1000 habitants
            $table->integer('total_crimes')->nullable();
            $table->integer('violent_crimes')->nullable();
            $table->integer('property_crimes')->nullable();

            // Violences spécifiques
            $table->integer('homicides')->nullable();
            $table->integer('feminicides')->nullable(); // 💜 TRÈS IMPORTANT
            $table->integer('domestic_violence_reports')->nullable(); // Violences conjugales
            $table->integer('sexual_assault_reports')->nullable();
            $table->integer('rape_reports')->nullable();

            // Sentiment de sécurité
            $table->decimal('feeling_safe_percentage', 5, 2)->nullable(); // % se sentant en sécurité
            $table->decimal('feeling_safe_night_percentage', 5, 2)->nullable(); // La nuit

            // Justice
            $table->integer('prison_population')->nullable();
            $table->decimal('prison_occupancy_rate', 5, 2)->nullable(); // Taux d'occupation
            $table->decimal('recidivism_rate', 5, 2)->nullable(); // Taux de récidive

            // Police et moyens
            $table->decimal('police_per_100k', 8, 2)->nullable(); // Policiers pour 100k habitants
            $table->decimal('police_budget_billions_euros', 10, 2)->nullable();

            $table->text('sources')->nullable();
            $table->timestamps();
        });

        // ========================================
        // 6. EMPLOI DÉTAILLÉ
        // ========================================
        Schema::create('france_employment_detailed', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();

            // Types de contrats
            $table->decimal('cdi_percentage', 5, 2)->nullable(); // CDI
            $table->decimal('cdd_percentage', 5, 2)->nullable(); // CDD
            $table->decimal('interim_percentage', 5, 2)->nullable(); // Intérim
            $table->decimal('self_employed_percentage', 5, 2)->nullable(); // Indépendants

            // Temps de travail
            $table->decimal('full_time_percentage', 5, 2)->nullable();
            $table->decimal('part_time_percentage', 5, 2)->nullable();
            $table->decimal('involuntary_part_time_percentage', 5, 2)->nullable(); // Temps partiel subi
            $table->decimal('average_weekly_hours', 5, 2)->nullable();

            // Salaires par secteur (médian mensuel net)
            $table->decimal('median_salary_private_sector', 10, 2)->nullable();
            $table->decimal('median_salary_public_sector', 10, 2)->nullable();
            $table->decimal('median_salary_agriculture', 10, 2)->nullable();
            $table->decimal('median_salary_industry', 10, 2)->nullable();
            $table->decimal('median_salary_construction', 10, 2)->nullable();
            $table->decimal('median_salary_services', 10, 2)->nullable();
            $table->decimal('median_salary_tech', 10, 2)->nullable();

            // Inégalités salariales
            $table->decimal('gender_pay_gap_percentage', 5, 2)->nullable(); // Écart salarial H/F
            $table->decimal('executive_worker_pay_ratio', 8, 2)->nullable(); // Ratio cadre/ouvrier

            // Chômage détaillé
            $table->decimal('youth_unemployment_rate', 5, 2)->nullable(); // 15-24 ans
            $table->decimal('senior_unemployment_rate', 5, 2)->nullable(); // 55+ ans
            $table->decimal('long_term_unemployment_rate', 5, 2)->nullable(); // > 1 an

            // Conditions de travail
            $table->decimal('workplace_accident_rate', 5, 2)->nullable(); // Accidents du travail
            $table->decimal('burnout_rate', 5, 2)->nullable(); // Burn-out
            $table->decimal('telework_percentage', 5, 2)->nullable(); // Télétravail

            $table->text('sources')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('france_employment_detailed');
        Schema::dropIfExists('france_security');
        Schema::dropIfExists('france_environment');
        Schema::dropIfExists('france_housing');
        Schema::dropIfExists('france_health');
        Schema::dropIfExists('france_education');
    }
};
