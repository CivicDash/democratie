<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Décrit les champs éditables d'une table de statistiques à partir du schéma.
 *
 * Avant cette classe, une valeur saisie dans l'administration traversait trois
 * nommages indépendants — les clés du useForm() Vue, celles du validate() du
 * contrôleur, les colonnes réelles — et rien ne déclarait qu'ils devaient coïncider.
 * Ils avaient dérivé : sur soixante-quatre champs de saisie répartis sur dix écrans,
 * dix-neuf seulement atteignaient la base. Quatre écrans n'écrivaient rien du tout,
 * en affichant « mises à jour ».
 *
 * Faire dériver la liste des champs ET les règles de validation de la même source
 * — le schéma — rend cette divergence impossible plutôt qu'improbable. Ajouter une
 * colonne à la table l'expose au formulaire ; en retirer une la retire des deux côtés.
 */
final class ChampsStatistiques
{
    /**
     * Colonnes jamais éditables à la main : clés, horodatages, agrégats JSON
     * alimentés par les imports.
     */
    private const EXCLUES = ['id', 'created_at', 'updated_at', 'deleted_at',
        'population_by_age_group', 'population_by_gender', 'detailed_breakdown', 'metadata', 'quarter'];

    /**
     * Libellés français. Une colonne absente de cette table reste éditable : elle
     * s'affiche sous une forme lisible dérivée de son nom, et le jour où quelqu'un
     * ajoute une colonne, l'écran ne l'ignore pas en silence.
     */
    private const LIBELLES = [
        // Démographie
        'population_total' => 'Population totale',
        'birth_rate' => 'Taux de natalité',
        'death_rate' => 'Taux de mortalité',
        'life_expectancy_male' => 'Espérance de vie — hommes',
        'life_expectancy_female' => 'Espérance de vie — femmes',
        'median_salary_euros' => 'Salaire médian',

        // Économie
        'quarter' => 'Trimestre (vide pour une donnée annuelle)',
        'gdp_billions_euros' => 'PIB',
        'gdp_growth_rate' => 'Croissance du PIB',
        'gdp_per_capita_euros' => 'PIB par habitant',
        'unemployment_rate' => 'Taux de chômage',
        'inflation_rate' => 'Inflation',
        'food_inflation_rate' => 'Inflation alimentaire',
        'energy_inflation_rate' => 'Inflation énergie',
        'services_inflation_rate' => 'Inflation services',
        'public_debt_billions_euros' => 'Dette publique',
        'public_debt_gdp_percentage' => 'Dette publique rapportée au PIB',
        'trade_balance_billions_euros' => 'Balance commerciale',
        'exports_billions_euros' => 'Exportations',
        'imports_billions_euros' => 'Importations',

        // Budget de l'État
        'recettes_nettes' => 'Recettes nettes',
        'depenses_nettes' => 'Dépenses nettes',
        'deficit' => 'Déficit',
        'dette_publique' => 'Dette publique',
        'pib' => 'PIB',
        'deficit_pib_pct' => 'Déficit rapporté au PIB',
        'dette_pib_pct' => 'Dette rapportée au PIB',

        // Recettes
        'total_billions_euros' => 'Total',
        'tva_billions_euros' => 'TVA',
        'income_tax_billions_euros' => 'Impôt sur le revenu',
        'corporate_tax_billions_euros' => 'Impôt sur les sociétés',
        'property_tax_billions_euros' => 'Taxe foncière',
        'housing_tax_billions_euros' => "Taxe d'habitation",
        'fuel_tax_billions_euros' => 'Taxes sur les carburants',
        'social_contributions_billions_euros' => 'Cotisations sociales',
        'other_taxes_billions_euros' => 'Autres prélèvements',

        // Dépenses
        'health_billions_euros' => 'Santé',
        'education_billions_euros' => 'Éducation',
        'security_defense_billions_euros' => 'Sécurité et défense',
        'justice_billions_euros' => 'Justice',
        'social_welfare_billions_euros' => 'Protection sociale',
        'unemployment_billions_euros' => 'Indemnisation du chômage',
        'pensions_billions_euros' => 'Retraites',
        'business_subsidies_billions_euros' => 'Aides aux entreprises',
        'infrastructure_billions_euros' => 'Infrastructures et logement',
        'environment_billions_euros' => 'Environnement',
        'culture_billions_euros' => 'Culture',
        'debt_interest_billions_euros' => 'Charge de la dette',
        'other_spending_billions_euros' => 'Autres dépenses',

        // Éducation
        'illiteracy_rate' => "Taux d'illettrisme",
        'numeracy_rate' => 'Maîtrise du calcul',
        'no_diploma_percentage' => 'Sans diplôme',
        'brevet_percentage' => 'Brevet',
        'cap_bep_percentage' => 'CAP ou BEP',
        'bac_percentage' => 'Baccalauréat',
        'bac_plus_2_percentage' => 'Bac +2',
        'bac_plus_3_percentage' => 'Bac +3',
        'bac_plus_5_percentage' => 'Bac +5',
        'bac_plus_8_percentage' => 'Bac +8 (doctorat)',
        'school_enrollment_rate' => 'Taux de scolarisation',
        'bac_success_rate' => 'Réussite au baccalauréat',
        'dropout_rate' => 'Décrochage scolaire',
        'neet_rate' => 'Jeunes ni en emploi, ni en études, ni en formation',
        'university_students' => 'Étudiants à l\'université',
        'higher_education_access_rate' => "Accès à l'enseignement supérieur",

        // Santé
        'doctors_per_100k' => 'Médecins pour 100 000 habitants',
        'nurses_per_100k' => 'Infirmiers pour 100 000 habitants',
        'hospital_beds_per_1k' => 'Lits d\'hôpital pour 1 000 habitants',
        'medical_desert_population_percentage' => 'Population en désert médical',
        'health_spending_per_capita_euros' => 'Dépense de santé par habitant',
        'health_spending_gdp_percentage' => 'Dépense de santé rapportée au PIB',
        'out_of_pocket_health_spending_percentage' => 'Reste à charge des ménages',
        'vaccination_rate_children' => 'Couverture vaccinale des enfants',
        'flu_vaccination_rate_elderly' => 'Vaccination antigrippale des aînés',
        'cancer_screening_rate' => 'Participation au dépistage des cancers',
        'depression_rate' => 'Prévalence de la dépression',
        'psychiatrists_per_100k' => 'Psychiatres pour 100 000 habitants',
        'suicide_rate_per_100k' => 'Suicides pour 100 000 habitants',
        'smoking_rate' => 'Tabagisme quotidien',
        'alcohol_consumption_liters' => "Consommation d'alcool par habitant",

        // Environnement
        'co2_emissions_per_capita_tons' => 'Émissions de CO₂ par habitant',
        'total_co2_emissions_mt' => 'Émissions de CO₂ totales',
        'renewable_energy_percentage' => 'Part des énergies renouvelables',
        'nuclear_energy_percentage' => 'Part du nucléaire',
        'pollution_days' => 'Jours de dépassement des seuils de pollution',
        'pm25_concentration' => 'Concentration moyenne en PM2,5',
        'air_quality_deaths' => 'Décès attribués à la pollution de l\'air',
        'waste_per_capita_kg' => 'Déchets par habitant',
        'recycling_rate' => 'Taux de recyclage',
        'plastic_recycling_rate' => 'Recyclage des plastiques',
        'protected_areas_percentage' => 'Territoire en aire protégée',
        'forest_coverage_percentage' => 'Couverture forestière',
        'endangered_species' => 'Espèces menacées',
        'water_quality_index' => "Indice de qualité de l'eau",
        'water_consumption_per_capita_m3' => "Consommation d'eau par habitant",

        // Sécurité
        'crime_rate_per_1000' => 'Faits constatés pour 1 000 habitants',
        'total_crimes' => 'Crimes et délits enregistrés',
        'violent_crimes' => 'Atteintes volontaires à l\'intégrité physique',
        'property_crimes' => 'Atteintes aux biens',
        'homicides' => 'Homicides',
        'feminicides' => 'Féminicides',
        'domestic_violence_reports' => 'Plaintes pour violences conjugales',
        'sexual_assault_reports' => 'Plaintes pour agression sexuelle',
        'rape_reports' => 'Plaintes pour viol',
        'feeling_safe_percentage' => 'Sentiment de sécurité',
        'feeling_safe_night_percentage' => 'Sentiment de sécurité la nuit',
        'prison_population' => 'Population carcérale',
        'prison_occupancy_rate' => 'Taux d\'occupation des prisons',
        'recidivism_rate' => 'Taux de récidive',
        'police_per_100k' => 'Forces de l\'ordre pour 100 000 habitants',
        'police_budget_billions_euros' => 'Budget des forces de l\'ordre',

        // Emploi
        'cdi_percentage' => 'Part des CDI',
        'cdd_percentage' => 'Part des CDD',
        'interim_percentage' => "Part de l'intérim",
        'self_employed_percentage' => 'Part des indépendants',
        'full_time_percentage' => 'Temps plein',
        'part_time_percentage' => 'Temps partiel',
        'involuntary_part_time_percentage' => 'Temps partiel subi',
        'average_weekly_hours' => 'Durée hebdomadaire moyenne',
        'median_salary_private_sector' => 'Salaire médian — privé',
        'median_salary_public_sector' => 'Salaire médian — public',
        'median_salary_agriculture' => 'Salaire médian — agriculture',
        'median_salary_industry' => 'Salaire médian — industrie',
        'median_salary_construction' => 'Salaire médian — construction',
        'median_salary_services' => 'Salaire médian — services',
        'median_salary_tech' => 'Salaire médian — numérique',
        'gender_pay_gap_percentage' => 'Écart de salaire femmes-hommes',
        'executive_worker_pay_ratio' => 'Rapport cadre / ouvrier',
        'youth_unemployment_rate' => 'Chômage des moins de 25 ans',
        'senior_unemployment_rate' => 'Chômage des seniors',
        'long_term_unemployment_rate' => 'Chômage de longue durée',
        'workplace_accident_rate' => 'Accidents du travail',
        'burnout_rate' => 'Épuisement professionnel',
        'telework_percentage' => 'Télétravail',

        'sources' => 'Sources',
    ];

    /**
     * Les champs éditables du modèle, prêts à être rendus par le formulaire.
     *
     * @return array<int, array{nom: string, libelle: string, type: string, suffixe: ?string, pas: ?string}>
     */
    public static function pour(string $modele, ?string $cleAnnee = null): array
    {
        $instance = new $modele;
        $cleAnnee ??= self::cleAnnee($instance);

        return array_values(array_map(
            fn (string $colonne) => self::decrire($colonne, self::typeSql($instance, $colonne)),
            self::colonnesEditables($instance, $cleAnnee),
        ));
    }

    /**
     * Les règles de validation, dérivées des mêmes colonnes et de leur type SQL.
     *
     * @return array<string, string>
     */
    public static function regles(string $modele, ?string $cleAnnee = null): array
    {
        $instance = new $modele;
        $cleAnnee ??= self::cleAnnee($instance);
        $regles = [];

        foreach (self::colonnesEditables($instance, $cleAnnee) as $colonne) {
            $regles[$colonne] = match (self::typeSql($instance, $colonne)) {
                'integer', 'bigint', 'smallint' => 'nullable|integer',
                'text', 'varchar', 'string' => 'nullable|string|max:5000',
                default => 'nullable|numeric',
            };
        }

        return $regles;
    }

    /** Nom de la colonne portant le millésime : `year` partout sauf budget_annuel. */
    public static function cleAnnee(Model $instance): string
    {
        return Schema::hasColumn($instance->getTable(), 'year') ? 'year' : 'annee';
    }

    /** @return array<int, string> */
    private static function colonnesEditables(Model $instance, string $cleAnnee): array
    {
        $table = $instance->getTable();

        $colonnes = Cache::remember(
            "champs_stats_{$table}",
            now()->addHour(),
            fn () => Schema::getColumnListing($table),
        );

        $fillable = $instance->getFillable();

        return array_values(array_filter(
            $colonnes,
            fn (string $c) => $c !== $cleAnnee
                && ! in_array($c, self::EXCLUES, true)
                // Le $fillable fait foi côté modèle : une colonne qui n'y est pas ne
                // pourrait de toute façon pas être écrite.
                && ($fillable === [] || in_array($c, $fillable, true)),
        ));
    }

    private static function typeSql(Model $instance, string $colonne): string
    {
        $table = $instance->getTable();

        $types = Cache::remember(
            "types_stats_{$table}",
            now()->addHour(),
            fn () => collect(Schema::getColumns($table))->pluck('type_name', 'name')->all(),
        );

        return $types[$colonne] ?? 'numeric';
    }

    /** @return array{nom: string, libelle: string, type: string, suffixe: ?string, pas: ?string} */
    private static function decrire(string $colonne, string $typeSql): array
    {
        [$type, $suffixe, $pas] = match (true) {
            in_array($typeSql, ['text', 'varchar', 'string'], true) => ['texte', null, null],
            str_ends_with($colonne, '_percentage'),
            str_ends_with($colonne, '_rate'),
            str_ends_with($colonne, '_pct') => ['nombre', '%', '0.1'],
            str_ends_with($colonne, '_billions_euros'),
            in_array($colonne, ['recettes_nettes', 'depenses_nettes', 'deficit', 'dette_publique', 'pib'], true) => ['nombre', 'Md€', '0.01'],
            str_ends_with($colonne, '_euros') => ['nombre', '€', '1'],
            str_ends_with($colonne, '_mt') => ['nombre', 'Mt', '0.1'],
            str_ends_with($colonne, '_kg') => ['nombre', 'kg', '0.1'],
            str_ends_with($colonne, '_m3') => ['nombre', 'm³', '0.1'],
            str_ends_with($colonne, '_liters') => ['nombre', 'L', '0.1'],
            str_ends_with($colonne, '_tons') => ['nombre', 't', '0.01'],
            in_array($typeSql, ['int4', 'integer', 'int8', 'bigint', 'smallint'], true) => ['nombre', null, '1'],
            default => ['nombre', null, '0.01'],
        };

        return [
            'nom' => $colonne,
            'libelle' => self::LIBELLES[$colonne] ?? Str::ucfirst(str_replace('_', ' ', $colonne)),
            'type' => $type,
            'suffixe' => $suffixe,
            'pas' => $pas,
        ];
    }
}
