<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    imports: Object,
    filters: Object,
    sources: Object,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Administration', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Historique Imports', icon: '📥' },
];

// Filtres
const filterBySource = (source) => {
    router.get(route('admin.imports'), { source }, { preserveState: true });
};

const filterByStatus = (status) => {
    router.get(route('admin.imports'), { status }, { preserveState: true });
};

const clearFilters = () => {
    router.get(route('admin.imports'));
};

// Classes pour les statuts
const getStatusClass = (status) => {
    const classes = {
        running: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        partial: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return classes[status] || '';
};

const getStatusLabel = (status) => {
    return {
        running: '🔄 En cours',
        success: '✅ Succès',
        failed: '❌ Échec',
        partial: '⚠️ Partiel',
    }[status] || status;
};

const formatDuration = (seconds) => {
    if (!seconds) return '-';
    if (seconds < 60) return `${seconds}s`;
    const min = Math.floor(seconds / 60);
    const sec = seconds % 60;
    if (min < 60) return `${min}m ${sec}s`;
    const h = Math.floor(min / 60);
    const m = min % 60;
    return `${h}h ${m}m`;
};
</script>

<template>
    <Head title="Historique des imports" />
    
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />
                
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                            📥 Historique des imports
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400">
                            Suivi de toutes les synchronisations de données
                        </p>
                    </div>
                    
                    <Link :href="route('admin.dashboard')" class="btn-secondary">
                        ← Retour au dashboard
                    </Link>
                </div>
                
                <!-- Filtres -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <!-- Par source -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Source:</span>
                            <button
                                v-for="(info, key) in sources"
                                :key="key"
                                @click="filterBySource(key)"
                                class="px-3 py-1 text-sm rounded-full border transition"
                                :class="filters.source === key 
                                    ? 'bg-indigo-100 border-indigo-300 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-300'
                                    : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700'"
                            >
                                {{ info.icon }} {{ info.label }}
                            </button>
                        </div>
                        
                        <!-- Par statut -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Statut:</span>
                            <button
                                v-for="status in ['success', 'failed', 'partial', 'running']"
                                :key="status"
                                @click="filterByStatus(status)"
                                class="px-3 py-1 text-sm rounded-full border transition"
                                :class="filters.status === status 
                                    ? 'bg-indigo-100 border-indigo-300 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-300'
                                    : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700'"
                            >
                                {{ getStatusLabel(status) }}
                            </button>
                        </div>
                        
                        <!-- Clear -->
                        <button
                            v-if="filters.source || filters.status"
                            @click="clearFilters"
                            class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:underline"
                        >
                            ✕ Effacer filtres
                        </button>
                    </div>
                </div>
                
                <!-- Table -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Commande
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Source
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Résultats
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Origine
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Durée
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Par
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr
                                    v-for="log in imports.data"
                                    :key="log.id"
                                    class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
                                >
                                    <td class="px-6 py-4">
                                        <Link
                                            :href="route('admin.imports.show', log.id)"
                                            class="text-sm font-mono bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded hover:underline"
                                        >
                                            {{ log.command }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs"
                                            :style="{ backgroundColor: (sources[log.source]?.color || '#6B7280') + '20' }"
                                        >
                                            {{ sources[log.source]?.icon || '📦' }}
                                            {{ sources[log.source]?.label || log.source }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusClass(log.status)"
                                        >
                                            {{ getStatusLabel(log.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span v-if="log.records_created > 0" class="text-green-600 dark:text-green-400 mr-2">
                                            +{{ log.records_created }}
                                        </span>
                                        <span v-if="log.records_updated > 0" class="text-blue-600 dark:text-blue-400 mr-2">
                                            ↻{{ log.records_updated }}
                                        </span>
                                        <span v-if="log.errors_count > 0" class="text-red-600 dark:text-red-400">
                                            ⚠{{ log.errors_count }}
                                        </span>
                                        <span v-if="!log.records_created && !log.records_updated && !log.errors_count" class="text-slate-400">
                                            -
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                        {{ log.triggered_by === 'scheduler' ? 'Planifiée' : 'Manuelle' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                        {{ formatDuration(log.duration_seconds) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                        {{ new Date(log.started_at).toLocaleString('fr-FR') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                        {{ log.user?.name || 'Scheduler' }}
                                    </td>
                                </tr>
                                
                                <tr v-if="imports.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        Aucun import trouvé
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="imports.last_page > 1" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Affichage de {{ imports.from }} à {{ imports.to }} sur {{ imports.total }} imports
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="imports.prev_page_url"
                                :href="imports.prev_page_url"
                                class="px-3 py-1 text-sm border rounded hover:bg-slate-50 dark:hover:bg-slate-700"
                            >
                                ← Précédent
                            </Link>
                            <Link
                                v-if="imports.next_page_url"
                                :href="imports.next_page_url"
                                class="px-3 py-1 text-sm border rounded hover:bg-slate-50 dark:hover:bg-slate-700"
                            >
                                Suivant →
                            </Link>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>

