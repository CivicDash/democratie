<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    href: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    badge: {
        type: [String, Number],
        default: null,
    },
    badgeColor: {
        type: String,
        default: "gray", // gray, indigo, red, green, yellow
    },
});

const badgeColors = {
    gray: "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300",
    indigo: "bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300",
    red: "bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300",
    green: "bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300",
    yellow: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300",
};
</script>

<template>
    <Link
        :href="href"
        class="flex items-start gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
    >
        <span v-if="icon" class="text-xl flex-shrink-0 mt-0.5">{{ icon }}</span>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                    {{ title }}
                </span>
                <span
                    v-if="badge"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium"
                    :class="badgeColors[badgeColor] || badgeColors.gray"
                >
                    {{ badge }}
                </span>
            </div>
            <p v-if="description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                {{ description }}
            </p>
        </div>
        <svg
            class="w-4 h-4 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 mt-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </Link>
</template>
