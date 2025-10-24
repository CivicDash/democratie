<script setup>
defineProps({
    icon: {
        type: String,
        default: '📭',
    },
    title: {
        type: String,
        required: true,
    },
    description: String,
});
</script>

<template>
    <div class="text-center py-12">
        <div class="text-6xl mb-4">{{ icon }}</div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
            {{ title }}
        </h3>
        <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            {{ description }}
        </p>
        <slot />
    </div>
</template>

