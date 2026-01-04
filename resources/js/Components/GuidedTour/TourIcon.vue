<script setup>
/**
 * Composant pour afficher l'icône d'un tour de visite guidée
 * Utilise les logos institutionnels quand disponibles
 */
import InstitutionLogo from '@/Components/InstitutionLogo.vue';

const props = defineProps({
    tourId: {
        type: String,
        required: true,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
    },
});

// Mapping des tours vers les logos institutionnels
const institutionMap = {
    deputes: 'an',
    senateurs: 'senat',
    gouvernement: 'gouvernement',
    elu: null, // Utilise un emoji
    dashboard: null,
    participation: null,
    lois: null,
    bienvenue: null,
};

// Emojis pour les tours sans logo institutionnel
const emojiMap = {
    dashboard: '🏠',
    participation: '💬',
    lois: '📜',
    elu: '👔',
    bienvenue: '👋',
    deputes: null, // Utilise le logo
    senateurs: null,
    gouvernement: null,
};

const institution = institutionMap[props.tourId];
const emoji = emojiMap[props.tourId];

const sizeClasses = {
    xs: 'text-xs',
    sm: 'text-sm',
    md: 'text-lg',
    lg: 'text-2xl',
    xl: 'text-3xl',
};
</script>

<template>
    <span v-if="institution" class="inline-flex">
        <InstitutionLogo :institution="institution" :size="size" />
    </span>
    <span v-else :class="sizeClasses[size]">
        {{ emoji || '🎯' }}
    </span>
</template>
