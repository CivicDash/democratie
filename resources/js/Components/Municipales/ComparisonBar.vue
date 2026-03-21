<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const props = defineProps({
    label: { type: String, default: 'Participation' },
    data2020: Number,
    dataT1: Number,
    dataT2: Number,
});

const chartData = computed(() => {
    const labels = ['2020'];
    const values = [props.data2020 || 0];
    const colors = ['#94A3B8'];

    if (props.dataT1 != null) {
        labels.push('2026 T1');
        values.push(props.dataT1);
        colors.push('#6366F1');
    }
    if (props.dataT2 != null) {
        labels.push('2026 T2');
        values.push(props.dataT2);
        colors.push('#8B5CF6');
    }

    return {
        labels,
        datasets: [{
            label: props.label,
            data: values,
            backgroundColor: colors,
            borderRadius: 6,
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } },
    },
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => ctx.parsed.y.toFixed(1) + '%' } },
    },
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ label }}</h3>
        <div class="h-48">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
