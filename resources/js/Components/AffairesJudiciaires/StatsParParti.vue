<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    statsParParti: { type: Object, default: () => ({}) },
});

const showRatio = ref(true);

const sortedPartis = computed(() => {
    const entries = Object.entries(props.statsParParti);
    return entries
        .filter(([, data]) => data.total_affaires > 0)
        .sort((a, b) => {
            if (showRatio.value) {
                return (b[1].ratio_affaires_pour_100 || 0) - (a[1].ratio_affaires_pour_100 || 0);
            }
            return (b[1].total_affaires || 0) - (a[1].total_affaires || 0);
        });
});

const maxValue = computed(() => {
    if (!sortedPartis.value.length) return 1;
    if (showRatio.value) {
        return Math.max(...sortedPartis.value.map(([, d]) => d.ratio_affaires_pour_100 || 0), 0.01);
    }
    return Math.max(...sortedPartis.value.map(([, d]) => d.total_affaires || 0), 1);
});

const barWidth = (data) => {
    const val = showRatio.value ? (data.ratio_affaires_pour_100 || 0) : (data.total_affaires || 0);
    return Math.max((val / maxValue.value) * 100, 2);
};

const displayValue = (data) => {
    if (showRatio.value) {
        return `${(data.ratio_affaires_pour_100 || 0).toFixed(2)} pour 100 élus`;
    }
    return `${data.total_affaires || 0} affaire(s)`;
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Répartition par parti</h3>
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
                <button
                    @click="showRatio = true"
                    :class="['px-3 py-1 text-xs font-medium rounded-md transition-colors',
                        showRatio ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400']"
                >
                    Ratio normalisé
                </button>
                <button
                    @click="showRatio = false"
                    :class="['px-3 py-1 text-xs font-medium rounded-md transition-colors',
                        !showRatio ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400']"
                >
                    Total brut
                </button>
            </div>
        </div>

        <div v-if="!showRatio" class="mb-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-2 text-xs text-amber-700 dark:text-amber-300">
            Les totaux bruts ne sont pas comparables entre partis de tailles différentes. Préférez le ratio normalisé.
        </div>

        <div class="space-y-3">
            <div v-for="[parti, data] in sortedPartis" :key="parti">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ parti }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ displayValue(data) }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div
                        class="bg-indigo-600 dark:bg-indigo-500 h-2.5 rounded-full transition-all duration-500"
                        :style="{ width: barWidth(data) + '%' }"
                    ></div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    <span>{{ data.condamnations_definitives || 0 }} condamnation(s) définitive(s)</span>
                    <span>{{ data.total_elus_parti || '?' }} élus recensés</span>
                </div>
            </div>
        </div>

        <div v-if="sortedPartis.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p class="text-sm">Aucune donnée disponible.</p>
        </div>
    </div>
</template>
