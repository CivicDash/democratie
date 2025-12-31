<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    trending: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    interpellations: { type: Array, default: () => [] },
});

function formatNumber(num) {
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num?.toString() || '0';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) return "Aujourd'hui";
    if (days === 1) return 'Hier';
    if (days < 7) return `Il y a ${days} jours`;
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}

function getTypeIcon(type) {
    const icons = {
        proposal: '💡',
        question: '❓',
        debate: '💬',
        petition: '📜',
        interpellation: '📣',
    };
    return icons[type] || '💡';
}

function getTypeLabel(type) {
    const labels = {
        proposal: 'Proposition',
        question: 'Question',
        debate: 'Débat',
        petition: 'Pétition',
        interpellation: 'Interpellation',
    };
    return labels[type] || type;
}

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Participation Citoyenne', current: true, icon: '💬' },
];
</script>

<template>
    <Head title="Participation Citoyenne" />

    <AuthenticatedLayout>
        <!-- Hero Section Full Width -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-800 to-cyan-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative w-full px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <!-- Titre -->
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight">
                            <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">
                                Participation
                            </span>
                            <span class="block text-white">Citoyenne</span>
                        </h1>
                        <p class="text-emerald-200 text-lg max-w-2xl">
                            Proposez vos idées, votez pour les meilleures, interpellez vos élus et participez activement à la démocratie.
                        </p>
                    </div>
                    
                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <Link
                            :href="route('participation.ideas.create')"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition-all shadow-lg"
                        >
                            <span class="text-xl">✨</span>
                            Proposer une idée
                        </Link>
                        <Link
                            :href="route('participation.ideas.index')"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 text-white font-medium rounded-xl hover:bg-white/20 transition-all border border-white/20"
                        >
                            <span class="text-xl">🔍</span>
                            Explorer les idées
                        </Link>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-2xl md:text-3xl font-bold text-white">{{ formatNumber(stats.total_ideas || 0) }}</div>
                        <div class="text-emerald-200 text-xs uppercase tracking-wide">Idées</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-2xl md:text-3xl font-bold text-white">{{ formatNumber(stats.total_votes || 0) }}</div>
                        <div class="text-emerald-200 text-xs uppercase tracking-wide">Votes</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-2xl md:text-3xl font-bold text-white">{{ formatNumber(stats.total_comments || 0) }}</div>
                        <div class="text-emerald-200 text-xs uppercase tracking-wide">Commentaires</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-2xl md:text-3xl font-bold text-white">{{ formatNumber(stats.total_interpellations || 0) }}</div>
                        <div class="text-emerald-200 text-xs uppercase tracking-wide">Interpellations</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-2xl md:text-3xl font-bold text-white">{{ formatNumber(stats.responses || 0) }}</div>
                        <div class="text-emerald-200 text-xs uppercase tracking-wide">Réponses d'élus</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    
                    <!-- Trending -->
                    <div class="lg:col-span-2">
                        <Card>
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span>🔥</span> Tendances
                                </h2>
                                <Link 
                                    :href="route('participation.ideas.index', { sort: 'trending' })"
                                    class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm"
                                >
                                    Voir tout →
                                </Link>
                            </div>

                            <div class="space-y-4">
                                <Link
                                    v-for="(idea, index) in trending"
                                    :key="idea.id"
                                    :href="route('participation.ideas.show', idea.slug || idea.id)"
                                    class="block p-4 bg-gray-50 dark:bg-gray-800 rounded-xl hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-600 border border-transparent transition-all group"
                                >
                                    <div class="flex gap-4">
                                        <!-- Rank -->
                                        <div class="w-10 h-10 rounded-full shrink-0 flex items-center justify-center text-lg font-bold"
                                            :class="{
                                                'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400': index === 0,
                                                'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400': index === 1,
                                                'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400': index === 2,
                                                'bg-gray-100 dark:bg-gray-700 text-gray-400': index > 2,
                                            }"
                                        >
                                            {{ index + 1 }}
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span>{{ getTypeIcon(idea.idea_type) }}</span>
                                                <span 
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                    :class="{
                                                        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400': idea.idea_type === 'proposal',
                                                        'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400': idea.idea_type === 'question',
                                                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': idea.idea_type === 'debate',
                                                        'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400': idea.idea_type === 'petition',
                                                        'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400': idea.idea_type === 'interpellation',
                                                    }"
                                                >
                                                    {{ getTypeLabel(idea.idea_type) }}
                                                </span>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors line-clamp-1">
                                                {{ idea.title }}
                                            </h3>
                                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span>👤 {{ idea.author?.name || 'Anonyme' }}</span>
                                                <span>⬆️ {{ idea.votes_pour || 0 }} votes</span>
                                                <span>💬 {{ idea.posts_count || 0 }}</span>
                                            </div>
                                        </div>

                                        <!-- Score -->
                                        <div class="text-right shrink-0">
                                            <div 
                                                class="text-2xl font-bold"
                                                :class="(idea.votes_pour - idea.votes_contre) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'"
                                            >
                                                +{{ (idea.votes_pour || 0) - (idea.votes_contre || 0) }}
                                            </div>
                                        </div>
                                    </div>
                                </Link>

                                <div v-if="trending.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <div class="text-4xl mb-3">🌱</div>
                                    <p>Aucune idée pour le moment. Soyez le premier à proposer !</p>
                                    <Link
                                        :href="route('participation.ideas.create')"
                                        class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl transition-colors"
                                    >
                                        ✨ Proposer une idée
                                    </Link>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Actions -->
                        <Card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Actions rapides</h3>
                            <div class="space-y-3">
                                <Link
                                    :href="route('participation.ideas.create', { idea_type: 'proposal' })"
                                    class="flex items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-lg text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors"
                                >
                                    <span class="text-xl">💡</span>
                                    <span>Faire une proposition</span>
                                </Link>
                                <Link
                                    :href="route('participation.ideas.create', { idea_type: 'question' })"
                                    class="flex items-center gap-3 p-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-lg text-sky-700 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors"
                                >
                                    <span class="text-xl">❓</span>
                                    <span>Poser une question</span>
                                </Link>
                                <Link
                                    :href="route('participation.ideas.create', { idea_type: 'interpellation' })"
                                    class="flex items-center gap-3 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-700 rounded-lg text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-colors"
                                >
                                    <span class="text-xl">📣</span>
                                    <span>Interpeller un élu</span>
                                </Link>
                            </div>
                        </Card>

                        <!-- Recent -->
                        <Card>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🕐 Récentes</h3>
                                <Link 
                                    :href="route('participation.ideas.index', { sort: 'recent' })"
                                    class="text-sky-600 dark:text-sky-400 hover:underline text-sm"
                                >
                                    Voir tout →
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <Link
                                    v-for="idea in recent"
                                    :key="idea.id"
                                    :href="route('participation.ideas.show', idea.slug || idea.id)"
                                    class="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <div class="flex items-start gap-2">
                                        <span>{{ getTypeIcon(idea.idea_type) }}</span>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">{{ idea.title }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(idea.published_at || idea.created_at) }}</p>
                                        </div>
                                    </div>
                                </Link>
                                <div v-if="recent.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                    Aucune idée récente
                                </div>
                            </div>
                        </Card>

                        <!-- Interpellations with responses -->
                        <Card v-if="interpellations.length > 0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💬 Réponses d'élus</h3>
                            <div class="space-y-3">
                                <Link
                                    v-for="idea in interpellations"
                                    :key="idea.id"
                                    :href="route('participation.ideas.show', idea.slug || idea.id)"
                                    class="block p-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-700 rounded-lg hover:bg-violet-100 dark:hover:bg-violet-900/30 transition-colors"
                                >
                                    <div class="flex items-start gap-2">
                                        <span>📣</span>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">{{ idea.title }}</h4>
                                            <p class="text-xs text-violet-600 dark:text-violet-400 mt-1">
                                                ✅ {{ idea.elus?.filter(e => e.response_status === 'answered').length || 0 }} réponse(s)
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- CTA Footer -->
            <div class="bg-gradient-to-r from-emerald-100 via-teal-100 to-cyan-100 dark:from-emerald-900/20 dark:via-teal-900/20 dark:to-cyan-900/20 py-16 border-t border-gray-200 dark:border-gray-700">
                <div class="max-w-4xl mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                        Votre voix compte
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                        Chaque vote, chaque commentaire, chaque idée contribue à façonner notre avenir commun.
                    </p>
                    <Link
                        :href="route('participation.ideas.create')"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-lg rounded-xl transition-all shadow-lg"
                    >
                        <span class="text-2xl">🚀</span>
                        Commencer maintenant
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
