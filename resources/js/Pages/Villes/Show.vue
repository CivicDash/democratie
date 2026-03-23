<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NuanceBadge from '@/Components/Municipales/NuanceBadge.vue';
import { computed } from 'vue';

const props = defineProps({
    ville: Object,
    maire: Object,
    mandatsMaires: Array,
    evolutionPopulation: Array,
    budgets: Array,
    stats: Object,
    elus: Object,
    resultats_municipales_2026: Object,
    villesVoisines: Array,
    breadcrumbs: Array,
});

const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';

const hasResultats = computed(() => !!props.resultats_municipales_2026?.tours?.length);

const populationChart = computed(() => {
    if (!props.evolutionPopulation?.length) return null;
    const data = [...props.evolutionPopulation].reverse().slice(-10);
    const max = Math.max(...data.map(d => d.population));
    return data.map(d => ({
        ...d,
        height: (d.population / max) * 100,
    }));
});

const participationBarWidth = (value, total) => {
    if (!total || !value) return '0%';
    return Math.round((value / total) * 100) + '%';
};
</script>

<template>
    <Head :title="ville.nom + ' - Fiche ville'" />

    <AuthenticatedLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <!-- Hero -->
            <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white py-12 px-6">
                <div class="max-w-7xl mx-auto">
                    <div class="flex flex-wrap items-center gap-4 mb-4">
                        <h1 class="text-3xl md:text-4xl font-bold">
                            {{ ville.nom }}
                        </h1>
                        <span v-if="ville.est_prefecture" class="px-3 py-1 bg-amber-500/20 text-amber-200 text-sm font-medium rounded-full border border-amber-400/30">
                            🏛️ Préfecture
                        </span>
                        <span v-if="ville.est_chef_lieu_region" class="px-3 py-1 bg-purple-500/20 text-purple-200 text-sm font-medium rounded-full border border-purple-400/30">
                            🌟 Chef-lieu de région
                        </span>
                    </div>
                    <p class="text-blue-100 text-lg">
                        {{ ville.departement_nom }} ({{ ville.departement_code }}) • {{ ville.region_nom }}
                    </p>

                    <!-- Stats rapides -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-8">
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ ville.population_formate }}</div>
                            <div class="text-sm text-blue-200">Population</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ ville.superficie }}</div>
                            <div class="text-sm text-blue-200">Superficie</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ ville.densite }}</div>
                            <div class="text-sm text-blue-200">Densité</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ ville.code_postal }}</div>
                            <div class="text-sm text-blue-200">Code postal</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ ville.code_insee }}</div>
                            <div class="text-sm text-blue-200">Code INSEE</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bandeau Élections Municipales 2026 -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                <!-- Post-électoral : résumé rapide avec résultats -->
                <div v-if="hasResultats" class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center text-3xl">
                                🗳️
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">Résultats Municipales 2026</h3>
                                <p class="text-emerald-100">
                                    {{ resultats_municipales_2026.tours[0]?.statut_libelle }} — Participation : {{ resultats_municipales_2026.tours[0]?.taux_participation?.toFixed(1) }}%
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div v-if="resultats_municipales_2026.transition?.nouveau_maire" class="text-right hidden md:block">
                                <div class="text-sm text-emerald-200">Maire élu(e)</div>
                                <div class="text-lg font-bold">{{ resultats_municipales_2026.transition.nouveau_maire.nom_complet }}</div>
                            </div>
                            <Link
                                :href="route('elections.municipales.resultats.index')"
                                class="px-6 py-3 bg-white text-teal-600 font-bold rounded-xl hover:bg-emerald-50 transition"
                            >
                                Tous les résultats →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pré-électoral : bandeau découverte -->
                <Link
                    v-else
                    :href="route('elections.municipales.recherche', { q: ville.nom })"
                    class="block bg-gradient-to-r from-fuchsia-600 via-purple-600 to-indigo-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all hover:scale-[1.01] group"
                >
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center text-3xl">
                                🗳️
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">Élections Municipales 2026</h3>
                                <p class="text-fuchsia-100">
                                    Découvrez les candidats à la mairie de {{ ville.nom }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-center hidden md:block">
                                <div class="text-2xl font-bold">15 mars</div>
                                <div class="text-sm text-fuchsia-200">1er tour</div>
                            </div>
                            <div class="text-center hidden md:block">
                                <div class="text-2xl font-bold">22 mars</div>
                                <div class="text-sm text-fuchsia-200">2nd tour</div>
                            </div>
                            <div class="px-6 py-3 bg-white text-purple-600 font-bold rounded-xl group-hover:bg-fuchsia-50 transition">
                                Voir les listes →
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Colonne principale -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Maire actuel -->
                        <div v-if="maire" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                                <span class="text-2xl">👔</span>
                                Maire en exercice
                            </h2>
                            <Link :href="maire.url" class="flex items-center gap-6 group">
                                <img
                                    v-if="maire.photo_url"
                                    :src="maire.photo_url"
                                    :alt="maire.nom"
                                    class="w-24 h-24 rounded-2xl object-cover shadow-lg"
                                />
                                <div v-else class="w-24 h-24 rounded-2xl bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-600 dark:to-slate-700 flex items-center justify-center text-4xl">
                                    👤
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                        {{ maire.nom }}
                                    </h3>
                                    <p class="text-slate-600 dark:text-slate-400">
                                        Depuis {{ maire.debut_mandat }}
                                    </p>
                                    <span v-if="maire.nuance_politique" class="inline-block mt-2 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm rounded-full">
                                        {{ maire.nuance_politique }}
                                    </span>
                                </div>
                            </Link>
                        </div>

                        <!-- Résultats Élections Municipales 2026 -->
                        <div v-if="hasResultats" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                    <span class="text-2xl">🗳️</span>
                                    Résultats — Municipales 2026
                                </h2>
                            </div>

                            <div class="p-6 space-y-6">
                                <!-- Transition maire -->
                                <div v-if="resultats_municipales_2026.transition?.ancien_maire || resultats_municipales_2026.transition?.nouveau_maire"
                                    class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50">
                                    <div v-if="resultats_municipales_2026.transition.ancien_maire" class="flex-1 text-center">
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Sortant</div>
                                        <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ resultats_municipales_2026.transition.ancien_maire.nom_complet }}
                                        </div>
                                        <NuanceBadge v-if="resultats_municipales_2026.transition.ancien_maire.nuance_politique"
                                            :nuance="resultats_municipales_2026.transition.ancien_maire.nuance_politique" size="xs" class="mt-1" />
                                    </div>
                                    <div class="text-2xl">
                                        {{ resultats_municipales_2026.transition.nouveau_maire?.reelu ? '🔄' : '➡️' }}
                                    </div>
                                    <div v-if="resultats_municipales_2026.transition.nouveau_maire" class="flex-1 text-center">
                                        <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-1">
                                            {{ resultats_municipales_2026.transition.nouveau_maire.reelu ? 'Réélu(e)' : 'Élu(e)' }}
                                        </div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">
                                            {{ resultats_municipales_2026.transition.nouveau_maire.nom_complet }}
                                        </div>
                                        <NuanceBadge v-if="resultats_municipales_2026.transition.nouveau_maire.nuance_politique"
                                            :nuance="resultats_municipales_2026.transition.nouveau_maire.nuance_politique" size="xs" class="mt-1" />
                                    </div>
                                </div>

                                <!-- Par tour -->
                                <div v-for="tour in resultats_municipales_2026.tours" :key="tour.tour" class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-bold text-slate-900 dark:text-white text-lg">
                                            {{ tour.tour === 1 ? '1er tour' : '2nd tour' }}
                                        </h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': tour.statut_commune?.includes('elu'),
                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': tour.statut_commune === 'second_tour',
                                                'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300': !tour.statut_commune?.includes('elu') && tour.statut_commune !== 'second_tour',
                                            }">
                                            {{ tour.statut_libelle }}
                                        </span>
                                    </div>

                                    <!-- Participation -->
                                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Participation</span>
                                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                                {{ tour.taux_participation?.toFixed(1) }}%
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-3 text-sm">
                                                <span class="w-20 text-slate-500 dark:text-slate-400">Inscrits</span>
                                                <div class="flex-1 bg-slate-200 dark:bg-slate-600 rounded-full h-2.5">
                                                    <div class="bg-slate-400 dark:bg-slate-300 h-2.5 rounded-full w-full"></div>
                                                </div>
                                                <span class="w-20 text-right font-medium text-slate-700 dark:text-slate-300">{{ formatNumber(tour.inscrits) }}</span>
                                            </div>
                                            <div class="flex items-center gap-3 text-sm">
                                                <span class="w-20 text-slate-500 dark:text-slate-400">Votants</span>
                                                <div class="flex-1 bg-slate-200 dark:bg-slate-600 rounded-full h-2.5">
                                                    <div class="bg-blue-500 h-2.5 rounded-full" :style="{ width: participationBarWidth(tour.votants, tour.inscrits) }"></div>
                                                </div>
                                                <span class="w-20 text-right font-medium text-slate-700 dark:text-slate-300">{{ formatNumber(tour.votants) }}</span>
                                            </div>
                                            <div class="flex items-center gap-3 text-sm">
                                                <span class="w-20 text-slate-500 dark:text-slate-400">Exprimés</span>
                                                <div class="flex-1 bg-slate-200 dark:bg-slate-600 rounded-full h-2.5">
                                                    <div class="bg-indigo-500 h-2.5 rounded-full" :style="{ width: participationBarWidth(tour.exprimes, tour.inscrits) }"></div>
                                                </div>
                                                <span class="w-20 text-right font-medium text-slate-700 dark:text-slate-300">{{ formatNumber(tour.exprimes) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="tour.blancs || tour.nuls" class="flex gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            <span v-if="tour.blancs">Blancs : {{ formatNumber(tour.blancs) }}</span>
                                            <span v-if="tour.nuls">Nuls : {{ formatNumber(tour.nuls) }}</span>
                                        </div>
                                    </div>

                                    <!-- Sièges -->
                                    <div v-if="tour.nb_sieges_a_pourvoir" class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ tour.nb_sieges_a_pourvoir }} sièges à pourvoir
                                    </div>

                                    <!-- Tableau des listes -->
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b border-slate-200 dark:border-slate-700">
                                                    <th class="text-left py-2 px-2 font-medium text-slate-500 dark:text-slate-400">Liste</th>
                                                    <th class="text-left py-2 px-2 font-medium text-slate-500 dark:text-slate-400">Nuance</th>
                                                    <th class="text-right py-2 px-2 font-medium text-slate-500 dark:text-slate-400">Voix</th>
                                                    <th class="text-right py-2 px-2 font-medium text-slate-500 dark:text-slate-400">%</th>
                                                    <th class="text-center py-2 px-2 font-medium text-slate-500 dark:text-slate-400">Sièges</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="liste in tour.listes" :key="liste.nom_liste"
                                                    class="border-b border-slate-100 dark:border-slate-700/50"
                                                    :class="liste.elu ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : ''">
                                                    <td class="py-3 px-2">
                                                        <div class="font-medium text-slate-900 dark:text-white" :class="{ 'text-emerald-700 dark:text-emerald-400': liste.elu }">
                                                            {{ liste.elu ? '✓ ' : '' }}{{ liste.tete_de_liste || liste.nom_liste }}
                                                        </div>
                                                        <div v-if="liste.tete_de_liste && liste.nom_liste" class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]">
                                                            {{ liste.nom_liste }}
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-2">
                                                        <NuanceBadge v-if="liste.nuance_politique" :nuance="liste.nuance_politique" size="xs" />
                                                    </td>
                                                    <td class="py-3 px-2 text-right font-medium text-slate-700 dark:text-slate-300">
                                                        {{ formatNumber(liste.voix) }}
                                                    </td>
                                                    <td class="py-3 px-2 text-right font-bold" :class="liste.elu ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300'">
                                                        {{ liste.pourcentage_exprimes?.toFixed(2) }}%
                                                    </td>
                                                    <td class="py-3 px-2 text-center">
                                                        <span v-if="liste.sieges_obtenus != null" class="font-semibold text-slate-900 dark:text-white">
                                                            {{ liste.sieges_obtenus }}
                                                        </span>
                                                        <span v-if="liste.sieges_cc != null" class="text-xs text-slate-500 dark:text-slate-400 ml-1">
                                                            ({{ liste.sieges_cc }} CC)
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historique des maires -->
                        <div v-if="mandatsMaires?.length" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                                <span class="text-2xl">📜</span>
                                Historique des maires
                            </h2>
                            <div class="space-y-4">
                                <div
                                    v-for="mandat in mandatsMaires"
                                    :key="mandat.id"
                                    class="flex items-center gap-4 p-4 rounded-xl transition-colors"
                                    :class="mandat.est_actuel ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-slate-50 dark:bg-slate-700/50'"
                                >
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl"
                                        :class="mandat.sexe === 'F' ? 'bg-pink-100 dark:bg-pink-900/30' : 'bg-blue-100 dark:bg-blue-900/30'">
                                        {{ mandat.sexe === 'F' ? '👩' : '👨' }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <component
                                                :is="mandat.maire_url ? Link : 'span'"
                                                :href="mandat.maire_url"
                                                class="font-semibold text-slate-900 dark:text-white"
                                                :class="mandat.maire_url ? 'hover:text-blue-600 transition-colors' : ''"
                                            >
                                                {{ mandat.nom_complet }}
                                            </component>
                                            <span v-if="mandat.est_actuel" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">
                                                En exercice
                                            </span>
                                        </div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ mandat.periode }} · {{ mandat.duree }}
                                        </div>
                                        <div v-if="mandat.nuance_politique" class="text-xs text-slate-500 mt-1">
                                            {{ mandat.nuance_politique }} {{ mandat.parti ? `(${mandat.parti})` : '' }}
                                        </div>
                                    </div>
                                    <div v-if="mandat.score_election" class="text-right">
                                        <div class="text-lg font-bold text-slate-900 dark:text-white">
                                            {{ mandat.score_election }}%
                                        </div>
                                        <div class="text-xs text-slate-500">Élection</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Évolution population -->
                        <div v-if="populationChart?.length" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                                <span class="text-2xl">📈</span>
                                Évolution de la population
                            </h2>
                            <div class="flex items-end gap-2 h-48">
                                <div
                                    v-for="point in populationChart"
                                    :key="point.annee"
                                    class="flex-1 flex flex-col items-center justify-end"
                                >
                                    <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ point.population_formate }}
                                    </div>
                                    <div
                                        class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-lg transition-all duration-300"
                                        :style="{ height: point.height + '%' }"
                                    ></div>
                                    <div class="text-xs text-slate-500 mt-2">{{ point.annee }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Budgets -->
                        <div v-if="budgets?.length" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                                <span class="text-2xl">💰</span>
                                Budgets annuels
                            </h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700">
                                            <th class="text-left py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Année</th>
                                            <th class="text-right py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Recettes fonct.</th>
                                            <th class="text-right py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Dépenses fonct.</th>
                                            <th class="text-right py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Dette</th>
                                            <th class="text-right py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Dette/hab.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="budget in budgets" :key="budget.annee" class="border-b border-slate-100 dark:border-slate-700/50">
                                            <td class="py-3 px-2 font-medium text-slate-900 dark:text-white">{{ budget.annee }}</td>
                                            <td class="py-3 px-2 text-right text-green-600 dark:text-green-400">{{ budget.recettes_fonctionnement }}</td>
                                            <td class="py-3 px-2 text-right text-red-600 dark:text-red-400">{{ budget.depenses_fonctionnement }}</td>
                                            <td class="py-3 px-2 text-right text-slate-600 dark:text-slate-400">{{ budget.dette }}</td>
                                            <td class="py-3 px-2 text-right text-slate-600 dark:text-slate-400">{{ budget.dette_par_habitant }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Santé financière -->
                        <div v-if="stats" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <span>📊</span>
                                Indicateurs clés
                            </h3>
                            <div class="space-y-4">
                                <div v-if="stats.score_sante_financiere !== null" class="text-center p-4 rounded-xl"
                                    :class="{
                                        'bg-emerald-50 dark:bg-emerald-900/20': stats.score_sante_color === 'emerald',
                                        'bg-green-50 dark:bg-green-900/20': stats.score_sante_color === 'green',
                                        'bg-yellow-50 dark:bg-yellow-900/20': stats.score_sante_color === 'yellow',
                                        'bg-orange-50 dark:bg-orange-900/20': stats.score_sante_color === 'orange',
                                        'bg-red-50 dark:bg-red-900/20': stats.score_sante_color === 'red',
                                    }">
                                    <div class="text-3xl font-bold"
                                        :class="{
                                            'text-emerald-600': stats.score_sante_color === 'emerald',
                                            'text-green-600': stats.score_sante_color === 'green',
                                            'text-yellow-600': stats.score_sante_color === 'yellow',
                                            'text-orange-600': stats.score_sante_color === 'orange',
                                            'text-red-600': stats.score_sante_color === 'red',
                                        }">
                                        {{ stats.score_sante_financiere }}/100
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">
                                        Santé financière : {{ stats.score_sante_label }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                        <div class="text-slate-500 dark:text-slate-400">Taux d'endettement</div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ stats.taux_endettement }}</div>
                                    </div>
                                    <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                        <div class="text-slate-500 dark:text-slate-400">Dette/habitant</div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ stats.dette_par_habitant }}</div>
                                    </div>
                                    <div v-if="stats.evolution_population" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                        <div class="text-slate-500 dark:text-slate-400">Évolution pop. (5 ans)</div>
                                        <div class="font-semibold" :class="stats.evolution_population?.startsWith('-') ? 'text-red-600' : 'text-green-600'">
                                            {{ stats.evolution_population }}
                                        </div>
                                    </div>
                                    <div v-if="stats.nb_maires" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                        <div class="text-slate-500 dark:text-slate-400">Maires (historique)</div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ stats.nb_maires }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Élus -->
                        <div v-if="elus.deputes?.length || elus.senateurs?.length" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <span>🏛️</span>
                                Élus nationaux
                            </h3>
                            
                            <!-- Députés -->
                            <div v-if="elus.deputes?.length" class="mb-4">
                                <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">Députés</h4>
                                <div class="space-y-2">
                                    <Link
                                        v-for="depute in elus.deputes"
                                        :key="depute.uid"
                                        :href="depute.url"
                                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                                    >
                                        <img
                                            v-if="depute.photo_url"
                                            :src="depute.photo_url"
                                            :alt="depute.nom"
                                            class="w-10 h-10 rounded-full object-cover"
                                        />
                                        <div v-else class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            🏛️
                                        </div>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ depute.nom }}</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Sénateurs -->
                            <div v-if="elus.senateurs?.length">
                                <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">Sénateurs</h4>
                                <div class="space-y-2">
                                    <Link
                                        v-for="senateur in elus.senateurs"
                                        :key="senateur.matricule"
                                        :href="senateur.url"
                                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                                    >
                                        <img
                                            v-if="senateur.photo_url"
                                            :src="senateur.photo_url"
                                            :alt="senateur.nom"
                                            class="w-10 h-10 rounded-full object-cover"
                                        />
                                        <div v-else class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                            🏛️
                                        </div>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ senateur.nom }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Liens externes -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <span>🔗</span>
                                Liens externes
                            </h3>
                            <div class="space-y-2">
                                <a 
                                    v-if="ville.wikipedia_url"
                                    :href="ville.wikipedia_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                >
                                    <span class="text-xl">📚</span>
                                    <div>
                                        <div class="font-medium text-slate-900 dark:text-white">Wikipedia</div>
                                        <div class="text-xs text-slate-500">Article encyclopédique</div>
                                    </div>
                                    <span class="ml-auto text-slate-400">↗</span>
                                </a>
                                <a 
                                    v-if="ville.site_officiel"
                                    :href="ville.site_officiel"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                >
                                    <span class="text-xl">🌐</span>
                                    <div>
                                        <div class="font-medium text-slate-900 dark:text-white">Site officiel</div>
                                        <div class="text-xs text-slate-500">Mairie de {{ ville.nom }}</div>
                                    </div>
                                    <span class="ml-auto text-slate-400">↗</span>
                                </a>
                                <a 
                                    :href="`https://www.google.com/maps/search/${encodeURIComponent(ville.nom + ', France')}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                >
                                    <span class="text-xl">🗺️</span>
                                    <div>
                                        <div class="font-medium text-slate-900 dark:text-white">Google Maps</div>
                                        <div class="text-xs text-slate-500">Voir sur la carte</div>
                                    </div>
                                    <span class="ml-auto text-slate-400">↗</span>
                                </a>
                            </div>
                        </div>

                        <!-- Intercommunalité -->
                        <div v-if="ville.epci_nom" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <span>🤝</span>
                                Intercommunalité
                            </h3>
                            <p class="text-slate-600 dark:text-slate-400">
                                {{ ville.epci_nom }}
                            </p>
                        </div>

                        <!-- Arrondissements -->
                        <div v-if="ville.arrondissements?.length" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <span>🏘️</span>
                                Arrondissements
                            </h3>
                            <div class="space-y-2">
                                <Link
                                    v-for="arr in ville.arrondissements"
                                    :key="arr.nom"
                                    :href="arr.url"
                                    class="flex justify-between items-center p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                                >
                                    <span class="text-slate-900 dark:text-white">{{ arr.nom }}</span>
                                    <span class="text-sm text-slate-500">{{ arr.population }}</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Villes voisines -->
                        <div v-if="villesVoisines?.length" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <span>📍</span>
                                Villes similaires
                            </h3>
                            <div class="space-y-2">
                                <Link
                                    v-for="v in villesVoisines"
                                    :key="v.id"
                                    :href="v.url"
                                    class="flex justify-between items-center p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                                >
                                    <span class="text-slate-900 dark:text-white">{{ v.nom }}</span>
                                    <span class="text-sm text-slate-500">{{ v.population_formate }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
