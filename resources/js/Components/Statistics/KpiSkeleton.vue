<script setup>
defineProps({
    count: { type: Number, default: 4 },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div
        class="grid gap-4"
        :class="{
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': count === 4,
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3': count === 3,
            'grid-cols-1 sm:grid-cols-2': count === 2,
        }"
    >
        <div
            v-for="i in count"
            :key="i"
            class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 animate-pulse"
            :class="{ 'p-3': compact }"
        >
            <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700 rounded mb-3"></div>
            <div class="h-7 w-28 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
            <div class="h-4 w-16 bg-gray-100 dark:bg-gray-700/50 rounded"></div>
        </div>
    </div>
</template>
