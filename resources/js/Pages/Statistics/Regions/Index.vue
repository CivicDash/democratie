<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    regions: Array,
    totaux: Object,
    annee: Number,
    anneesDisponibles: Array,
    breadcrumbs: Array,
});

const selectedAnnee = ref(props.annee);
const sortBy = ref('population');
const sortOrder = ref('desc');

const changeAnnee = () => {
    router.get(route('statistics.regions.index'), { annee: selectedAnnee.value }, { preserveState: true });
};

// Régions triées
const regionsSorted = computed(() => {
    const sorted = [...props.regions].sort((a, b) => {
        const valA = a[sortBy.value] ?? 0;
        const valB = b[sortBy.value] ?? 0;
        return sortOrder.value === 'desc' ? valB - valA : valA - valB;
    });
    return sorted;
});

// Séparation métropole / DROM
const regionsMetropole = computed(() => regionsSorted.value.filter(r => !r.est_drom));
const regionsDrom = computed(() => regionsSorted.value.filter(r => r.est_drom));

// Max population pour les barres
const maxPop = computed(() => Math.max(...props.regions.map(r => r.population)));

// Format nombre
const formatNumber = (num) => {
    if (!num) return '0';
    return num.toLocaleString('fr-FR');
};

// Toggle tri
const toggleSort = (column) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'desc';
    }
};

// Couleurs par indicateur
const getIndicatorColor = (value, type, isGood = null) => {
    if (value === null || value === undefined) return 'text-slate-400';
    
    if (type === 'chomage') {
        if (value < 6) return 'text-green-600';
        if (value < 8) return 'text-yellow-600';
        return 'text-red-600';
    }
    if (type === 'pauvrete') {
        if (value < 12) return 'text-green-600';
        if (value < 16) return 'text-yellow-600';
        return 'text-red-600';
    }
    return 'text-slate-700 dark:text-slate-300';
};
</script>

<template>
    <Head title="Statistiques Régions" />

    <AuthenticatedLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50/30 to-purple-50/30 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <!-- Hero avec motifs -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-violet-700 text-white">
                <!-- Motifs décoratifs -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-32 -left-32 w-[500px] h-[500px] bg-purple-400/10 rounded-full blur-3xl"></div>
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.08) 1px, transparent 0); background-size: 32px 32px;"></div>
                    
                    <!-- Carte de France stylisée -->
                    <div class="absolute right-8 top-1/2 -translate-y-1/2 w-64 h-64 opacity-10 hidden lg:block">
                        <svg viewBox="0 0 100 100" class="w-full h-full">
                            <path fill="currentColor" d="M50,5 L75,15 L85,35 L90,55 L80,75 L60,90 L40,95 L25,85 L15,65 L10,45 L20,25 L35,10 Z"/>
                        </svg>
                    </div>
                </div>

                <div class="relative py-8 md:py-12 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                            <div>
                                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold flex items-center gap-3">
                                    <span class="text-3xl sm:text-4xl">🗺️</span>
                                    <span>Régions de France</span>
                                </h1>
                                <p class="text-indigo-100 text-sm sm:text-base lg:text-lg mt-2">
                                    {{ totaux.nb_regions }} régions • {{ formatNumber(totaux.nb_communes) }} communes • {{ totaux.population_millions }}M habitants
                                </p>
                            </div>
                            
                            <!-- Sélecteur année -->
                            <div class="flex items-center gap-3">
                                <label class="text-indigo-200 text-sm">Année :</label>
                                <select 
                                    v-model="selectedAnnee"
                                    @change="changeAnnee"
                                    class="px-4 py-2 bg-white/10 backdrop-blur border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-white/30"
                                >
                                    <option v-for="a in anneesDisponibles" :key="a" :value="a" class="text-slate-900">{{ a }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">18</div>
                                <div class="text-xs sm:text-sm text-indigo-200">Régions</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">13</div>
                                <div class="text-xs sm:text-sm text-indigo-200">Métropole</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">5</div>
                                <div class="text-xs sm:text-sm text-indigo-200">Outre-mer</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ totaux.population_millions }}M</div>
                                <div class="text-xs sm:text-sm text-indigo-200">Habitants</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                
                <!-- France métropolitaine -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🇫🇷</span> France métropolitaine
                            <span class="text-sm font-normal text-slate-500">({{ regionsMetropole.length }} régions)</span>
                        </h2>
                    </div>

                    <!-- Vue mobile : cartes -->
                    <div class="sm:hidden p-4 space-y-3">
                        <Link
                            v-for="region in regionsMetropole"
                            :key="region.code"
                            :href="route('statistics.regions.show', region.code)"
                            class="block bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ region.nom }}</h3>
                                <span class="text-xs text-slate-500 bg-slate-200 dark:bg-slate-600 px-2 py-0.5 rounded">{{ region.code }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <div class="text-slate-500 text-xs">Population</div>
                                    <div class="font-bold text-indigo-600">{{ region.population_millions }}M</div>
                                </div>
                                <div>
                                    <div class="text-slate-500 text-xs">Communes</div>
                                    <div class="font-medium">{{ formatNumber(region.nb_communes) }}</div>
                                </div>
                                <div v-if="region.taux_chomage">
                                    <div class="text-slate-500 text-xs">Chômage</div>
                                    <div :class="getIndicatorColor(region.taux_chomage, 'chomage')" class="font-medium">{{ region.taux_chomage }}%</div>
                                </div>
                                <div v-if="region.pib_formate">
                                    <div class="text-slate-500 text-xs">PIB</div>
                                    <div class="font-medium">{{ region.pib_formate }}</div>
                                </div>
                            </div>
                            <div class="mt-3 h-2 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full"
                                    :style="{ width: (region.population / maxPop * 100) + '%' }"
                                ></div>
                            </div>
                        </Link>
                    </div>

                    <!-- Vue desktop : tableau -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="text-left py-3 px-4 font-medium text-slate-600 dark:text-slate-400">Région</th>
                                    <th 
                                        @click="toggleSort('nb_communes')" 
                                        class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400 cursor-pointer hover:text-indigo-600"
                                    >
                                        Communes
                                        <span v-if="sortBy === 'nb_communes'">{{ sortOrder === 'desc' ? '↓' : '↑' }}</span>
                                    </th>
                                    <th 
                                        @click="toggleSort('population')" 
                                        class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400 cursor-pointer hover:text-indigo-600"
                                    >
                                        Population
                                        <span v-if="sortBy === 'population'">{{ sortOrder === 'desc' ? '↓' : '↑' }}</span>
                                    </th>
                                    <th class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400 hidden lg:table-cell">Densité</th>
                                    <th 
                                        @click="toggleSort('pib')" 
                                        class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400 cursor-pointer hover:text-indigo-600 hidden md:table-cell"
                                    >
                                        PIB
                                        <span v-if="sortBy === 'pib'">{{ sortOrder === 'desc' ? '↓' : '↑' }}</span>
                                    </th>
                                    <th 
                                        @click="toggleSort('taux_chomage')" 
                                        class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400 cursor-pointer hover:text-indigo-600 hidden lg:table-cell"
                                    >
                                        Chômage
                                        <span v-if="sortBy === 'taux_chomage'">{{ sortOrder === 'desc' ? '↓' : '↑' }}</span>
                                    </th>
                                    <th class="py-3 px-4 w-32 hidden xl:table-cell"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="region in regionsMetropole" 
                                    :key="region.code"
                                    class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                                >
                                    <td class="py-4 px-4">
                                        <Link 
                                            :href="route('statistics.regions.show', region.code)"
                                            class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                        >
                                            {{ region.nom }}
                                        </Link>
                                        <div class="text-xs text-slate-500">Code : {{ region.code }}</div>
                                    </td>
                                    <td class="py-4 px-4 text-right text-slate-600 dark:text-slate-400">
                                        {{ formatNumber(region.nb_communes) }}
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ region.population_formate }}</div>
                                        <div class="text-xs text-slate-500">{{ region.population_millions }}M</div>
                                    </td>
                                    <td class="py-4 px-4 text-right text-slate-600 dark:text-slate-400 hidden lg:table-cell">
                                        {{ region.densite }} <span class="text-xs">hab/km²</span>
                                    </td>
                                    <td class="py-4 px-4 text-right hidden md:table-cell">
                                        <span v-if="region.pib_formate" class="font-medium text-slate-900 dark:text-white">
                                            {{ region.pib_formate }}
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                    <td class="py-4 px-4 text-right hidden lg:table-cell">
                                        <span 
                                            v-if="region.taux_chomage" 
                                            :class="getIndicatorColor(region.taux_chomage, 'chomage')"
                                            class="font-medium"
                                        >
                                            {{ region.taux_chomage }}%
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                    <td class="py-4 px-4 hidden xl:table-cell">
                                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all"
                                                :style="{ width: (region.population / maxPop * 100) + '%' }"
                                            ></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Outre-mer -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🌴</span> Outre-mer
                            <span class="text-sm font-normal text-slate-500">({{ regionsDrom.length }} territoires)</span>
                        </h2>
                    </div>

                    <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Link
                            v-for="region in regionsDrom"
                            :key="region.code"
                            :href="route('statistics.regions.show', region.code)"
                            class="bg-gradient-to-br from-cyan-50 to-teal-50 dark:from-cyan-900/20 dark:to-teal-900/20 rounded-xl p-5 hover:from-cyan-100 hover:to-teal-100 dark:hover:from-cyan-900/30 dark:hover:to-teal-900/30 transition-all border border-cyan-200/50 dark:border-cyan-800/50 group"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg text-slate-900 dark:text-white group-hover:text-cyan-700 dark:group-hover:text-cyan-400 transition-colors">
                                        {{ region.nom }}
                                    </h3>
                                    <span class="text-xs text-slate-500">Code : {{ region.code }}</span>
                                </div>
                                <span class="text-2xl">🏝️</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div class="text-slate-500 text-xs mb-1">Population</div>
                                    <div class="font-bold text-cyan-700 dark:text-cyan-400">{{ region.population_formate }}</div>
                                </div>
                                <div>
                                    <div class="text-slate-500 text-xs mb-1">Communes</div>
                                    <div class="font-medium text-slate-900 dark:text-white">{{ region.nb_communes }}</div>
                                </div>
                                <div v-if="region.taux_chomage">
                                    <div class="text-slate-500 text-xs mb-1">Chômage</div>
                                    <div :class="getIndicatorColor(region.taux_chomage, 'chomage')" class="font-medium">{{ region.taux_chomage }}%</div>
                                </div>
                                <div>
                                    <div class="text-slate-500 text-xs mb-1">Densité</div>
                                    <div class="font-medium text-slate-900 dark:text-white">{{ region.densite }} hab/km²</div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- Légende indicateurs -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <span class="text-xl flex-shrink-0">ℹ️</span>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Légende taux de chômage :</strong>
                        <span class="inline-flex items-center gap-4 ml-2">
                            <span class="text-green-600">● &lt; 6%</span>
                            <span class="text-yellow-600">● 6-8%</span>
                            <span class="text-red-600">● &gt; 8%</span>
                        </span>
                    </div>
                </div>

                <!-- Lien retour -->
                <div class="text-center">
                    <Link 
                        :href="route('statistics.france')"
                        class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Retour aux statistiques France
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
