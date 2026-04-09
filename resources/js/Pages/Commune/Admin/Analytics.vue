<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    ville: Object,
    stats: Object,
    evolution: Object,
    top_articles: Array,
    top_evenements: Array,
});

const formatNumber = (n) => {
    if (!n) return '0';
    return n.toLocaleString('fr-FR');
};

const statCards = computed(() => [
    { label: 'Vues totales', value: formatNumber(props.stats?.vues_totales), icon: '👁️', trend: props.stats?.vues_trend },
    { label: 'Abonnes', value: formatNumber(props.stats?.abonnes_count), icon: '👤', trend: props.stats?.abonnes_trend },
    { label: 'Articles publies', value: formatNumber(props.stats?.articles_count), icon: '📝', trend: null },
    { label: 'Evenements a venir', value: formatNumber(props.stats?.evenements_count), icon: '📅', trend: null },
]);

const maxVues = computed(() => {
    if (!props.evolution?.vues) return 1;
    return Math.max(...props.evolution.vues.map(v => v.count), 1);
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Analytiques - {{ ville.nom }}</h1>
                <Link :href="route('commune.admin.dashboard', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    &larr; Retour au dashboard
                </Link>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-2xl">{{ card.icon }}</span>
                        <span
                            v-if="card.trend !== null && card.trend !== undefined"
                            class="text-xs font-medium px-2 py-0.5 rounded-full"
                            :class="card.trend >= 0
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'"
                        >
                            {{ card.trend >= 0 ? '+' : '' }}{{ card.trend }}%
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ card.value }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ card.label }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Evolution des vues (simple bar chart) -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Vues - 30 derniers jours</h2>
                    <div v-if="evolution?.vues?.length" class="flex items-end gap-1 h-40">
                        <div
                            v-for="(day, i) in evolution.vues"
                            :key="i"
                            class="flex-1 group relative"
                        >
                            <div
                                class="bg-blue-500 dark:bg-blue-400 rounded-t transition-all hover:bg-blue-600 dark:hover:bg-blue-300"
                                :style="{ height: `${Math.max((day.count / maxVues) * 100, 2)}%` }"
                            />
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap z-10">
                                {{ day.date }} : {{ day.count }} vues
                            </div>
                        </div>
                    </div>
                    <div v-else class="h-40 flex items-center justify-center text-slate-400 text-sm">
                        Pas encore de donnees
                    </div>
                    <div v-if="evolution?.vues?.length" class="flex justify-between text-xs text-slate-400 mt-2">
                        <span>{{ evolution.vues[0]?.date }}</span>
                        <span>{{ evolution.vues[evolution.vues.length - 1]?.date }}</span>
                    </div>
                </div>

                <!-- Evolution des abonnes -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Nouveaux abonnes - 30 derniers jours</h2>
                    <div v-if="evolution?.abonnes?.length" class="flex items-end gap-1 h-40">
                        <div
                            v-for="(day, i) in evolution.abonnes"
                            :key="i"
                            class="flex-1 group relative"
                        >
                            <div
                                class="bg-emerald-500 dark:bg-emerald-400 rounded-t transition-all hover:bg-emerald-600 dark:hover:bg-emerald-300"
                                :style="{ height: `${Math.max((day.count / Math.max(...evolution.abonnes.map(a => a.count), 1)) * 100, 2)}%` }"
                            />
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap z-10">
                                {{ day.date }} : {{ day.count }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="h-40 flex items-center justify-center text-slate-400 text-sm">
                        Pas encore de donnees
                    </div>
                </div>
            </div>

            <!-- Top contenu -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Top articles -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Articles les plus vus</h2>
                    <div v-if="top_articles?.length" class="space-y-3">
                        <div
                            v-for="(article, i) in top_articles"
                            :key="article.id"
                            class="flex items-center gap-3"
                        >
                            <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-sm font-bold text-slate-500 dark:text-slate-400">
                                {{ i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ article.titre }}</p>
                                <p class="text-xs text-slate-500">{{ article.categorie }}</p>
                            </div>
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ formatNumber(article.vues_count) }} vues</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Aucun article publie</p>
                </div>

                <!-- Top evenements -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Evenements populaires</h2>
                    <div v-if="top_evenements?.length" class="space-y-3">
                        <div
                            v-for="(event, i) in top_evenements"
                            :key="event.id"
                            class="flex items-center gap-3"
                        >
                            <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-sm font-bold text-slate-500 dark:text-slate-400">
                                {{ i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ event.titre }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ new Date(event.date_debut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) }}
                                </p>
                            </div>
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ event.inscrits_count || 0 }} inscrits</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Aucun evenement</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
