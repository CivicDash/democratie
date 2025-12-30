<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    effectifs: Object,
    ages: Object,
    parite: Object,
    professions: Object,
    groupes: Object,
    totaux: Object,
});

// Configuration des 3 catégories
const categories = [
    { key: 'deputes', label: 'Députés', icon: '🏛️', color: 'blue', bgGradient: 'from-blue-500 to-blue-600' },
    { key: 'senateurs', label: 'Sénateurs', icon: '🏰', color: 'purple', bgGradient: 'from-purple-500 to-purple-600' },
    { key: 'maires', label: 'Maires', icon: '🏘️', color: 'emerald', bgGradient: 'from-emerald-500 to-emerald-600' },
];

// Helpers
const getColorClass = (color, type = 'text') => {
    const colors = {
        blue: { text: 'text-blue-600', bg: 'bg-blue-500', bgLight: 'bg-blue-50 dark:bg-blue-900/20', border: 'border-blue-200' },
        purple: { text: 'text-purple-600', bg: 'bg-purple-500', bgLight: 'bg-purple-50 dark:bg-purple-900/20', border: 'border-purple-200' },
        emerald: { text: 'text-emerald-600', bg: 'bg-emerald-500', bgLight: 'bg-emerald-50 dark:bg-emerald-900/20', border: 'border-emerald-200' },
    };
    return colors[color]?.[type] || '';
};

// Format nombre avec séparateur de milliers
const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};
</script>

<template>
    <Head title="Statistiques des Élus Français" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header avec stats globales -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                        📊 Statistiques des Élus Français
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">
                        Députés, Sénateurs et Maires en exercice
                    </p>
                    
                    <!-- Résumé global -->
                    <div class="inline-flex items-center gap-6 px-6 py-4 bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl text-white">
                        <div class="text-center">
                            <div class="text-3xl font-bold">{{ formatNumber(totaux.elus_total) }}</div>
                            <div class="text-sm text-slate-300">élus au total</div>
                        </div>
                        <div class="w-px h-12 bg-slate-600"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-pink-400">{{ totaux.pct_femmes_global }}%</div>
                            <div class="text-sm text-slate-300">de femmes</div>
                        </div>
                        <div class="w-px h-12 bg-slate-600"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-400">{{ formatNumber(totaux.hommes_total) }}</div>
                            <div class="text-sm text-slate-300">hommes</div>
                        </div>
                        <div class="w-px h-12 bg-slate-600"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-pink-400">{{ formatNumber(totaux.femmes_total) }}</div>
                            <div class="text-sm text-slate-300">femmes</div>
                        </div>
                    </div>
                </div>

                <!-- ===== EFFECTIFS ===== -->
                <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <span>👥</span> Effectifs
                    </h2>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div 
                            v-for="cat in categories" 
                            :key="cat.key"
                            class="relative overflow-hidden rounded-xl p-6"
                            :class="`bg-gradient-to-br ${cat.bgGradient}`"
                        >
                            <div class="absolute top-2 right-2 text-5xl opacity-20">{{ cat.icon }}</div>
                            <div class="relative">
                                <div class="text-white/80 text-sm font-medium mb-1">{{ cat.label }}</div>
                                <div class="text-5xl font-bold text-white mb-2">
                                    {{ formatNumber(effectifs[cat.key].actifs) }}
                                </div>
                                <div class="text-white/70 text-sm" v-if="effectifs[cat.key].total !== effectifs[cat.key].actifs">
                                    sur {{ formatNumber(effectifs[cat.key].total) }} sièges
                                </div>
                                <div class="text-white/70 text-sm" v-else>
                                    en exercice
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ===== PARITÉ ===== -->
                <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <span>⚖️</span> Parité Hommes / Femmes
                    </h2>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div 
                            v-for="cat in categories" 
                            :key="cat.key"
                            class="rounded-xl border p-6"
                            :class="getColorClass(cat.color, 'border')"
                        >
                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-2xl">{{ cat.icon }}</span>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ cat.label }}</h3>
                            </div>
                            
                            <!-- Pourcentage femmes -->
                            <div class="text-center mb-4">
                                <div class="text-4xl font-bold text-pink-600">{{ parite[cat.key].pct_femmes }}%</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">de femmes</div>
                            </div>
                            
                            <!-- Barre de répartition -->
                            <div class="h-4 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 mb-4">
                                <div 
                                    class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500"
                                    :style="{ width: (100 - parite[cat.key].pct_femmes) + '%' }"
                                ></div>
                            </div>
                            
                            <!-- Détails -->
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <span>👨 Hommes</span>
                                    <span class="font-bold">{{ formatNumber(parite[cat.key].hommes) }}</span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                    <span>👩 Femmes</span>
                                    <span class="font-bold">{{ formatNumber(parite[cat.key].femmes) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comparatif parité -->
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                        <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">Classement parité</div>
                        <div class="flex items-center justify-center gap-4 flex-wrap">
                            <template v-for="(cat, index) in [...categories].sort((a, b) => parite[b.key].pct_femmes - parite[a.key].pct_femmes)" :key="cat.key">
                                <div class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                    <span class="text-lg font-bold" :class="index === 0 ? 'text-yellow-500' : 'text-gray-400'">
                                        {{ index + 1 }}
                                    </span>
                                    <span class="text-xl">{{ cat.icon }}</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ cat.label }}</span>
                                    <span class="font-bold text-pink-600">{{ parite[cat.key].pct_femmes }}%</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                <!-- ===== ÂGE ===== -->
                <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <span>🎂</span> Répartition par Âge
                    </h2>
                    
                    <!-- Stats clés -->
                    <div class="grid md:grid-cols-3 gap-4 mb-8">
                        <div 
                            v-for="cat in categories" 
                            :key="cat.key"
                            class="text-center p-4 rounded-xl"
                            :class="getColorClass(cat.color, 'bgLight')"
                        >
                            <div class="text-2xl mb-1">{{ cat.icon }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Âge moyen</div>
                            <div class="text-3xl font-bold" :class="getColorClass(cat.color, 'text')">
                                {{ ages[cat.key].moyenne }}
                            </div>
                            <div class="text-xs text-gray-500">ans</div>
                            <div class="text-xs text-gray-400 mt-1">
                                ({{ ages[cat.key].min }} - {{ ages[cat.key].max }} ans)
                            </div>
                        </div>
                    </div>
                    
                    <!-- Distribution par tranche -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Tranche</th>
                                    <th v-for="cat in categories" :key="cat.key" class="text-center py-3 px-4 font-semibold" :class="getColorClass(cat.color, 'text')">
                                        {{ cat.icon }} {{ cat.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="(_, tranche) in ages.deputes.distribution" 
                                    :key="tranche"
                                    class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                >
                                    <td class="py-3 px-4 font-medium text-gray-700 dark:text-gray-300">{{ tranche }}</td>
                                    <td v-for="cat in categories" :key="cat.key" class="text-center py-3 px-4">
                                        <div class="inline-flex items-center gap-2">
                                            <span class="font-bold" :class="getColorClass(cat.color, 'text')">
                                                {{ formatNumber(ages[cat.key].distribution[tranche]) }}
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                ({{ effectifs[cat.key].actifs > 0 ? ((ages[cat.key].distribution[tranche] / effectifs[cat.key].actifs) * 100).toFixed(1) : 0 }}%)
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- ===== PROFESSIONS ===== -->
                <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <span>💼</span> Top 10 des Professions
                    </h2>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div v-for="cat in categories" :key="cat.key">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                <span class="text-xl">{{ cat.icon }}</span>
                                {{ cat.label }}
                            </h3>
                            
                            <div class="space-y-1">
                                <div 
                                    v-for="(prof, index) in professions[cat.key]" 
                                    :key="index"
                                    class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                >
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <span 
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                            :class="getColorClass(cat.color, 'bg')"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                            {{ prof.profession }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold ml-2" :class="getColorClass(cat.color, 'text')">
                                        {{ formatNumber(prof.count) }}
                                    </span>
                                </div>
                                
                                <div v-if="professions[cat.key].length === 0" class="text-center py-4 text-gray-400">
                                    Aucune donnée
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ===== GROUPES / NUANCES POLITIQUES ===== -->
                <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <span>🎨</span> Groupes & Nuances Politiques
                    </h2>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div v-for="cat in categories" :key="cat.key">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                <span class="text-xl">{{ cat.icon }}</span>
                                {{ cat.label }}
                                <span class="text-xs text-gray-500 font-normal">
                                    {{ cat.key === 'maires' ? '(nuances)' : '(groupes)' }}
                                </span>
                            </h3>
                            
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <div 
                                    v-for="groupe in groupes[cat.key]" 
                                    :key="groupe.sigle"
                                    class="flex items-center justify-between py-2 px-4 rounded-lg"
                                    :class="getColorClass(cat.color, 'bgLight')"
                                >
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                            {{ groupe.sigle }}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 truncate" v-if="groupe.nom !== groupe.sigle">
                                            {{ groupe.nom }}
                                        </div>
                                    </div>
                                    <div class="text-lg font-bold ml-3" :class="getColorClass(cat.color, 'text')">
                                        {{ formatNumber(groupe.effectif) }}
                                    </div>
                                </div>
                                
                                <div v-if="groupes[cat.key].length === 0" class="text-center py-4 text-gray-400">
                                    Aucune donnée
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
