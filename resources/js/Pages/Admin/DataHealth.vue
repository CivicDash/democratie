<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    healthData: Object,
    snapshots: Array,
    snapshotDiff: Object,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Administration', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Santé des données', icon: '🏥' },
];

function freshnessColor(status) {
    return {
        ok: 'text-green-600 dark:text-green-400',
        warning: 'text-yellow-600 dark:text-yellow-400',
        error: 'text-red-600 dark:text-red-400',
    }[status] || 'text-gray-500';
}

function freshnessBg(status) {
    return {
        ok: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
        error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
    }[status] || 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700';
}

function diffColor(change) {
    if (change > 0) return 'text-green-600 dark:text-green-400';
    if (change < 0) return 'text-red-600 dark:text-red-400';
    return 'text-gray-500';
}
</script>

<template>
    <Head title="Santé des données" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <Breadcrumb :items="breadcrumbItems" />

            <div class="mt-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🏥 Santé des données</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" v-if="healthData?.checked_at">
                        Dernière vérification : {{ new Date(healthData.checked_at).toLocaleString('fr-FR') }}
                    </p>
                </div>
                <div class="flex gap-3" v-if="healthData">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                        ✅ {{ healthData.healthy }} saines
                    </span>
                    <span v-if="healthData.warnings > 0" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                        ⚠️ {{ healthData.warnings }} avert.
                    </span>
                    <span v-if="healthData.errors > 0" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                        ❌ {{ healthData.errors }} erreurs
                    </span>
                </div>
            </div>

            <!-- Grille des sources -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="source in healthData?.sources || []"
                    :key="source.name"
                    class="border rounded-xl p-4 transition-all"
                    :class="freshnessBg(source.status)"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ source.name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ source.command }}</p>
                        </div>
                        <span class="text-lg" :class="freshnessColor(source.status)">
                            {{ source.status_icon }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Enregistrements</span>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ Number(source.count).toLocaleString('fr-FR') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Attendu min</span>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ Number(source.expected_min).toLocaleString('fr-FR') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Dernier import</span>
                            <p class="font-medium" :class="freshnessColor(source.status)">{{ source.last_import || 'Jamais' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Fraîcheur</span>
                            <p class="font-medium">{{ source.freshness_label }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Snapshots -->
            <div class="mt-10" v-if="snapshots?.length > 0">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📸 Historique des snapshots</h2>

                <!-- Diff -->
                <div v-if="Object.keys(snapshotDiff || {}).length > 0" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                    <h3 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">Changements depuis le dernier snapshot</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <div v-for="(change, key) in snapshotDiff" :key="key" class="text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ key }}</span>
                            <p class="font-semibold" :class="diffColor(change.change)">
                                {{ change.change > 0 ? '+' : '' }}{{ change.change }}
                                <span v-if="change.percentage" class="text-xs">({{ change.percentage > 0 ? '+' : '' }}{{ change.percentage }}%)</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Checksum</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="snapshot in snapshots" :key="snapshot.id">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ snapshot.date }}</td>
                                <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ snapshot.checksum?.substring(0, 12) }}...</td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ snapshot.notes || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
