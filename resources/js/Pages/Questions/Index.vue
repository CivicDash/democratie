<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    questions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    rubriques: { type: Array, default: () => [] },
    ministeres: { type: Array, default: () => [] },
    groupes: { type: Array, default: () => [] },
});

// Filtres locaux
const localFilters = ref({
    search: props.filters.search || '',
    rubrique: props.filters.rubrique || '',
    ministere: props.filters.ministere || '',
    groupe: props.filters.groupe || '',
});

function applyFilters() {
    const queryParams = {};
    if (localFilters.value.search) queryParams.search = localFilters.value.search;
    if (localFilters.value.rubrique) queryParams.rubrique = localFilters.value.rubrique;
    if (localFilters.value.ministere) queryParams.ministere = localFilters.value.ministere;
    if (localFilters.value.groupe) queryParams.groupe = localFilters.value.groupe;

    router.get(route('questions.index'), queryParams, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    localFilters.value = { search: '', rubrique: '', ministere: '', groupe: '' };
    router.get(route('questions.index'));
}

// Debounce search
let searchTimeout;
watch(() => localFilters.value.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

// Appliquer immédiatement les filtres dropdown
watch(() => [localFilters.value.rubrique, localFilters.value.ministere, localFilters.value.groupe], applyFilters, { deep: true });

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function getGroupeColor(sigle) {
    const colors = {
        'RN': 'bg-blue-900 text-blue-100',
        'DR': 'bg-blue-700 text-white',
        'EPR': 'bg-amber-500 text-white',
        'DEM': 'bg-yellow-500 text-black',
        'HOR': 'bg-cyan-500 text-white',
        'LIOT': 'bg-teal-500 text-white',
        'SOC': 'bg-rose-600 text-white',
        'ECO': 'bg-green-600 text-white',
        'LFI': 'bg-red-600 text-white',
        'GDR': 'bg-red-700 text-white',
        'UDR': 'bg-slate-600 text-white',
    };
    return colors[sigle] || 'bg-slate-600 text-white';
}

const hasActiveFilters = computed(() => {
    return localFilters.value.search || localFilters.value.rubrique || 
           localFilters.value.ministere || localFilters.value.groupe;
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Députés', href: route('representants.deputes.index'), icon: '👥' },
    { label: 'Questions au Gouvernement', current: true, icon: '❓' },
];
</script>

<template>
    <Head title="Questions au Gouvernement" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-800 dark:to-indigo-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" class="mb-4 text-indigo-100" />
                    
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                                <span class="text-3xl">❓</span>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Questions au Gouvernement</h1>
                                <p class="text-indigo-100 mt-1">Interpellations des députés aux membres du gouvernement</p>
                            </div>
                        </div>

                        <Link
                            :href="route('questions.stats')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-xl transition-all shrink-0 backdrop-blur-sm"
                        >
                            📊 Statistiques
                        </Link>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-white">{{ stats.total?.toLocaleString() || 0 }}</div>
                            <div class="text-sm text-indigo-200">Questions totales</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-emerald-300">{{ stats.repondues?.toLocaleString() || 0 }}</div>
                            <div class="text-sm text-indigo-200">Répondues</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-amber-300">{{ stats.ce_mois || 0 }}</div>
                            <div class="text-sm text-indigo-200">Ce mois</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-violet-300">{{ stats.deputés_actifs || 0 }}</div>
                            <div class="text-sm text-indigo-200">Députés actifs</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Filters Sidebar -->
                    <aside class="lg:w-72 shrink-0 space-y-4">
                        <!-- Search -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🔍 Rechercher</label>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                placeholder="Sujet, rubrique..."
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <!-- Rubrique -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📋 Rubrique</label>
                            <select
                                v-model="localFilters.rubrique"
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Toutes</option>
                                <option v-for="r in rubriques" :key="r" :value="r">{{ r }}</option>
                            </select>
                        </div>

                        <!-- Ministère -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🏛️ Ministère</label>
                            <select
                                v-model="localFilters.ministere"
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Tous</option>
                                <option v-for="m in ministeres" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>

                        <!-- Groupe -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">👥 Groupe politique</label>
                            <select
                                v-model="localFilters.groupe"
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Tous</option>
                                <option v-for="g in groupes" :key="g" :value="g">{{ g }}</option>
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
                                :key="q.uid"
                                :href="route('questions.show', q.uid)"
                                class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md transition-all group"
                            >
                                <div class="flex items-start gap-4">
                                    <!-- Député photo -->
                                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-2xl shrink-0 overflow-hidden">
                                        <img 
                                            v-if="q.acteur?.photo_url" 
                                            :src="q.acteur.photo_url" 
                                            :alt="q.acteur.nom"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else>👤</span>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <!-- Badges -->
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-medium rounded-full">
                                                {{ q.type }}
                                            </span>
                                            <span 
                                                v-if="q.groupe_sigle"
                                                class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                :class="getGroupeColor(q.groupe_sigle)"
                                            >
                                                {{ q.groupe_sigle }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(q.date_question) }}
                                            </span>
                                        </div>

                                        <!-- Titre -->
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                            {{ q.analyse || q.rubrique || 'Question #' + q.numero }}
                                        </h3>

                                        <!-- Rubrique & Ministère -->
                                        <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span v-if="q.rubrique" class="flex items-center gap-1">
                                                📋 {{ q.rubrique }}
                                            </span>
                                            <span v-if="q.ministere_sigle" class="flex items-center gap-1">
                                                🏛️ {{ q.ministere_sigle }}
                                            </span>
                                        </div>

                                        <!-- Auteur -->
                                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            Par <span class="text-indigo-600 dark:text-indigo-400 font-medium">
                                                {{ q.acteur?.prenom }} {{ q.acteur?.nom }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Statut réponse -->
                                    <div class="shrink-0 text-right">
                                        <div 
                                            v-if="q.date_reponse"
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
                            <p class="text-gray-500 dark:text-gray-400">Aucune question trouvée</p>
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
                                        ? 'bg-indigo-600 text-white'
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
