<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    interpellations: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
});

const selectedStatus = ref(props.filters.status || '');
const selectedSort = ref(props.filters.sort || 'recent');

function applyFilters() {
    router.get(route('elu.interpellations'), {
        status: selectedStatus.value || undefined,
        sort: selectedSort.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

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

function getStatusInfo(status) {
    const statuses = {
        'pending': { label: 'En attente', color: 'amber', icon: '⏳' },
        'answered': { label: 'Répondu', color: 'emerald', icon: '✅' },
        'declined': { label: 'Décliné', color: 'gray', icon: '❌' },
    };
    return statuses[status] || statuses['pending'];
}

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Espace Élu', href: route('elu.dashboard'), icon: '🏛️' },
    { label: 'Interpellations', current: true, icon: '📬' },
];
</script>

<template>
    <Head title="Mes Interpellations" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-purple-800 to-violet-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2 flex items-center gap-3">
                            <span class="text-4xl">📬</span>
                            Mes Interpellations
                        </h1>
                        <p class="text-indigo-200 text-lg">
                            Les citoyens vous interpellent directement
                        </p>
                    </div>
                    
                    <!-- Stats rapides -->
                    <div class="flex flex-wrap gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[80px]">
                            <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
                            <div class="text-indigo-200 text-xs uppercase">Total</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[80px]">
                            <div class="text-2xl font-bold text-amber-400">{{ stats.pending }}</div>
                            <div class="text-indigo-200 text-xs uppercase">En attente</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[80px]">
                            <div class="text-2xl font-bold text-emerald-400">{{ stats.answered }}</div>
                            <div class="text-indigo-200 text-xs uppercase">Répondues</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Filtres -->
                <Card class="mb-6">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                📊 Statut
                            </label>
                            <select
                                v-model="selectedStatus"
                                @change="applyFilters"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">Tous</option>
                                <option value="pending">⏳ En attente</option>
                                <option value="answered">✅ Répondues</option>
                                <option value="declined">❌ Déclinées</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                📅 Tri
                            </label>
                            <select
                                v-model="selectedSort"
                                @change="applyFilters"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="recent">Plus récentes</option>
                                <option value="oldest">Plus anciennes</option>
                                <option value="popular">Plus populaires</option>
                            </select>
                        </div>
                    </div>
                </Card>

                <!-- Liste des interpellations -->
                <div class="space-y-4">
                    <Link
                        v-for="interpellation in interpellations.data"
                        :key="interpellation.id"
                        :href="route('elu.interpellations.show', interpellation.id)"
                        class="block"
                    >
                        <Card 
                            class="hover:shadow-lg transition-shadow"
                            :class="{
                                'border-l-4 border-l-amber-500': interpellation.response_status === 'pending',
                                'border-l-4 border-l-emerald-500': interpellation.response_status === 'answered',
                                'border-l-4 border-l-gray-400': interpellation.response_status === 'declined',
                            }"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span 
                                            class="px-2 py-0.5 text-xs font-medium rounded-full"
                                            :class="{
                                                'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': interpellation.response_status === 'pending',
                                                'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400': interpellation.response_status === 'answered',
                                                'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400': interpellation.response_status === 'declined',
                                            }"
                                        >
                                            {{ getStatusInfo(interpellation.response_status).icon }} {{ getStatusInfo(interpellation.response_status).label }}
                                        </span>
                                        <span v-if="!interpellation.viewed_at" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs rounded-full">
                                            🆕 Nouveau
                                        </span>
                                    </div>
                                    
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                        {{ interpellation.topic?.title }}
                                    </h3>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <span>👤</span>
                                            {{ interpellation.topic?.author?.name || 'Anonyme' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>📅</span>
                                            {{ formatRelativeDate(interpellation.created_at) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>👍</span>
                                            {{ interpellation.topic?.votes_pour || 0 }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>👎</span>
                                            {{ interpellation.topic?.votes_contre || 0 }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex-shrink-0 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </Card>
                    </Link>

                    <!-- Empty state -->
                    <div v-if="interpellations.data?.length === 0" class="text-center py-16">
                        <div class="text-6xl mb-4">📭</div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            Aucune interpellation
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Vous n'avez reçu aucune interpellation correspondant à ces critères.
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="interpellations.last_page > 1" class="flex justify-center gap-2 pt-6">
                        <Link
                            v-for="link in interpellations.links"
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                        ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
