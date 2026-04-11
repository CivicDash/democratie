<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    duration: { type: Number, default: 1500 },
    suffix: { type: String, default: '' },
    prefix: { type: String, default: '' },
    decimals: { type: Number, default: 0 },
});

const displayValue = ref(0);
let animationFrame = null;

const animate = (from, to) => {
    const start = performance.now();
    const diff = to - from;

    const step = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / props.duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        displayValue.value = from + diff * eased;
        if (progress < 1) {
            animationFrame = requestAnimationFrame(step);
        }
    };

    if (animationFrame) cancelAnimationFrame(animationFrame);
    animationFrame = requestAnimationFrame(step);
};

const formatValue = (val) => {
    const num = Number(val).toFixed(props.decimals);
    return Number(num).toLocaleString('fr-FR');
};

onMounted(() => {
    animate(0, props.value);
});

watch(() => props.value, (newVal, oldVal) => {
    animate(oldVal || 0, newVal);
});
</script>

<template>
    <span>{{ prefix }}{{ formatValue(displayValue) }}{{ suffix }}</span>
</template>
