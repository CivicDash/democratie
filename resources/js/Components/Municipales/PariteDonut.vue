<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    hommes: Number,
    femmes: Number,
    tauxFemmes: Number,
});

const chartData = computed(() => ({
    labels: ['Hommes', 'Femmes'],
    datasets: [{
        data: [props.hommes || 0, props.femmes || 0],
        backgroundColor: ['#3B82F6', '#EC4899'],
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '60%',
    plugins: {
        legend: {
            position: 'bottom',
            labels: { boxWidth: 12, padding: 12, font: { size: 12 } },
        },
    },
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Parité des maires</h3>
        <div class="text-center mb-4">
            <span class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ tauxFemmes?.toFixed(1) || '–' }}%</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">de femmes maires</span>
        </div>
        <div class="h-48">
            <Doughnut :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
