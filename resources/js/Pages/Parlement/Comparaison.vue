<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    effectifs: Object,
    ages: Object,
    parite: Object,
    professions: Object,
    groupes: Object,
    totaux: Object,
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Données', icon: '📊' },
    { label: 'Statistiques Élus', current: true, icon: '📈' },
];

// Configuration des 3 catégories
const categories = [
    { key: 'deputes', label: 'Députés', icon: '🏛️', color: 'blue', bgGradient: 'from-blue-500 to-blue-600' },
    { key: 'senateurs', label: 'Sénateurs', icon: '🔴', color: 'purple', bgGradient: 'from-purple-500 to-purple-600' },
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

// Format nombre avec séparateur de milliers - gérer NaN
const formatNumber = (num) => {
    if (num === null || num === undefined || isNaN(num)) return '0';
    return new Intl.NumberFormat('fr-FR').format(num);
};

// Safe percentage
const safePct = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 0;
    return val;
};

// Safe number
const safeNum = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 0;
    return val;
};
</script>

<template>
    <Head title="Statistiques des Élus Français" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#28285a] via-[#1e1e4a] to-slate-900">
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex items-center gap-6 mb-8">
                    <div class="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center text-4xl">
                        📊
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                            Statistiques des Élus Français
                        </h1>
                        <p class="text-blue-200 text-lg">
                            Députés, Sénateurs et Maires en exercice
                        </p>
                    </div>
                </div>

                <!-- Chiffres clés dans la hero -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl md:text-4xl font-bold text-white">{{ formatNumber(safeNum(totaux?.elus_total)) }}</div>
                        <div class="text-blue-200 text-sm">élus au total</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl md:text-4xl font-bold text-pink-400">{{ safePct(totaux?.pct_femmes_global) }}%</div>
                        <div class="text-blue-200 text-sm">de femmes</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl md:text-4xl font-bold text-blue-400">{{ formatNumber(safeNum(totaux?.hommes_total)) }}</div>
                        <div class="text-blue-200 text-sm">hommes</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl md:text-4xl font-bold text-pink-400">{{ formatNumber(safeNum(totaux?.femmes_total)) }}</div>
                        <div class="text-blue-200 text-sm">femmes</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-8">

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
                                <div class="text-4xl font-bold text-pink-600">{{ safePct(parite[cat.key]?.pct_femmes) }}%</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">de femmes</div>
                            </div>
                            
                            <!-- Barre de répartition -->
                            <div class="h-4 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 mb-4">
                                <div 
                                    class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500"
                                    :style="{ width: (100 - safePct(parite[cat.key]?.pct_femmes)) + '%' }"
                                ></div>
                            </div>
                            
                            <!-- Détails -->
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <span>👨 Hommes</span>
                                    <span class="font-bold">{{ formatNumber(safeNum(parite[cat.key]?.hommes)) }}</span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                    <span>👩 Femmes</span>
                                    <span class="font-bold">{{ formatNumber(safeNum(parite[cat.key]?.femmes)) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comparatif parité -->
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                        <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">Classement parité</div>
                        <div class="flex items-center justify-center gap-4 flex-wrap">
                            <template v-for="(cat, index) in [...categories].sort((a, b) => safePct(parite[b.key]?.pct_femmes) - safePct(parite[a.key]?.pct_femmes))" :key="cat.key">
                                <div class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                    <span class="text-lg font-bold" :class="index === 0 ? 'text-yellow-500' : 'text-gray-400'">
                                        {{ index + 1 }}
                                    </span>
                                    <span class="text-xl">{{ cat.icon }}</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ cat.label }}</span>
                                    <span class="font-bold text-pink-600">{{ safePct(parite[cat.key]?.pct_femmes) }}%</span>
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
                                {{ safeNum(ages[cat.key]?.moyenne) }}
                            </div>
                            <div class="text-xs text-gray-500">ans</div>
                            <div class="text-xs text-gray-400 mt-1">
                                ({{ safeNum(ages[cat.key]?.min) }} - {{ safeNum(ages[cat.key]?.max) }} ans)
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
                                    v-for="tranche in ['< 30 ans', '30-39 ans', '40-49 ans', '50-59 ans', '60-69 ans', '70+ ans']" 
                                    :key="tranche"
                                    class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                >
                                    <td class="py-3 px-4 font-medium text-gray-700 dark:text-gray-300">{{ tranche }}</td>
                                    <td v-for="cat in categories" :key="cat.key" class="text-center py-3 px-4">
                                        <div class="inline-flex items-center gap-2">
                                            <span class="font-bold" :class="getColorClass(cat.color, 'text')">
                                                {{ formatNumber(safeNum(ages[cat.key]?.distribution?.[tranche])) }}
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                ({{ safeNum(effectifs[cat.key]?.actifs) > 0 ? ((safeNum(ages[cat.key]?.distribution?.[tranche]) / safeNum(effectifs[cat.key]?.actifs)) * 100).toFixed(1) : 0 }}%)
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
                                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate" :title="prof.nom">
                                            {{ prof.nom }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold ml-2" :class="getColorClass(cat.color, 'text')">
                                        {{ formatNumber(prof.count) }}
                                    </span>
                                </div>
                                
                                <div v-if="!professions[cat.key] || professions[cat.key].length === 0" class="text-center py-4 text-gray-400">
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
                                    v-for="(groupe, index) in groupes[cat.key]" 
                                    :key="index"
                                    class="flex items-center justify-between py-2 px-4 rounded-lg"
                                    :class="getColorClass(cat.color, 'bgLight')"
                                >
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <div 
                                            class="w-3 h-3 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: groupe.couleur || '#6b7280' }"
                                        ></div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm truncate" :title="groupe.nom">
                                            {{ groupe.nom }}
                                        </div>
                                    </div>
                                    <div class="text-lg font-bold ml-3" :class="getColorClass(cat.color, 'text')">
                                        {{ formatNumber(groupe.count) }}
                                    </div>
                                </div>
                                
                                <div v-if="!groupes[cat.key] || groupes[cat.key].length === 0" class="text-center py-4 text-gray-400">
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

<style scoped>
/* Animation pour les barres */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 500ms;
}
</style>
