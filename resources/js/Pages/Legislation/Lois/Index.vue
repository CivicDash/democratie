<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    lois: Object,
    stats: Object,
    etats: Array,
    types: Array,
    annees: Array,
    thematiques: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedEtat = ref(props.filters?.etat || '');
const selectedType = ref(props.filters?.type || '');
const selectedAnnee = ref(props.filters?.annee || '');
const selectedSort = ref(props.filters?.sort || 'recent');
const selectedThematique = ref(props.filters?.thematique || '');
const showMobileFilters = ref(false);
const showAllThematiques = ref(false);

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Législation', icon: '⚖️' },
    { label: 'Lois', current: true, icon: '📜' },
];

const etatConfig = {
    '01': { bg: 'bg-sky-500', bgLight: 'bg-sky-50 dark:bg-sky-900/20', text: 'text-sky-700 dark:text-sky-300', label: 'En cours', icon: '🔄' },
    '02': { bg: 'bg-slate-500', bgLight: 'bg-slate-50 dark:bg-slate-800', text: 'text-slate-700 dark:text-slate-300', label: 'Fusionné', icon: '🔗' },
    '03': { bg: 'bg-rose-500', bgLight: 'bg-rose-50 dark:bg-rose-900/20', text: 'text-rose-700 dark:text-rose-300', label: 'Rejeté', icon: '❌' },
    '04': { bg: 'bg-emerald-500', bgLight: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-700 dark:text-emerald-300', label: 'Promulgué', icon: '✅' },
    '05': { bg: 'bg-amber-500', bgLight: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-700 dark:text-amber-300', label: 'Caduc', icon: '⏰' },
    '06': { bg: 'bg-orange-500', bgLight: 'bg-orange-50 dark:bg-orange-900/20', text: 'text-orange-700 dark:text-orange-300', label: 'Retiré', icon: '↩️' },
};

const sortOptions = [
    { value: 'recent', label: 'Récents', icon: '🕐' },
    { value: 'ancien', label: 'Anciens', icon: '📅' },
    { value: 'titre', label: 'A-Z', icon: '🔤' },
];

const applyFilters = () => {
    router.get(route('lois.index'), {
        search: search.value || undefined,
        etat: selectedEtat.value || undefined,
        type: selectedType.value || undefined,
        annee: selectedAnnee.value || undefined,
        sort: selectedSort.value || undefined,
        thematique: selectedThematique.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    selectedEtat.value = '';
    selectedType.value = '';
    selectedAnnee.value = '';
    selectedSort.value = 'recent';
    selectedThematique.value = '';
    router.get(route('lois.index'));
};

const selectThematique = (slug) => {
    selectedThematique.value = slug;
    applyFilters();
};

const clearThematique = () => {
    selectedThematique.value = '';
    applyFilters();
};

const getSelectedThematique = () => {
    if (!selectedThematique.value) return null;
    return props.thematiques?.find(t => t.slug === selectedThematique.value);
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (search.value) count++;
    if (selectedEtat.value) count++;
    if (selectedType.value) count++;
    if (selectedAnnee.value) count++;
    if (selectedThematique.value) count++;
    return count;
});

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

watch([selectedEtat, selectedType, selectedAnnee, selectedSort, selectedThematique], applyFilters);

const getEtatConfig = (code) => {
    const trimmedCode = code?.trim();
    return etatConfig[trimmedCode] || etatConfig['01'];
};

const formatTitre = (titre) => {
    if (!titre) return 'Sans titre';
    return titre.length > 120 ? titre.substring(0, 120) + '...' : titre;
};

const formatNumber = (num) => {
    if (!num) return '0';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num.toLocaleString();
};
</script>

<template>
    <Head title="Lois - Législation française" />

    <AuthenticatedLayout>
        <!-- Hero Section Full Width -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-blue-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative w-full px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbItems" variant="light" class="mb-4 hidden sm:block" />
                
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white flex items-center gap-3">
                            <span class="text-3xl sm:text-4xl">📜</span>
                            Lois & Projets de loi
                        </h1>
                        <p class="text-indigo-200 text-sm sm:text-base mt-2 max-w-xl hidden sm:block">
                            Suivez le parcours législatif des textes à travers l'Assemblée et le Sénat
                        </p>
                    </div>
                    
                    <Link
                        :href="route('lois.statistiques')"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-6 sm:py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all text-sm sm:text-base"
                    >
                        <span>📊</span>
                        <span>Statistiques</span>
                    </Link>
                </div>

                <!-- Stats compacts scrollables -->
                <div class="flex gap-3 mt-6 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-hide">
                    <div class="flex-shrink-0 bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20 flex items-center gap-2">
                        <span class="text-white font-bold">{{ formatNumber(stats?.total) }}</span>
                        <span class="text-indigo-200 text-xs">total</span>
                    </div>
                    <div class="flex-shrink-0 bg-emerald-500/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-emerald-400/30 flex items-center gap-2">
                        <span class="text-emerald-300 font-bold">{{ formatNumber(stats?.promulguees) }}</span>
                        <span class="text-emerald-200 text-xs">promulguées</span>
                    </div>
                    <div class="flex-shrink-0 bg-sky-500/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-sky-400/30 flex items-center gap-2">
                        <span class="text-sky-300 font-bold">{{ formatNumber(stats?.en_cours) }}</span>
                        <span class="text-sky-200 text-xs">en cours</span>
                    </div>
                    <div class="flex-shrink-0 bg-amber-500/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-amber-400/30 flex items-center gap-2">
                        <span class="text-amber-300 font-bold">{{ formatNumber(stats?.cette_annee) }}</span>
                        <span class="text-amber-200 text-xs">cette année</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <!-- Quick Sort Tabs - Sticky -->
            <div class="sticky top-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between py-3">
                        <!-- Sort Tabs -->
                        <div class="flex gap-1 sm:gap-2">
                            <button
                                v-for="option in sortOptions"
                                :key="option.value"
                                @click="selectedSort = option.value"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-all',
                                    selectedSort === option.value
                                        ? 'bg-indigo-500 text-white shadow-sm'
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
                            <span v-if="activeFiltersCount > 0" class="px-1.5 py-0.5 bg-indigo-500 text-white text-xs rounded-full">
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
                            v-model="search"
                            type="text"
                            placeholder="🔍 Rechercher..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2.5 text-sm"
                        />
                        
                        <!-- Année & État -->
                        <div class="grid grid-cols-2 gap-3">
                            <select
                                v-model="selectedAnnee"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5"
                            >
                                <option value="">Toutes années</option>
                                <option v-for="annee in annees" :key="annee" :value="annee">
                                    {{ annee }}
                                </option>
                            </select>
                            <select
                                v-model="selectedEtat"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5"
                            >
                                <option value="">Tous états</option>
                                <option v-for="etat in etats" :key="etat.code" :value="etat.code">
                                    {{ etat.libelle }}
                                </option>
                            </select>
                        </div>

                        <!-- Thématique sélectionnée -->
                        <div v-if="getSelectedThematique()" class="flex items-center justify-between p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                            <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                                {{ getSelectedThematique().icone }} {{ getSelectedThematique().nom }}
                            </span>
                            <button @click="clearThematique" class="text-indigo-600 hover:text-indigo-800">✕</button>
                        </div>

                        <!-- Clear button -->
                        <button
                            v-if="activeFiltersCount > 0"
                            @click="resetFilters"
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
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                            <div class="relative">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Rechercher..."
                                    class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-700 border-0 rounded-lg text-sm
                                           text-gray-900 dark:text-white placeholder-gray-400
                                           focus:ring-2 focus:ring-indigo-500"
                                />
                                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Année -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📅 Année</h3>
                            <select
                                v-model="selectedAnnee"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm"
                            >
                                <option value="">Toutes les années</option>
                                <option v-for="annee in annees" :key="annee" :value="annee">
                                    {{ annee }}
                                </option>
                            </select>
                        </div>

                        <!-- État -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📊 État</h3>
                            <div class="space-y-1">
                                <button
                                    @click="selectedEtat = ''"
                                    :class="[
                                        'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors text-left',
                                        !selectedEtat
                                            ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-medium'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <span>📋</span>
                                    <span>Tous les états</span>
                                </button>
                                <button
                                    v-for="etat in etats"
                                    :key="etat.code"
                                    @click="selectedEtat = etat.code"
                                    :class="[
                                        'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors text-left',
                                        selectedEtat === etat.code
                                            ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-medium'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <span>{{ getEtatConfig(etat.code).icon }}</span>
                                    <span class="truncate">{{ etat.libelle }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Clear Filters -->
                        <button
                            v-if="activeFiltersCount > 0"
                            @click="resetFilters"
                            class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition-colors"
                        >
                            ✕ Réinitialiser
                        </button>
                    </aside>

                    <!-- Main Content -->
                    <main class="flex-1 min-w-0">
                        <!-- Thématiques Tags (si disponibles) -->
                        <div v-if="thematiques?.length" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">🏷️ Thématiques</span>
                                <button 
                                    @click="showAllThematiques = !showAllThematiques"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    {{ showAllThematiques ? 'Réduire' : `Voir tout (${thematiques.length})` }}
                                </button>
                            </div>
                            
                            <!-- Thématique sélectionnée -->
                            <div v-if="getSelectedThematique()" class="mb-3">
                                <span 
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold
                                           bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-2 border-indigo-500"
                                >
                                    <span>{{ getSelectedThematique().icone }}</span>
                                    {{ getSelectedThematique().nom }}
                                    <span class="text-xs opacity-70">({{ getSelectedThematique().count?.toLocaleString() }})</span>
                                    <button @click="clearThematique" class="ml-1 hover:text-rose-600">✕</button>
                                </span>
                            </div>
                            
                            <!-- Tags Grid -->
                            <div 
                                :class="[
                                    'flex flex-wrap gap-2 transition-all duration-300 overflow-hidden',
                                    showAllThematiques ? 'max-h-[500px]' : 'max-h-12'
                                ]"
                            >
                                <button
                                    v-for="theme in thematiques"
                                    :key="theme.slug"
                                    @click="selectThematique(theme.slug)"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all',
                                        selectedThematique === theme.slug
                                            ? 'bg-indigo-500 text-white shadow-md scale-105'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    <span>{{ theme.icone }}</span>
                                    <span class="truncate max-w-[120px]">{{ theme.nom }}</span>
                                    <span class="text-[10px] opacity-60">({{ theme.count }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Results count -->
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="text-gray-900 dark:text-white font-semibold">{{ lois.total?.toLocaleString() }}</span> lois
                            </p>
                        </div>

                        <!-- Lois Grid -->
                        <div v-if="lois.data?.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <Link
                                v-for="loi in lois.data.filter(l => l.loicod)"
                                :key="loi.loicod"
                                :href="route('lois.show', loi.loicod.trim())"
                                class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 
                                       hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-lg
                                       transition-all duration-200 overflow-hidden"
                            >
                                <!-- Card Header -->
                                <div class="p-4 border-b border-gray-100 dark:border-gray-700/50">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span 
                                                v-if="loi.numero"
                                                class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono 
                                                       text-gray-600 dark:text-gray-400 shrink-0"
                                            >
                                                {{ loi.numero.trim() }}
                                            </span>
                                            <span v-if="loi.loidatjo" class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                                {{ new Date(loi.loidatjo).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                            </span>
                                        </div>
                                        <span 
                                            :class="loi.est_caduc 
                                                ? 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' 
                                                : [getEtatConfig(loi.etaloicod).bgLight, getEtatConfig(loi.etaloicod).text]"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold shrink-0"
                                        >
                                            <span>{{ loi.est_caduc ? '🕰️' : getEtatConfig(loi.etaloicod).icon }}</span>
                                            <span class="hidden sm:inline">{{ loi.est_caduc ? 'Probablement caduc' : (loi.etat?.etaloilib?.trim() || getEtatConfig(loi.etaloicod).label) }}</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div class="p-4">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug
                                               group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-3">
                                        {{ formatTitre(loi.loitit || loi.loiint) }}
                                    </h3>

                                    <div v-if="loi.type_loi?.typloilib" class="mt-3 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>📁</span>
                                        <span class="truncate">{{ loi.type_loi.typloilib.trim() }}</span>
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/50">
                                    <div class="flex items-center justify-between text-xs text-gray-400">
                                        <span>Voir le parcours</span>
                                        <svg class="w-4 h-4 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="text-5xl mb-4">📄</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aucune loi trouvée</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">
                                Essayez de modifier vos critères de recherche
                            </p>
                            <button
                                @click="resetFilters"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 
                                       rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                Réinitialiser les filtres
                            </button>
                        </div>

                        <!-- Pagination -->
                        <div v-if="lois.last_page > 1" class="flex justify-center gap-1 sm:gap-2 mt-6">
                            <Link
                                v-for="link in lois.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 rounded-lg text-sm transition-colors min-w-[40px] text-center',
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : link.url
                                            ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed pointer-events-none'
                                ]"
                                v-html="link.label"
                            />
                        </div>

                        <!-- Results Count -->
                        <div v-if="lois.total" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ lois.from }}-{{ lois.to }} sur {{ lois.total?.toLocaleString() }} lois
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
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
