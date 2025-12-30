<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    ideas: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    tags: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

// ============================================================================
// FILTERS STATE
// ============================================================================
const localFilters = ref({
    search: props.filters.search || '',
    idea_type: props.filters.idea_type || '',
    scope: props.filters.scope || '',
    region_id: props.filters.region_id || '',
    tag_id: props.filters.tag_id || '',
    sort: props.filters.sort || 'recent',
});

// ============================================================================
// CONSTANTS
// ============================================================================
const ideaTypes = [
    { value: '', label: 'Tous types', icon: '📋' },
    { value: 'proposal', label: 'Propositions', icon: '💡', color: 'emerald' },
    { value: 'question', label: 'Questions', icon: '❓', color: 'sky' },
    { value: 'debate', label: 'Débats', icon: '💬', color: 'amber' },
    { value: 'petition', label: 'Pétitions', icon: '📜', color: 'violet' },
    { value: 'interpellation', label: 'Interpellations', icon: '📣', color: 'rose' },
];

const scopes = [
    { value: '', label: 'Toute échelle', icon: '🌍' },
    { value: 'national', label: 'National', icon: '🇫🇷' },
    { value: 'regional', label: 'Régional', icon: '🗺️' },
    { value: 'departemental', label: 'Départemental', icon: '📍' },
    { value: 'communal', label: 'Communal', icon: '🏘️' },
];

const sortOptions = [
    { value: 'recent', label: 'Plus récents', icon: '🕐' },
    { value: 'trending', label: 'Tendances', icon: '🔥' },
    { value: 'popular', label: 'Plus populaires', icon: '⭐' },
    { value: 'controversial', label: 'Controversés', icon: '⚡' },
];

// ============================================================================
// METHODS
// ============================================================================
function applyFilters() {
    const queryParams = {};
    if (localFilters.value.search) queryParams.search = localFilters.value.search;
    if (localFilters.value.idea_type) queryParams.idea_type = localFilters.value.idea_type;
    if (localFilters.value.scope) queryParams.scope = localFilters.value.scope;
    if (localFilters.value.region_id) queryParams.region_id = localFilters.value.region_id;
    if (localFilters.value.tag_id) queryParams.tag_id = localFilters.value.tag_id;
    if (localFilters.value.sort && localFilters.value.sort !== 'recent') queryParams.sort = localFilters.value.sort;
    
    router.get(route('participation.ideas.index'), queryParams, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    localFilters.value = {
        search: '',
        idea_type: '',
        scope: '',
        region_id: '',
        tag_id: '',
        sort: 'recent',
    };
    router.get(route('participation.ideas.index'));
}

function getIdeaTypeInfo(type) {
    return ideaTypes.find(t => t.value === type) || ideaTypes[0];
}

function getScopeInfo(scope) {
    return scopes.find(s => s.value === scope) || scopes[0];
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

function formatNumber(num) {
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num?.toString() || '0';
}

// Debounced search
let searchTimeout;
watch(() => localFilters.value.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

// Immediate filter application for dropdowns
watch(() => [localFilters.value.idea_type, localFilters.value.scope, localFilters.value.region_id, localFilters.value.tag_id, localFilters.value.sort], () => {
    applyFilters();
}, { deep: true });

// ============================================================================
// COMPUTED
// ============================================================================
const hasActiveFilters = computed(() => {
    return localFilters.value.search || 
           localFilters.value.idea_type || 
           localFilters.value.scope || 
           localFilters.value.region_id ||
           localFilters.value.tag_id ||
           localFilters.value.sort !== 'recent';
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Participation', href: route('participation.hub'), icon: '💬' },
    { label: 'Idées Citoyennes', current: true, icon: '💡' },
];
</script>

<template>
    <Head title="Idées Citoyennes" />

    <AuthenticatedLayout>
        <!-- Hero Section Full Width -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-800 to-cyan-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <!-- Titre -->
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                            <span class="text-4xl">💡</span>
                            Idées Citoyennes
                        </h1>
                        <p class="text-emerald-200 text-lg max-w-2xl">
                            Proposez, votez et débattez des idées qui vous tiennent à cœur. 
                            Ensemble, façonnons notre démocratie.
                        </p>
                    </div>
                    
                    <!-- CTA -->
                    <Link
                        :href="route('participation.ideas.create')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition-all shadow-lg shrink-0"
                    >
                        <span class="text-xl">✨</span>
                        Proposer une idée
                    </Link>
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
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Sidebar Filters -->
                    <aside class="lg:w-72 shrink-0 space-y-6">
                        <!-- Search -->
                        <Card>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🔍 Rechercher</label>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                placeholder="Mots-clés..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm"
                            />
                        </Card>

                        <!-- Type Filter -->
                        <Card>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">📋 Type</label>
                            <div class="space-y-2">
                                <button
                                    v-for="type in ideaTypes"
                                    :key="type.value"
                                    @click="localFilters.idea_type = type.value"
                                    :class="[
                                        'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors text-left',
                                        localFilters.idea_type === type.value
                                            ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 font-medium'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
                                    ]"
                                >
                                    <span>{{ type.icon }}</span>
                                    <span>{{ type.label }}</span>
                                </button>
                            </div>
                        </Card>

                        <!-- Scope Filter -->
                        <Card>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">🗺️ Échelle</label>
                            <div class="space-y-2">
                                <button
                                    v-for="scope in scopes"
                                    :key="scope.value"
                                    @click="localFilters.scope = scope.value"
                                    :class="[
                                        'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors text-left',
                                        localFilters.scope === scope.value
                                            ? 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400 font-medium'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
                                    ]"
                                >
                                    <span>{{ scope.icon }}</span>
                                    <span>{{ scope.label }}</span>
                                </button>
                            </div>
                        </Card>

                        <!-- Tags -->
                        <Card v-if="tags.length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">🏷️ Thématique</label>
                            <select
                                v-model="localFilters.tag_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm"
                            >
                                <option value="">Toutes</option>
                                <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                    {{ tag.icone }} {{ tag.nom }}
                                </option>
                            </select>
                        </Card>

                        <!-- Clear Filters -->
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition-colors"
                        >
                            ✕ Réinitialiser les filtres
                        </button>
                    </aside>

                    <!-- Main Content -->
                    <main class="flex-1">
                        <!-- Sort & Results count -->
                        <Card class="mb-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <p class="text-gray-600 dark:text-gray-400">
                                    <span class="text-gray-900 dark:text-white font-semibold">{{ ideas.total }}</span> idées trouvées
                                </p>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Trier par :</span>
                                    <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                                        <button
                                            v-for="option in sortOptions"
                                            :key="option.value"
                                            @click="localFilters.sort = option.value"
                                            :class="[
                                                'px-3 py-1.5 rounded text-sm transition-colors',
                                                localFilters.sort === option.value
                                                    ? 'bg-emerald-500 text-white'
                                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                                            ]"
                                            :title="option.label"
                                        >
                                            {{ option.icon }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Ideas Grid -->
                        <div v-if="ideas.data?.length > 0" class="space-y-4">
                            <Link
                                v-for="idea in ideas.data"
                                :key="idea.id"
                                :href="route('topics.show', idea.slug || idea.id)"
                                class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-600 transition-all group"
                            >
                                <div class="flex gap-4">
                                    <!-- Vote Column -->
                                    <div class="flex flex-col items-center shrink-0 w-16">
                                        <div class="p-2 text-gray-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                        <span 
                                            class="text-xl font-bold"
                                            :class="(idea.votes_pour - idea.votes_contre) > 0 ? 'text-emerald-600 dark:text-emerald-400' : (idea.votes_pour - idea.votes_contre) < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400'"
                                        >
                                            {{ idea.votes_pour - idea.votes_contre }}
                                        </span>
                                        <div class="p-2 text-gray-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Badges -->
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
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
                                                {{ getIdeaTypeInfo(idea.idea_type).icon }} {{ getIdeaTypeInfo(idea.idea_type).label }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ getScopeInfo(idea.scope).icon }} {{ getScopeInfo(idea.scope).label }}
                                            </span>
                                            <span v-if="idea.loi_cod" class="px-2 py-0.5 bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400 text-xs rounded-full">
                                                📜 Lié à une loi
                                            </span>
                                        </div>

                                        <!-- Title -->
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors line-clamp-1">
                                            {{ idea.title }}
                                        </h3>

                                        <!-- Description -->
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                            {{ idea.description }}
                                        </p>

                                        <!-- Meta -->
                                        <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <span>👤</span>
                                                {{ idea.author?.name || 'Anonyme' }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span>📅</span>
                                                {{ formatDate(idea.published_at || idea.created_at) }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span>💬</span>
                                                {{ idea.posts_count || 0 }} commentaires
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span>👁️</span>
                                                {{ idea.views_count || 0 }} vues
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Empty State -->
                        <Card v-else class="text-center py-16">
                            <div class="text-6xl mb-4">🔍</div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucune idée trouvée</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                Essayez de modifier vos critères de recherche ou soyez le premier à proposer une idée !
                            </p>
                            <Link
                                :href="route('participation.ideas.create')"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl transition-colors"
                            >
                                ✨ Proposer une idée
                            </Link>
                        </Card>

                        <!-- Pagination -->
                        <div v-if="ideas.last_page > 1" class="flex justify-center gap-2 mt-8">
                            <Link
                                v-for="link in ideas.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm transition-colors',
                                    link.active
                                        ? 'bg-emerald-600 text-white'
                                        : link.url
                                            ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </main>
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
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
