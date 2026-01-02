<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    stats: Object,
    parPresident: Array,
    pariteParAnnee: Array,
    topMinistres: Array,
    evolutionMembres: Array,
    records: Object,
    repartitionTypes: Object,
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Données', icon: '📊' },
    { label: 'Statistiques Gouvernements', current: true, icon: '🏛️' },
];

// Labels des types de fonction
const typeLabels = {
    'premier_ministre': 'Premiers ministres',
    'ministre_etat': 'Ministres d\'État',
    'ministre': 'Ministres',
    'ministre_delegue': 'Ministres délégués',
    'secretaire_etat': 'Secrétaires d\'État',
};

// Couleurs pour les graphiques
const presidentColors = {
    'Emmanuel Macron': '#FFD700',
    'François Hollande': '#FF69B4',
    'Nicolas Sarkozy': '#0066CC',
    'Jacques Chirac': '#0066CC',
    'François Mitterrand': '#FF69B4',
    'Valéry Giscard d\'Estaing': '#87CEEB',
    'Georges Pompidou': '#0066CC',
    'Charles de Gaulle': '#0066CC',
};

// Calcul du max pour les barres de parité
const maxParite = computed(() => {
    return Math.max(...props.pariteParAnnee.map(p => p.total));
});

// Formater la durée en texte
const formatDuree = (jours) => {
    if (jours < 30) return `${jours} jours`;
    if (jours < 365) return `${Math.round(jours / 30)} mois`;
    const ans = Math.floor(jours / 365);
    const mois = Math.round((jours % 365) / 30);
    return mois > 0 ? `${ans} an${ans > 1 ? 's' : ''} et ${mois} mois` : `${ans} an${ans > 1 ? 's' : ''}`;
};
</script>

<template>
    <Head title="Statistiques gouvernementales" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#28285a] via-[#1e1e4a] to-slate-900">
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex items-center gap-6">
                    <div class="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center text-4xl">
                        📊
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                            Statistiques gouvernementales
                        </h1>
                        <p class="text-blue-200 text-lg">
                            Analyse des gouvernements de la Ve République
                        </p>
                    </div>
                </div>

                <!-- Chiffres clés -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.total_gouvernements }}</div>
                        <div class="text-blue-200 text-sm">Gouvernements</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.moyenne_membres }}</div>
                        <div class="text-blue-200 text-sm">Membres en moyenne</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ formatDuree(stats.duree_moyenne_jours) }}</div>
                        <div class="text-blue-200 text-sm">Durée moyenne</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.total_ministres_uniques }}</div>
                        <div class="text-blue-200 text-sm">Ministres distincts</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid lg:grid-cols-2 gap-6 mb-8">
                    <!-- Records -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            🏆 Records
                        </h2>
                        <div class="space-y-4">
                            <div v-if="records.plus_long" class="flex items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                <div>
                                    <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">⏱️ Le plus long</div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ records.plus_long.nom }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">PM: {{ records.plus_long.premier_ministre }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-emerald-600">{{ records.plus_long.duree }}</div>
                                </div>
                            </div>
                            
                            <div v-if="records.plus_court" class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                <div>
                                    <div class="text-xs text-amber-600 dark:text-amber-400 font-medium">⚡ Le plus court</div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ records.plus_court.nom }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">PM: {{ records.plus_court.premier_ministre }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-amber-600">{{ records.plus_court.duree }}</div>
                                </div>
                            </div>

                            <div v-if="records.plus_nombreux" class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">👥 Le plus nombreux</div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ records.plus_nombreux.nom }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">PM: {{ records.plus_nombreux.premier_ministre }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-blue-600">{{ records.plus_nombreux.nb_membres }}</div>
                                    <div class="text-xs text-gray-500">membres</div>
                                </div>
                            </div>

                            <div v-if="records.moins_nombreux" class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <div>
                                    <div class="text-xs text-purple-600 dark:text-purple-400 font-medium">🎯 Le plus resserré</div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ records.moins_nombreux.nom }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">PM: {{ records.moins_nombreux.premier_ministre }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-purple-600">{{ records.moins_nombreux.nb_membres }}</div>
                                    <div class="text-xs text-gray-500">membres</div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Par Président -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            🏛️ Par Président
                        </h2>
                        <div class="space-y-3">
                            <div 
                                v-for="pres in parPresident" 
                                :key="pres.president"
                                class="relative"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ pres.president }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ pres.nb_gouvernements }} gouv. • {{ pres.moyenne_membres }} moy.
                                    </span>
                                </div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{ 
                                            width: (pres.nb_gouvernements / 12 * 100) + '%', 
                                            backgroundColor: presidentColors[pres.president] || '#6b7280',
                                            minWidth: '8%'
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Évolution de la parité -->
                <Card class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                        ⚖️ Évolution de la parité (% de femmes)
                    </h2>
                    <div class="overflow-x-auto">
                        <div class="flex items-end gap-1 h-48 min-w-[800px]">
                            <div 
                                v-for="annee in pariteParAnnee" 
                                :key="annee.annee"
                                class="flex-1 flex flex-col items-center"
                            >
                                <div class="w-full flex flex-col items-center">
                                    <!-- Pourcentage -->
                                    <span class="text-xs font-bold mb-1" :class="annee.pct_femmes >= 40 ? 'text-emerald-600' : 'text-gray-500'">
                                        {{ annee.pct_femmes }}%
                                    </span>
                                    <!-- Barre -->
                                    <div 
                                        class="w-full max-w-8 rounded-t transition-all duration-500"
                                        :class="annee.pct_femmes >= 40 ? 'bg-emerald-500' : annee.pct_femmes >= 30 ? 'bg-amber-500' : 'bg-rose-400'"
                                        :style="{ height: Math.max(annee.pct_femmes * 1.5, 4) + 'px' }"
                                    ></div>
                                </div>
                                <!-- Année -->
                                <span class="text-xs text-gray-500 mt-2 -rotate-45 origin-top-left">
                                    {{ annee.annee }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-center gap-6 mt-6 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded bg-emerald-500"></div>
                            <span class="text-gray-600 dark:text-gray-400">≥ 40% (parité)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded bg-amber-500"></div>
                            <span class="text-gray-600 dark:text-gray-400">30-40%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded bg-rose-400"></div>
                            <span class="text-gray-600 dark:text-gray-400">&lt; 30%</span>
                        </div>
                    </div>
                </Card>

                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Top Ministres -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            🌟 Top 10 des ministres les plus présents
                        </h2>
                        <div class="space-y-3">
                            <Link
                                v-for="(ministre, index) in topMinistres"
                                :key="ministre.slug"
                                :href="route('gouvernement.personne', ministre.slug)"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition group"
                            >
                                <div class="w-8 h-8 flex items-center justify-center rounded-full font-bold text-sm"
                                     :class="index < 3 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                                    {{ index + 1 }}
                                </div>
                                <img 
                                    v-if="ministre.photo"
                                    :src="ministre.photo"
                                    :alt="ministre.nom"
                                    class="w-10 h-10 rounded-full object-cover"
                                />
                                <div v-else class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg">
                                    👤
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition">
                                        {{ ministre.nom }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ ministre.parti || 'Sans étiquette' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-600">{{ ministre.nb_postes }}</div>
                                    <div class="text-xs text-gray-500">postes</div>
                                </div>
                            </Link>
                        </div>
                    </Card>

                    <!-- Répartition par type -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            📋 Répartition par type de fonction
                        </h2>
                        <div class="space-y-4">
                            <div 
                                v-for="(count, type) in repartitionTypes" 
                                :key="type"
                                class="relative"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ typeLabels[type] || type }}
                                    </span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ count }}
                                    </span>
                                </div>
                                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                        :class="{
                                            'bg-blue-600': type === 'premier_ministre',
                                            'bg-purple-600': type === 'ministre_etat',
                                            'bg-indigo-500': type === 'ministre',
                                            'bg-emerald-500': type === 'ministre_delegue',
                                            'bg-amber-500': type === 'secretaire_etat',
                                        }"
                                        :style="{ width: Math.min((count / 2000) * 100, 100) + '%', minWidth: '10%' }"
                                    >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Lien retour -->
                <div class="mt-8 text-center">
                    <Link 
                        :href="route('gouvernement.index')"
                        class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour au gouvernement
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
