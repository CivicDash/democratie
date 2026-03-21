<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    nuances: Object,
});

const nuanceColors = {
    'LEXG': '#B91C1C', 'LCOM': '#DC2626', 'LFI': '#EF4444',
    'LSOC': '#F43F5E', 'LDVG': '#E11D48', 'LUG': '#BE185D',
    'LECO': '#22C55E', 'LVEC': '#16A34A', 'LREG': '#A3E635',
    'LDVC': '#FBBF24', 'LMDM': '#F59E0B', 'LREN': '#EAB308', 'LUC': '#D97706',
    'LUDI': '#38BDF8', 'LUD': '#0EA5E9',
    'LDVD': '#3B82F6', 'LLR': '#2563EB', 'LUDR': '#1D4ED8',
    'LRN': '#1E3A5F', 'LEXD': '#0F172A', 'LUXD': '#334155',
    'LREC': '#475569', 'LDSV': '#D946EF', 'LHOR': '#78716C',
    'LDIV': '#9CA3AF',
};

const chartData = computed(() => {
    if (!props.nuances) return null;

    const entries = Object.entries(props.nuances)
        .sort((a, b) => (b[1].communes_gagnees || 0) - (a[1].communes_gagnees || 0))
        .slice(0, 10);

    return {
        labels: entries.map(([k]) => k),
        datasets: [{
            data: entries.map(([, v]) => v.communes_gagnees || 0),
            backgroundColor: entries.map(([k]) => nuanceColors[k] || '#9CA3AF'),
            borderWidth: 2,
            borderColor: '#fff',
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: { boxWidth: 12, padding: 8, font: { size: 11 } },
        },
        tooltip: {
            callbacks: {
                label: (ctx) => `${ctx.label}: ${ctx.parsed} communes`,
            },
        },
    },
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Communes gagnées par nuance</h3>
        <div v-if="chartData" class="h-64">
            <Doughnut :data="chartData" :options="chartOptions" />
        </div>
        <div v-else class="h-64 flex items-center justify-center text-gray-400">Aucune donnée</div>
    </div>
</template>
