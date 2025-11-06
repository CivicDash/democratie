<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    stats: Object,
    recentReports: Array,
    topModerators: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', { 
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusBadge = (status) => {
    const badges = {
        pending: { variant: 'yellow', label: '⏳ En attente' },
        investigating: { variant: 'blue', label: '🔍 Investigation' },
        resolved: { variant: 'green', label: '✅ Résolu' },
        rejected: { variant: 'gray', label: '❌ Rejeté' },
    };
    return badges[status] || badges.pending;
};

const getReasonLabel = (reason) => {
    const reasons = {
        spam: '🚫 Spam',
        harassment: '😡 Harcèlement',
        misinformation: '❌ Désinformation',
        offtopic: '📍 Hors-sujet',
        illegal: '⚖️ Illégal',
        other: '⚠️ Autre',
    };
    return reasons[reason] || reason;
};
</script>

<template>
    <Head title="Dashboard Modération" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🚨 Dashboard Modération
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card class="text-center">
                        <div class="text-4xl mb-2">⏳</div>
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ stats.pending_reports || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">En attente</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">🔍</div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ stats.investigating_reports || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Investigation</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">✅</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ stats.resolved_today || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Résolus aujourd'hui</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">👮</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ stats.active_moderators || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Modérateurs actifs</div>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Reports -->
                    <Card>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                📋 Signalements Récents
                            </h3>
                            <Link :href="route('moderation.reports.index')">
                                <PrimaryButton size="sm">Voir tous</PrimaryButton>
                            </Link>
                        </div>

                        <div v-if="recentReports.length > 0" class="space-y-3">
                            <div v-for="report in recentReports" :key="report.id"
                                class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-2">
                                    <Badge :variant="getStatusBadge(report.status).variant" size="sm">
                                        {{ getStatusBadge(report.status).label }}
                                    </Badge>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDate(report.created_at) }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ getReasonLabel(report.reason) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-2">
                                    {{ report.description }}
                                </p>
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Par {{ report.reporter?.name || 'Anonyme' }}</span>
                                    <Link :href="route('moderation.reports.show', report.id)" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Voir détails →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Aucun signalement récent
                        </div>
                    </Card>

                    <!-- Top Moderators -->
                    <Card>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            🏆 Top Modérateurs
                        </h3>

                        <div v-if="topModerators.length > 0" class="space-y-3">
                            <div v-for="(moderator, index) in topModerators" :key="moderator.id"
                                class="flex items-center justify-between p-3 rounded-lg"
                                :class="index === 0 ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800/50'">
                                <div class="flex items-center gap-3">
                                    <div class="text-2xl">
                                        {{ index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '👮' }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ moderator.name }}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            Actif depuis {{ formatDate(moderator.created_at) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                        {{ moderator.resolved_count || 0 }}
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        résolus
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Aucun modérateur actif
                        </div>
                    </Card>
                </div>

                <!-- Quick Actions -->
                <Card class="mt-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        ⚡ Actions Rapides
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Link :href="route('moderation.reports.priority')" class="p-4 text-center border-2 border-yellow-500 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors">
                            <div class="text-3xl mb-2">⚠️</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">Prioritaires</div>
                        </Link>
                        <Link :href="route('moderation.reports.index', { status: 'pending' })" class="p-4 text-center border-2 border-blue-500 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                            <div class="text-3xl mb-2">📋</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">En attente</div>
                        </Link>
                        <Link :href="route('moderation.sanctions.index')" class="p-4 text-center border-2 border-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <div class="text-3xl mb-2">🔨</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">Sanctions</div>
                        </Link>
                        <Link :href="route('moderation.stats')" class="p-4 text-center border-2 border-indigo-500 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                            <div class="text-3xl mb-2">📊</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">Statistiques</div>
                        </Link>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

