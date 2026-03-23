<script setup>
defineProps({
    sources: { type: Array, default: () => [] },
});

const fiabiliteLabel = (f) => {
    const map = { haute: 'Source officielle', moyenne: 'Presse nationale', basse: 'Autre source' };
    return map[f] || f;
};

const fiabiliteClass = (f) => {
    const map = {
        haute: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        moyenne: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        basse: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    return map[f] || 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <div v-if="sources.length" class="space-y-1.5">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sources</p>
        <div v-for="(source, idx) in sources" :key="idx" class="flex items-center gap-2 text-sm">
            <span :class="['px-1.5 py-0.5 rounded text-xs font-medium', fiabiliteClass(source.fiabilite || 'moyenne')]">
                {{ fiabiliteLabel(source.fiabilite || 'moyenne') }}
            </span>
            <a
                v-if="source.url"
                :href="source.url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-indigo-600 dark:text-indigo-400 hover:underline truncate"
            >
                {{ source.media || source.url }}
            </a>
            <span v-else class="text-gray-600 dark:text-gray-400">{{ source.media }}</span>
            <span v-if="source.date" class="text-xs text-gray-400 dark:text-gray-500 shrink-0">{{ source.date }}</span>
        </div>
    </div>
</template>
