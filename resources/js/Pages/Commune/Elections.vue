<script setup>
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    ville: Object,
    page: Object,
    tours: Array,
});

const selectedTourIndex = ref(props.tours?.length ? props.tours.length - 1 : 0);

const currentTour = computed(() => props.tours?.[selectedTourIndex.value] || null);
const listes = computed(() => currentTour.value?.listes || []);
const maxVoix = computed(() => Math.max(...listes.value.map(l => l.voix || 0), 1));

const nuanceColors = {
    'DVD': '#4a90d9', 'LR': '#0066cc', 'RN': '#0d2c54', 'REC': '#3d3d3d',
    'DVG': '#ff6b6b', 'PS': '#ff8c94', 'LFI': '#c62b1f', 'EELV': '#00a651',
    'REM': '#ffcc00', 'LREM': '#ffcc00', 'ENS': '#ffcc00', 'HOR': '#00c4b3',
    'DVC': '#ff9f43', 'UDI': '#5fbcd3', 'PCF': '#dd1c1a', 'DIV': '#999',
    'ECO': '#77b255', 'EXG': '#bb0000', 'EXD': '#1a1a2e', 'REG': '#a855f7',
    'COM': '#dd1c1a', 'SOC': '#ff8c94', 'FI': '#c62b1f',
};

const getColor = (nuance) => nuanceColors[nuance] || '#6b7280';

const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Elections - ${ville.nom}`">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Elections municipales</h1>
            <p class="text-slate-500 dark:text-slate-400 mb-8">Resultats des elections municipales a {{ ville.nom }}.</p>

            <template v-if="tours?.length">
                <!-- Tour selector -->
                <div v-if="tours.length > 1" class="flex gap-2 mb-6">
                    <button
                        v-for="(tour, idx) in tours"
                        :key="idx"
                        @click="selectedTourIndex = idx"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="selectedTourIndex === idx
                            ? 'bg-blue-600 text-white'
                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'"
                    >
                        {{ tour.tour === 1 ? '1er tour' : '2nd tour' }}
                    </button>
                </div>

                <!-- Stats de participation -->
                <div v-if="currentTour" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 text-center">
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumber(currentTour.inscrits) }}</div>
                        <div class="text-xs text-slate-500 mt-1">Inscrits</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ currentTour.taux_participation }}%</div>
                        <div class="text-xs text-slate-500 mt-1">Participation</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 text-center">
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumber(currentTour.votants) }}</div>
                        <div class="text-xs text-slate-500 mt-1">Votants</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 text-center">
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumber(currentTour.exprimes) }}</div>
                        <div class="text-xs text-slate-500 mt-1">Exprimes</div>
                    </div>
                </div>

                <!-- Statut -->
                <div v-if="currentTour?.statut" class="mb-6 px-4 py-2.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl text-sm text-blue-700 dark:text-blue-300 font-medium">
                    {{ currentTour.statut }}
                </div>

                <!-- Resultats par liste -->
                <div v-if="listes.length" class="space-y-3">
                    <div
                        v-for="l in listes"
                        :key="l.id"
                        class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700"
                        :class="l.elu ? 'ring-2 ring-green-400/50' : ''"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    v-if="l.nuance"
                                    class="inline-block w-3 h-3 rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: getColor(l.nuance) }"
                                />
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900 dark:text-white truncate">{{ l.nom_liste || l.tete_liste }}</div>
                                    <div v-if="l.tete_liste && l.nom_liste" class="text-sm text-slate-500 dark:text-slate-400 truncate">{{ l.tete_liste }}</div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-4">
                                <div class="text-lg font-bold" :class="l.elu ? 'text-green-600 dark:text-green-400' : 'text-slate-900 dark:text-white'">
                                    {{ l.pourcentage ? l.pourcentage.toFixed(2) + '%' : '-' }}
                                </div>
                                <div class="text-xs text-slate-500">{{ formatNumber(l.voix) }} voix</div>
                            </div>
                        </div>

                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{ width: (l.voix / maxVoix * 100) + '%', backgroundColor: getColor(l.nuance) }"
                            />
                        </div>

                        <div class="flex items-center justify-between mt-2 text-xs text-slate-500">
                            <span v-if="l.nuance" class="font-medium">{{ l.nuance }}</span>
                            <span v-if="l.sieges" class="text-slate-600 dark:text-slate-400">{{ l.sieges }} siege(s)</span>
                            <span v-if="l.elu" class="text-green-600 dark:text-green-400 font-bold">Elu(e)</span>
                        </div>
                    </div>
                </div>
            </template>

            <div v-else class="text-center py-16">
                <div class="text-4xl mb-3">🗳️</div>
                <p class="text-slate-500 dark:text-slate-400">Aucun resultat electoral disponible pour cette commune.</p>
            </div>
        </div>
    </CommuneLayout>
</template>
