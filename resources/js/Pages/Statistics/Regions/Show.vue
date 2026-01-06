<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    region: Object,
    departements: Array,
    topVilles: Array,
    prefecture: Object,
    moyennesNationales: Object,
    annee: Number,
    breadcrumbs: Array,
});

// Format nombre
const formatNumber = (num) => {
    if (!num) return '0';
    return num.toLocaleString('fr-FR');
};

// Comparaison avec moyenne nationale
const getComparison = (value, nationalValue, isLowerBetter = false) => {
    if (!value || !nationalValue) return null;
    const diff = value - nationalValue;
    const pct = ((diff / nationalValue) * 100).toFixed(1);
    
    if (isLowerBetter) {
        return {
            diff,
            pct,
            isGood: diff < 0,
            label: diff < 0 ? 'inférieur' : 'supérieur',
            icon: diff < 0 ? '✓' : '⚠',
        };
    }
    return {
        diff,
        pct,
        isGood: diff > 0,
        label: diff > 0 ? 'supérieur' : 'inférieur',
        icon: diff > 0 ? '✓' : '⚠',
    };
};

// Indicateurs comparatifs
const comparaisons = computed(() => ({
    chomage: getComparison(props.region.taux_chomage, props.moyennesNationales.taux_chomage, true),
    pauvrete: getComparison(props.region.taux_pauvrete, props.moyennesNationales.taux_pauvrete, true),
    revenu: getComparison(props.region.revenu_median, props.moyennesNationales.revenu_median, false),
}));

// Max population département pour les barres
const maxPopDept = computed(() => Math.max(...props.departements.map(d => d.population)));

// Emoji région
const getRegionEmoji = (nom) => {
    const emojis = {
        'Île-de-France': '🗼',
        'Provence-Alpes-Côte d\'Azur': '☀️',
        'Auvergne-Rhône-Alpes': '🏔️',
        'Occitanie': '🌻',
        'Nouvelle-Aquitaine': '🍷',
        'Bretagne': '⚓',
        'Normandie': '🏰',
        'Hauts-de-France': '⛏️',
        'Grand Est': '🍺',
        'Pays de la Loire': '🚢',
        'Bourgogne-Franche-Comté': '🍇',
        'Centre-Val de Loire': '👑',
        'Corse': '🏝️',
        'Guadeloupe': '🌴',
        'Martinique': '🌺',
        'Guyane': '🌳',
        'La Réunion': '🌋',
        'Mayotte': '🐢',
    };
    return emojis[nom] || '🗺️';
};
</script>

<template>
    <Head :title="'Région ' + region.nom" />

    <AuthenticatedLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50/30 to-purple-50/30 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <!-- Hero -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-violet-700 text-white">
                <!-- Motifs -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-32 -left-32 w-[500px] h-[500px] bg-purple-400/10 rounded-full blur-3xl"></div>
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.08) 1px, transparent 0); background-size: 32px 32px;"></div>
                    <div class="absolute right-8 top-1/2 -translate-y-1/2 text-[8rem] opacity-10 hidden lg:block">
                        {{ getRegionEmoji(region.nom) }}
                    </div>
                </div>

                <div class="relative py-8 md:py-12 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        <!-- Navigation -->
                        <Link 
                            :href="route('statistics.regions.index')"
                            class="inline-flex items-center gap-2 text-indigo-200 hover:text-white mb-4 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Toutes les régions
                        </Link>

                        <!-- Titre -->
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-4xl">{{ getRegionEmoji(region.nom) }}</span>
                                    <div>
                                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold">{{ region.nom }}</h1>
                                        <p class="text-indigo-200">
                                            Code région : {{ region.code }}
                                            <span v-if="region.est_drom" class="ml-2 px-2 py-0.5 bg-cyan-500/20 text-cyan-200 rounded text-xs">Outre-mer</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Préfecture -->
                            <div v-if="prefecture" class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/20">
                                <div class="text-xs text-indigo-200 mb-1">Préfecture de région</div>
                                <Link 
                                    :href="prefecture.url"
                                    class="font-semibold hover:text-indigo-200 transition-colors"
                                >
                                    🏛️ {{ prefecture.nom }}
                                </Link>
                            </div>
                        </div>

                        <!-- Stats principales -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                                <div class="text-2xl sm:text-3xl font-bold">{{ region.population_formate }}</div>
                                <div class="text-sm text-indigo-200">Habitants</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                                <div class="text-2xl sm:text-3xl font-bold">{{ formatNumber(region.nb_communes) }}</div>
                                <div class="text-sm text-indigo-200">Communes</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                                <div class="text-2xl sm:text-3xl font-bold">{{ departements.length }}</div>
                                <div class="text-sm text-indigo-200">Départements</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                                <div class="text-2xl sm:text-3xl font-bold">{{ region.densite }}</div>
                                <div class="text-sm text-indigo-200">hab/km²</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                
                <!-- Indicateurs économiques -->
                <section v-if="region.pib || region.taux_chomage || region.revenu_median" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>📊</span> Indicateurs économiques et sociaux
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                            <!-- PIB -->
                            <div v-if="region.pib_formate" class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200/50 dark:border-blue-800/50">
                                <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">💰 PIB</div>
                                <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ region.pib_formate }}</div>
                            </div>
                            
                            <!-- Chômage -->
                            <div v-if="region.taux_chomage" class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-4 border border-amber-200/50 dark:border-amber-800/50">
                                <div class="text-xs text-amber-600 dark:text-amber-400 font-medium mb-1">📉 Taux de chômage</div>
                                <div class="text-2xl font-bold" :class="region.taux_chomage > 8 ? 'text-red-600' : region.taux_chomage > 6 ? 'text-amber-600' : 'text-green-600'">
                                    {{ region.taux_chomage }}%
                                </div>
                                <div v-if="comparaisons.chomage" class="text-xs mt-1" :class="comparaisons.chomage.isGood ? 'text-green-600' : 'text-red-600'">
                                    {{ comparaisons.chomage.icon }} {{ Math.abs(comparaisons.chomage.diff).toFixed(1) }}pts {{ comparaisons.chomage.label }} à la moyenne
                                </div>
                            </div>
                            
                            <!-- Pauvreté -->
                            <div v-if="region.taux_pauvrete" class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl p-4 border border-red-200/50 dark:border-red-800/50">
                                <div class="text-xs text-red-600 dark:text-red-400 font-medium mb-1">⚠️ Taux de pauvreté</div>
                                <div class="text-2xl font-bold" :class="region.taux_pauvrete > 16 ? 'text-red-600' : 'text-amber-600'">
                                    {{ region.taux_pauvrete }}%
                                </div>
                                <div v-if="comparaisons.pauvrete" class="text-xs mt-1" :class="comparaisons.pauvrete.isGood ? 'text-green-600' : 'text-red-600'">
                                    {{ comparaisons.pauvrete.icon }} {{ Math.abs(comparaisons.pauvrete.diff).toFixed(1) }}pts {{ comparaisons.pauvrete.label }} à la moyenne
                                </div>
                            </div>
                            
                            <!-- Revenu médian -->
                            <div v-if="region.revenu_median_formate" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200/50 dark:border-green-800/50">
                                <div class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">💵 Revenu médian</div>
                                <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ region.revenu_median_formate }}</div>
                                <div v-if="comparaisons.revenu" class="text-xs mt-1" :class="comparaisons.revenu.isGood ? 'text-green-600' : 'text-red-600'">
                                    {{ comparaisons.revenu.icon }} {{ comparaisons.revenu.isGood ? '+' : '' }}{{ comparaisons.revenu.pct }}% vs moyenne
                                </div>
                            </div>
                        </div>

                        <!-- Comparaison nationale -->
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">🇫🇷 Moyennes nationales (référence)</h4>
                            <div class="flex flex-wrap gap-4 text-sm">
                                <span>Chômage: <strong>{{ moyennesNationales.taux_chomage }}%</strong></span>
                                <span>Pauvreté: <strong>{{ moyennesNationales.taux_pauvrete }}%</strong></span>
                                <span>Revenu médian: <strong>{{ formatNumber(moyennesNationales.revenu_median) }} €</strong></span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Départements -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>📍</span> Départements
                            <span class="text-sm font-normal text-slate-500">({{ departements.length }})</span>
                        </h2>
                    </div>
                    
                    <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Link
                            v-for="dept in departements"
                            :key="dept.code"
                            :href="route('villes.index', { departement: dept.code })"
                            class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all group border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-indigo-600 transition-colors">
                                        {{ dept.nom }}
                                    </h3>
                                    <span class="text-xs text-slate-500">{{ dept.code }}</span>
                                </div>
                                <span class="text-lg">🏛️</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                <div>
                                    <div class="text-slate-500 text-xs">Population</div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ dept.population_formate }}</div>
                                </div>
                                <div>
                                    <div class="text-slate-500 text-xs">Communes</div>
                                    <div class="font-medium text-slate-700 dark:text-slate-300">{{ dept.nb_communes }}</div>
                                </div>
                            </div>
                            <div class="h-2 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full"
                                    :style="{ width: (dept.population / maxPopDept * 100) + '%' }"
                                ></div>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- Top 10 villes -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🏆</span> Top 10 des villes
                        </h2>
                        <Link 
                            :href="route('villes.index', { region: region.code })"
                            class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
                        >
                            Voir toutes →
                        </Link>
                    </div>
                    
                    <div class="p-4 sm:p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            <Link
                                v-for="(ville, index) in topVilles"
                                :key="ville.id"
                                :href="ville.url"
                                class="flex items-center gap-4 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all group"
                            >
                                <!-- Rang -->
                                <div 
                                    class="w-8 h-8 flex items-center justify-center rounded-full font-bold text-sm flex-shrink-0"
                                    :class="{
                                        'bg-gradient-to-br from-amber-200 to-amber-400 text-amber-900': index === 0,
                                        'bg-gradient-to-br from-slate-200 to-slate-400 text-slate-700': index === 1,
                                        'bg-gradient-to-br from-orange-200 to-orange-400 text-orange-900': index === 2,
                                        'bg-slate-100 dark:bg-slate-600 text-slate-500': index > 2,
                                    }"
                                >
                                    {{ index + 1 }}
                                </div>
                                
                                <!-- Infos -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-semibold text-slate-900 dark:text-white group-hover:text-indigo-600 transition-colors truncate">
                                            {{ ville.nom }}
                                        </h4>
                                        <span v-if="ville.est_prefecture" class="text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded">
                                            Préf.
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-500 truncate">{{ ville.departement }}</p>
                                </div>
                                
                                <!-- Population -->
                                <div class="text-right flex-shrink-0">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ ville.population_formate }}</div>
                                    <div class="text-xs text-slate-500">habitants</div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </section>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link 
                        :href="route('villes.index', { region: region.code })"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-indigo-500/25"
                    >
                        <span>🏘️</span>
                        Explorer les {{ formatNumber(region.nb_communes) }} communes
                    </Link>
                    <Link 
                        :href="route('statistics.regions.index')"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all"
                    >
                        <span>🗺️</span>
                        Toutes les régions
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
