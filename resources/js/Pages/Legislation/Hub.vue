<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    stats: Object,
    thematiques: Array,
    dernieresLois: Array,
    tendances: Object,
});

const searchQuery = ref('');
const activeTab = ref('overview'); // overview, thematiques, browse

const breadcrumbItems = [
    { label: 'Accueil', href: '/' },
    { label: 'Législation' },
];

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(route('lois.index'), { search: searchQuery.value });
    }
};

const formatCount = (count) => {
    if (!count) return '0';
    if (count >= 1000) return (count / 1000).toFixed(1) + 'k';
    return count.toLocaleString();
};

const topThematiques = computed(() => {
    return props.thematiques?.slice(0, 12) || [];
});
</script>

<template>
    <Head title="Législation - Hub" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 dark:bg-gray-900">
            <!-- Hero Header -->
            <header class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
                <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Breadcrumb -->
                    <div class="py-3 border-b border-slate-700/50">
                        <Breadcrumb :items="breadcrumbItems" dark />
                    </div>
                    
                    <!-- Hero Content -->
                    <div class="py-12 lg:py-16">
                        <div class="text-center max-w-4xl mx-auto">
                            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                                📜 Hub Législation
                            </h1>
                            <p class="text-xl text-slate-300 mb-8">
                                Explorez le cycle de vie des lois françaises, de la proposition à la promulgation
                            </p>

                            <!-- Search Bar -->
                            <div class="max-w-2xl mx-auto mb-8">
                                <div class="relative">
                                    <input
                                        v-model="searchQuery"
                                        @keyup.enter="handleSearch"
                                        type="text"
                                        placeholder="Rechercher une loi, un thème, un numéro..."
                                        class="w-full pl-12 pr-32 py-4 bg-white/10 backdrop-blur border border-white/20 rounded-2xl 
                                               text-white placeholder-slate-400 text-lg
                                               focus:ring-2 focus:ring-sky-400 focus:border-sky-400 focus:bg-white/20"
                                    />
                                    <svg class="absolute left-4 top-4.5 h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <button 
                                        @click="handleSearch"
                                        class="absolute right-2 top-2 px-6 py-2 bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-xl transition-colors"
                                    >
                                        Rechercher
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 max-w-3xl mx-auto">
                                <div class="bg-white/10 backdrop-blur rounded-xl p-4 border border-white/10">
                                    <div class="text-3xl font-bold text-white">{{ formatCount(stats?.total) }}</div>
                                    <div class="text-sm text-slate-300">Lois totales</div>
                                </div>
                                <div class="bg-emerald-500/20 backdrop-blur rounded-xl p-4 border border-emerald-400/30">
                                    <div class="text-3xl font-bold text-emerald-400">{{ formatCount(stats?.promulguees) }}</div>
                                    <div class="text-sm text-emerald-300">Promulguées</div>
                                </div>
                                <div class="bg-sky-500/20 backdrop-blur rounded-xl p-4 border border-sky-400/30">
                                    <div class="text-3xl font-bold text-sky-400">{{ formatCount(stats?.en_cours) }}</div>
                                    <div class="text-sm text-sky-300">En cours</div>
                                </div>
                                <div class="bg-amber-500/20 backdrop-blur rounded-xl p-4 border border-amber-400/30">
                                    <div class="text-3xl font-bold text-amber-400">{{ formatCount(stats?.cette_annee) }}</div>
                                    <div class="text-sm text-amber-300">Cette année</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Navigation Tabs -->
            <nav class="bg-white dark:bg-gray-800 border-b border-slate-200 dark:border-gray-700 sticky top-0 z-10">
                <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-14">
                        <div class="flex items-center gap-1">
                            <button
                                @click="activeTab = 'overview'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                    activeTab === 'overview' 
                                        ? 'bg-slate-100 dark:bg-gray-700 text-slate-900 dark:text-white' 
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                ]"
                            >
                                📊 Vue d'ensemble
                            </button>
                            <button
                                @click="activeTab = 'thematiques'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                    activeTab === 'thematiques' 
                                        ? 'bg-slate-100 dark:bg-gray-700 text-slate-900 dark:text-white' 
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                ]"
                            >
                                🏷️ Par thématique
                            </button>
                            <Link
                                :href="route('lois.index')"
                                class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 
                                       hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                📜 Toutes les lois
                            </Link>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('legislation.scrutins.index')"
                                class="px-3 py-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400"
                            >
                                🗳️ Scrutins
                            </Link>
                            <Link
                                :href="route('parlement.calendrier')"
                                class="px-3 py-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400"
                            >
                                📅 Calendrier
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-8">
                    <!-- Quick Actions -->
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Link
                            :href="route('lois.index', { etat: '04' })"
                            class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-slate-200 dark:border-gray-700 
                                   hover:border-emerald-300 dark:hover:border-emerald-600 hover:shadow-lg transition-all"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl">
                                    <span class="text-2xl">✅</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-white group-hover:text-emerald-600">Lois promulguées</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ formatCount(stats?.promulguees) }} textes</div>
                                </div>
                            </div>
                        </Link>

                        <Link
                            :href="route('lois.index', { etat: '01' })"
                            class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-slate-200 dark:border-gray-700 
                                   hover:border-sky-300 dark:hover:border-sky-600 hover:shadow-lg transition-all"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-sky-100 dark:bg-sky-900/30 rounded-xl">
                                    <span class="text-2xl">🔄</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-white group-hover:text-sky-600">En cours d'examen</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ formatCount(stats?.en_cours) }} textes</div>
                                </div>
                            </div>
                        </Link>

                        <Link
                            :href="route('lois.index', { annee: new Date().getFullYear() })"
                            class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-slate-200 dark:border-gray-700 
                                   hover:border-amber-300 dark:hover:border-amber-600 hover:shadow-lg transition-all"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                                    <span class="text-2xl">📅</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-white group-hover:text-amber-600">Cette année</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ formatCount(stats?.cette_annee) }} lois</div>
                                </div>
                            </div>
                        </Link>

                        <Link
                            :href="route('legislation.scrutins.index')"
                            class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-slate-200 dark:border-gray-700 
                                   hover:border-purple-300 dark:hover:border-purple-600 hover:shadow-lg transition-all"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                                    <span class="text-2xl">🗳️</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-white group-hover:text-purple-600">Scrutins publics</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">Votes AN</div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid lg:grid-cols-3 gap-8">
                        <!-- Dernières lois -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700">
                                <div class="p-4 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                                    <h2 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>📜</span> Dernières lois promulguées
                                    </h2>
                                    <Link :href="route('lois.index', { etat: '04' })" class="text-sm text-sky-600 dark:text-sky-400 hover:underline">
                                        Voir tout →
                                    </Link>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-gray-700">
                                    <Link
                                        v-for="loi in dernieresLois"
                                        :key="loi.loicod"
                                        :href="route('lois.show', loi.loicod)"
                                        class="block p-4 hover:bg-slate-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="px-2 py-1 bg-slate-100 dark:bg-gray-700 rounded text-xs font-mono text-slate-600 dark:text-slate-400 flex-shrink-0">
                                                {{ loi.numero || loi.loicod }}
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-slate-900 dark:text-white line-clamp-2">
                                                    {{ loi.titre }}
                                                </div>
                                                <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                                    JO du {{ loi.date_jo }}
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Thématiques populaires -->
                        <div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700">
                                <div class="p-4 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                                    <h2 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>🏷️</span> Thématiques
                                    </h2>
                                    <button @click="activeTab = 'thematiques'" class="text-sm text-sky-600 dark:text-sky-400 hover:underline">
                                        Voir tout →
                                    </button>
                                </div>
                                <div class="p-4 space-y-2">
                                    <Link
                                        v-for="theme in topThematiques.slice(0, 8)"
                                        :key="theme.slug"
                                        :href="route('lois.index', { thematique: theme.slug })"
                                        class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span>{{ theme.icone }}</span>
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate max-w-[150px]">{{ theme.nom }}</span>
                                        </div>
                                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ formatCount(theme.count) }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thematiques Tab -->
                <div v-if="activeTab === 'thematiques'" class="space-y-6">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Explorer par thématique</h2>
                        <p class="text-slate-500 dark:text-slate-400">Cliquez sur une thématique pour voir les lois associées</p>
                    </div>

                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <Link
                            v-for="theme in thematiques"
                            :key="theme.slug"
                            :href="route('lois.index', { thematique: theme.slug })"
                            class="group bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 
                                   hover:border-slate-300 dark:hover:border-gray-600 hover:shadow-lg transition-all overflow-hidden"
                        >
                            <div class="p-5">
                                <div class="flex items-start gap-4">
                                    <div 
                                        class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center text-3xl"
                                        :style="{ backgroundColor: theme.couleur + '20' }"
                                    >
                                        {{ theme.icone }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400">
                                            {{ theme.nom }}
                                        </h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                            {{ formatCount(theme.count) }} lois
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="px-5 py-3 bg-slate-50 dark:bg-gray-800/50 border-t border-slate-100 dark:border-gray-700/50 flex items-center justify-between">
                                <span class="text-xs text-slate-400 dark:text-slate-500">Voir les lois</span>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </Link>
                    </div>
                </div>
            </main>
        </div>
    </AuthenticatedLayout>
</template>

