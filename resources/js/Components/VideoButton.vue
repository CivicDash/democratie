<script setup>
defineProps({
    href: { type: String, required: true },
    label: { type: String, default: 'Voir la vidéo' },
    variant: { type: String, default: 'default' },
});
</script>

<template>
    <a :href="href" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 transition-colors font-medium"
       :class="{
           'px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm shadow-sm': variant === 'default',
           'px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-full text-sm': variant === 'hero',
           'px-3 py-1 bg-red-50 hover:bg-red-100 text-red-700 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-400 rounded-lg text-xs': variant === 'compact',
       }"
    >
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path d="M6.5 5.5v9l7-4.5-7-4.5z"/>
        </svg>
        {{ label }}
    </a>
</template>
