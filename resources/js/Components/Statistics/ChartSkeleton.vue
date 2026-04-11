<script setup>
defineProps({
    title: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 sm:p-6 animate-pulse">
        <div v-if="title" class="h-5 w-48 bg-gray-200 dark:bg-gray-700 rounded mb-4"></div>
        <div class="h-48 sm:h-64 lg:h-72 bg-gray-100 dark:bg-gray-700/50 rounded-lg flex items-end justify-around px-4 pb-4 gap-2">
            <div v-for="i in 7" :key="i" class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-t" :style="{ height: `${20 + Math.random() * 60}%` }"></div>
        </div>
    </div>
</template>
