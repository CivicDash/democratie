<script setup>
import { Link } from '@inertiajs/vue3';
import NuanceBadge from './NuanceBadge.vue';

const props = defineProps({
    codeCommune: String,
    nomCommune: String,
    codeDepartement: String,
    tauxParticipation: Number,
    statutCommune: String,
    statutLibelle: String,
    listeGagnante: Object,
});

const statutColor = {
    'elu_t1': 'text-emerald-600 dark:text-emerald-400',
    'elu_t2': 'text-emerald-600 dark:text-emerald-400',
    'second_tour': 'text-amber-600 dark:text-amber-400',
    'sans_candidat': 'text-gray-400',
    'annule': 'text-red-500',
};
</script>

<template>
    <Link
        :href="route('elections.municipales.resultats.commune', { code: codeCommune })"
        class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md transition-all"
    >
        <div class="flex justify-between items-start mb-2">
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ nomCommune }}</h4>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ codeDepartement }}</span>
            </div>
            <span :class="['text-xs font-medium', statutColor[statutCommune] || 'text-gray-500']">
                {{ statutLibelle }}
            </span>
        </div>

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Participation : <strong>{{ tauxParticipation?.toFixed(1) }}%</strong>
            </span>
        </div>

        <div v-if="listeGagnante" class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                {{ listeGagnante.tete_de_liste_prenom }} {{ listeGagnante.tete_de_liste_nom }}
            </div>
            <div class="flex items-center gap-2 mt-1">
                <NuanceBadge v-if="listeGagnante.nuance_politique" :nuance="listeGagnante.nuance_politique" size="xs" />
                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ listeGagnante.pourcentage_exprimes?.toFixed(1) }}%</span>
            </div>
        </div>
    </Link>
</template>
