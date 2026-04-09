<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    ville: Object,
    page: Object,
    maire: { type: Object, default: null },
});
</script>

<template>
    <div class="relative overflow-hidden">
        <!-- Image de couverture -->
        <div class="h-48 sm:h-64 bg-gradient-to-r from-blue-600 to-indigo-700 relative">
            <img
                v-if="page?.image_couverture_url"
                :src="page.image_couverture_url"
                :alt="ville.nom"
                class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        </div>

        <!-- Infos superposees -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 -mt-20 relative z-10">
            <div class="flex items-end gap-4 sm:gap-6">
                <!-- Blason / Logo -->
                <div class="w-20 h-20 sm:w-28 sm:h-28 rounded-2xl bg-white dark:bg-slate-800 shadow-xl border-4 border-white dark:border-slate-800 overflow-hidden flex-shrink-0">
                    <img
                        v-if="page?.logo_url || ville.blason_url"
                        :src="page?.logo_url || ville.blason_url"
                        :alt="ville.nom"
                        class="w-full h-full object-contain p-2"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-3xl font-bold text-white" :style="{ background: page?.couleur_primaire || '#1e40af' }">
                        {{ ville.nom?.charAt(0) }}
                    </div>
                </div>

                <!-- Nom + badges -->
                <div class="pb-2 sm:pb-4 text-white">
                    <h1 class="text-2xl sm:text-4xl font-bold drop-shadow-lg">{{ ville.nom }}</h1>
                    <div class="flex items-center gap-2 mt-1 text-sm text-white/80">
                        <span>{{ ville.departement_nom }}</span>
                        <span v-if="ville.code_postal" class="opacity-60">{{ ville.code_postal }}</span>
                        <span v-if="ville.est_prefecture" class="bg-amber-500/80 text-white px-2 py-0.5 rounded text-xs font-medium">Prefecture</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats rapides -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Population</div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white">{{ ville.population_formate || '-' }}</div>
                </div>
                <div v-if="maire" class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Maire</div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white truncate">{{ maire.prenom }} {{ maire.nom }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Superficie</div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white">{{ ville.superficie_formate || '-' }}</div>
                </div>
                <div v-if="page?.abonnes_count > 0" class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Abonnes CivicDash</div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white">{{ page.abonnes_count }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
