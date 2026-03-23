<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    affaires: Object,
    counts: Object,
    tab: String,
    search: String,
    health_metrics: Object,
    types_affaire: Array,
    categories: Array,
    statuts_judiciaires: Array,
});

const tabs = [
    { key: 'detecte', label: 'En attente', icon: '🔍' },
    { key: 'en_review', label: 'En review', icon: '👁️' },
    { key: 'a_completer', label: 'À compléter', icon: '📝' },
    { key: 'valide', label: 'Validées', icon: '✅' },
    { key: 'rejete', label: 'Rejetées', icon: '❌' },
    { key: 'conteste', label: 'Contestées', icon: '⚠️' },
];

const hasAlerts = computed(() => {
    const m = props.health_metrics;
    return m.detectees_non_reviewees > 0 || m.contestees_non_traitees > 0 || m.sans_source_haute > 0;
});

function switchTab(tab) {
    router.get(route('admin.affaires.index'), { tab }, { preserveState: true });
}

function prendreEnCharge(id) {
    router.post(route('admin.affaires.prendre', id), {}, { preserveScroll: true });
}

const confidenceColor = (c) => {
    if (c >= 0.8) return 'text-green-600';
    if (c >= 0.5) return 'text-yellow-600';
    return 'text-red-600';
};

const statutColor = (s) => {
    const map = {
        condamne_definitif: 'bg-red-100 text-red-800',
        condamne_appel: 'bg-orange-100 text-orange-800',
        condamne_premiere_instance: 'bg-yellow-100 text-yellow-800',
        mis_en_examen: 'bg-yellow-100 text-yellow-800',
        en_cours: 'bg-gray-100 text-gray-800',
        relaxe: 'bg-green-100 text-green-800',
        acquitte: 'bg-green-100 text-green-800',
    };
    return map[s] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Affaires judiciaires — Modération" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Affaires judiciaires</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">File de modération et validation</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('admin.affaires.stats')" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Statistiques
                    </Link>
                    <Link :href="route('admin.affaires.create')" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        + Saisie manuelle
                    </Link>
                </div>
            </div>

            <div v-if="hasAlerts" class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">Alertes de santé du workflow</h3>
                <div class="flex flex-wrap gap-4 text-sm">
                    <span v-if="health_metrics.detectees_non_reviewees > 0" class="text-amber-700 dark:text-amber-300">
                        {{ health_metrics.detectees_non_reviewees }} en attente &gt;7 jours
                    </span>
                    <span v-if="health_metrics.contestees_non_traitees > 0" class="text-red-700 dark:text-red-300 font-semibold">
                        {{ health_metrics.contestees_non_traitees }} contestation(s) &gt;72h
                    </span>
                    <span v-if="health_metrics.sans_source_haute > 0" class="text-orange-700 dark:text-orange-300">
                        {{ health_metrics.sans_source_haute }} publiée(s) sans source haute
                    </span>
                </div>
            </div>

            <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                <nav class="-mb-px flex space-x-4 overflow-x-auto">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        @click="switchTab(t.key)"
                        :class="[
                            'whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium transition-colors',
                            tab === t.key
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'
                        ]"
                    >
                        {{ t.icon }} {{ t.label }}
                        <span v-if="counts[t.key]" class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                            {{ counts[t.key] }}
                        </span>
                    </button>
                </nav>
            </div>

            <div v-if="affaires.data.length === 0" class="text-center py-16">
                <div class="text-5xl mb-4">📋</div>
                <p class="text-gray-500 dark:text-gray-400">Aucune affaire dans cet onglet.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="affaire in affaires.data"
                    :key="affaire.id"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-sm transition-shadow"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ affaire.prenom }} {{ affaire.nom }}
                                </span>
                                <span v-if="affaire.parti_politique" class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ affaire.parti_politique }})
                                </span>
                                <span v-if="affaire.fonction_au_moment" class="text-xs px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                    {{ affaire.fonction_au_moment }}
                                </span>
                            </div>
                            <Link :href="route('admin.affaires.show', affaire.id)" class="text-sm text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 line-clamp-2">
                                {{ affaire.titre }}
                            </Link>
                            <div class="flex items-center gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span v-if="affaire.source_detection" class="capitalize">
                                    Source : {{ affaire.source_detection }}
                                </span>
                                <span v-if="affaire.detection_confidence" :class="confidenceColor(affaire.detection_confidence)">
                                    Confiance : {{ (affaire.detection_confidence * 100).toFixed(0) }}%
                                </span>
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', statutColor(affaire.statut_judiciaire)]">
                                    {{ affaire.statut_judiciaire_libelle || affaire.statut_judiciaire }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                v-if="tab === 'detecte'"
                                @click="prendreEnCharge(affaire.id)"
                                class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
                            >
                                Prendre en charge
                            </button>
                            <Link
                                :href="route('admin.affaires.show', affaire.id)"
                                class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                            >
                                Voir
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="affaires.links && affaires.last_page > 1" class="mt-6 flex justify-center">
                <nav class="flex gap-1">
                    <Link
                        v-for="link in affaires.links"
                        :key="link.label"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1.5 text-sm rounded-lg border',
                            link.active
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                    />
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
