<script setup>
/**
 * Logos officiels des institutions françaises
 * Utilise les SVG vectoriels fournis
 */
const props = defineProps({
    institution: {
        type: String,
        required: true,
        validator: (value) => ['an', 'senat', 'gouvernement', 'elysee'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
    },
    withText: {
        type: Boolean,
        default: false,
    },
});

const sizeClasses = {
    xs: 'w-4 h-4',
    sm: 'w-6 h-6',
    md: 'w-8 h-8',
    lg: 'w-12 h-12',
    xl: 'w-16 h-16',
};

const logos = {
    an: {
        src: '/images/Logo_de_l\'Assemblée_nationale_française.svg',
        alt: 'Assemblée Nationale',
        label: 'Assemblée Nationale',
        shortLabel: 'AN',
    },
    senat: {
        src: '/images/Logo_du_Sénat_Republique_française.svg',
        alt: 'Sénat',
        label: 'Sénat',
        shortLabel: 'Sénat',
    },
    gouvernement: {
        src: null, // Pas encore de logo
        alt: 'Gouvernement',
        label: 'Gouvernement',
        shortLabel: 'Gouv.',
        fallback: '🏛️',
    },
    elysee: {
        src: null, // Pas encore de logo
        alt: 'Élysée',
        label: 'Présidence de la République',
        shortLabel: 'Élysée',
        fallback: '🏰',
    },
};

const logo = logos[props.institution];
const sizeClass = sizeClasses[props.size];
</script>

<template>
    <div class="inline-flex items-center gap-2">
        <img
            v-if="logo.src"
            :src="logo.src"
            :alt="logo.alt"
            :class="[sizeClass, 'object-contain']"
        />
        <span
            v-else
            :class="[
                sizeClass,
                'flex items-center justify-center text-center',
                size === 'xs' ? 'text-xs' : size === 'sm' ? 'text-sm' : size === 'md' ? 'text-lg' : size === 'lg' ? 'text-2xl' : 'text-3xl'
            ]"
        >
            {{ logo.fallback }}
        </span>
        <span v-if="withText" class="font-medium text-current">
            {{ logo.label }}
        </span>
    </div>
</template>
