<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    statsGlobales: Object,
    statsParRegion: Array,
    statsParTaille: Array,
    topVilles: Array,
    statsBudget: Object,
    evolutionPopulation: Array,
    annee: Number,
    anneesDisponibles: Array,
    breadcrumbs: Array,
});

const selectedAnnee = ref(props.annee);
const activeTab = ref('global');

const changeAnnee = () => {
    router.get(route('statistiques.villes'), { annee: selectedAnnee.value }, { preserveState: true });
};

// Graphique régions
const maxPopRegion = computed(() => {
    return Math.max(...props.statsParRegion.map(r => r.population));
});

// Régions métropolitaines vs DROM
const regionsMetropole = computed(() => 
    props.statsParRegion.filter(r => !['01', '02', '03', '04', '06'].includes(r.code))
);
const regionsDrom = computed(() => 
    props.statsParRegion.filter(r => ['01', '02', '03', '04', '06'].includes(r.code))
);

// Format nombre
const formatNumber = (num) => {
    if (!num) return '0';
    return num.toLocaleString('fr-FR');
};

// Onglets
const tabs = [
    { id: 'global', icon: '📊', label: 'Vue globale', shortLabel: 'Global' },
    { id: 'regions', icon: '🗺️', label: 'Par région', shortLabel: 'Régions' },
    { id: 'budget', icon: '💰', label: 'Budgets', shortLabel: 'Budgets' },
    { id: 'top', icon: '🏆', label: 'Top villes', shortLabel: 'Top 20' },
];
</script>

<template>
    <Head title="Statistiques Villes" />

    <AuthenticatedLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <!-- Hero avec motifs -->
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-700 text-white">
                <!-- Motifs décoratifs -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <!-- Cercles -->
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-32 -left-32 w-[500px] h-[500px] bg-teal-400/10 rounded-full blur-3xl"></div>
                    
                    <!-- Grille de points -->
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 40px 40px;"></div>
                    
                    <!-- Formes géométriques -->
                    <div class="absolute top-1/4 right-1/4 w-32 h-32 border border-white/10 rounded-xl rotate-12"></div>
                    <div class="absolute bottom-1/4 left-1/3 w-24 h-24 border border-white/10 rounded-full"></div>
                    <div class="absolute top-1/2 right-1/6 w-16 h-16 bg-white/5 rounded-lg rotate-45"></div>
                    
                    <!-- Icônes flottantes -->
                    <div class="absolute top-16 right-16 text-6xl opacity-10 hidden lg:block">🏘️</div>
                    <div class="absolute bottom-8 right-1/3 text-4xl opacity-10 hidden lg:block">🏛️</div>
                    <div class="absolute top-1/3 left-12 text-3xl opacity-10 hidden lg:block">📊</div>
                </div>

                <div class="relative py-8 md:py-12 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        <!-- Titre -->
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                            <div>
                                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold flex items-center gap-3">
                                    <span class="text-3xl sm:text-4xl">🏘️</span>
                                    <span>Statistiques Territoriales</span>
                                </h1>
                                <p class="text-emerald-100 text-sm sm:text-base lg:text-lg mt-2 max-w-2xl">
                                    {{ statsGlobales.total_villes_formate }} communes, {{ statsGlobales.nb_regions }} régions, {{ statsGlobales.nb_departements }} départements
                                </p>
                            </div>
                            
                            <!-- Badge France -->
                            <div class="flex-shrink-0">
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                                    <span class="text-xl">🇫🇷</span>
                                    <span class="font-medium">France</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats principales - Grid responsive -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10 hover:bg-white/15 transition-colors">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ statsGlobales.total_villes_formate }}</div>
                                <div class="text-xs sm:text-sm text-emerald-200">Communes</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10 hover:bg-white/15 transition-colors">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ statsGlobales.total_population_millions }}M</div>
                                <div class="text-xs sm:text-sm text-emerald-200">Habitants</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10 hover:bg-white/15 transition-colors">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ statsGlobales.nb_regions }}</div>
                                <div class="text-xs sm:text-sm text-emerald-200">Régions</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10 hover:bg-white/15 transition-colors">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ statsGlobales.nb_departements }}</div>
                                <div class="text-xs sm:text-sm text-emerald-200">Départements</div>
                            </div>
                            <div class="col-span-2 sm:col-span-1 bg-white/10 backdrop-blur-sm rounded-xl p-3 sm:p-4 text-center border border-white/10 hover:bg-white/15 transition-colors">
                                <div class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ statsGlobales.densite_moyenne }}</div>
                                <div class="text-xs sm:text-sm text-emerald-200">hab/km² moy.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation par onglets (style boutons pill) -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Boutons de navigation -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button 
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'px-4 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 text-sm',
                            activeTab === tab.id 
                                ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/25' 
                                : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'
                        ]"
                    >
                        <span>{{ tab.icon }}</span>
                        <span class="hidden sm:inline">{{ tab.label }}</span>
                        <span class="sm:hidden">{{ tab.shortLabel }}</span>
                    </button>
                </div>

                <!-- Contenu des onglets -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                        <!-- Vue globale -->
                        <div v-if="activeTab === 'global'" class="space-y-8">
                            <!-- Répartition par taille -->
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    <span>📏</span> Répartition par taille
                                </h3>
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                                    <div 
                                        v-for="tranche in statsGlobales.repartition_taille" 
                                        :key="tranche.label"
                                        class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-700/50 dark:to-slate-700/30 rounded-xl p-4 text-center border border-slate-200/50 dark:border-slate-600/50"
                                    >
                                        <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                                            {{ formatNumber(tranche.count) }}
                                        </div>
                                        <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                                            {{ tranche.label }}
                                        </div>
                                        <div class="mt-3 h-2 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500"
                                                :style="{ width: tranche.pct + '%' }"
                                            ></div>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1">{{ tranche.pct }}%</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Répartition détaillée -->
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    <span>📊</span> Répartition détaillée
                                </h3>
                                <div class="space-y-3">
                                    <div 
                                        v-for="tranche in statsParTaille" 
                                        :key="tranche.label"
                                        class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4"
                                    >
                                        <div class="w-full sm:w-40 text-sm font-medium text-slate-600 dark:text-slate-400">
                                            {{ tranche.label }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="h-8 bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden flex items-center">
                                                <div 
                                                    class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg flex items-center justify-end px-2 transition-all duration-500"
                                                    :style="{ width: Math.max((tranche.count / statsGlobales.total_villes * 100), 3) + '%' }"
                                                >
                                                    <span class="text-xs text-white font-medium whitespace-nowrap">{{ formatNumber(tranche.count) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full sm:w-32 text-right text-sm text-slate-500">
                                            {{ tranche.population_formate }} hab.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lien vers liste complète -->
                            <div class="text-center pt-4">
                                <Link 
                                    :href="route('villes.index')"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40"
                                >
                                    <span>🔍</span>
                                    Explorer toutes les villes
                                </Link>
                            </div>
                        </div>

                        <!-- Par région -->
                        <div v-if="activeTab === 'regions'" class="space-y-6">
                            <!-- Métropole -->
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    <span>🇫🇷</span> France métropolitaine
                                    <span class="text-sm font-normal text-slate-500">({{ regionsMetropole.length }} régions)</span>
                                </h3>
                                
                                <!-- Vue mobile : cartes -->
                                <div class="grid grid-cols-1 sm:hidden gap-3">
                                    <Link
                                        v-for="region in regionsMetropole"
                                        :key="region.code"
                                        :href="route('villes.index', { region: region.code })"
                                        class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                    >
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ region.nom }}</h4>
                                            <span class="text-xs text-slate-500 bg-slate-200 dark:bg-slate-600 px-2 py-0.5 rounded">{{ region.code }}</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-center text-sm">
                                            <div>
                                                <div class="font-bold text-emerald-600">{{ region.population_millions }}M</div>
                                                <div class="text-xs text-slate-500">habitants</div>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-700 dark:text-slate-300">{{ formatNumber(region.nb_villes) }}</div>
                                                <div class="text-xs text-slate-500">communes</div>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-700 dark:text-slate-300">{{ region.densite }}</div>
                                                <div class="text-xs text-slate-500">hab/km²</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 h-2 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"
                                                :style="{ width: (region.population / maxPopRegion * 100) + '%' }"
                                            ></div>
                                        </div>
                                    </Link>
                                </div>

                                <!-- Vue desktop : tableau -->
                                <div class="hidden sm:block overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="text-left py-3 px-4 font-medium text-slate-600 dark:text-slate-400">Région</th>
                                                <th class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400">Communes</th>
                                                <th class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400">Population</th>
                                                <th class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400 hidden lg:table-cell">Superficie</th>
                                                <th class="text-right py-3 px-4 font-medium text-slate-600 dark:text-slate-400">Densité</th>
                                                <th class="py-3 px-4 w-40 hidden md:table-cell">Répartition</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr 
                                                v-for="region in regionsMetropole" 
                                                :key="region.code"
                                                class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                                            >
                                                <td class="py-3 px-4">
                                                    <Link 
                                                        :href="route('villes.index', { region: region.code })"
                                                        class="font-medium text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                                                    >
                                                        {{ region.nom }}
                                                    </Link>
                                                </td>
                                                <td class="py-3 px-4 text-right text-slate-600 dark:text-slate-400">
                                                    {{ formatNumber(region.nb_villes) }}
                                                </td>
                                                <td class="py-3 px-4 text-right font-medium text-slate-900 dark:text-white">
                                                    {{ region.population_formate }}
                                                </td>
                                                <td class="py-3 px-4 text-right text-slate-600 dark:text-slate-400 hidden lg:table-cell">
                                                    {{ formatNumber(region.superficie) }} km²
                                                </td>
                                                <td class="py-3 px-4 text-right text-slate-600 dark:text-slate-400">
                                                    {{ region.densite }} <span class="text-xs">hab/km²</span>
                                                </td>
                                                <td class="py-3 px-4 hidden md:table-cell">
                                                    <div class="h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                                        <div 
                                                            class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all"
                                                            :style="{ width: (region.population / maxPopRegion * 100) + '%' }"
                                                        ></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- DROM -->
                            <div v-if="regionsDrom.length > 0">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    <span>🌴</span> Outre-mer
                                    <span class="text-sm font-normal text-slate-500">({{ regionsDrom.length }} territoires)</span>
                                </h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <Link
                                        v-for="region in regionsDrom"
                                        :key="region.code"
                                        :href="route('villes.index', { region: region.code })"
                                        class="bg-gradient-to-br from-cyan-50 to-teal-50 dark:from-cyan-900/20 dark:to-teal-900/20 rounded-xl p-4 hover:from-cyan-100 hover:to-teal-100 dark:hover:from-cyan-900/30 dark:hover:to-teal-900/30 transition-colors border border-cyan-200/50 dark:border-cyan-800/50"
                                    >
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ region.nom }}</h4>
                                            <span class="text-lg">🏝️</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-slate-500">Population:</span>
                                                <span class="font-medium text-slate-900 dark:text-white ml-1">{{ region.population_formate }}</span>
                                            </div>
                                            <div>
                                                <span class="text-slate-500">Communes:</span>
                                                <span class="font-medium text-slate-900 dark:text-white ml-1">{{ region.nb_villes }}</span>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Budgets -->
                        <div v-if="activeTab === 'budget'" class="space-y-6">
                            <div v-if="!statsBudget?.has_data" class="text-center py-12">
                                <div class="text-6xl mb-4">📊</div>
                                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-300">
                                    Pas de données budgétaires disponibles
                                </h3>
                                <p class="text-slate-500 dark:text-slate-400 mt-2">
                                    Les données OFGL seront importées prochainement.
                                </p>
                            </div>

                            <div v-else>
                                <!-- Sélecteur année -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                        Budgets communaux {{ statsBudget.annee }}
                                    </h3>
                                    <select 
                                        v-if="anneesDisponibles?.length > 0"
                                        v-model="selectedAnnee"
                                        @change="changeAnnee"
                                        class="px-4 py-2 rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 text-sm"
                                    >
                                        <option v-for="a in anneesDisponibles" :key="a" :value="a">{{ a }}</option>
                                    </select>
                                </div>

                                <!-- Stats budget -->
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8">
                                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 text-center border border-green-200/50 dark:border-green-800/50">
                                        <div class="text-xl sm:text-2xl font-bold text-green-700 dark:text-green-400">
                                            {{ statsBudget.recettes_fonctionnement_md }} Md€
                                        </div>
                                        <div class="text-xs sm:text-sm text-green-600 dark:text-green-500">
                                            Recettes fonct.
                                        </div>
                                    </div>
                                    <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl p-4 text-center border border-red-200/50 dark:border-red-800/50">
                                        <div class="text-xl sm:text-2xl font-bold text-red-700 dark:text-red-400">
                                            {{ statsBudget.depenses_fonctionnement_md }} Md€
                                        </div>
                                        <div class="text-xs sm:text-sm text-red-600 dark:text-red-500">
                                            Dépenses fonct.
                                        </div>
                                    </div>
                                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl p-4 text-center border border-amber-200/50 dark:border-amber-800/50">
                                        <div class="text-xl sm:text-2xl font-bold text-amber-700 dark:text-amber-400">
                                            {{ statsBudget.dette_totale_md }} Md€
                                        </div>
                                        <div class="text-xs sm:text-sm text-amber-600 dark:text-amber-500">
                                            Dette totale
                                        </div>
                                    </div>
                                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 text-center border border-blue-200/50 dark:border-blue-800/50">
                                        <div class="text-xl sm:text-2xl font-bold text-blue-700 dark:text-blue-400">
                                            {{ formatNumber(statsBudget.dette_par_habitant) }} €
                                        </div>
                                        <div class="text-xs sm:text-sm text-blue-600 dark:text-blue-500">
                                            Dette/habitant
                                        </div>
                                    </div>
                                </div>

                                <!-- Couverture -->
                                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 text-center">
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        Données couvrant <strong>{{ formatNumber(statsBudget.nb_communes) }}</strong> communes
                                        représentant <strong>{{ statsBudget.population_couverte_formate }}</strong> habitants
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Top villes -->
                        <div v-if="activeTab === 'top'" class="space-y-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                                <Link
                                    v-for="(ville, index) in topVilles"
                                    :key="ville.id"
                                    :href="ville.url"
                                    class="flex items-center gap-3 sm:gap-4 p-3 sm:p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all group border border-transparent hover:border-emerald-200 dark:hover:border-emerald-800"
                                >
                                    <!-- Rang -->
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-full font-bold text-sm sm:text-lg flex-shrink-0"
                                        :class="{
                                            'bg-gradient-to-br from-amber-200 to-amber-400 text-amber-900': index === 0,
                                            'bg-gradient-to-br from-slate-200 to-slate-400 text-slate-700': index === 1,
                                            'bg-gradient-to-br from-orange-200 to-orange-400 text-orange-900': index === 2,
                                            'bg-slate-100 dark:bg-slate-600 text-slate-500 dark:text-slate-400': index > 2,
                                        }"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    
                                    <!-- Infos ville -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors truncate">
                                            {{ ville.nom }}
                                        </h4>
                                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 truncate">
                                            {{ ville.departement }}
                                            <span v-if="ville.region" class="hidden sm:inline"> • {{ ville.region }}</span>
                                        </p>
                                    </div>
                                    
                                    <!-- Population -->
                                    <div class="text-right flex-shrink-0">
                                        <div class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                            {{ ville.population_formate }}
                                        </div>
                                        <div v-if="ville.densite" class="text-xs text-slate-500 hidden sm:block">
                                            {{ ville.densite }} hab/km²
                                        </div>
                                    </div>
                                    
                                    <!-- Photo maire (desktop) -->
                                    <div v-if="ville.maire" class="hidden lg:block flex-shrink-0">
                                        <img
                                            v-if="ville.maire.photo_url"
                                            :src="ville.maire.photo_url"
                                            :alt="ville.maire.nom"
                                            class="w-10 h-10 rounded-full object-cover ring-2 ring-white dark:ring-slate-700"
                                        />
                                        <div v-else class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-lg">
                                            👤
                                        </div>
                                    </div>
                                </Link>
                            </div>
                            
                            <!-- Lien voir plus -->
                            <div class="text-center pt-4">
                                <Link 
                                    :href="route('villes.index', { sort: 'population' })"
                                    class="inline-flex items-center gap-2 text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium transition-colors"
                                >
                                    Voir le classement complet
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Footer info -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 flex items-start gap-3">
                    <span class="text-xl flex-shrink-0">ℹ️</span>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Sources :</strong> INSEE (démographie), OFGL (budgets communaux), 
                        Ministère de l'Intérieur (élus). Les données de population sont issues du 
                        recensement le plus récent disponible.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
