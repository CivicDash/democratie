<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    stats: { type: Object, required: true },
    pendingInterpellations: { type: Array, default: () => [] },
    recentResponses: { type: Array, default: () => [] },
    eluData: { type: Object, default: null },
});

// Couleur du taux de réponse
const responseRateColor = computed(() => {
    const rate = props.stats.response_rate || 0;
    if (rate >= 80) return 'text-emerald-400';
    if (rate >= 50) return 'text-amber-400';
    return 'text-rose-400';
});

// Couleur du délai moyen
const avgResponseColor = computed(() => {
    const days = props.stats.avg_response_days;
    if (!days) return 'text-white';
    if (days <= 3) return 'text-emerald-400';
    if (days <= 7) return 'text-amber-400';
    return 'text-rose-400';
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function formatRelativeDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) return "Aujourd'hui";
    if (days === 1) return 'Hier';
    if (days < 7) return `Il y a ${days} jours`;
    return formatDate(dateStr);
}

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Espace Élu', current: true, icon: '🏛️' },
];
</script>

<template>
    <Head title="Espace Élu" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-purple-800 to-violet-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 rounded-full overflow-hidden bg-white/20 flex-shrink-0">
                            <img 
                                v-if="eluData?.photo_url"
                                :src="eluData.photo_url"
                                :alt="eluData.nom_complet"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-4xl">
                                🏛️
                            </div>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                                Bienvenue, {{ eluData?.nom_complet || 'Élu' }}
                            </h1>
                            <p class="text-indigo-200 text-lg">
                                Gérez vos interpellations citoyennes
                            </p>
                        </div>
                    </div>
                    
                    <!-- Stats rapides -->
                    <div class="flex flex-wrap gap-3">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[90px]">
                            <div class="text-2xl md:text-3xl font-bold text-white">{{ stats.total_interpellations }}</div>
                            <div class="text-indigo-200 text-xs uppercase tracking-wide">Total</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[90px]">
                            <div class="text-2xl md:text-3xl font-bold text-amber-400">{{ stats.pending }}</div>
                            <div class="text-indigo-200 text-xs uppercase tracking-wide">En attente</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[90px]">
                            <div class="text-2xl md:text-3xl font-bold text-emerald-400">{{ stats.answered }}</div>
                            <div class="text-indigo-200 text-xs uppercase tracking-wide">Répondues</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[90px]">
                            <div class="text-2xl md:text-3xl font-bold" :class="responseRateColor">{{ stats.response_rate }}%</div>
                            <div class="text-indigo-200 text-xs uppercase tracking-wide">Taux réponse</div>
                        </div>
                        <div v-if="stats.avg_response_days" class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[90px]">
                            <div class="text-2xl md:text-3xl font-bold" :class="avgResponseColor">{{ stats.avg_response_days }}j</div>
                            <div class="text-indigo-200 text-xs uppercase tracking-wide">Délai moyen</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    
                    <!-- Interpellations en attente -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card>
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                    ⏳ Interpellations en attente
                                </h2>
                                <Link 
                                    :href="route('elu.interpellations')"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    Voir tout →
                                </Link>
                            </div>

                            <div v-if="pendingInterpellations.length > 0" class="space-y-4">
                                <Link
                                    v-for="interpellation in pendingInterpellations"
                                    :key="interpellation.id"
                                    :href="route('elu.interpellations.show', interpellation.id)"
                                    class="block p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl hover:shadow-md transition"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                                {{ interpellation.topic?.title }}
                                            </h3>
                                            <div class="flex items-center gap-3 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                <span>👤 {{ interpellation.topic?.author?.name || 'Anonyme' }}</span>
                                                <span>📅 {{ formatRelativeDate(interpellation.created_at) }}</span>
                                                <span>👍 {{ interpellation.topic?.votes_pour || 0 }}</span>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 text-xs font-medium rounded-full">
                                            En attente
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-3">✅</div>
                                <p>Aucune interpellation en attente !</p>
                                <p class="text-sm mt-1">Vous avez répondu à toutes les interpellations.</p>
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Actions rapides -->
                        <Card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Actions rapides</h3>
                            <div class="space-y-2">
                                <Link 
                                    :href="route('elu.ma-fiche')"
                                    class="w-full flex items-center gap-3 px-4 py-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-xl transition"
                                >
                                    <span>👤</span>
                                    <span>Voir ma fiche publique</span>
                                </Link>
                                <Link 
                                    :href="route('elu.interpellations')"
                                    class="w-full flex items-center gap-3 px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-xl transition"
                                >
                                    <span>📬</span>
                                    <span>Toutes les interpellations</span>
                                </Link>
                                <Link 
                                    :href="route('elu.interpellations', { status: 'pending' })"
                                    class="w-full flex items-center gap-3 px-4 py-3 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-xl transition"
                                >
                                    <span>⏳</span>
                                    <span>En attente ({{ stats.pending }})</span>
                                </Link>
                                <Link 
                                    :href="route('elu.stats')"
                                    class="w-full flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-xl transition"
                                >
                                    <span>📊</span>
                                    <span>Mes statistiques</span>
                                </Link>
                            </div>
                        </Card>

                        <!-- Dernières réponses -->
                        <Card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">✅ Dernières réponses</h3>
                            <div v-if="recentResponses.length > 0" class="space-y-3">
                                <Link
                                    v-for="response in recentResponses"
                                    :key="response.id"
                                    :href="route('elu.interpellations.show', response.id)"
                                    class="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ response.topic?.title }}
                                    </h4>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Répondu {{ formatRelativeDate(response.answered_at) }}
                                    </div>
                                </Link>
                            </div>
                            <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
                                <p class="text-sm">Aucune réponse récente</p>
                            </div>
                        </Card>

                        <!-- Aide -->
                        <Card class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800">
                            <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-2">💡 Astuce</h3>
                            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                Répondre aux interpellations citoyennes renforce votre image de proximité et votre transparence. 
                                Les citoyens apprécient les élus réactifs !
                            </p>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
