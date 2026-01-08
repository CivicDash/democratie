<template>
    <img 
        :src="'/images/Sénat_français_2023.svg'" 
        :alt="alt"
        :class="sizeClass"
        class="inline-block"
    />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md', // xs, sm, md, lg, xl
    },
    alt: {
        type: String,
        default: 'Logo du Sénat',
    },
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'xs': return 'w-4 h-4';
        case 'sm': return 'w-5 h-5';
        case 'md': return 'w-6 h-6';
        case 'lg': return 'w-8 h-8';
        case 'xl': return 'w-10 h-10';
        case '2xl': return 'w-12 h-12';
        default: return 'w-6 h-6';
    }
});
</script>
