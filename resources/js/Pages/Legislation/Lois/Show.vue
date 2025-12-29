<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    loi: Object,
    parcours: Array,
    loisSimilaires: Array,
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

                            <!-- Progress Card -->
                            <div class="xl:w-72 bg-slate-50 dark:bg-gray-700/50 rounded-xl p-5 border border-slate-200 dark:border-gray-600">
                                <div class="text-center mb-4">
                                    <div class="text-4xl font-bold text-slate-900 dark:text-white">{{ loi.progression }}%</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">Progression législative</div>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-gray-600 rounded-full h-2.5">
                                    <div 
                                        :class="[
                                            loi.progression === 100 ? 'bg-emerald-500' : 
                                            loi.progression === 0 ? 'bg-rose-500' : 'bg-sky-500'
                                        ]"
                                        class="h-2.5 rounded-full transition-all duration-500"
                                        :style="{ width: loi.progression + '%' }"
                                    />
                                </div>
                                
                                <!-- External Links -->
                                <div class="flex gap-2 mt-5">
                                    <a 
                                        v-if="loi.url_jo"
                                        :href="loi.url_jo"
                                        target="_blank"
                                        class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-600 border border-slate-200 dark:border-gray-500 
                                               rounded-lg text-center text-sm font-medium text-slate-700 dark:text-slate-200
                                               hover:bg-slate-50 dark:hover:bg-gray-500 transition-colors"
                                    >
                                        📰 Journal Officiel
                                    </a>
                                    <a 
                                        v-if="loi.url_an"
                                        :href="loi.url_an"
                                        target="_blank"
                                        class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-600 border border-slate-200 dark:border-gray-500 
                                               rounded-lg text-center text-sm font-medium text-slate-700 dark:text-slate-200
                                               hover:bg-slate-50 dark:hover:bg-gray-500 transition-colors"
                                    >
                                        🏛️ Assemblée
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
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
