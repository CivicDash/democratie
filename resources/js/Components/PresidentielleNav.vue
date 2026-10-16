<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

/**
 * Navigation commune du back-office présidentielle, sur deux niveaux : cinq rubriques,
 * puis les pages de la rubrique ouverte. Seize entrées à plat ne tenaient plus sur une
 * ligne (la barre de défilement masquée cachait Eurostat et Audience). Défilement
 * horizontal en mobile, retour à la ligne au-delà.
 */
const rubriques = [
    { label: 'Tableau de bord', icon: '📊', items: [
        { name: 'admin.presidentielle.moderation', label: 'Tableau de bord', icon: '📊' },
    ] },
    { label: 'Candidats', icon: '🗳️', items: [
        { name: 'admin.presidentielle.candidats', label: 'Candidats', icon: '🗳️' },
        { name: 'admin.presidentielle.parcours', label: 'Parcours', icon: '🧭' },
        { name: 'admin.presidentielle.hatvp', label: 'HATVP', icon: '💼' },
        { name: 'admin.presidentielle.evenements', label: 'Calendrier', icon: '📅' },
        { name: 'admin.presidentielle.medias', label: 'Médias', icon: '🖼️' },
    ] },
    { label: 'Programmes', icon: '📋', items: [
        { name: 'admin.presidentielle.propositions', label: "File d'ingestion", icon: '📥' },
        { name: 'admin.presidentielle.mesures', label: 'Mesures', icon: '📋' },
        { name: 'admin.presidentielle.controverses', label: 'Controverses', icon: '⚖️' },
        { name: 'admin.presidentielle.budgets', label: 'Budgets', icon: '💶' },
        { name: 'admin.presidentielle.themes', label: 'Thèmes', icon: '🧮' },
    ] },
    { label: 'Chiffres', icon: '📈', items: [
        { name: 'admin.presidentielle.affirmations', label: 'Repères chiffrés', icon: '📊' },
        { name: 'admin.presidentielle.eurostat', label: 'Eurostat', icon: '🇪🇺' },
    ] },
    { label: 'Site public', icon: '🌐', items: [
        { name: 'admin.presidentielle.quiz', label: 'Quiz', icon: '❓' },
        { name: 'admin.presidentielle.signalements', label: 'Signalements', icon: '🚩' },
        { name: 'admin.presidentielle.audience', label: 'Audience', icon: '📈' },
    ] },
];

function estActif(name) {
    // « Mesures » reste actif sur la sous-page Arguments, etc.
    return route().current(name) || route().current(`${name}.*`);
}

const rubriqueActive = computed(() => rubriques.find((r) => r.items.some((it) => estActif(it.name))) ?? rubriques[0]);
</script>

<template>
    <nav aria-label="Sections présidentielle" class="space-y-2">
        <div class="flex gap-2 overflow-x-auto md:flex-wrap md:overflow-visible pb-1 -mb-1" style="scrollbar-width: none">
            <Link v-for="r in rubriques" :key="r.label" :href="route(r.items[0].name)"
                :aria-current="r === rubriqueActive ? 'true' : undefined"
                class="flex-none inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium border transition whitespace-nowrap"
                :class="r === rubriqueActive
                    ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                    : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600'">
                <span aria-hidden="true">{{ r.icon }}</span>{{ r.label }}
                <span v-if="r.items.length > 1" class="text-xs opacity-70">{{ r.items.length }}</span>
            </Link>
        </div>
        <div v-if="rubriqueActive.items.length > 1" class="flex gap-1 overflow-x-auto md:flex-wrap md:overflow-visible border-b border-gray-200 dark:border-gray-700"
             style="scrollbar-width: none" :aria-label="`Pages de la rubrique ${rubriqueActive.label}`">
            <Link v-for="it in rubriqueActive.items" :key="it.name" :href="route(it.name)"
                :aria-current="estActif(it.name) ? 'page' : undefined"
                class="flex-none inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap border-b-2 -mb-px transition"
                :class="estActif(it.name)
                    ? 'border-blue-600 text-blue-700 dark:text-blue-300 font-medium'
                    : 'border-transparent text-gray-600 dark:text-gray-300 hover:text-blue-600 hover:border-blue-300'">
                <span aria-hidden="true">{{ it.icon }}</span>{{ it.label }}
            </Link>
        </div>
    </nav>
</template>
