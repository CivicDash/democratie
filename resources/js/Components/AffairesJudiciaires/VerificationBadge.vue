<script setup>
import { computed } from 'vue';

const props = defineProps({
    statutValidation: { type: String, default: 'valide' },
    dateValidation: { type: String, default: null },
    nbSources: { type: Number, default: 0 },
    dateModification: { type: String, default: null },
});

const variant = computed(() => {
    if (props.statutValidation === 'conteste') {
        return {
            label: 'Contesté — en cours de vérification',
            bg: 'bg-yellow-100 dark:bg-yellow-900/30',
            text: 'text-yellow-800 dark:text-yellow-300',
            tooltip: 'Cette information est contestée et en cours de vérification.',
        };
    }
    if (props.dateModification && props.dateValidation && props.dateModification > props.dateValidation) {
        return {
            label: `Mis à jour le ${props.dateModification}`,
            bg: 'bg-blue-100 dark:bg-blue-900/30',
            text: 'text-blue-800 dark:text-blue-300',
            tooltip: 'Cette information a été mise à jour après sa validation initiale.',
        };
    }
    return {
        label: 'Vérifié par CivicDash',
        bg: 'bg-green-100 dark:bg-green-900/30',
        text: 'text-green-800 dark:text-green-300',
        tooltip: props.dateValidation
            ? `Information vérifiée par un modérateur le ${props.dateValidation}. ${props.nbSources} source(s) vérifiée(s).`
            : 'Information vérifiée par un modérateur.',
    };
});
</script>

<template>
    <div
        :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', variant.bg, variant.text]"
        :title="variant.tooltip"
    >
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
        </svg>
        <span>{{ variant.label }}</span>
    </div>
</template>
