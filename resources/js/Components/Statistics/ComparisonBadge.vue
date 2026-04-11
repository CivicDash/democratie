<script setup>
import { computed } from 'vue';

const props = defineProps({
    current: { type: Number, required: true },
    previous: { type: Number, required: true },
    label: { type: String, default: '' },
    invert: { type: Boolean, default: false },
    format: { type: String, default: 'percent' },
});

const change = computed(() => props.current - props.previous);

const percentage = computed(() => {
    if (props.previous === 0) return null;
    return ((change.value) / Math.abs(props.previous)) * 100;
});

const isPositive = computed(() => {
    return props.invert ? change.value < 0 : change.value > 0;
});

const isNegative = computed(() => {
    return props.invert ? change.value > 0 : change.value < 0;
});

const colorClasses = computed(() => {
    if (isPositive.value) return 'text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30';
    if (isNegative.value) return 'text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/30';
    return 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800';
});

const formattedChange = computed(() => {
    if (percentage.value === null) return 'N/A';
    const sign = percentage.value > 0 ? '+' : '';
    if (props.format === 'absolute') {
        return `${sign}${change.value.toLocaleString('fr-FR')}`;
    }
    return `${sign}${percentage.value.toFixed(1)}%`;
});
</script>

<template>
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" :class="colorClasses">
        <span v-if="change > 0">↑</span>
        <span v-else-if="change < 0">↓</span>
        <span v-else>→</span>
        {{ formattedChange }}
        <span v-if="label" class="font-normal opacity-75">{{ label }}</span>
    </span>
</template>
