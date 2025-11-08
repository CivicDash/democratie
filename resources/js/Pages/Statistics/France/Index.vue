<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import FranceMap from '@/Components/Statistics/FranceMap.vue';
import FranceMapInteractive from '@/Components/Statistics/FranceMapInteractive.vue';
import RegionDetailModal from '@/Components/Statistics/RegionDetailModal.vue';
import { Line, Bar, Doughnut, Pie } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

// Enregistrer les composants Chart.js
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    selectedYear: Number,
    availableYears: Array,
    demographics: Object,
    economyAnnual: Object,
    economyQuarterly: Array,
    migration: Object,
    revenue: Object,
    spending: Object,
    lostRevenue: Object,
    regionalData: Array,
    demographicsHistory: Array,
    economyHistory: Array,
    budgetRevenueHistory: Array,
    budgetSpendingHistory: Array,
    lostRevenueHistory: Array,
    // Nouveaux indicateurs sociaux
    qualityOfLife: Object,
    education: Object,
    health: Object,
    housing: Object,
    environment: Object,
    security: Object,
    employmentDetailed: Object,
    educationHistory: Array,
    healthHistory: Array,
    housingHistory: Array,
    environmentHistory: Array,
    securityHistory: Array,
    employmentDetailedHistory: Array,
    qualityOfLifeHistory: Array,
});

const selectedYear = ref(props.selectedYear);
const activeTab = ref('overview'); // overview, economy, budget, migration, regions, quality, education, health, housing, environment, security, employment

// Carte interactive
const selectedRegion = ref(null);
const showRegionModal = ref(false);
const heatmapMetric = ref('unemployment_rate'); // unemployment_rate, poverty_rate, gdp_billions_euros

// Changer d'année
const changeYear = (year) => {
    router.get(route('statistics.france'), { year }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Gérer la sélection d'une région
const handleRegionSelected = (region) => {
    selectedRegion.value = region;
    showRegionModal.value = true;
};

// Gérer la sélection d'un département
const handleDepartmentSelected = (department) => {
    console.log('Département sélectionné:', department);
    // TODO: Afficher un modal ou naviguer vers les détails du département
    // Pour l'instant, on pourrait trouver la région correspondante
};

// Fermer le modal
const closeRegionModal = () => {
    showRegionModal.value = false;
    selectedRegion.value = null;
};

// ========================================
// GRAPHIQUES - DÉMOGRAPHIE
// ========================================

const populationChartData = computed(() => {
    if (!props.demographicsHistory || props.demographicsHistory.length === 0) return null;
    
    return {
        labels: props.demographicsHistory.map(d => d.year),
        datasets: [{
            label: 'Population totale (millions)',
            data: props.demographicsHistory.map(d => (d.population_total / 1000000).toFixed(2)),
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

const ageGroupChartData = computed(() => {
    if (!props.demographics?.population_by_age_group) return null;
    
    const data = props.demographics.population_by_age_group;
    return {
        labels: Object.keys(data),
        datasets: [{
            label: 'Population par tranche d\'âge',
            data: Object.values(data).map(v => (v / 1000000).toFixed(2)),
            backgroundColor: [
                '#3B82F6',
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#8B5CF6',
                '#EC4899',
                '#6366F1',
            ],
        }]
    };
});

// ========================================
// GRAPHIQUES - ÉCONOMIE
// ========================================

const gdpGrowthChartData = computed(() => {
    if (!props.economyHistory || props.economyHistory.length === 0) return null;
    
    return {
        labels: props.economyHistory.map(e => e.year),
        datasets: [{
            label: 'Croissance du PIB (%)',
            data: props.economyHistory.map(e => e.gdp_growth_rate),
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

const quarterlyGdpChartData = computed(() => {
    if (!props.economyQuarterly || props.economyQuarterly.length === 0) return null;
    
    return {
        labels: props.economyQuarterly.map(e => `T${e.quarter}`),
        datasets: [{
            label: 'PIB (Md€)',
            data: props.economyQuarterly.map(e => e.gdp_billions_euros),
            backgroundColor: '#3B82F6',
        }]
    };
});

const unemploymentInflationChartData = computed(() => {
    if (!props.economyHistory || props.economyHistory.length === 0) return null;
    
    return {
        labels: props.economyHistory.map(e => e.year),
        datasets: [
            {
                label: 'Taux de chômage (%)',
                data: props.economyHistory.map(e => e.unemployment_rate),
                borderColor: '#EF4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                yAxisID: 'y',
            },
            {
                label: 'Taux d\'inflation (%)',
                data: props.economyHistory.map(e => e.inflation_rate),
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                yAxisID: 'y',
            }
        ]
    };
});

// ========================================
// GRAPHIQUES - BUDGET
// ========================================

const revenueBreakdownChartData = computed(() => {
    if (!props.revenue) return null;
    
    return {
        labels: ['TVA', 'Impôt sur le revenu', 'Impôt sociétés', 'Taxe foncière', 'TICPE', 'Cotisations sociales', 'Autres'],
        datasets: [{
            label: 'Recettes (Md€)',
            data: [
                props.revenue.tva_billions_euros,
                props.revenue.income_tax_billions_euros,
                props.revenue.corporate_tax_billions_euros,
                props.revenue.property_tax_billions_euros,
                props.revenue.fuel_tax_billions_euros,
                props.revenue.social_contributions_billions_euros,
                props.revenue.other_taxes_billions_euros,
            ],
            backgroundColor: [
                '#3B82F6',
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#8B5CF6',
                '#EC4899',
                '#6366F1',
            ],
        }]
    };
});

const spendingBreakdownChartData = computed(() => {
    if (!props.spending) return null;
    
    return {
        labels: [
            'Santé',
            'Éducation',
            'Retraites',
            'Défense & Sécurité',
            'Aide sociale',
            'Subventions entreprises',
            'Intérêts dette',
            'Chômage',
            'Infrastructures',
            'Environnement',
            'Autres'
        ],
        datasets: [{
            label: 'Dépenses (Md€)',
            data: [
                props.spending.health_billions_euros,
                props.spending.education_billions_euros,
                props.spending.pensions_billions_euros,
                props.spending.security_defense_billions_euros,
                props.spending.social_welfare_billions_euros,
                props.spending.business_subsidies_billions_euros,
                props.spending.debt_interest_billions_euros,
                props.spending.unemployment_billions_euros,
                props.spending.infrastructure_billions_euros,
                props.spending.environment_billions_euros,
                props.spending.other_spending_billions_euros,
            ],
            backgroundColor: [
                '#EF4444',
                '#3B82F6',
                '#8B5CF6',
                '#F59E0B',
                '#10B981',
                '#EC4899',
                '#6366F1',
                '#F97316',
                '#14B8A6',
                '#22C55E',
                '#94A3B8',
            ],
        }]
    };
});

const budgetBalanceChartData = computed(() => {
    if (!props.budgetRevenueHistory || !props.budgetSpendingHistory) return null;
    
    return {
        labels: props.budgetRevenueHistory.map(r => r.year),
        datasets: [
            {
                label: 'Recettes (Md€)',
                data: props.budgetRevenueHistory.map(r => r.total_billions_euros),
                backgroundColor: '#10B981',
            },
            {
                label: 'Dépenses (Md€)',
                data: props.budgetSpendingHistory.map(s => s.total_billions_euros),
                backgroundColor: '#EF4444',
            }
        ]
    };
});

// ========================================
// GRAPHIQUES - RECETTES PERDUES
// ========================================

const lostRevenueChartData = computed(() => {
    if (!props.lostRevenue) return null;
    
    return {
        labels: [
            'Fraude TVA',
            'Fraude impôt revenu',
            'Fraude impôt sociétés',
            'Fraude sociale',
            'Évasion fiscale',
            'Optimisation fiscale',
            'Paradis fiscaux'
        ],
        datasets: [{
            label: 'Recettes perdues (Md€)',
            data: [
                props.lostRevenue.vat_fraud_billions_euros,
                props.lostRevenue.income_tax_fraud_billions_euros,
                props.lostRevenue.corporate_tax_fraud_billions_euros,
                props.lostRevenue.social_fraud_billions_euros,
                props.lostRevenue.tax_evasion_billions_euros,
                props.lostRevenue.tax_optimization_billions_euros,
                props.lostRevenue.offshore_billions_euros,
            ],
            backgroundColor: [
                '#DC2626',
                '#EF4444',
                '#F87171',
                '#FCA5A5',
                '#FEE2E2',
                '#FEF2F2',
                '#FFF5F5',
            ],
        }]
    };
});

const lostRevenueHistoryChartData = computed(() => {
    if (!props.lostRevenueHistory || props.lostRevenueHistory.length === 0) return null;
    
    return {
        labels: props.lostRevenueHistory.map(l => l.year),
        datasets: [{
            label: 'Total recettes perdues (Md€)',
            data: props.lostRevenueHistory.map(l => l.total_lost_billions_euros),
            borderColor: '#DC2626',
            backgroundColor: 'rgba(220, 38, 38, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

// ========================================
// GRAPHIQUES - MIGRATION
// ========================================

const migrationFlowChartData = computed(() => {
    if (!props.migration) return null;
    
    return {
        labels: ['Immigration', 'Émigration', 'Solde migratoire'],
        datasets: [{
            label: 'Flux migratoires',
            data: [
                props.migration.immigration_total,
                props.migration.emigration_total,
                props.migration.net_migration,
            ],
            backgroundColor: ['#10B981', '#EF4444', '#3B82F6'],
        }]
    };
});

// Options des graphiques
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
    },
};

const chartOptionsWithPercentage = {
    ...chartOptions,
    scales: {
        y: {
            ticks: {
                callback: function(value) {
                    return value + '%';
                }
            }
        }
    }
};

// ========================================
// NOUVEAUX GRAPHIQUES - QUALITÉ DE VIE
// ========================================

const idhChartData = computed(() => {
    if (!props.qualityOfLifeHistory || props.qualityOfLifeHistory.length === 0) return null;
    
    return {
        labels: props.qualityOfLifeHistory.map(d => d.year),
        datasets: [{
            label: 'IDH',
            data: props.qualityOfLifeHistory.map(d => d.idh),
            borderColor: '#9333EA',
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

const bnbChartData = computed(() => {
    if (!props.qualityOfLifeHistory || props.qualityOfLifeHistory.length === 0) return null;
    
    return {
        labels: props.qualityOfLifeHistory.map(d => d.year),
        datasets: [{
            label: 'Bonheur National Brut (/10)',
            data: props.qualityOfLifeHistory.map(d => d.bnb),
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

// ========================================
// NOUVEAUX GRAPHIQUES - ÉDUCATION
// ========================================

const educationLevelChartData = computed(() => {
    if (!props.education) return null;
    
    return {
        labels: ['Sans diplôme', 'Bac', 'Bac+2', 'Bac+3', 'Bac+5', 'Bac+8'],
        datasets: [{
            label: 'Pourcentage de la population',
            data: [
                100 - props.education.population_with_bac, // Approximation sans diplôme
                props.education.population_with_bac - props.education.population_with_bac2,
                props.education.population_with_bac2 - props.education.population_with_bac3,
                props.education.population_with_bac3 - props.education.population_with_bac5,
                props.education.population_with_bac5 - props.education.population_with_bac8,
                props.education.population_with_bac8,
            ],
            backgroundColor: [
                '#EF4444',
                '#F59E0B',
                '#FBBF24',
                '#10B981',
                '#3B82F6',
                '#8B5CF6',
            ],
        }]
    };
});

const dropoutChartData = computed(() => {
    if (!props.educationHistory || props.educationHistory.length === 0) return null;
    
    return {
        labels: props.educationHistory.map(d => d.year),
        datasets: [{
            label: 'Taux de décrochage scolaire (%)',
            data: props.educationHistory.map(d => d.school_dropout_rate),
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

// ========================================
// NOUVEAUX GRAPHIQUES - SÉCURITÉ
// ========================================

const crimeChartData = computed(() => {
    if (!props.securityHistory || props.securityHistory.length === 0) return null;
    
    return {
        labels: props.securityHistory.map(d => d.year),
        datasets: [{
            label: 'Taux de criminalité (pour 1000 hab.)',
            data: props.securityHistory.map(d => d.crime_rate),
            borderColor: '#F59E0B',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

const feminicidesChartData = computed(() => {
    if (!props.securityHistory || props.securityHistory.length === 0) return null;
    
    return {
        labels: props.securityHistory.map(d => d.year),
        datasets: [{
            label: 'Féminicides',
            data: props.securityHistory.map(d => d.feminicides),
            backgroundColor: '#DC2626',
            borderColor: '#DC2626',
        }]
    };
});

// ========================================
// NOUVEAUX GRAPHIQUES - EMPLOI
// ========================================

const salaryBySectorChartData = computed(() => {
    if (!props.employmentDetailed) return null;
    
    const sectors = JSON.parse(props.employmentDetailed.median_salary_by_sector || '{}');
    
    return {
        labels: Object.keys(sectors),
        datasets: [{
            label: 'Salaire médian (€)',
            data: Object.values(sectors),
            backgroundColor: [
                '#3B82F6',
                '#10B981',
                '#F59E0B',
                '#8B5CF6',
                '#EC4899',
                '#14B8A6',
            ],
        }]
    };
});

const genderPayGapChartData = computed(() => {
    if (!props.employmentDetailedHistory || props.employmentDetailedHistory.length === 0) return null;
    
    return {
        labels: props.employmentDetailedHistory.map(d => d.year),
        datasets: [{
            label: 'Écart salarial Hommes/Femmes (%)',
            data: props.employmentDetailedHistory.map(d => d.gender_pay_gap),
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

// ========================================
// NOUVEAUX GRAPHIQUES - SANTÉ
// ========================================

const doctorsChartData = computed(() => {
    if (!props.healthHistory || props.healthHistory.length === 0) return null;
    
    return {
        labels: props.healthHistory.map(d => d.year),
        datasets: [{
            label: 'Médecins pour 100k habitants',
            data: props.healthHistory.map(d => d.doctors_per_100k),
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

const healthSpendingChartData = computed(() => {
    if (!props.healthHistory || props.healthHistory.length === 0) return null;
    
    return {
        labels: props.healthHistory.map(d => d.year),
        datasets: [{
            label: 'Dépenses de santé par habitant (€)',
            data: props.healthHistory.map(d => d.health_spending_per_capita_euros),
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

// ========================================
// NOUVEAUX GRAPHIQUES - LOGEMENT
// ========================================

const housingPriceChartData = computed(() => {
    if (!props.housing) return null;
    
    const prices = JSON.parse(props.housing.average_price_per_sqm_by_region || '{}');
    
    return {
        labels: Object.keys(prices).slice(0, 10), // Top 10 régions
        datasets: [{
            label: 'Prix au m² (€)',
            data: Object.values(prices).slice(0, 10),
            backgroundColor: '#F59E0B',
        }]
    };
});

const housingDistributionChartData = computed(() => {
    if (!props.housing) return null;
    
    return {
        labels: ['Propriétaires', 'Locataires', 'Logement social'],
        datasets: [{
            data: [
                props.housing.owner_rate,
                props.housing.tenant_rate,
                props.housing.social_housing_rate,
            ],
            backgroundColor: ['#10B981', '#3B82F6', '#8B5CF6'],
        }]
    };
});

// ========================================
// NOUVEAUX GRAPHIQUES - ENVIRONNEMENT
// ========================================

const co2ChartData = computed(() => {
    if (!props.environmentHistory || props.environmentHistory.length === 0) return null;
    
    return {
        labels: props.environmentHistory.map(d => d.year),
        datasets: [{
            label: 'Émissions CO2 par habitant (tonnes)',
            data: props.environmentHistory.map(d => d.co2_emissions_per_capita_tons),
            borderColor: '#F59E0B',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

const recyclingChartData = computed(() => {
    if (!props.environmentHistory || props.environmentHistory.length === 0) return null;
    
    return {
        labels: props.environmentHistory.map(d => d.year),
        datasets: [{
            label: 'Taux de recyclage (%)',
            data: props.environmentHistory.map(d => d.recycling_rate),
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});
</script>

<template>
    <Head title="Statistiques France" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header Sticky -->
            <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 shadow-md">
                <div class="px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 truncate">
                                📊 Statistiques France
                            </h1>
                            <p class="mt-1 text-sm sm:text-base text-gray-600 dark:text-gray-400 line-clamp-2">
                                Vue d'ensemble des données publiques - INSEE, Ministères, Cour des Comptes
                            </p>
                        </div>
                        
                        <!-- Sélecteur d'année -->
                        <div class="flex-shrink-0">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Année
                            </label>
                            <select
                                v-model="selectedYear"
                                @change="changeYear(selectedYear)"
                                class="w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-sm"
                            >
                                <option v-for="year in availableYears" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabs Desktop + Dropdown Mobile -->
                    <div class="mt-4">
                        <!-- Dropdown pour mobile (< md) -->
                        <div class="block md:hidden">
                            <select
                                v-model="activeTab"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-sm font-medium py-3"
                            >
                                <option value="overview">🏠 Vue d'ensemble</option>
                                <option value="economy">💰 Économie</option>
                                <option value="budget">💶 Budget</option>
                                <option value="migration">🌍 Migration</option>
                                <option value="regions">🗺️ Régions</option>
                                <option value="quality">✨ Qualité de vie</option>
                                <option value="education">📚 Éducation</option>
                                <option value="security">🔒 Sécurité</option>
                                <option value="health">🏥 Santé</option>
                                <option value="housing">🏠 Logement</option>
                                <option value="environment">🌍 Environnement</option>
                                <option value="employment">💼 Emploi</option>
                            </select>
                        </div>

                        <!-- Tabs classiques pour desktop (≥ md) -->
                        <div class="hidden md:block border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex space-x-4 lg:space-x-8 overflow-x-auto">
                                <button
                                    @click="activeTab = 'overview'"
                                    :class="[
                                        activeTab === 'overview'
                                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                    ]"
                                >
                                    🏠 Vue d'ensemble
                                </button>
                                <button
                                    @click="activeTab = 'economy'"
                                    :class="[
                                        activeTab === 'economy'
                                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                    ]"
                                >
                                    💰 Économie
                                </button>
                                <button
                                    @click="activeTab = 'budget'"
                                    :class="[
                                        activeTab === 'budget'
                                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                    ]"
                                >
                                    💶 Budget
                                </button>
                                <button
                                    @click="activeTab = 'migration'"
                                    :class="[
                                        activeTab === 'migration'
                                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                    ]"
                                >
                                    🌍 Migration
                                </button>
                                <button
                                    @click="activeTab = 'regions'"
                                    :class="[
                                        activeTab === 'regions'
                                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                    ]"
                                >
                                    🗺️ Régions
                                </button>
                            <button
                                @click="activeTab = 'quality'"
                                :class="[
                                    activeTab === 'quality'
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                ]"
                            >
                                ✨ Qualité de vie
                            </button>
                            <button
                                @click="activeTab = 'education'"
                                :class="[
                                    activeTab === 'education'
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                ]"
                            >
                                📚 Éducation
                            </button>
                            <button
                                @click="activeTab = 'security'"
                                :class="[
                                    activeTab === 'security'
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                ]"
                            >
                                🔒 Sécurité
                            </button>
                            <button
                                @click="activeTab = 'health'"
                                :class="[
                                    activeTab === 'health'
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                ]"
                            >
                                🏥 Santé
                            </button>
                            <button
                                @click="activeTab = 'housing'"
                                :class="[
                                    activeTab === 'housing'
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                ]"
                            >
                                🏠 Logement
                            </button>
                            <button
                                @click="activeTab = 'environment'"
                                :class="[
                                    activeTab === 'environment'
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                ]"
                            >
                                🌍 Environnement
                            </button>
                            <button
                                @click="activeTab = 'employment'"
                                :class="[
                                    activeTab === 'employment'
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                                ]"
                            >
                                💼 Emploi
                            </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal - Full width avec padding responsive -->
            <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">
                <!-- VUE D'ENSEMBLE -->
                <div v-if="activeTab === 'overview'" class="space-y-6">
                    <!-- KPIs principaux -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">👥</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ (demographics?.population_total / 1000000).toFixed(2) }}M
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Population totale
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">📈</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ economyAnnual?.gdp_growth_rate }}%
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Croissance PIB
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">💼</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ economyAnnual?.unemployment_rate }}%
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Taux de chômage
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">🚨</div>
                                <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                                    {{ lostRevenue?.total_lost_billions_euros }}Md€
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Recettes perdues
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Graphiques overview -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <Card v-if="populationChartData">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                👥 Évolution de la population
                            </h3>
                            <div style="height: 300px;">
                                <Line :data="populationChartData" :options="chartOptions" />
                            </div>
                        </Card>

                        <Card v-if="gdpGrowthChartData">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                📈 Croissance du PIB
                            </h3>
                            <div style="height: 300px;">
                                <Line :data="gdpGrowthChartData" :options="chartOptionsWithPercentage" />
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- ÉCONOMIE -->
                <div v-if="activeTab === 'economy'" class="space-y-6">
                    <!-- KPIs économiques -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">💰</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ economyAnnual?.gdp_billions_euros }}Md€
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    PIB annuel
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">🔥</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ economyAnnual?.inflation_rate }}%
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Inflation
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">⚖️</div>
                                <div class="text-3xl font-bold" :class="economyAnnual?.trade_balance_billions_euros < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                    {{ economyAnnual?.trade_balance_billions_euros }}Md€
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Balance commerciale
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Graphiques économie -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <Card v-if="quarterlyGdpChartData">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                📊 PIB par trimestre {{ selectedYear }}
                            </h3>
                            <div style="height: 300px;">
                                <Bar :data="quarterlyGdpChartData" :options="chartOptions" />
                            </div>
                        </Card>

                        <Card v-if="unemploymentInflationChartData">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                📉 Chômage & Inflation
                            </h3>
                            <div style="height: 300px;">
                                <Line :data="unemploymentInflationChartData" :options="chartOptionsWithPercentage" />
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- BUDGET -->
                <div v-if="activeTab === 'budget'" class="space-y-6">
                    <!-- KPIs budget -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">💶</div>
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ revenue?.total_billions_euros }}Md€
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Recettes totales
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">💸</div>
                                <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                                    {{ spending?.total_billions_euros }}Md€
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Dépenses totales
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">⚠️</div>
                                <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                                    {{ (spending?.total_billions_euros - revenue?.total_billions_euros).toFixed(1) }}Md€
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Déficit
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Graphiques budget -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <Card v-if="revenueBreakdownChartData">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                💰 Répartition des recettes {{ selectedYear }}
                            </h3>
                            <div style="height: 400px;">
                                <Doughnut :data="revenueBreakdownChartData" :options="chartOptions" />
                            </div>
                        </Card>

                        <Card v-if="spendingBreakdownChartData">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                💸 Répartition des dépenses {{ selectedYear }}
                            </h3>
                            <div style="height: 400px;">
                                <Doughnut :data="spendingBreakdownChartData" :options="chartOptions" />
                            </div>
                        </Card>
                    </div>

                    <Card v-if="budgetBalanceChartData">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            ⚖️ Évolution Recettes vs Dépenses
                        </h3>
                        <div style="height: 300px;">
                            <Bar :data="budgetBalanceChartData" :options="chartOptions" />
                        </div>
                    </Card>

                    <!-- RECETTES PERDUES -->
                    <Card class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="text-5xl">🚨</div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-red-900 dark:text-red-100 mb-2">
                                    Recettes perdues : {{ lostRevenue?.total_lost_billions_euros }}Md€
                                </h3>
                                <p class="text-red-700 dark:text-red-300 text-sm mb-4">
                                    {{ lostRevenue?.notes }}
                                </p>
                                <p class="text-xs text-red-600 dark:text-red-400">
                                    📚 Sources : {{ lostRevenue?.sources }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div v-if="lostRevenueChartData" style="height: 350px;">
                                <Bar :data="lostRevenueChartData" :options="chartOptions" />
                            </div>
                            <div v-if="lostRevenueHistoryChartData" style="height: 350px;">
                                <Line :data="lostRevenueHistoryChartData" :options="chartOptions" />
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- MIGRATION -->
                <div v-if="activeTab === 'migration'" class="space-y-6">
                    <!-- KPIs migration -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">📥</div>
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ migration?.immigration_total?.toLocaleString() }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Immigration
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">📤</div>
                                <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                                    {{ migration?.emigration_total?.toLocaleString() }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Émigration
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">⚖️</div>
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ migration?.net_migration?.toLocaleString() }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Solde migratoire
                                </div>
                            </div>
                        </Card>

                        <Card>
                            <div class="text-center">
                                <div class="text-4xl mb-2">🛡️</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ migration?.asylum_requests?.toLocaleString() }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Demandes d'asile
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Graphiques migration -->
                    <Card v-if="migrationFlowChartData">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            🌍 Flux migratoires {{ selectedYear }}
                        </h3>
                        <div style="height: 300px;">
                            <Bar :data="migrationFlowChartData" :options="chartOptions" />
                        </div>
                    </Card>
                </div>

                <!-- RÉGIONS -->
                <div v-if="activeTab === 'regions'" class="space-y-6">
                    <!-- Sélecteur de métrique pour la heatmap -->
                    <Card>
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    🗺️ Carte interactive de France {{ selectedYear }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Cliquez sur une région pour voir ses détails
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Afficher par:
                                </label>
                                <select
                                    v-model="heatmapMetric"
                                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                >
                                    <option value="unemployment_rate">Taux de chômage</option>
                                    <option value="poverty_rate">Taux de pauvreté</option>
                                    <option value="gdp_billions_euros">PIB régional</option>
                                </select>
                            </div>
                        </div>
                    </Card>

                    <!-- Carte interactive -->
                    <Card>
                        <FranceMapInteractive
                            :regional-data="regionalData"
                            :heatmap-metric="heatmapMetric"
                            @department-selected="handleDepartmentSelected"
                        />
                    </Card>

                    <!-- Ancienne carte (fallback) -->
                    <!-- <Card>
                        <FranceMap
                            :regional-data="regionalData"
                            :heatmap-metric="heatmapMetric"
                            @region-selected="handleRegionSelected"
                        />
                    </Card> -->

                    <!-- Liste des régions (en dessous de la carte) -->
                    <Card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            📋 Toutes les régions
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            <button
                                v-for="region in regionalData"
                                :key="region.id"
                                @click="handleRegionSelected(region)"
                                class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md hover:border-indigo-500 dark:hover:border-indigo-600 transition text-left"
                            >
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ region.region_name }}
                                </h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Population:</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ (region.population / 1000000).toFixed(2) }}M
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Chômage:</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ region.unemployment_rate }}%
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">PIB:</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ region.gdp_billions_euros }}Md€
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Revenu médian:</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ region.median_income_euros?.toLocaleString() }}€
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Taux de pauvreté:</span>
                                        <span class="font-medium text-red-600 dark:text-red-400">
                                            {{ region.poverty_rate }}%
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </Card>
                </div>
                
                <!-- Modal détails région -->
                <RegionDetailModal
                    :show="showRegionModal"
                    :region="selectedRegion"
                    @close="closeRegionModal"
                />

                <!-- QUALITÉ DE VIE -->
                <div v-if="activeTab === 'quality'" class="space-y-6">
                    <Card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            ✨ Qualité de vie en France
                        </h2>

                        <!-- Indicateurs principaux -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 mb-8" v-if="qualityOfLife">
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-purple-600 dark:text-purple-400">IDH</p>
                                        <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">
                                            {{ qualityOfLife.idh }}
                                        </p>
                                        <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">
                                            Rang mondial: {{ qualityOfLife.idh_world_rank }}
                                        </p>
                                    </div>
                                    <div class="text-4xl">📊</div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-600 dark:text-green-400">BNB</p>
                                        <p class="text-3xl font-bold text-green-900 dark:text-green-100">
                                            {{ qualityOfLife.bnb }}/10
                                        </p>
                                        <p class="text-xs text-green-700 dark:text-green-300 mt-1">Bonheur National Brut</p>
                                    </div>
                                    <div class="text-4xl">😊</div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/30 rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Big Mac Index</p>
                                        <p class="text-3xl font-bold text-yellow-900 dark:text-yellow-100">
                                            {{ qualityOfLife.big_mac_index_euros }}€
                                        </p>
                                        <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">Pouvoir d'achat relatif</p>
                                    </div>
                                    <div class="text-4xl">🍔</div>
                                </div>
                            </div>
                        </div>

                        <!-- Graphiques évolution -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    📈 Évolution de l'IDH
                                </h3>
                                <div class="h-64">
                                    <Line v-if="idhChartData" :data="idhChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    😊 Évolution du BNB
                                </h3>
                                <div class="h-64">
                                    <Line v-if="bnbChartData" :data="bnbChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- ÉDUCATION -->
                <div v-if="activeTab === 'education'" class="space-y-6">
                    <Card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📚 Éducation & Formation
                        </h2>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8" v-if="education">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Illettrisme</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ education.illiteracy_rate }}%</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium">Bac</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ education.population_with_bac }}%</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Bac+5</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ education.population_with_bac5 }}%</p>
                            </div>
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Réussite Bac</p>
                                <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ education.bac_success_rate }}%</p>
                            </div>
                        </div>

                        <!-- Graphiques -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    🎓 Niveau d'éducation de la population
                                </h3>
                                <div class="h-64">
                                    <Bar v-if="educationLevelChartData" :data="educationLevelChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    📉 Évolution du décrochage scolaire
                                </h3>
                                <div class="h-64">
                                    <Line v-if="dropoutChartData" :data="dropoutChartData" :options="chartOptionsWithPercentage" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- SÉCURITÉ -->
                <div v-if="activeTab === 'security'" class="space-y-6">
                    <Card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🔒 Sécurité & Justice
                        </h2>

                        <!-- Alerte féminicides -->
                        <div v-if="security && security.feminicides > 0" class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <span class="text-3xl">⚠️</span>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-lg font-bold text-red-800 dark:text-red-200">
                                        {{ security.feminicides }} féminicides en {{ selectedYear }}
                                    </h3>
                                    <p class="mt-2 text-sm text-red-700 dark:text-red-300">
                                        Les violences faites aux femmes restent un fléau majeur. Chaque victime compte.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 mb-8" v-if="security">
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-6">
                                <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">Taux de criminalité</p>
                                <p class="text-3xl font-bold text-orange-900 dark:text-orange-100">{{ security.crime_rate }}</p>
                                <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">Pour 1000 habitants</p>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Sentiment de sécurité</p>
                                <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ security.feeling_of_security }}%</p>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">Se sentent en sécurité</p>
                            </div>

                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6">
                                <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">Violences domestiques</p>
                                <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ security.domestic_violence }}</p>
                                <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">Signalements</p>
                            </div>
                        </div>

                        <!-- Graphiques -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    📊 Évolution de la criminalité
                                </h3>
                                <div class="h-64">
                                    <Line v-if="crimeChartData" :data="crimeChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    🚨 Féminicides par année
                                </h3>
                                <div class="h-64">
                                    <Bar v-if="feminicidesChartData" :data="feminicidesChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- EMPLOI DÉTAILLÉ -->
                <div v-if="activeTab === 'employment'" class="space-y-6">
                    <Card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            💼 Emploi & Conditions de travail
                        </h2>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8" v-if="employmentDetailed">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium">CDI</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ employmentDetailed.cdi_rate }}%</p>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                                <p class="text-xs text-orange-600 dark:text-orange-400 font-medium">CDD</p>
                                <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ employmentDetailed.cdd_rate }}%</p>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                                <p class="text-xs text-red-600 dark:text-red-400 font-medium">Temps partiel subi</p>
                                <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ employmentDetailed.involuntary_part_time }}%</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Écart salarial H/F</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ employmentDetailed.gender_pay_gap }}%</p>
                            </div>
                        </div>

                        <!-- Graphiques -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    💰 Salaire médian par secteur ({{ selectedYear }})
                                </h3>
                                <div class="h-64">
                                    <Bar v-if="salaryBySectorChartData" :data="salaryBySectorChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    📊 Évolution de l'écart salarial Hommes/Femmes
                                </h3>
                                <div class="h-64">
                                    <Line v-if="genderPayGapChartData" :data="genderPayGapChartData" :options="chartOptionsWithPercentage" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- SANTÉ -->
                <div v-if="activeTab === 'health'" class="space-y-6">
                    <Card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🏥 Santé & Accès aux soins
                        </h2>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8" v-if="health">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Médecins/100k hab</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ health.doctors_per_100k }}</p>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                                <p class="text-xs text-red-600 dark:text-red-400 font-medium">Déserts médicaux</p>
                                <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ health.medical_deserts_percentage }}%</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium">Dépenses santé/hab</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ health.health_spending_per_capita_euros }}€</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Vaccination</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ health.vaccination_rate }}%</p>
                            </div>
                        </div>

                        <!-- Graphiques -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    👨‍⚕️ Évolution des médecins pour 100k habitants
                                </h3>
                                <div class="h-64">
                                    <Line v-if="doctorsChartData" :data="doctorsChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    💰 Évolution des dépenses de santé
                                </h3>
                                <div class="h-64">
                                    <Line v-if="healthSpendingChartData" :data="healthSpendingChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- LOGEMENT -->
                <div v-if="activeTab === 'housing'" class="space-y-6">
                    <Card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🏠 Logement & Habitat
                        </h2>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8" v-if="housing">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium">Propriétaires</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ housing.owner_rate }}%</p>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Locataires</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ housing.tenant_rate }}%</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Logement social</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ housing.social_housing_rate }}%</p>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                                <p class="text-xs text-red-600 dark:text-red-400 font-medium">SDF</p>
                                <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ housing.homelessness }}</p>
                            </div>
                        </div>

                        <!-- Graphiques -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    💰 Prix moyen au m² ({{ selectedYear }})
                                </h3>
                                <div class="h-64">
                                    <Bar v-if="housingPriceChartData" :data="housingPriceChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    🏘️ Répartition Propriétaires/Locataires
                                </h3>
                                <div class="h-64">
                                    <Doughnut v-if="housingDistributionChartData" :data="housingDistributionChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- ENVIRONNEMENT -->
                <div v-if="activeTab === 'environment'" class="space-y-6">
                    <Card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🌍 Environnement & Écologie
                        </h2>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8" v-if="environment">
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                                <p class="text-xs text-orange-600 dark:text-orange-400 font-medium">CO2/hab (tonnes)</p>
                                <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ environment.co2_emissions_per_capita_tons }}</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium">Énergies renouvelables</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ environment.renewable_energy_share }}%</p>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Qualité de l'air</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ environment.air_quality_index }}/100</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Recyclage</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ environment.recycling_rate }}%</p>
                            </div>
                        </div>

                        <!-- Graphiques -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    🏭 Évolution des émissions de CO2
                                </h3>
                                <div class="h-64">
                                    <Line v-if="co2ChartData" :data="co2ChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    ♻️ Évolution du taux de recyclage
                                </h3>
                                <div class="h-64">
                                    <Line v-if="recyclingChartData" :data="recyclingChartData" :options="chartOptionsWithPercentage" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

