<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap gap-2 justify-center">
        <template v-for="(link, index) in links" :key="index">
            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 dark:text-gray-600 border border-gray-300 dark:border-gray-700 rounded-md">
                <span v-html="link.label"></span>
            </div>
            <Link
                v-else
                :href="link.url"
                class="px-4 py-2 text-sm border rounded-md transition-colors"
                :class="link.active 
                    ? 'bg-indigo-600 text-white border-indigo-600' 
                    : 'text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'"
            >
                <span v-html="link.label"></span>
            </Link>
        </template>
    </div>
</template>

