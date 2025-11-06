<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import { Line, Bar } from 'vue-chartjs';

const props = defineProps({
    show: Boolean,
    region: Object,
    historicalData: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

const closeModal = () => {
    emit('close');
};

// Graphique d'évolution du chômage
const unemploymentChartData = computed(() => {
    if (!props.historicalData || props.historicalData.length === 0) return null;
    
    return {
        labels: props.historicalData.map(d => d.year),
        datasets: [{
            label: 'Taux de chômage (%)',
            data: props.historicalData.map(d => d.unemployment_rate),
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    };
});

// Graphique d'évolution du PIB
const gdpChartData = computed(() => {
    if (!props.historicalData || props.historicalData.length === 0) return null;
    
    return {
        labels: props.historicalData.map(d => d.year),
        datasets: [{
            label: 'PIB (Md€)',
            data: props.historicalData.map(d => d.gdp_billions_euros),
            backgroundColor: '#3B82F6',
        }]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
    },
};
</script>

<template>
    <Modal :show="show" @close="closeModal" max-width="4xl">
        <div v-if="region" class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ region.region_name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Code région: {{ region.region_code }}
                    </p>
                </div>
                <button
                    @click="closeModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ (region.population / 1000000).toFixed(2) }}M
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        Population
                    </div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ region.unemployment_rate }}%
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        Chômage
                    </div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ region.gdp_billions_euros }}Md€
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        PIB
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ region.median_income_euros?.toLocaleString() }}€
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        Revenu médian
                    </div>
                </div>
            </div>

            <!-- Indicateurs détaillés -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    📊 Indicateurs sociaux
                </h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Taux de pauvreté</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div
                                    class="bg-red-500 h-2 rounded-full"
                                    :style="{ width: `${(region.poverty_rate / 25) * 100}%` }"
                                ></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 w-12 text-right">
                                {{ region.poverty_rate }}%
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Taux de chômage</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div
                                    class="bg-orange-500 h-2 rounded-full"
                                    :style="{ width: `${(region.unemployment_rate / 15) * 100}%` }"
                                ></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 w-12 text-right">
                                {{ region.unemployment_rate }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques historiques -->
            <div v-if="historicalData && historicalData.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-if="unemploymentChartData" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3 text-sm">
                        📉 Évolution du chômage
                    </h4>
                    <div style="height: 200px;">
                        <Line :data="unemploymentChartData" :options="chartOptions" />
                    </div>
                </div>

                <div v-if="gdpChartData" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3 text-sm">
                        💰 Évolution du PIB
                    </h4>
                    <div style="height: 200px;">
                        <Bar :data="gdpChartData" :options="chartOptions" />
                    </div>
                </div>
            </div>

            <!-- Comparaison nationale -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center gap-2">
                    <span>🇫🇷</span>
                    <span>Comparaison avec la moyenne nationale</span>
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700 dark:text-blue-300">Chômage:</span>
                        <span class="ml-2 font-medium text-blue-900 dark:text-blue-100">
                            {{ region.unemployment_rate > 7.4 ? '↑' : '↓' }}
                            {{ region.unemployment_rate }}% (nat: 7.4%)
                        </span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-300">Pauvreté:</span>
                        <span class="ml-2 font-medium text-blue-900 dark:text-blue-100">
                            {{ region.poverty_rate > 14.5 ? '↑' : '↓' }}
                            {{ region.poverty_rate }}% (nat: 14.5%)
                        </span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-300">Revenu médian:</span>
                        <span class="ml-2 font-medium text-blue-900 dark:text-blue-100">
                            {{ region.median_income_euros > 22500 ? '↑' : '↓' }}
                            {{ region.median_income_euros?.toLocaleString() }}€ (nat: 22 500€)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex justify-end">
                <button
                    @click="closeModal"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Fermer
                </button>
            </div>
        </div>
    </Modal>
</template>

