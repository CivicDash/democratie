<script setup>
defineProps({
    padding: {
        type: String,
        default: 'p-4 sm:p-6',
    },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg" :class="padding">
        <slot />
    </div>
</template>

