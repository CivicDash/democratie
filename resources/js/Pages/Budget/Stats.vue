<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    sectors: Array,
    averages: Object,
    ranking: Array,
    stats: Object,
});

const getSectorIcon = (name) => {
    const icons = {
        'Santé': '🏥',
        'Éducation': '🎓',
        'Transport': '🚇',
        'Écologie': '🌱',
        'Sécurité': '👮',
        'Culture': '🎭',
        'Logement': '🏠',
        'Emploi': '💼',
        'Justice': '⚖️',
        'Sport': '⚽',
    };
    return icons[name] || '📊';
};

const getRankBadge = (rank) => {
    if (rank === 1) return { variant: 'green', icon: '🥇' };
    if (rank === 2) return { variant: 'blue', icon: '🥈' };
    if (rank === 3) return { variant: 'yellow', icon: '🥉' };
    return { variant: 'gray', icon: `#${rank}` };
};
</script>

<template>
    <Head title="Statistiques Budget" />

    <MainLayout title="Statistiques Budget">
        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        📊 Statistiques du Budget Participatif
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Découvrez les priorités de la communauté
                    </p>
                </div>

                <!-- Overview Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card class="text-center">
                        <div class="text-4xl mb-2">👥</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ stats?.total_participants || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Participants</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">📊</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ sectors.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Secteurs</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">📅</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            {{ stats?.last_updated ? new Date(stats.last_updated).toLocaleDateString('fr-FR') : 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Dernière mise à jour</div>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Average Allocations -->
                    <Card>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            📊 Allocations Moyennes
                        </h2>
                        <div class="space-y-4">
                            <div v-for="sector in sectors" :key="sector.id">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">{{ getSectorIcon(sector.name) }}</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ sector.name }}
                                        </span>
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ (averages[sector.id] || 0).toFixed(1) }}%
                                    </span>
                                </div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500" 
                                        :style="{ width: `${averages[sector.id] || 0}%` }">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Ranking -->
                    <Card>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            🏆 Classement des Priorités
                        </h2>
                        <div class="space-y-3">
                            <div v-for="(item, index) in ranking" :key="item.sector_id"
                                class="flex items-center justify-between p-3 rounded-lg"
                                :class="index < 3 ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'bg-gray-50 dark:bg-gray-800/50'">
                                <div class="flex items-center gap-3">
                                    <Badge :variant="getRankBadge(index + 1).variant" size="lg">
                                        {{ getRankBadge(index + 1).icon }}
                                    </Badge>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ getSectorIcon(item.sector_name) }} {{ item.sector_name }}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ item.participant_count }} participants
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                        {{ item.avg_allocation.toFixed(1) }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

