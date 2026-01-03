<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    questions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    themes: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
});

// Filtres locaux
const localFilters = ref({
    search: props.filters.search || '',
    type: props.filters.type || '',
    theme: props.filters.theme || '',
    statut: props.filters.statut || '',
});

function applyFilters() {
    const queryParams = {};
    if (localFilters.value.search) queryParams.search = localFilters.value.search;
    if (localFilters.value.type) queryParams.type = localFilters.value.type;
    if (localFilters.value.theme) queryParams.theme = localFilters.value.theme;
    if (localFilters.value.statut) queryParams.statut = localFilters.value.statut;

    router.get(route('questions.senat.index'), queryParams, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    localFilters.value = { search: '', type: '', theme: '', statut: '' };
    router.get(route('questions.senat.index'));
}

// Debounce search
let searchTimeout;
watch(() => localFilters.value.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

// Appliquer immédiatement les filtres dropdown
watch(
    () => [localFilters.value.type, localFilters.value.theme, localFilters.value.statut], 
    applyFilters, 
    { deep: true }
);

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function getTypeLabel(type) {
    const labels = {
        'QE': 'Question écrite',
        'Question écrite': 'QE',
        'QO': 'Question orale',
        'Question orale': 'QO',
        'QAG': 'Question d\'actualité',
        'Question d\'actualité au Gouvernement': 'QAG',
    };
    return labels[type] || type;
}

function getTypeBadgeClass(type) {
    if (type?.includes('écrite') || type === 'QE') {
        return 'bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300';
    }
    if (type?.includes('orale') || type === 'QO') {
        return 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300';
    }
    if (type?.includes('actualité') || type === 'QAG') {
        return 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300';
    }
    return 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
}

const hasActiveFilters = computed(() => {
    return localFilters.value.search || localFilters.value.type || 
           localFilters.value.theme || localFilters.value.statut;
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénateurs', href: route('representants.senateurs.index'), icon: '🏛️' },
    { label: 'Questions au Gouvernement', current: true, icon: '❓' },
];
</script>

<template>
    <Head title="Questions au Gouvernement - Sénat" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            
            <!-- Hero Section Full Width -->
            <section class="relative overflow-hidden bg-gradient-to-br from-rose-800 via-pink-700 to-fuchsia-800">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>
                
                <div class="relative w-full px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                    <!-- Breadcrumb -->
                    <div class="max-w-full mx-auto">
                        <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                    </div>
                    
                    <div class="max-w-full mx-auto flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Titre -->
                        <div>
                            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                                <span class="text-4xl">❓</span>
                                Questions au Gouvernement
                                <span class="text-2xl bg-white/20 px-3 py-1 rounded-full">Sénat</span>
                            </h1>
                            <p class="text-rose-100 text-lg">
                                Interpellations des sénateurs aux membres du gouvernement
                            </p>
                        </div>
                        
                        <!-- Stats rapides -->
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-white">{{ stats.total?.toLocaleString() || 0 }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Questions</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-emerald-400">{{ stats.repondues?.toLocaleString() || 0 }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Répondues</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-amber-400">{{ stats.en_attente || 0 }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">En attente</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-violet-400">{{ stats.senateurs_actifs || 0 }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Sénateurs actifs</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liens -->
                    <div class="max-w-full mx-auto mt-6 flex flex-wrap gap-3">
                        <Link 
                            :href="route('questions.senat.stats')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition border border-white/20 text-sm"
                        >
                            <span>📊</span>
                            Voir les statistiques détaillées →
                        </Link>
                        <Link 
                            :href="route('questions.index')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition border border-white/20 text-sm"
                        >
                            <span>🏛️</span>
                            Questions Assemblée Nationale →
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Content - Full Width -->
            <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Filters Sidebar -->
                    <aside class="lg:w-72 shrink-0 space-y-4">
                        <!-- Search -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🔍 Rechercher</label>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                placeholder="Sujet, thème, ministère..."
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 text-sm focus:ring-rose-500 focus:border-rose-500"
                            />
                        </div>

                        <!-- Type -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📝 Type</label>
                            <select
                                v-model="localFilters.type"
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white text-sm focus:ring-rose-500 focus:border-rose-500"
                            >
                                <option value="">Tous les types</option>
                                <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>

                        <!-- Thème -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🏷️ Thème</label>
                            <select
                                v-model="localFilters.theme"
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white text-sm focus:ring-rose-500 focus:border-rose-500"
                            >
                                <option value="">Tous les thèmes</option>
                                <option v-for="th in themes" :key="th" :value="th">{{ th }}</option>
                            </select>
                        </div>

                        <!-- Statut -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📌 Statut</label>
                            <select
                                v-model="localFilters.statut"
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white text-sm focus:ring-rose-500 focus:border-rose-500"
                            >
                                <option value="">Tous</option>
                                <option value="repondu">✅ Répondues</option>
                                <option value="en_attente">⏳ En attente</option>
                            </select>
                        </div>

                        <!-- Clear -->
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition-colors"
                        >
                            ✕ Réinitialiser les filtres
                        </button>
                    </aside>

                    <!-- Questions List -->
                    <main class="flex-1">
                        <div class="mb-4 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-900 dark:text-white font-semibold">{{ questions.total }}</span> questions
                        </div>

                        <div v-if="questions.data?.length > 0" class="space-y-4">
                            <Link
                                v-for="q in questions.data"
                                :key="q.numero"
                                :href="route('questions.senat.show', q.numero)"
                                class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-rose-300 dark:hover:border-rose-600 hover:shadow-md transition-all group"
                            >
                                <div class="flex items-start gap-4">
                                    <!-- Sénateur photo -->
                                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-2xl shrink-0 overflow-hidden">
                                        <img 
                                            v-if="q.senateur?.photo_url" 
                                            :src="q.senateur.photo_url" 
                                            :alt="q.senateur.nom"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else>👤</span>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <!-- Badges -->
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <span 
                                                class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                :class="getTypeBadgeClass(q.type)"
                                            >
                                                {{ q.type }}
                                            </span>
                                            <span class="px-2 py-0.5 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 text-xs font-medium rounded-full">
                                                Sénat
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(q.date_question) }}
                                            </span>
                                        </div>

                                        <!-- Thème ou début du texte -->
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors line-clamp-2">
                                            {{ q.theme || (q.texte_question?.substring(0, 150) + '...') || 'Question #' + q.numero }}
                                        </h3>

                                        <!-- Ministère -->
                                        <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span v-if="q.ministre_destinataire" class="flex items-center gap-1">
                                                🏛️ {{ q.ministre_destinataire }}
                                            </span>
                                            <span v-if="q.numero" class="flex items-center gap-1">
                                                #{{ q.numero }}
                                            </span>
                                        </div>

                                        <!-- Auteur -->
                                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            Par <span class="text-rose-600 dark:text-rose-400 font-medium">
                                                {{ q.senateur?.prenom }} {{ q.senateur?.nom }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Statut réponse -->
                                    <div class="shrink-0 text-right">
                                        <div 
                                            v-if="q.a_reponse"
                                            class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-medium rounded-full"
                                        >
                                            ✅ Répondue
                                        </div>
                                        <div 
                                            v-else
                                            class="px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-medium rounded-full"
                                        >
                                            ⏳ En attente
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Empty -->
                        <div v-else class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="text-6xl mb-4">🔍</div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Aucune question trouvée</p>
                            <p class="text-sm text-gray-400">
                                Les questions seront disponibles après l'import. <br/>
                                Exécutez : <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">php artisan import:questions-senat</code>
                            </p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="questions.last_page > 1" class="flex justify-center gap-2 mt-8">
                            <Link
                                v-for="link in questions.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                    link.active
                                        ? 'bg-rose-600 text-white'
                                        : link.url
                                            ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
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
