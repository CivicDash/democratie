<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stats_global: Object,
    health_metrics: Object,
});

const formatHours = (h) => {
    if (!h) return 'N/A';
    if (h < 1) return `${Math.round(h * 60)} min`;
    if (h < 24) return `${Math.round(h)}h`;
    return `${Math.round(h / 24)}j`;
};
</script>

<template>
    <Head title="Statistiques modération — Affaires judiciaires" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <Link :href="route('admin.affaires.index')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600">
                    &larr; Retour à la file
                </Link>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Dashboard modération</h1>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total détectées</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ health_metrics?.total_detectees || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Validées / publiées</p>
                    <p class="text-3xl font-bold text-green-600 mt-1">{{ health_metrics?.total_validees || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Rejetées</p>
                    <p class="text-3xl font-bold text-red-600 mt-1">{{ health_metrics?.total_rejetees || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Taux de rejet</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ health_metrics?.taux_rejet || 0 }}%</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Performance</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">En attente</dt>
                            <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ health_metrics?.en_attente || 0 }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Délai moyen de validation</dt>
                            <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatHours(health_metrics?.delai_moyen_validation) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Par source de détection</h2>
                    <div v-if="health_metrics?.par_source" class="space-y-2">
                        <div v-for="(count, source) in health_metrics.par_source" :key="source" class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ source }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ count }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée</p>
                </div>
            </div>

            <div v-if="stats_global" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Statistiques publiques</h2>
                <div class="grid grid-cols-3 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Par statut judiciaire</h3>
                        <dl class="space-y-1">
                            <div v-for="(count, statut) in stats_global.par_statut" :key="statut" class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">{{ statut }}</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ count }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Par catégorie</h3>
                        <dl class="space-y-1">
                            <div v-for="(count, cat) in stats_global.par_categorie" :key="cat" class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">{{ cat }}</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ count }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Par type</h3>
                        <dl class="space-y-1">
                            <div v-for="(count, type) in stats_global.par_type" :key="type" class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">{{ type }}</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ count }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
