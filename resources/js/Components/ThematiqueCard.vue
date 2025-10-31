<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    thematique: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="`/legislation/thematiques/${thematique.code}`"
        class="block bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-all overflow-hidden border-t-4 group"
        :style="{ borderTopColor: thematique.couleur_hex }"
    >
        <div class="p-6">
            <!-- Icon & Title -->
            <div class="flex items-start gap-4 mb-4">
                <div
                    class="w-16 h-16 rounded-lg flex items-center justify-center text-3xl shadow-sm group-hover:scale-110 transition-transform"
                    :style="{ backgroundColor: thematique.couleur_hex + '20', color: thematique.couleur_hex }"
                >
                    {{ thematique.icone }}
                </div>
                
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                        {{ thematique.nom }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ thematique.code }}
                    </p>
                </div>
            </div>

            <!-- Description -->
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                {{ thematique.description }}
            </p>

            <!-- Stats -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ thematique.nb_propositions }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        proposition{{ thematique.nb_propositions > 1 ? 's' : '' }}
                    </span>
                </div>

                <div class="text-blue-600 dark:text-blue-400 text-sm font-medium group-hover:translate-x-1 transition-transform">
                    Voir →
                </div>
            </div>

            <!-- Enfants (sous-thématiques) si présentes -->
            <div v-if="thematique.enfants && thematique.enfants.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Sous-thématiques :</p>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="enfant in thematique.enfants.slice(0, 3)"
                        :key="enfant.id"
                        class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded"
                    >
                        {{ enfant.nom }}
                    </span>
                    <span
                        v-if="thematique.enfants.length > 3"
                        class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded"
                    >
                        +{{ thematique.enfants.length - 3 }}
                    </span>
                </div>
            </div>
        </div>
    </Link>
</template>

