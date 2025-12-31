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

// Mobile filters toggle
const showMobileFilters = ref(false);

// ============================================================================
// CONSTANTS
// ============================================================================
const ideaTypes = [
    { value: '', label: 'Tous types', icon: '📋' },
    { value: 'discussion', label: 'Discussions', icon: '💬', color: 'slate' },
    { value: 'proposal', label: 'Propositions', icon: '💡', color: 'emerald' },
    { value: 'question', label: 'Questions', icon: '❓', color: 'sky' },
    { value: 'debate', label: 'Débats', icon: '🎯', color: 'amber' },
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
    { value: 'recent', label: 'Récents', icon: '🕐' },
    { value: 'trending', label: 'Tendances', icon: '🔥' },
    { value: 'popular', label: 'Populaires', icon: '⭐' },
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

function setSort(sortValue) {
    localFilters.value.sort = sortValue;
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
    const now = new Date();
    const diff = now - date;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (hours < 24) return hours <= 1 ? 'Il y a 1h' : `Il y a ${hours}h`;
    if (days === 1) return 'Hier';
    if (days < 7) return `Il y a ${days}j`;
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
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
           localFilters.value.tag_id;
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (localFilters.value.search) count++;
    if (localFilters.value.idea_type) count++;
    if (localFilters.value.scope) count++;
    if (localFilters.value.region_id) count++;
    if (localFilters.value.tag_id) count++;
    return count;
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
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative w-full px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-4 hidden sm:block" />
                
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white flex items-center gap-3">
                            <span class="text-3xl sm:text-4xl">💡</span>
                            Idées Citoyennes
                        </h1>
                        <p class="text-emerald-200 text-sm sm:text-base mt-2 max-w-xl hidden sm:block">
                            Proposez, votez et débattez des idées qui vous tiennent à cœur
                        </p>
                    </div>
                    
                    <Link
                        :href="route('participation.ideas.create')"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-6 sm:py-3 bg-white text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition-all shadow-lg text-sm sm:text-base"
                    >
                        <span>✨</span>
                        <span class="hidden xs:inline">Proposer une idée</span>
                        <span class="xs:hidden">Nouvelle idée</span>
                    </Link>
                </div>

                <!-- Stats compacts sur mobile -->
                <div class="flex gap-3 mt-6 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-hide">
                    <div class="flex-shrink-0 bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20 flex items-center gap-2">
                        <span class="text-white font-bold">{{ formatNumber(stats.total_ideas || 0) }}</span>
                        <span class="text-emerald-200 text-xs">idées</span>
                    </div>
                    <div class="flex-shrink-0 bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20 flex items-center gap-2">
                        <span class="text-white font-bold">{{ formatNumber(stats.total_votes || 0) }}</span>
                        <span class="text-emerald-200 text-xs">votes</span>
                    </div>
                    <div class="flex-shrink-0 bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20 flex items-center gap-2">
                        <span class="text-white font-bold">{{ formatNumber(stats.total_comments || 0) }}</span>
                        <span class="text-emerald-200 text-xs">commentaires</span>
                    </div>
                    <div class="flex-shrink-0 bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20 flex items-center gap-2">
                        <span class="text-white font-bold">{{ formatNumber(stats.responses || 0) }}</span>
                        <span class="text-emerald-200 text-xs">réponses d'élus</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal FULL WIDTH -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <!-- Quick Sort Tabs - Mobile first, always visible -->
            <div class="sticky top-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between py-3">
                        <!-- Sort Tabs -->
                        <div class="flex gap-1 sm:gap-2 overflow-x-auto scrollbar-hide">
                            <button
                                v-for="option in sortOptions"
                                :key="option.value"
                                @click="setSort(option.value)"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-all',
                                    localFilters.sort === option.value
                                        ? 'bg-emerald-500 text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                            >
                                <span>{{ option.icon }}</span>
                                <span class="hidden xs:inline">{{ option.label }}</span>
                            </button>
                        </div>

                        <!-- Filters Toggle (Mobile) -->
                        <button
                            @click="showMobileFilters = !showMobileFilters"
                            class="lg:hidden flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            <span>🎛️</span>
                            <span class="hidden sm:inline">Filtres</span>
                            <span v-if="activeFiltersCount > 0" class="px-1.5 py-0.5 bg-emerald-500 text-white text-xs rounded-full">
                                {{ activeFiltersCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Mobile Filters Dropdown -->
                    <div 
                        v-if="showMobileFilters"
                        class="lg:hidden pb-4 border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4"
                    >
                        <!-- Search -->
                        <input
                            v-model="localFilters.search"
                            type="text"
                            placeholder="🔍 Rechercher..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2.5 text-sm"
                        />
                        
                        <!-- Type & Scope in row -->
                        <div class="grid grid-cols-2 gap-3">
                            <select
                                v-model="localFilters.idea_type"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5"
                            >
                                <option v-for="type in ideaTypes" :key="type.value" :value="type.value">
                                    {{ type.icon }} {{ type.label }}
                                </option>
                            </select>
                            <select
                                v-model="localFilters.scope"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5"
                            >
                                <option v-for="scope in scopes" :key="scope.value" :value="scope.value">
                                    {{ scope.icon }} {{ scope.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Tags -->
                        <select
                            v-if="tags.length > 0"
                            v-model="localFilters.tag_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5"
                        >
                            <option value="">🏷️ Toutes thématiques</option>
                            <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                {{ tag.icone }} {{ tag.nom }}
                            </option>
                        </select>

                        <!-- Clear button -->
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="w-full px-4 py-2.5 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors"
                        >
                            ✕ Réinitialiser les filtres
                        </button>
                    </div>
                </div>
            </div>

            <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex gap-8">
                    <!-- Desktop Sidebar Filters -->
                    <aside class="hidden lg:block w-64 shrink-0 space-y-4">
                        <!-- Search -->
                        <Card class="!p-4">
                            <input
                                v-model="localFilters.search"
                                type="text"
                                placeholder="🔍 Rechercher..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm"
                            />
                        </Card>

                        <!-- Type Filter -->
                        <Card class="!p-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📋 Type</h3>
                            <div class="space-y-1">
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
                        <Card class="!p-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🗺️ Échelle</h3>
                            <div class="space-y-1">
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
                        <Card v-if="tags.length > 0" class="!p-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🏷️ Thématique</h3>
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
                            ✕ Réinitialiser
                        </button>
                    </aside>

                    <!-- Main Content -->
                    <main class="flex-1 min-w-0">
                        <!-- Results count -->
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="text-gray-900 dark:text-white font-semibold">{{ ideas.total }}</span> idées
                            </p>
                        </div>

                        <!-- Ideas List -->
                        <div v-if="ideas.data?.length > 0" class="space-y-3">
                            <Link
                                v-for="idea in ideas.data"
                                :key="idea.id"
                                :href="route('participation.ideas.show', idea.slug || idea.id)"
                                class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-600 transition-all group"
                            >
                                <div class="flex gap-3 sm:gap-4">
                                    <!-- Vote Column - Compact on mobile -->
                                    <div class="flex flex-col items-center shrink-0 w-10 sm:w-14">
                                        <div class="hidden sm:block p-1 text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                        <span 
                                            class="text-lg sm:text-xl font-bold"
                                            :class="(idea.votes_pour - idea.votes_contre) > 0 ? 'text-emerald-600' : (idea.votes_pour - idea.votes_contre) < 0 ? 'text-rose-600' : 'text-gray-400'"
                                        >
                                            {{ idea.votes_pour - idea.votes_contre > 0 ? '+' : '' }}{{ idea.votes_pour - idea.votes_contre }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">votes</span>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Badges - Inline on mobile -->
                                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-1.5 sm:mb-2">
                                            <span 
                                                class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                :class="{
                                                    'bg-slate-100 dark:bg-slate-900/30 text-slate-700 dark:text-slate-400': idea.idea_type === 'discussion',
                                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400': idea.idea_type === 'proposal',
                                                    'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400': idea.idea_type === 'question',
                                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': idea.idea_type === 'debate',
                                                    'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400': idea.idea_type === 'petition',
                                                    'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400': idea.idea_type === 'interpellation',
                                                }"
                                            >
                                                {{ getIdeaTypeInfo(idea.idea_type).icon }}
                                                <span class="hidden sm:inline">{{ getIdeaTypeInfo(idea.idea_type).label }}</span>
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(idea.published_at || idea.created_at) }}
                                            </span>
                                        </div>

                                        <!-- Title -->
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors line-clamp-2">
                                            {{ idea.title }}
                                        </h3>

                                        <!-- Description - Hidden on mobile -->
                                        <p class="hidden sm:block text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                            {{ idea.description }}
                                        </p>

                                        <!-- Meta - Compact on mobile -->
                                        <div class="flex flex-wrap items-center gap-3 sm:gap-4 mt-2 sm:mt-3 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <span>👤</span>
                                                <span class="truncate max-w-[80px] sm:max-w-none">{{ idea.author?.name || 'Anonyme' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span>💬</span>
                                                {{ idea.posts_count || 0 }}
                                            </span>
                                            <span class="hidden sm:flex items-center gap-1">
                                                <span>👁️</span>
                                                {{ idea.views_count || 0 }}
                                            </span>
                                            <span class="flex items-center gap-1 text-gray-400">
                                                {{ getScopeInfo(idea.scope).icon }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Chevron -->
                                    <div class="hidden sm:flex items-center text-gray-300 group-hover:text-emerald-400 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="text-5xl mb-4">🔍</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aucune idée trouvée</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm max-w-sm mx-auto">
                                Modifiez vos filtres ou soyez le premier à proposer une idée !
                            </p>
                            <Link
                                :href="route('participation.ideas.create')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl transition-colors text-sm"
                            >
                                ✨ Proposer une idée
                            </Link>
                        </div>

                        <!-- Pagination -->
                        <div v-if="ideas.last_page > 1" class="flex justify-center gap-1 sm:gap-2 mt-6">
                            <Link
                                v-for="link in ideas.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 rounded-lg text-sm transition-colors min-w-[40px] text-center',
                                    link.active
                                        ? 'bg-emerald-600 text-white'
                                        : link.url
                                            ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed pointer-events-none'
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
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
