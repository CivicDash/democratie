<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    globalStats: { type: Object, required: true },
    evolutionByMonth: { type: Array, default: () => [] },
    topThemes: { type: Array, default: () => [] },
    responseTimeEvolution: { type: Array, default: () => [] },
    eluData: { type: Object, default: null },
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Espace Élu', href: route('elu.dashboard'), icon: '🏛️' },
    { label: 'Statistiques', current: true, icon: '📊' },
];

// Couleur du taux de réponse
const responseRateColor = computed(() => {
    const rate = props.globalStats.response_rate || 0;
    if (rate >= 80) return 'text-emerald-600 dark:text-emerald-400';
    if (rate >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
});

// Couleur du délai moyen
const avgResponseColor = computed(() => {
    const days = props.globalStats.avg_response_days;
    if (!days) return 'text-gray-600 dark:text-gray-400';
    if (days <= 3) return 'text-emerald-600 dark:text-emerald-400';
    if (days <= 7) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
});

// Label de performance
const performanceLabel = computed(() => {
    const rate = props.globalStats.response_rate || 0;
    if (rate >= 90) return { text: 'Excellent', emoji: '🏆', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' };
    if (rate >= 70) return { text: 'Très bien', emoji: '⭐', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' };
    if (rate >= 50) return { text: 'Bien', emoji: '👍', class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' };
    return { text: 'À améliorer', emoji: '📈', class: 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300' };
});

function formatMonth(mois) {
    const [year, month] = mois.split('-');
    const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
    return `${months[parseInt(month) - 1]} ${year.slice(2)}`;
}
</script>

<template>
    <Head title="Mes Statistiques - Espace Élu" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-800 to-cyan-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2 flex items-center gap-3">
                            <span>📊</span>
                            Mes Statistiques
                        </h1>
                        <p class="text-emerald-200 text-lg">
                            Performance et engagement citoyen
                        </p>
                    </div>
                    
                    <!-- Badge de performance -->
                    <div :class="performanceLabel.class" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-lg">
                        <span class="text-2xl">{{ performanceLabel.emoji }}</span>
                        <span>{{ performanceLabel.text }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Stats principales -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <Card class="text-center">
                        <div class="text-4xl font-bold text-gray-900 dark:text-white">{{ globalStats.total }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Interpellations reçues</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl font-bold text-emerald-600 dark:text-emerald-400">{{ globalStats.answered }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Réponses apportées</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl font-bold" :class="responseRateColor">{{ globalStats.response_rate }}%</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Taux de réponse</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl font-bold" :class="avgResponseColor">
                            {{ globalStats.avg_response_days ?? '-' }}
                            <span v-if="globalStats.avg_response_days" class="text-2xl">j</span>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Délai moyen de réponse</div>
                    </Card>
                </div>

                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Évolution mensuelle -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📈 Évolution mensuelle</h2>
                        
                        <div v-if="evolutionByMonth.length > 0" class="space-y-3">
                            <div v-for="month in evolutionByMonth" :key="month.mois" class="flex items-center gap-4">
                                <div class="w-20 text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ formatMonth(month.mois) }}
                                </div>
                                <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
                                    <div 
                                        class="h-full bg-emerald-500"
                                        :style="{ width: `${month.total > 0 ? (month.repondues / month.total) * 100 : 0}%` }"
                                    ></div>
                                    <div 
                                        class="h-full bg-amber-500"
                                        :style="{ width: `${month.total > 0 ? ((month.total - month.repondues) / month.total) * 100 : 0}%` }"
                                    ></div>
                                </div>
                                <div class="w-16 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ month.repondues }}/{{ month.total }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Pas encore de données
                        </div>
                        
                        <div class="flex items-center gap-6 mt-6 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                                <span class="text-gray-600 dark:text-gray-400">Répondues</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                                <span class="text-gray-600 dark:text-gray-400">En attente</span>
                            </div>
                        </div>
                    </Card>

                    <!-- Thématiques populaires -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🏷️ Thématiques populaires</h2>
                        
                        <div v-if="topThemes.length > 0" class="space-y-3">
                            <div v-for="(theme, index) in topThemes" :key="theme.theme" class="flex items-center gap-4">
                                <div class="w-6 h-6 flex items-center justify-center text-sm font-bold rounded-full"
                                    :class="index === 0 ? 'bg-amber-400 text-amber-900' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1 truncate text-gray-700 dark:text-gray-300">
                                    {{ theme.theme }}
                                </div>
                                <div class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-sm font-medium rounded-full">
                                    {{ theme.count }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Pas encore de thématiques identifiées
                        </div>
                    </Card>
                </div>

                <!-- Conseils -->
                <Card class="mt-8 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800">
                    <h2 class="text-xl font-bold text-indigo-900 dark:text-indigo-100 mb-4">💡 Conseils pour améliorer votre score</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <div class="text-2xl mb-2">⏱️</div>
                            <h3 class="font-semibold text-indigo-800 dark:text-indigo-200 mb-1">Répondez rapidement</h3>
                            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                Un délai de réponse court (moins de 3 jours) améliore votre image de proximité.
                            </p>
                        </div>
                        <div>
                            <div class="text-2xl mb-2">✍️</div>
                            <h3 class="font-semibold text-indigo-800 dark:text-indigo-200 mb-1">Réponses personnalisées</h3>
                            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                Évitez les réponses génériques, les citoyens apprécient les réponses personnalisées.
                            </p>
                        </div>
                        <div>
                            <div class="text-2xl mb-2">📣</div>
                            <h3 class="font-semibold text-indigo-800 dark:text-indigo-200 mb-1">Partagez vos réponses</h3>
                            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                Mettez en avant votre engagement citoyen sur vos réseaux sociaux.
                            </p>
                        </div>
                    </div>
                </Card>

                <!-- Retour -->
                <div class="mt-8 text-center">
                    <Link 
                        :href="route('elu.dashboard')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition font-medium"
                    >
                        ← Retour au dashboard
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
