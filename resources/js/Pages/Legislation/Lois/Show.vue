<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import LawVoteWidget from '@/Components/LawVoteWidget.vue';

const props = defineProps({
    loi: Object,
    stats: Object,
    parcours: Array,
    loisSimilaires: Array,
    scrutins: Array,
    scrutinsSolennels: Array,
    scrutinsAmendements: Array,
    scrutinsAutres: Array,
    citizenVoteStats: Object,
    groupesPositions: Object,
    amendementsLies: Object,
    dossierAN: Object,
    parlementairesAssocies: Object,
    debatsLies: Array,
});

// Couleur selon le sort de l'amendement
const getSortColor = (sortCode) => {
    const colors = {
        'ADO': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        'REJ': 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
        'RET': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        'TOM': 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
        'IRR': 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    return colors[sortCode] || 'bg-slate-100 text-slate-600';
};

// Compter le total des scrutins
const totalScrutins = computed(() => {
    return (props.scrutinsSolennels?.length || 0) + 
           (props.scrutinsAmendements?.length || 0) + 
           (props.scrutinsAutres?.length || 0);
});

// Formatter la durée
const dureeFormatee = computed(() => {
    if (!props.stats?.duree_jours || props.stats.duree_jours <= 0) return null;
    const jours = Math.abs(props.stats.duree_jours);
    if (jours < 30) return `${jours} jour${jours > 1 ? 's' : ''}`;
    if (jours < 365) return `${Math.round(jours / 30)} mois`;
    const annees = Math.floor(jours / 365);
    const moisRestants = Math.round((jours % 365) / 30);
    if (moisRestants > 0) return `${annees} an${annees > 1 ? 's' : ''} et ${moisRestants} mois`;
    return `${annees} an${annees > 1 ? 's' : ''}`;
});

// Vérifier si on a des positions de groupes
const hasGroupesPositions = computed(() => {
    return props.groupesPositions && (
        props.groupesPositions.pour?.length > 0 ||
        props.groupesPositions.contre?.length > 0 ||
        props.groupesPositions.abstention?.length > 0
    );
});

const breadcrumbItems = computed(() => [
    { label: 'Accueil', href: '/' },
    { label: 'Législation' },
    { label: 'Lois', href: '/lois' },
    { label: props.loi.numero || 'Détail' },
]);

const etatConfig = {
    blue: { bg: 'bg-sky-500', bgLight: 'bg-sky-50 dark:bg-sky-900/20', text: 'text-sky-700 dark:text-sky-300' },
    green: { bg: 'bg-emerald-500', bgLight: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-700 dark:text-emerald-300' },
    red: { bg: 'bg-rose-500', bgLight: 'bg-rose-50 dark:bg-rose-900/20', text: 'text-rose-700 dark:text-rose-300' },
    yellow: { bg: 'bg-amber-500', bgLight: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-700 dark:text-amber-300' },
    orange: { bg: 'bg-orange-500', bgLight: 'bg-orange-50 dark:bg-orange-900/20', text: 'text-orange-700 dark:text-orange-300' },
    gray: { bg: 'bg-slate-500', bgLight: 'bg-slate-50 dark:bg-slate-800', text: 'text-slate-700 dark:text-slate-300' },
};

const getEtatConfig = (couleur) => etatConfig[couleur] || etatConfig.gray;

const chambreConfig = {
    'A': { icon: '🏛️', name: 'Assemblée Nationale', color: 'bg-sky-500', colorLight: 'bg-sky-100 dark:bg-sky-900/30', textColor: 'text-sky-700 dark:text-sky-300' },
    'S': { icon: '🏛️', name: 'Sénat', color: 'bg-rose-500', colorLight: 'bg-rose-100 dark:bg-rose-900/30', textColor: 'text-rose-700 dark:text-rose-300' },
    'I': { icon: '⚖️', name: 'Commission Mixte Paritaire', color: 'bg-slate-500', colorLight: 'bg-slate-100 dark:bg-slate-800', textColor: 'text-slate-700 dark:text-slate-300' },
};

const getChambreConfig = (code) => chambreConfig[code] || { icon: '📋', name: 'Autre', color: 'bg-slate-400', colorLight: 'bg-slate-100', textColor: 'text-slate-600' };
</script>

<template>
    <Head :title="`Loi ${loi.numero || ''} - CivicDash`" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 dark:bg-gray-900">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 border-b border-slate-200 dark:border-gray-700">
                <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Breadcrumb -->
                    <div class="py-3 border-b border-slate-100 dark:border-gray-700/50">
                        <Breadcrumb :items="breadcrumbItems" />
                    </div>
                    
                    <!-- Title Section -->
                    <div class="py-8">
                        <div class="flex flex-col xl:flex-row xl:items-start gap-6">
                            <!-- Main Info -->
                            <div class="flex-1 min-w-0">
                                <!-- Badges -->
                                <div class="flex flex-wrap items-center gap-3 mb-4">
                                    <span 
                                        v-if="loi.numero"
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-gray-700 rounded-lg text-sm font-mono font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        {{ loi.numero }}
                                    </span>
                                    <span 
                                        :class="[getEtatConfig(loi.etat.couleur).bgLight, getEtatConfig(loi.etat.couleur).text]"
                                        class="px-3 py-1.5 rounded-lg text-sm font-semibold"
                                    >
                                        {{ loi.etat.libelle }}
                                    </span>
                                    <span 
                                        v-if="loi.urgence"
                                        class="px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm font-semibold text-amber-700 dark:text-amber-300"
                                    >
                                        ⚡ Procédure accélérée
                                    </span>
                                </div>

                                <!-- Title -->
                                <h1 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ loi.titre || loi.intitule || 'Sans titre' }}
                                </h1>

                                <!-- Meta -->
                                <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-slate-500 dark:text-slate-400">
                                    <span v-if="loi.date_jo" class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        JO du {{ loi.date_jo }}
                                    </span>
                                    <span v-if="loi.chambre_origine" class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        Origine : {{ loi.chambre_origine }}
                                    </span>
                                    <span v-if="loi.type?.libelle" class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        {{ loi.type.libelle }}
                                    </span>
                                </div>

                                <!-- Thématiques -->
                                <div v-if="loi.thematiques?.length" class="flex flex-wrap gap-2 mt-4">
                                    <span 
                                        v-for="theme in loi.thematiques" 
                                        :key="theme.code"
                                        class="px-3 py-1 bg-slate-100 dark:bg-gray-700 rounded-full text-xs font-medium text-slate-600 dark:text-slate-400"
                                    >
                                        {{ theme.libelle }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar & Links -->
                            <div class="xl:w-72 space-y-4">
                                <!-- Progress -->
                                <div class="bg-slate-50 dark:bg-gray-700/50 rounded-xl p-5 border border-slate-200 dark:border-gray-600">
                                    <div class="text-center mb-3">
                                        <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ loi.progression }}%</div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">Progression</div>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-gray-600 rounded-full h-2">
                                        <div 
                                            :class="[
                                                loi.progression === 100 ? 'bg-emerald-500' : 
                                                loi.progression === 0 ? 'bg-rose-500' : 'bg-sky-500'
                                            ]"
                                            class="h-2 rounded-full transition-all duration-500"
                                            :style="{ width: loi.progression + '%' }"
                                        />
                                    </div>
                                </div>
                                
                                <!-- External Links -->
                                <div class="flex gap-2">
                                    <a 
                                        v-if="loi.url_jo"
                                        :href="loi.url_jo"
                                        target="_blank"
                                        class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700
                                               rounded-lg text-center text-sm font-medium text-white transition-colors"
                                    >
                                        📰 Texte au JO
                                    </a>
                                    <a 
                                        v-if="loi.url_an"
                                        :href="loi.url_an"
                                        target="_blank"
                                        class="flex-1 px-4 py-2.5 bg-sky-600 hover:bg-sky-700
                                               rounded-lg text-center text-sm font-medium text-white transition-colors"
                                    >
                                        🏛️ Dossier AN
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Stats Cards Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-sky-600 dark:text-sky-400">{{ stats?.etapes_total || parcours.length }}</div>
                        <div class="text-xs text-slate-500 mt-1">Étapes</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats?.amendements_total || amendementsLies?.total || 0 }}</div>
                        <div class="text-xs text-slate-500 mt-1">Amendements</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats?.amendements_adoptes || 0 }}</div>
                        <div class="text-xs text-slate-500 mt-1">Adoptés</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ totalScrutins }}</div>
                        <div class="text-xs text-slate-500 mt-1">Scrutins</div>
                    </div>
                    <div v-if="dureeFormatee" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-slate-700 dark:text-slate-300">{{ dureeFormatee }}</div>
                        <div class="text-xs text-slate-500 mt-1">⏱️ Durée</div>
                    </div>
                    <div v-if="stats?.taux_adoption_amendements > 0" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.taux_adoption_amendements }}%</div>
                        <div class="text-xs text-slate-500 mt-1">Taux adoption</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                    <!-- Timeline (3 cols) -->
                    <div class="xl:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
                            <!-- Section Header -->
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                        <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Parcours législatif</h2>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ parcours.length }} étape(s) dans la navette parlementaire</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline Content -->
                            <div class="p-6">
                                <div class="relative">
                                    <!-- Vertical Line -->
                                    <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gradient-to-b from-sky-300 via-slate-300 to-emerald-300 dark:from-sky-600 dark:via-slate-600 dark:to-emerald-600" />

                                    <!-- Steps -->
                                    <div class="space-y-4">
                                        <div 
                                            v-for="(etape, index) in parcours" 
                                            :key="index"
                                            class="relative pl-14"
                                        >
                                            <!-- Timeline Dot -->
                                            <div 
                                                :class="getChambreConfig(etape.chambre).color"
                                                class="absolute left-2.5 w-5 h-5 rounded-full border-4 border-white dark:border-gray-800 shadow-sm flex items-center justify-center text-xs"
                                            />

                                            <!-- Card -->
                                            <div class="bg-slate-50 dark:bg-gray-700/50 rounded-lg p-4 border border-slate-200 dark:border-gray-600 hover:shadow-md transition-shadow">
                                                <div class="flex flex-wrap items-start justify-between gap-2">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-lg">{{ etape.chambre_icone }}</span>
                                                            <h4 class="font-semibold text-slate-900 dark:text-white">
                                                                {{ etape.type_lecture }}
                                                            </h4>
                                                        </div>
                                                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                                            {{ etape.chambre_nom }}
                                                            <span v-if="etape.session" class="text-slate-400 dark:text-slate-500">
                                                                • Session {{ etape.session }}-{{ etape.session + 1 }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <span 
                                                        :class="[getChambreConfig(etape.chambre).colorLight, getChambreConfig(etape.chambre).textColor]"
                                                        class="px-2 py-1 rounded text-xs font-medium"
                                                    >
                                                        Étape {{ index + 1 }}
                                                    </span>
                                                </div>

                                                <!-- Amendements -->
                                                <div v-if="etape.nb_amendements" class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                                                    <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        {{ etape.nb_amendements }} amendement(s)
                                                    </span>
                                                    <span v-if="etape.amendements_adoptes" class="text-emerald-600 dark:text-emerald-400 font-medium">
                                                        ✓ {{ etape.amendements_adoptes }} adopté(s)
                                                    </span>
                                                </div>

                                                <!-- Link to debates -->
                                                <a 
                                                    v-if="etape.url_debats"
                                                    :href="etape.url_debats"
                                                    target="_blank"
                                                    class="mt-3 inline-flex items-center gap-1.5 text-sm text-sky-600 dark:text-sky-400 hover:underline"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                    Voir les débats
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Final State (if promulgated) -->
                                        <div v-if="loi.est_promulguee" class="relative pl-14">
                                            <div class="absolute left-2.5 w-5 h-5 rounded-full bg-emerald-500 border-4 border-white dark:border-gray-800 shadow-sm" />
                                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg">✅</span>
                                                    <h4 class="font-semibold text-emerald-800 dark:text-emerald-300">Promulgation</h4>
                                                </div>
                                                <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-1">
                                                    Publiée au Journal Officiel le {{ loi.date_jo }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Empty State -->
                                        <div v-if="parcours.length === 0" class="text-center py-12">
                                            <div class="inline-flex items-center justify-center w-12 h-12 bg-slate-100 dark:bg-gray-700 rounded-full mb-3">
                                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </div>
                                            <p class="text-slate-500 dark:text-slate-400">Parcours législatif non disponible</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dossier Législatif AN -->
                        <div v-if="dossierAN?.existe" class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden mt-6">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-700 bg-sky-50 dark:bg-sky-900/20">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-sky-100 dark:bg-sky-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">📋 Dossier AN</h2>
                                        <p class="text-sm text-slate-500">{{ dossierAN.ref }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <div v-if="dossierAN.textes?.length" class="space-y-2">
                                    <p class="text-xs text-slate-500 mb-2">{{ dossierAN.textes.length }} texte(s) législatif(s) lié(s)</p>
                                    <div 
                                        v-for="texte in dossierAN.textes" 
                                        :key="texte.uid"
                                        class="flex items-center justify-between p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg text-sm"
                                    >
                                        <div>
                                            <span class="font-mono text-xs text-slate-400">{{ texte.type }}</span>
                                            <span class="ml-2 font-medium text-slate-700 dark:text-slate-300">n° {{ texte.numero }}</span>
                                        </div>
                                        <span v-if="texte.date_depot" class="text-xs text-slate-500">{{ texte.date_depot }}</span>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-slate-500">Aucun texte législatif détaillé disponible</p>
                            </div>
                        </div>

                        <!-- Amendements Liés -->
                        <div v-if="amendementsLies?.amendements?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden mt-6">
                            <!-- Section Header -->
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">📝 Amendements liés</h2>
                                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                                {{ amendementsLies.total }} amendement(s) trouvé(s)
                                                <span v-if="amendementsLies.total_an > 0 || amendementsLies.total_senat > 0" class="text-xs">
                                                    ({{ amendementsLies.total_an || 0 }} AN, {{ amendementsLies.total_senat || 0 }} Sénat)
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Stats par sort -->
                                    <div class="flex gap-2">
                                        <span v-if="amendementsLies.par_sort?.Adopté" class="px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 rounded text-xs font-medium">
                                            ✓ {{ amendementsLies.par_sort.Adopté }} adoptés
                                        </span>
                                        <span v-if="amendementsLies.par_sort?.Rejeté" class="px-2 py-1 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 rounded text-xs font-medium">
                                            ✗ {{ amendementsLies.par_sort.Rejeté }} rejetés
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Amendements List -->
                            <div class="divide-y divide-slate-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                                <a 
                                    v-for="amd in amendementsLies.amendements" 
                                    :key="amd.uid"
                                    :href="amd.url || (amd.chambre === 'AN' ? `https://www.assemblee-nationale.fr/dyn/17/amendements/${amd.texte_ref || ''}/${amd.numero}` : `https://www.senat.fr/amendements/${amd.session || '2024-2025'}/${amd.texte_ref || ''}/${amd.numero}.html`)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block p-4 hover:bg-slate-50 dark:hover:bg-gray-700/50 transition-colors group"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <span 
                                                    :class="amd.chambre === 'AN' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'"
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                                >
                                                    {{ amd.chambre }}
                                                </span>
                                                <span class="font-mono text-xs text-slate-500 group-hover:text-sky-600">{{ amd.numero }}</span>
                                                <span 
                                                    :class="getSortColor(amd.sort_code)"
                                                    class="px-2 py-0.5 rounded text-xs font-medium"
                                                >
                                                    {{ amd.sort }}
                                                </span>
                                                <span class="text-sky-500 opacity-0 group-hover:opacity-100 transition text-xs">↗ Voir détail</span>
                                            </div>
                                            <p class="text-sm text-slate-700 dark:text-slate-300 font-medium group-hover:text-sky-700 dark:group-hover:text-sky-400">
                                                {{ amd.article }}
                                            </p>
                                            <p v-if="amd.expose" class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                                {{ amd.expose }}
                                            </p>
                                            <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                                                <span>👤 {{ amd.auteur }}</span>
                                                <span v-if="amd.date_depot">📅 {{ amd.date_depot }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Méthode de liaison -->
                            <div class="px-4 py-3 bg-slate-50 dark:bg-gray-800/50 border-t border-slate-100 dark:border-gray-700">
                                <div v-if="amendementsLies.liaison_directe" class="flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400">
                                    <span>✓</span>
                                    <span>Liaison directe via dossier AN</span>
                                </div>
                                <p v-else-if="amendementsLies.mots_cles?.length" class="text-xs text-slate-500">
                                    🔍 Recherche par mots-clés : 
                                    <span v-for="(mot, i) in amendementsLies.mots_cles" :key="mot" class="font-medium">
                                        {{ mot }}{{ i < amendementsLies.mots_cles.length - 1 ? ', ' : '' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Scrutins liés - dans le corps principal -->
                        <div v-if="totalScrutins > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden mt-6">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">
                                            <span class="text-2xl">🗳️</span>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Scrutins parlementaires</h2>
                                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                                {{ totalScrutins }} vote(s) enregistré(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="divide-y divide-slate-100 dark:divide-gray-700">
                                <!-- Votes solennels (vote final sur le texte) -->
                                <div v-if="scrutinsSolennels?.length > 0" class="p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-lg">⭐</span>
                                        <h3 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wide">Vote final sur le texte</h3>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <Link
                                            v-for="scrutin in scrutinsSolennels"
                                            :key="scrutin.uid"
                                            :href="route('legislation.scrutins.show', scrutin.uid)"
                                            class="block p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border-2 border-emerald-200 dark:border-emerald-800 hover:border-emerald-400 dark:hover:border-emerald-600 transition-all group"
                                        >
                                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 line-clamp-2 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
                                                {{ scrutin.titre }}
                                            </p>
                                            <div class="flex items-center justify-between mt-3">
                                                <div class="flex items-center gap-3 text-sm">
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓ {{ scrutin.pour }}</span>
                                                    <span class="text-rose-600 dark:text-rose-400 font-bold">✗ {{ scrutin.contre }}</span>
                                                    <span class="text-slate-500">○ {{ scrutin.abstentions }}</span>
                                                </div>
                                                <span 
                                                    :class="scrutin.resultat === 'Adopté' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'"
                                                    class="text-xs px-2.5 py-1 rounded-full font-semibold"
                                                >
                                                    {{ scrutin.resultat }}
                                                </span>
                                            </div>
                                            <p v-if="scrutin.date" class="text-xs text-slate-500 mt-2">📅 {{ scrutin.date }}</p>
                                        </Link>
                                    </div>
                                </div>

                                <!-- Votes sur les articles -->
                                <div v-if="scrutinsAutres?.length > 0" class="p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-lg">📄</span>
                                        <h3 class="text-sm font-semibold text-sky-700 dark:text-sky-400 uppercase tracking-wide">Votes sur les articles</h3>
                                        <span class="text-xs text-slate-400">({{ scrutinsAutres.length }})</span>
                                    </div>
                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        <Link
                                            v-for="scrutin in scrutinsAutres.slice(0, 6)"
                                            :key="scrutin.uid"
                                            :href="route('legislation.scrutins.show', scrutin.uid)"
                                            class="block p-3 rounded-lg bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors"
                                        >
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-mono text-slate-400">#{{ scrutin.numero }}</span>
                                                <span 
                                                    :class="scrutin.resultat === 'Adopté' ? 'text-emerald-600' : 'text-rose-600'"
                                                    class="text-xs font-medium"
                                                >
                                                    {{ scrutin.resultat }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2">{{ scrutin.titre }}</p>
                                            <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                                                <span class="text-emerald-600">✓{{ scrutin.pour }}</span>
                                                <span class="text-rose-600">✗{{ scrutin.contre }}</span>
                                            </div>
                                        </Link>
                                    </div>
                                    <div v-if="scrutinsAutres.length > 6" class="mt-3 text-center">
                                        <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded-full">
                                            + {{ scrutinsAutres.length - 6 }} autres votes
                                        </span>
                                    </div>
                                </div>

                                <!-- Votes sur les amendements -->
                                <div v-if="scrutinsAmendements?.length > 0" class="p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-lg">📝</span>
                                        <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide">Votes sur les amendements</h3>
                                        <span class="text-xs text-slate-400">({{ scrutinsAmendements.length }})</span>
                                    </div>
                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        <Link
                                            v-for="scrutin in scrutinsAmendements.slice(0, 6)"
                                            :key="scrutin.uid"
                                            :href="route('legislation.scrutins.show', scrutin.uid)"
                                            class="block p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors"
                                        >
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-mono text-slate-400">#{{ scrutin.numero }}</span>
                                                <span 
                                                    :class="scrutin.resultat === 'Adopté' ? 'text-emerald-600' : 'text-rose-600'"
                                                    class="text-xs font-medium"
                                                >
                                                    {{ scrutin.resultat }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2">{{ scrutin.titre }}</p>
                                            <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                                                <span class="text-emerald-600">✓{{ scrutin.pour }}</span>
                                                <span class="text-rose-600">✗{{ scrutin.contre }}</span>
                                            </div>
                                        </Link>
                                    </div>
                                    <div v-if="scrutinsAmendements.length > 6" class="mt-3 text-center">
                                        <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded-full">
                                            + {{ scrutinsAmendements.length - 6 }} autres amendements
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Vote Citoyen Widget -->
                        <LawVoteWidget 
                            :loi-cod="loi.loicod"
                            :initial-stats="citizenVoteStats"
                        />

                        <!-- Débats Citoyens Liés -->
                        <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/20 dark:to-gray-800 rounded-xl border border-indigo-200 dark:border-indigo-800 overflow-hidden">
                            <div class="p-4 border-b border-indigo-200 dark:border-indigo-800/50 bg-indigo-50/50 dark:bg-indigo-900/30">
                                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="text-xl">💬</span> Débat citoyen
                                </h3>
                            </div>
                            
                            <!-- Débats existants -->
                            <div v-if="debatsLies?.length" class="p-4 space-y-3">
                                <Link 
                                    v-for="debat in debatsLies" 
                                    :key="debat.id"
                                    :href="route('participation.ideas.show', debat.slug || debat.id)"
                                    class="block p-3 rounded-lg bg-white dark:bg-gray-700/50 border border-indigo-100 dark:border-indigo-800/50 hover:border-indigo-300 dark:hover:border-indigo-600 transition group"
                                >
                                    <p class="text-sm font-medium text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 line-clamp-2">
                                        {{ debat.title }}
                                    </p>
                                    <div class="mt-2 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ debat.created_at }}</span>
                                        <span>{{ debat.posts_count }} messages</span>
                                        <span 
                                            :class="debat.status === 'open' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'"
                                            class="font-medium"
                                        >
                                            {{ debat.status === 'open' ? '● Ouvert' : '● Fermé' }}
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            <!-- Créer un débat -->
                            <div class="p-4" :class="debatsLies?.length ? 'border-t border-indigo-100 dark:border-indigo-800/50' : ''">
                                <div v-if="!debatsLies?.length" class="text-center py-4">
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mb-3">
                                        Aucun débat citoyen n'est encore ouvert sur cette loi
                                    </p>
                                </div>
                                <Link
                                    :href="`/topics/create?loi_cod=${encodeURIComponent(loi.loicod)}&loi_titre=${encodeURIComponent(loi.titre_court || loi.titre)}`"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition"
                                >
                                    <span class="text-lg">➕</span>
                                    {{ debatsLies?.length ? 'Nouveau débat' : 'Lancer le débat' }}
                                </Link>
                                <p class="text-xs text-center text-slate-400 dark:text-slate-500 mt-2">
                                    Donnez votre avis et échangez avec d'autres citoyens
                                </p>
                            </div>
                        </div>

                        <!-- Parlementaires Associés -->
                        <div v-if="parlementairesAssocies?.rapporteurs?.length || parlementairesAssocies?.auteurs_principaux?.length" class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
                            <div class="p-4 border-b border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800/50">
                                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>👥</span> Parlementaires impliqués
                                </h3>
                            </div>
                            <div class="p-4 space-y-4">
                                <!-- Rapporteurs -->
                                <div v-if="parlementairesAssocies.rapporteurs?.length">
                                    <p class="text-xs font-semibold text-sky-600 dark:text-sky-400 mb-2 uppercase tracking-wide">Rapporteur(s)</p>
                                    <div class="space-y-2">
                                        <Link 
                                            v-for="r in parlementairesAssocies.rapporteurs" 
                                            :key="r.uid"
                                            :href="route('representants.deputes.show', r.uid)"
                                            class="flex items-center gap-3 p-2 rounded-lg bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100 dark:hover:bg-sky-900/30 transition"
                                        >
                                            <img 
                                                v-if="r.photo" 
                                                :src="r.photo" 
                                                :alt="r.nom"
                                                class="w-10 h-10 rounded-full object-cover border-2 border-sky-300"
                                            />
                                            <div v-else class="w-10 h-10 rounded-full bg-sky-200 dark:bg-sky-800 flex items-center justify-center text-sky-600 dark:text-sky-400 font-bold text-sm">
                                                {{ r.nom?.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ r.nom }}</p>
                                                <p class="text-xs text-slate-500">{{ r.groupe }} • {{ r.nb_amendements }} amend.</p>
                                            </div>
                                        </Link>
                                    </div>
                                </div>

                                <!-- Auteurs principaux -->
                                <div v-if="parlementairesAssocies.auteurs_principaux?.length">
                                    <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mb-2 uppercase tracking-wide">Principaux auteurs d'amendements</p>
                                    <div class="space-y-1">
                                        <Link 
                                            v-for="a in parlementairesAssocies.auteurs_principaux" 
                                            :key="a.uid"
                                            :href="route('representants.deputes.show', a.uid)"
                                            class="flex items-center justify-between p-2 rounded hover:bg-slate-50 dark:hover:bg-gray-700/50 transition"
                                        >
                                            <div class="flex items-center gap-2">
                                                <img 
                                                    v-if="a.photo" 
                                                    :src="a.photo" 
                                                    :alt="a.nom"
                                                    class="w-8 h-8 rounded-full object-cover"
                                                />
                                                <div v-else class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 text-xs font-bold">
                                                    {{ a.nom?.charAt(0) }}
                                                </div>
                                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ a.nom }}</span>
                                            </div>
                                            <span class="text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded">
                                                {{ a.nb_amendements }}
                                            </span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Positions des Groupes Politiques -->
                        <div v-if="hasGroupesPositions" class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
                            <div class="p-4 border-b border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800/50">
                                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>🏛️</span> Positions des groupes
                                </h3>
                                <p class="text-xs text-slate-500 mt-1">Vote final sur le texte</p>
                            </div>
                            <div class="p-4 space-y-4">
                                <!-- Groupes POUR -->
                                <div v-if="groupesPositions.pour?.length > 0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Pour</span>
                                    </div>
                                    <div class="space-y-1.5">
                                        <div 
                                            v-for="g in groupesPositions.pour.slice(0, 4)" 
                                            :key="g.nom"
                                            class="flex items-center justify-between text-xs bg-emerald-50 dark:bg-emerald-900/20 rounded px-2 py-1.5"
                                        >
                                            <span class="text-slate-700 dark:text-slate-300 truncate flex-1">{{ g.sigle || g.nom }}</span>
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400 ml-2">{{ g.pour }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Groupes CONTRE -->
                                <div v-if="groupesPositions.contre?.length > 0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                                        <span class="text-sm font-medium text-rose-700 dark:text-rose-400">Contre</span>
                                    </div>
                                    <div class="space-y-1.5">
                                        <div 
                                            v-for="g in groupesPositions.contre.slice(0, 4)" 
                                            :key="g.nom"
                                            class="flex items-center justify-between text-xs bg-rose-50 dark:bg-rose-900/20 rounded px-2 py-1.5"
                                        >
                                            <span class="text-slate-700 dark:text-slate-300 truncate flex-1">{{ g.sigle || g.nom }}</span>
                                            <span class="font-bold text-rose-600 dark:text-rose-400 ml-2">{{ g.contre }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Groupes ABSTENTION -->
                                <div v-if="groupesPositions.abstention?.length > 0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-3 h-3 rounded-full bg-slate-400"></div>
                                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Abstention</span>
                                    </div>
                                    <div class="space-y-1.5">
                                        <div 
                                            v-for="g in groupesPositions.abstention.slice(0, 3)" 
                                            :key="g.nom"
                                            class="flex items-center justify-between text-xs bg-slate-100 dark:bg-slate-700/50 rounded px-2 py-1.5"
                                        >
                                            <span class="text-slate-600 dark:text-slate-400 truncate flex-1">{{ g.sigle || g.nom }}</span>
                                            <span class="font-medium text-slate-500 ml-2">{{ g.abstentions }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 p-5">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Légende</h3>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 rounded-full bg-sky-500" />
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Assemblée Nationale</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 rounded-full bg-rose-500" />
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Sénat</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 rounded-full bg-slate-500" />
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Commission Mixte Paritaire</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 rounded-full bg-emerald-500" />
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Promulgation</span>
                                </div>
                            </div>
                        </div>

                        <!-- Similar Laws -->
                        <div v-if="loisSimilaires?.length" class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 p-5">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Lois similaires</h3>
                            <div class="space-y-3">
                                <Link 
                                    v-for="similar in loisSimilaires" 
                                    :key="similar.loicod"
                                    :href="route('lois.show', similar.loicod)"
                                    class="block p-3 rounded-lg bg-slate-50 dark:bg-gray-700/50 hover:bg-slate-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <p class="text-sm font-medium text-slate-900 dark:text-white line-clamp-2">
                                        {{ similar.titre }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        <span v-if="similar.numero">{{ similar.numero }}</span>
                                        <span v-if="similar.date_jo">• {{ similar.date_jo }}</span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Back Button -->
                        <Link 
                            :href="route('lois.index')"
                            class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-white dark:bg-gray-800 
                                   border border-slate-200 dark:border-gray-700 rounded-xl
                                   text-sm font-medium text-slate-700 dark:text-slate-300
                                   hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour à la liste
                        </Link>
                    </div>
                </div>
            </main>
        </div>
    </AuthenticatedLayout>
</template>
