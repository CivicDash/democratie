<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    items: {
        type: Array,
        required: true,
        // Format: [{ label: 'Accueil', href: '/', icon: '🏠' }, { label: 'Page actuelle' }]
    },
    separator: {
        type: String,
        default: "chevron", // 'chevron', 'slash', 'arrow'
    },
    variant: {
        type: String,
        default: "default", // 'default', 'light' (for dark backgrounds)
    },
});

const getSeparatorIcon = (type) => {
    switch (type) {
        case "slash":
            return "/";
        case "arrow":
            return "→";
        default:
            return null; // Will use SVG chevron
    }
};
</script>

<template>
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex flex-wrap items-center gap-1 text-sm">
            <li v-for="(item, index) in items" :key="index" class="flex items-center">
                <!-- Separator (sauf pour le premier élément) -->
                <template v-if="index > 0">
                    <span 
                        v-if="separator !== 'chevron'" 
                        class="mx-2 select-none"
                        :class="variant === 'light' ? 'text-white/50' : 'text-gray-400 dark:text-gray-500'"
                    >
                        {{ getSeparatorIcon(separator) }}
                    </span>
                    <svg
                        v-else
                        class="mx-2 h-4 w-4 flex-shrink-0"
                        :class="variant === 'light' ? 'text-white/50' : 'text-gray-400 dark:text-gray-500'"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </template>

                <!-- Lien ou texte -->
                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="inline-flex items-center gap-1.5 transition-colors duration-150"
                    :class="variant === 'light' 
                        ? 'text-white/70 hover:text-white' 
                        : 'text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400'"
                >
                    <span v-if="item.icon" class="text-base">{{ item.icon }}</span>
                    <span>{{ item.label }}</span>
                </Link>

                <!-- Dernier élément (page courante) -->
                <span
                    v-else
                    class="inline-flex items-center gap-1.5 font-medium"
                    :class="[
                        variant === 'light' ? 'text-white' : 'text-gray-900 dark:text-gray-100',
                        { 'max-w-[200px] sm:max-w-[300px] truncate': index === items.length - 1 }
                    ]"
                    :title="item.label"
                >
                    <span v-if="item.icon" class="text-base flex-shrink-0">{{ item.icon }}</span>
                    <span class="truncate">{{ item.label }}</span>
                </span>
            </li>
        </ol>
    </nav>
</template>
