<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    evenement: Object,
    codeInsee: String,
});

const categorieIcons = {
    ceremonie: '🎖️',
    culture: '🎭',
    sport: '⚽',
    marche: '🛒',
    reunion: '🏛️',
    atelier: '🔧',
    fete: '🎉',
    environnement: '🌿',
    solidarite: '🤝',
    autre: '📌',
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const parts = dateStr.split(' ');
    return parts[0];
};

const formatHeure = (dateStr) => {
    if (!dateStr) return '';
    const parts = dateStr.split(' ');
    return parts[1] || '';
};
</script>

<template>
    <Link
        :href="route('commune.evenements.show', [codeInsee, evenement.slug])"
        class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5"
    >
        <div class="flex">
            <!-- Date card -->
            <div class="w-20 flex-shrink-0 flex flex-col items-center justify-center py-4 bg-blue-50 dark:bg-blue-900/20 border-r border-slate-200 dark:border-slate-700">
                <span class="text-2xl">{{ categorieIcons[evenement.categorie] || '📌' }}</span>
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 mt-1">
                    {{ formatDate(evenement.date_debut) }}
                </span>
                <span v-if="!evenement.journee_entiere" class="text-xs text-slate-500">
                    {{ formatHeure(evenement.date_debut) }}
                </span>
            </div>

            <!-- Content -->
            <div class="p-4 flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">
                        {{ evenement.titre }}
                    </h3>
                    <span v-if="evenement.annule" class="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 px-2 py-0.5 rounded-full flex-shrink-0">
                        Annule
                    </span>
                </div>

                <p v-if="evenement.lieu_nom" class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">
                    📍 {{ evenement.lieu_nom }}
                </p>

                <p v-if="evenement.description" class="text-sm text-slate-600 dark:text-slate-400 mt-1 line-clamp-2">
                    {{ evenement.description }}
                </p>

                <!-- Inscription info -->
                <div v-if="evenement.inscription_requise" class="mt-2 flex items-center gap-2">
                    <span
                        class="text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="evenement.est_complet
                            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                            : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'"
                    >
                        {{ evenement.est_complet ? 'Complet' : `${evenement.places_restantes ?? '?'} places` }}
                    </span>
                </div>
            </div>
        </div>
    </Link>
</template>
