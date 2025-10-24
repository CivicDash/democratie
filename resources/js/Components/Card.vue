<script setup>
defineProps({
    padding: {
        type: String,
        default: 'p-6',
    },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg" :class="padding">
        <slot />
    </div>
</template>

