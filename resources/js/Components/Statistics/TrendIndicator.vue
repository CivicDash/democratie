<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, required: true },
    invert: { type: Boolean, default: false },
    showLabel: { type: Boolean, default: true },
});

const isPositive = computed(() => {
    return props.invert ? props.value < 0 : props.value > 0;
});

const isNegative = computed(() => {
    return props.invert ? props.value > 0 : props.value < 0;
});

const colorClass = computed(() => {
    if (isPositive.value) return 'text-green-600 dark:text-green-400';
    if (isNegative.value) return 'text-red-600 dark:text-red-400';
    return 'text-gray-500 dark:text-gray-400';
});

const bgClass = computed(() => {
    if (isPositive.value) return 'bg-green-50 dark:bg-green-900/20';
    if (isNegative.value) return 'bg-red-50 dark:bg-red-900/20';
    return 'bg-gray-50 dark:bg-gray-800';
});

const arrow = computed(() => {
    if (props.value > 0) return '↑';
    if (props.value < 0) return '↓';
    return '→';
});

const formatted = computed(() => {
    return (props.value > 0 ? '+' : '') + props.value.toFixed(1) + '%';
});
</script>

<template>
    <span
        class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-xs font-medium"
        :class="[colorClass, bgClass]"
    >
        <span>{{ arrow }}</span>
        <span v-if="showLabel">{{ formatted }}</span>
    </span>
</template>
