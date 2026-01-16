<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    reports: Object,
    currentStatus: String,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Modération', href: route('moderation.dashboard'), icon: '🛡️' },
    { label: 'Signalements', icon: '🚨' },
];

const statuses = [
    { key: 'pending', label: 'En attente', icon: '⏳' },
    { key: 'resolved', label: 'Résolus', icon: '✅' },
    { key: 'dismissed', label: 'Rejetés', icon: '❌' },
    { key: 'all', label: 'Tous', icon: '📋' },
];
</script>

<template>
    <Head title="Signalements - Modération" />
    
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16">
                
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl shadow-xl p-8 text-white mb-8">
                    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                        🚨 Signalements
                    </h1>
                    <p class="text-red-100">Gérez les signalements de contenu</p>
                </div>
                
                <!-- Filtres -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
                    <div class="flex gap-2 flex-wrap">
                        <Link
                            v-for="status in statuses"
                            :key="status.key"
                            :href="route('moderation.reports.index', { status: status.key })"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium text-sm transition',
                                currentStatus === status.key
                                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'
                            ]"
                        >
                            {{ status.icon }} {{ status.label }}
                        </Link>
                    </div>
                </div>
                
                <!-- Liste -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div v-if="reports.data && reports.data.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700">
                        <div
                            v-for="report in reports.data"
                            :key="report.id"
                            class="p-6 hover:bg-slate-50 dark:hover:bg-slate-700/30"
                        >
                            <p class="font-medium text-slate-900 dark:text-white">{{ report.reason }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Signalé par {{ report.reporter?.name || 'Anonyme' }}
                            </p>
                        </div>
                    </div>
                    
                    <div v-else class="p-12 text-center">
                        <span class="text-6xl block mb-4">✅</span>
                        <p class="text-xl font-medium text-slate-900 dark:text-white">Aucun signalement</p>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">
                            Tous les signalements ont été traités ou il n'y en a pas encore.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
