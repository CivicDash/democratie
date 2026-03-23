<script setup>
import { useGuidedTour } from '@/composables/useGuidedTour';

const props = defineProps({
    tourId: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Visite guidée',
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
    variant: {
        type: String,
        default: 'primary', // primary, secondary, ghost
    },
});

const { startTour, isTourCompleted } = useGuidedTour();

const sizeClasses = {
    sm: 'px-2.5 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-base',
};

const variantClasses = {
    primary: 'bg-indigo-600 hover:bg-indigo-700 text-white',
    secondary: 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200',
    ghost: 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400',
};
</script>

<template>
    <button
        @click="startTour(tourId)"
        :class="[
            'inline-flex items-center gap-2 font-medium rounded-lg transition-colors',
            sizeClasses[size],
            variantClasses[variant],
        ]"
    >
        <span>🎯</span>
        <span>{{ label }}</span>
        <span 
            v-if="isTourCompleted(tourId)" 
            class="text-xs opacity-75"
            title="Déjà complétée"
        >
            ✓
        </span>
    </button>
</template>
