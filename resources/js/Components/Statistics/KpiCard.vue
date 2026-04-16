<script setup>
import { computed } from 'vue';
import TrendIndicator from './TrendIndicator.vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    previousValue: { type: Number, default: null },
    unit: { type: String, default: '' },
    icon: { type: String, default: null },
    format: { type: String, default: 'number' },
    decimals: { type: Number, default: 1 },
    invertTrend: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const formattedValue = computed(() => {
    if (typeof props.value === 'string') return props.value;
    if (props.value === null || props.value === undefined) return '—';

    const num = Number(props.value);
    if (isNaN(num)) return props.value;

    switch (props.format) {
        case 'percent':
            return num.toFixed(props.decimals) + '%';
        case 'currency':
            return num.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: props.decimals });
        case 'compact':
            if (num >= 1_000_000_000) return (num / 1_000_000_000).toFixed(1) + ' Md';
            if (num >= 1_000_000) return (num / 1_000_000).toFixed(1) + ' M';
            if (num >= 1_000) return (num / 1_000).toFixed(1) + ' k';
            return num.toLocaleString('fr-FR');
        default:
            return num.toLocaleString('fr-FR', { maximumFractionDigits: props.decimals });
    }
});

const trendPercentage = computed(() => {
    if (props.previousValue === null || props.previousValue === 0) return null;
    return ((Number(props.value) - props.previousValue) / Math.abs(props.previousValue)) * 100;
});
</script>

<template>
    <div
        class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition-all hover:shadow-md"
        :class="{ 'p-3': compact }"
    >
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider truncate" :class="{ 'text-[10px]': compact }">
                    {{ label }}
                </p>
                <div class="mt-1 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white" :class="{ 'text-xl': compact }">
                        {{ formattedValue }}
                    </span>
                    <span v-if="unit" class="text-sm text-gray-500 dark:text-gray-400">{{ unit }}</span>
                </div>
                <TrendIndicator
                    v-if="trendPercentage !== null"
                    :value="trendPercentage"
                    :invert="invertTrend"
                    class="mt-1"
                />
            </div>
            <span v-if="icon" class="text-2xl ml-2 flex-shrink-0" :class="{ 'text-xl': compact }">{{ icon }}</span>
        </div>
    </div>
</template>
