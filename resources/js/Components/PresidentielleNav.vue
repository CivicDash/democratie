<script setup>
import { Link } from '@inertiajs/vue3';

/**
 * Navigation commune du back-office présidentielle — identique sur toutes les
 * pages du module, avec état actif. Pilules lisibles, scroll horizontal en mobile.
 */
const items = [
    { name: 'admin.presidentielle.moderation', label: 'Tableau de bord', icon: '📊' },
    { name: 'admin.presidentielle.candidats', label: 'Candidats', icon: '🗳️' },
    { name: 'admin.presidentielle.propositions', label: "File d'ingestion", icon: '📥' },
    { name: 'admin.presidentielle.mesures', label: 'Mesures', icon: '📋' },
    { name: 'admin.presidentielle.controverses', label: 'Controverses', icon: '⚖️' },
    { name: 'admin.presidentielle.parcours', label: 'Parcours', icon: '🧭' },
    { name: 'admin.presidentielle.medias', label: 'Médias', icon: '🖼️' },
];

function estActif(name) {
    // « Mesures » reste actif sur la sous-page Arguments, etc.
    return route().current(name) || route().current(`${name}.*`);
}
</script>

<template>
    <nav aria-label="Sections présidentielle" class="flex gap-2 overflow-x-auto pb-1 -mb-1" style="scrollbar-width: none">
        <Link v-for="it in items" :key="it.name" :href="route(it.name)"
            class="flex-none inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium border transition whitespace-nowrap"
            :class="estActif(it.name)
                ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600'">
            <span aria-hidden="true">{{ it.icon }}</span>{{ it.label }}
        </Link>
    </nav>
</template>
