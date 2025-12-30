<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    stats: { type: Object, required: true },
});

function getGroupeColor(sigle) {
    const colors = {
        'RN': { bg: '#1e3a8a', text: '#93c5fd' },
        'DR': { bg: '#1d4ed8', text: '#ffffff' },
        'EPR': { bg: '#f59e0b', text: '#ffffff' },
        'DEM': { bg: '#eab308', text: '#000000' },
        'HOR': { bg: '#06b6d4', text: '#ffffff' },
        'LIOT': { bg: '#14b8a6', text: '#ffffff' },
        'SOC': { bg: '#e11d48', text: '#ffffff' },
        'ECO': { bg: '#16a34a', text: '#ffffff' },
        'LFI': { bg: '#dc2626', text: '#ffffff' },
        'GDR': { bg: '#b91c1c', text: '#ffffff' },
        'UDR': { bg: '#475569', text: '#ffffff' },
    };
    return colors[sigle] || { bg: '#475569', text: '#ffffff' };
}

function getMaxValue(array, key = 'nb') {
    if (!array?.length) return 1;
    return Math.max(...array.map(item => item[key] || 0));
}

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Députés', href: route('representants.deputes.index'), icon: '👥' },
    { label: 'Questions au Gouvernement', href: route('questions.index'), icon: '❓' },
    { label: 'Statistiques', current: true, icon: '📊' },
];
</script>

<template>
    <Head title="Statistiques - Questions au Gouvernement" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-800 dark:to-indigo-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" class="mb-4 text-indigo-100" />
                    
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-3xl">
                            📊
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">
                                Statistiques des Questions
                            </h1>
                            <p class="text-indigo-100 mt-1">
                                Analyse des questions posées par les députés
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Global Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 text-center shadow-sm">
                        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ stats.global?.total?.toLocaleString() || 0 }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Questions totales</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 text-center shadow-sm">
                        <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ stats.global?.repondues?.toLocaleString() || 0 }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Répondues</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 text-center shadow-sm">
                        <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">
                            {{ stats.global?.deputés_actifs || 0 }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Députés actifs</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 text-center shadow-sm">
                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                            {{ stats.delai_moyen_jours || '-' }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jours (délai moyen)</div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Top Rubriques -->
                    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            📋 Top Rubriques
                        </h2>
                        <div class="space-y-3">
                            <div 
                                v-for="r in stats.top_rubriques"
                                :key="r.rubrique"
                            >
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ r.rubrique }}</span>
                                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ r.nb }}</span>
                                </div>
                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-gradient-to-r from-indigo-500 to-indigo-400 transition-all"
                                        :style="{ width: `${(r.nb / getMaxValue(stats.top_rubriques)) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Top Ministères -->
                    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            🏛️ Top Ministères interrogés
                        </h2>
                        <div class="space-y-3">
                            <div 
                                v-for="m in stats.top_ministeres"
                                :key="m.ministere_sigle"
                            >
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ m.ministere_sigle }}</span>
                                    <span class="text-sm font-medium text-violet-600 dark:text-violet-400">{{ m.nb }}</span>
                                </div>
                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-gradient-to-r from-violet-500 to-violet-400 transition-all"
                                        :style="{ width: `${(m.nb / getMaxValue(stats.top_ministeres)) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Top Groupes -->
                    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            👥 Par Groupe Politique
                        </h2>
                        <div class="space-y-3">
                            <div 
                                v-for="g in stats.top_groupes"
                                :key="g.groupe_sigle"
                            >
                                <div class="flex justify-between items-center mb-1">
                                    <div class="flex items-center gap-2">
                                        <span 
                                            class="px-2 py-0.5 text-xs font-medium rounded"
                                            :style="{ backgroundColor: getGroupeColor(g.groupe_sigle).bg, color: getGroupeColor(g.groupe_sigle).text }"
                                        >
                                            {{ g.groupe_sigle }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-[200px]">
                                            {{ g.groupe_nom }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ g.nb }}</span>
                                </div>
                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full transition-all"
                                        :style="{ 
                                            width: `${(g.nb / getMaxValue(stats.top_groupes)) * 100}%`,
                                            backgroundColor: getGroupeColor(g.groupe_sigle).bg
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Top Députés -->
                    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            🏆 Députés les plus actifs
                        </h2>
                        <div class="space-y-2">
                            <Link
                                v-for="(d, index) in stats.top_deputes?.slice(0, 10)"
                                :key="d.uid"
                                :href="route('questions.depute', d.uid)"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            >
                                <div 
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                                    :class="{
                                        'bg-amber-500 text-white': index === 0,
                                        'bg-gray-400 text-gray-800': index === 1,
                                        'bg-amber-700 text-white': index === 2,
                                        'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300': index > 2,
                                    }"
                                >
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ d.nom }}</p>
                                    <p v-if="d.groupe" class="text-xs text-gray-500 dark:text-gray-400">{{ d.groupe }}</p>
                                </div>
                                <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ d.nb }}
                                </div>
                            </Link>
                        </div>
                    </section>
                </div>

                <!-- Évolution mensuelle -->
                <section 
                    v-if="stats.evolution_mensuelle?.length > 0"
                    class="mt-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm"
                >
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        📈 Évolution mensuelle
                    </h2>
                    <div class="flex items-end gap-1 h-40 overflow-x-auto pb-2">
                        <div 
                            v-for="m in stats.evolution_mensuelle"
                            :key="m.mois"
                            class="flex flex-col items-center flex-shrink-0"
                            :style="{ width: '40px' }"
                        >
                            <div 
                                class="w-8 bg-gradient-to-t from-indigo-600 to-indigo-400 rounded-t transition-all"
                                :style="{ height: `${(m.nb / getMaxValue(stats.evolution_mensuelle)) * 120}px` }"
                            ></div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 rotate-45 origin-left whitespace-nowrap">
                                {{ m.mois }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Actions -->
                <div class="flex justify-center mt-8">
                    <Link
                        :href="route('questions.index')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all"
                    >
                        📋 Voir toutes les questions
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
