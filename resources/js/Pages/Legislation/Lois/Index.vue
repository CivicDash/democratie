<script setup>
import { ref, watch } from 'vue';
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
const showThematiques = ref(false);

const breadcrumbItems = [
    { label: 'Accueil', href: '/' },
    { label: 'Législation' },
    { label: 'Cycle de vie des Lois' },
];

const etatConfig = {
    '01': { bg: 'bg-sky-500', bgLight: 'bg-sky-50 dark:bg-sky-900/20', text: 'text-sky-700 dark:text-sky-300', label: 'En cours', icon: '🔄' },
    '02': { bg: 'bg-slate-500', bgLight: 'bg-slate-50 dark:bg-slate-800', text: 'text-slate-700 dark:text-slate-300', label: 'Fusionné', icon: '🔗' },
    '03': { bg: 'bg-rose-500', bgLight: 'bg-rose-50 dark:bg-rose-900/20', text: 'text-rose-700 dark:text-rose-300', label: 'Rejeté', icon: '❌' },
    '04': { bg: 'bg-emerald-500', bgLight: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-700 dark:text-emerald-300', label: 'Promulgué', icon: '✅' },
    '05': { bg: 'bg-amber-500', bgLight: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-700 dark:text-amber-300', label: 'Caduc', icon: '⏰' },
    '06': { bg: 'bg-orange-500', bgLight: 'bg-orange-50 dark:bg-orange-900/20', text: 'text-orange-700 dark:text-orange-300', label: 'Retiré', icon: '↩️' },
};

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
    showThematiques.value = false;
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
</script>

<template>
    <Head title="Lois - Cycle de vie législatif" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 dark:bg-gray-900">
            <!-- Header Full Width -->
            <header class="bg-white dark:bg-gray-800 border-b border-slate-200 dark:border-gray-700">
                <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Breadcrumb -->
                    <div class="py-3 border-b border-slate-100 dark:border-gray-700/50">
                        <Breadcrumb :items="breadcrumbItems" />
                    </div>
                    
                    <!-- Title Section -->
                    <div class="py-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-slate-100 dark:bg-gray-700 rounded-xl">
                                    <svg class="w-8 h-8 text-slate-700 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">
                                        Cycle de vie des Lois
                                    </h1>
                                    <p class="mt-1 text-slate-500 dark:text-slate-400">
                                        Suivez le parcours législatif de chaque texte à travers l'Assemblée et le Sénat
                                    </p>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="flex flex-wrap gap-3">
                                <div class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-gray-700 rounded-lg">
                                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total?.toLocaleString() }}</span>
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Total</span>
                                </div>
                                <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.promulguees?.toLocaleString() }}</span>
                                    <span class="text-sm text-emerald-600 dark:text-emerald-400">Promulguées</span>
                                </div>
                                <div class="flex items-center gap-2 px-4 py-2 bg-sky-50 dark:bg-sky-900/20 rounded-lg">
                                    <span class="text-2xl font-bold text-sky-600 dark:text-sky-400">{{ stats.en_cours?.toLocaleString() }}</span>
                                    <span class="text-sm text-sky-600 dark:text-sky-400">En cours</span>
                                </div>
                                <div class="flex items-center gap-2 px-4 py-2 bg-teal-50 dark:bg-teal-900/20 rounded-lg">
                                    <span class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ stats.cette_annee?.toLocaleString() }}</span>
                                    <span class="text-sm text-teal-600 dark:text-teal-400">Cette année</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Full Width -->
            <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Thématiques Tags Bar -->
                <div v-if="thematiques?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-4 mb-4">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">🏷️ Thématiques</span>
                        <button 
                            @click="showThematiques = !showThematiques"
                            class="text-xs text-sky-600 dark:text-sky-400 hover:underline"
                        >
                            {{ showThematiques ? 'Réduire' : 'Voir tout (' + thematiques.length + ')' }}
                        </button>
                        <button 
                            v-if="selectedThematique"
                            @click="clearThematique"
                            class="ml-auto text-xs text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1"
                        >
                            <span>✕</span> Effacer le filtre
                        </button>
                    </div>
                    
                    <!-- Selected Thematique Badge -->
                    <div v-if="getSelectedThematique()" class="mb-3">
                        <span 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold
                                   bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 border-2 border-sky-500"
                        >
                            <span>{{ getSelectedThematique().icone }}</span>
                            {{ getSelectedThematique().nom }}
                            <span class="text-xs opacity-70">({{ getSelectedThematique().count?.toLocaleString() }} lois)</span>
                            <button @click="clearThematique" class="ml-1 hover:text-rose-600">✕</button>
                        </span>
                    </div>
                    
                    <!-- Thematiques Grid -->
                    <div 
                        :class="[
                            'flex flex-wrap gap-2 transition-all duration-300 overflow-hidden',
                            showThematiques ? 'max-h-[500px]' : 'max-h-12'
                        ]"
                    >
                        <button
                            v-for="theme in thematiques"
                            :key="theme.slug"
                            @click="selectThematique(theme.slug)"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all',
                                selectedThematique === theme.slug
                                    ? 'bg-sky-500 text-white shadow-md scale-105'
                                    : 'bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-gray-600'
                            ]"
                        >
                            <span>{{ theme.icone }}</span>
                            <span class="truncate max-w-[150px]">{{ theme.nom }}</span>
                            <span class="text-[10px] opacity-60">({{ theme.count }})</span>
                        </button>
                    </div>
                </div>

                <!-- Filters Bar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-4 mb-6">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Search -->
                        <div class="flex-1 min-w-0">
                            <div class="relative">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Rechercher par titre, numéro..."
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-gray-700 border-0 rounded-lg 
                                           text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500
                                           focus:ring-2 focus:ring-sky-500 focus:bg-white dark:focus:bg-gray-600"
                                />
                                <svg class="absolute left-3 top-3 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Filter Pills -->
                        <div class="flex flex-wrap items-center gap-3">
                            <select
                                v-model="selectedEtat"
                                class="px-4 py-2.5 bg-slate-50 dark:bg-gray-700 border-0 rounded-lg 
                                       text-slate-700 dark:text-slate-300 text-sm font-medium
                                       focus:ring-2 focus:ring-sky-500 cursor-pointer"
                            >
                                <option value="">Tous les états</option>
                                <option v-for="etat in etats" :key="etat.code" :value="etat.code">
                                    {{ etat.libelle }}
                                </option>
                            </select>

                            <select
                                v-model="selectedAnnee"
                                class="px-4 py-2.5 bg-slate-50 dark:bg-gray-700 border-0 rounded-lg 
                                       text-slate-700 dark:text-slate-300 text-sm font-medium
                                       focus:ring-2 focus:ring-sky-500 cursor-pointer"
                            >
                                <option value="">Toutes les années</option>
                                <option v-for="annee in annees" :key="annee" :value="annee">
                                    {{ annee }}
                                </option>
                            </select>

                            <select
                                v-model="selectedSort"
                                class="px-4 py-2.5 bg-slate-50 dark:bg-gray-700 border-0 rounded-lg 
                                       text-slate-700 dark:text-slate-300 text-sm font-medium
                                       focus:ring-2 focus:ring-sky-500 cursor-pointer"
                            >
                                <option value="recent">Plus récentes</option>
                                <option value="ancien">Plus anciennes</option>
                                <option value="titre">Titre A-Z</option>
                            </select>

                            <button
                                @click="resetFilters"
                                class="px-4 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 
                                       hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    <Link
                        v-for="loi in lois.data"
                        :key="loi.loicod"
                        :href="route('lois.show', loi.loicod)"
                        class="group bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 
                               hover:border-slate-300 dark:hover:border-gray-600 hover:shadow-lg
                               transition-all duration-200 overflow-hidden"
                    >
                        <!-- Card Header with État -->
                        <div class="p-4 border-b border-slate-100 dark:border-gray-700/50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span 
                                        v-if="loi.numero"
                                        class="px-2.5 py-1 bg-slate-100 dark:bg-gray-700 rounded-md text-xs font-mono 
                                               text-slate-600 dark:text-slate-400"
                                    >
                                        {{ loi.numero.trim() }}
                                    </span>
                                    <span v-if="loi.loidatjo" class="text-xs text-slate-400 dark:text-slate-500">
                                        {{ new Date(loi.loidatjo).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </span>
                                </div>
                                <span 
                                    :class="[getEtatConfig(loi.etaloicod).bgLight, getEtatConfig(loi.etaloicod).text]"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                >
                                    <span>{{ getEtatConfig(loi.etaloicod).icon }}</span>
                                    {{ loi.etat?.etaloilib?.trim() || getEtatConfig(loi.etaloicod).label }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white leading-snug
                                       group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors line-clamp-3">
                                {{ formatTitre(loi.loitit || loi.loiint) }}
                            </h3>

                            <div v-if="loi.type_loi?.typloilib" class="mt-3 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span class="truncate">{{ loi.type_loi.typloilib.trim() }}</span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-4 py-3 bg-slate-50 dark:bg-gray-800/50 border-t border-slate-100 dark:border-gray-700/50">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    Voir le parcours législatif
                                </span>
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-sky-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="lois.data.length === 0" class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 dark:bg-gray-700 rounded-full mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Aucune loi trouvée</h3>
                    <p class="mt-1 text-slate-500 dark:text-slate-400">Essayez de modifier vos critères de recherche.</p>
                    <button
                        @click="resetFilters"
                        class="mt-4 px-4 py-2 bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 dark:hover:bg-gray-600 
                               rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 transition-colors"
                    >
                        Réinitialiser les filtres
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="lois.links && lois.links.length > 3" class="mt-8 flex justify-center">
                    <nav class="inline-flex items-center gap-1 bg-white dark:bg-gray-800 rounded-xl p-1 shadow-sm border border-slate-200 dark:border-gray-700">
                        <template v-for="(link, index) in lois.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                    link.active
                                        ? 'bg-slate-900 dark:bg-white text-white dark:text-gray-900'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-gray-700'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-2 text-sm text-slate-300 dark:text-slate-600"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>

                <!-- Results Count -->
                <div v-if="lois.total" class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
                    {{ lois.from }}-{{ lois.to }} sur {{ lois.total?.toLocaleString() }} lois
                </div>
            </main>
        </div>
    </AuthenticatedLayout>
</template>
