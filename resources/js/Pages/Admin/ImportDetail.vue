<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    import: Object,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Administration', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Historique Imports', href: route('admin.imports'), icon: '📥' },
    { label: 'Détail', icon: '🧾' },
];

const getStatusClass = (status) => {
    const classes = {
        running: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        partial: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return classes[status] || '';
};
</script>

<template>
    <Head title="Détail import" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                            🧾 Rapport d'import
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400">
                            Détail de la tâche <code class="font-mono">{{ import.command }}</code>
                        </p>
                    </div>
                    <Link :href="route('admin.imports')" class="btn-secondary">
                        ← Retour à l'historique
                    </Link>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="getStatusClass(import.status)">
                                    {{ import.statusLabel }}
                                </span>
                                <span class="text-sm text-slate-600 dark:text-slate-400">
                                    Source: {{ import.sourceInfo?.label || import.source }}
                                </span>
                                <span class="text-sm text-slate-600 dark:text-slate-400">
                                    Origine: {{ import.triggeredBy === 'scheduler' ? 'Planifiée' : 'Manuelle' }}
                                </span>
                            </div>

                            <div class="grid md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400">Début</p>
                                    <p class="text-slate-900 dark:text-white">{{ import.startedAt || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400">Fin</p>
                                    <p class="text-slate-900 dark:text-white">{{ import.finishedAt || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400">Durée</p>
                                    <p class="text-slate-900 dark:text-white">{{ import.duration || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400">Exit code</p>
                                    <p class="text-slate-900 dark:text-white">{{ import.exitCode ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400">Expression cron</p>
                                    <p class="text-slate-900 dark:text-white">{{ import.scheduleExpression || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400">Lancé par</p>
                                    <p class="text-slate-900 dark:text-white">{{ import.user || 'Scheduler' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Résultats</h2>
                            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-center">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">+{{ import.recordsCreated }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Créés</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">↻{{ import.recordsUpdated }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Mis à jour</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                                    <p class="text-2xl font-bold text-slate-600 dark:text-slate-300">{{ import.recordsSkipped }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Ignorés</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">⚠{{ import.errorsCount }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Erreurs</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="import.errorMessage || import.outputTail" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Diagnostics</h2>
                            <div v-if="import.errorMessage" class="mb-4">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Message d'erreur</p>
                                <pre class="text-sm text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 p-3 rounded whitespace-pre-wrap">{{ import.errorMessage }}</pre>
                            </div>
                            <div v-if="import.outputTail">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Dernières lignes de sortie</p>
                                <pre class="text-sm text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-900/50 p-3 rounded whitespace-pre-wrap">{{ import.outputTail }}</pre>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Options</h2>
                            <pre class="text-xs text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-900/50 p-3 rounded whitespace-pre-wrap">
{{ JSON.stringify(import.options || {}, null, 2) }}
                            </pre>
                        </div>
                        <div v-if="import.errorDetails" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Détails erreur</h2>
                            <pre class="text-xs text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-900/50 p-3 rounded whitespace-pre-wrap">
{{ JSON.stringify(import.errorDetails, null, 2) }}
                            </pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
