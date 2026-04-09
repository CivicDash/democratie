<script setup>
import { Link } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import EvenementCard from '@/Components/Commune/EvenementCard.vue';

const props = defineProps({
    ville: Object,
    page: Object,
    evenements: Object,
    categories: Object,
    categorie_active: String,
    periode: String,
});
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Evenements - ${ville.nom}`">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Evenements</h1>

            <!-- Periode toggle + calendrier -->
            <div class="flex items-center justify-between mb-6">
                <Link
                    :href="route('commune.evenements.calendrier', ville.code_insee)"
                    class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Vue calendrier
                </Link>
            </div>
            <div class="flex items-center gap-2 mb-6">
                <Link
                    :href="route('commune.evenements', { codeInsee: ville.code_insee, periode: 'a_venir' })"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="periode !== 'passes' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                >
                    A venir
                </Link>
                <Link
                    :href="route('commune.evenements', { codeInsee: ville.code_insee, periode: 'passes' })"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="periode === 'passes' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                >
                    Passes
                </Link>
            </div>

            <!-- Filtres categories -->
            <div class="flex flex-wrap gap-2 mb-6">
                <Link
                    :href="route('commune.evenements', { codeInsee: ville.code_insee, periode })"
                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                    :class="!categorie_active ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
                >
                    Tous
                </Link>
                <Link
                    v-for="(label, key) in categories"
                    :key="key"
                    :href="route('commune.evenements', { codeInsee: ville.code_insee, categorie: key, periode })"
                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                    :class="categorie_active === key ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
                >
                    {{ label }}
                </Link>
            </div>

            <!-- Liste evenements -->
            <div v-if="evenements.data?.length" class="space-y-3">
                <EvenementCard v-for="evenement in evenements.data" :key="evenement.id" :evenement="evenement" :code-insee="ville.code_insee" />
            </div>

            <div v-else class="text-center py-16">
                <div class="text-4xl mb-3">📅</div>
                <p class="text-slate-500 dark:text-slate-400">
                    {{ periode === 'passes' ? 'Aucun evenement passe.' : 'Aucun evenement a venir.' }}
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="evenements.links?.length > 3" class="flex justify-center gap-1 mt-8">
                <Link
                    v-for="link in evenements.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-2 rounded-lg text-sm"
                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                    v-html="link.label"
                />
            </div>
        </div>
    </CommuneLayout>
</template>
