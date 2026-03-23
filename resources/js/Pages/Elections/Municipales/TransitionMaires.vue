<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NuanceBadge from '@/Components/Municipales/NuanceBadge.vue';

const props = defineProps({
    stats: Object,
    grandes_villes: Array,
});

const sortBy = ref('population');
const filterNuance = ref(null);

const renouvellement = props.stats?.renouvellement;
const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';

const hasData = computed(() => (props.grandes_villes?.length ?? 0) > 0);

const nuances = computed(() => {
    const set = new Set();
    props.grandes_villes?.forEach(m => { if (m.nuance) set.add(m.nuance); });
    return [...set].sort();
});

const sortedVilles = computed(() => {
    let list = [...(props.grandes_villes || [])];

    if (filterNuance.value) {
        list = list.filter(m => m.nuance === filterNuance.value);
    }

    if (sortBy.value === 'population') {
        list.sort((a, b) => (b.population || 0) - (a.population || 0));
    } else if (sortBy.value === 'changement') {
        list.sort((a, b) => (a.reelu === b.reelu ? 0 : a.reelu ? 1 : -1));
    }

    return list;
});
</script>

<template>
    <Head title="Transition des maires — Municipales 2026" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-indigo-900 via-purple-900 to-fuchsia-900 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid-trans" width="8" height="8" patternUnits="userSpaceOnUse">
                            <path d="M 8 0 L 0 0 0 8" fill="none" stroke="currentColor" stroke-width="0.3"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid-trans)" />
                </svg>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
                <nav class="mb-6 text-sm text-purple-300 flex items-center gap-2">
                    <Link :href="route('elections.municipales.resultats.index')" class="hover:text-white transition-colors">Résultats</Link>
                    <span>/</span>
                    <span class="text-white font-medium">Transition maires</span>
                </nav>

                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        Transition des <span class="text-fuchsia-300">maires</span>
                    </h1>
                    <p class="text-lg text-purple-200 max-w-2xl mx-auto mb-4">
                        Mandature 2020-2026 vers 2026-2032 : qui succède à qui dans les grandes villes ?
                    </p>
                    <p class="text-sm text-amber-300/80 max-w-xl mx-auto mb-10 bg-amber-500/10 rounded-lg px-4 py-2 border border-amber-400/20">
                        Données provisoires basées sur les têtes de liste gagnantes. Le maire définitif est élu par le conseil municipal.
                    </p>

                    <!-- Compteurs renouvellement -->
                    <div v-if="renouvellement" class="grid grid-cols-2 md:grid-cols-3 gap-4 max-w-2xl mx-auto">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-5 border border-white/20">
                            <div class="text-3xl font-bold text-emerald-300">{{ formatNumber(renouvellement.sortants_reelus) }}</div>
                            <div class="text-sm text-emerald-200">Maires réélus</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-5 border border-white/20">
                            <div class="text-3xl font-bold text-fuchsia-300">{{ formatNumber(renouvellement.nouveaux) }}</div>
                            <div class="text-sm text-fuchsia-200">Nouveaux maires</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-5 border border-white/20 col-span-2 md:col-span-1">
                            <div class="text-3xl font-bold text-white">{{ renouvellement.taux_reelection?.toFixed(1) }}%</div>
                            <div class="text-sm text-purple-200">Taux de réélection</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- État vide -->
            <div v-if="!hasData" class="text-center py-16">
                <div class="text-6xl mb-4">🏛️</div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Transition en cours de calcul</h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    La transition des maires n'a pas encore été calculée. Les résultats des élections doivent d'abord être entièrement importés et traités.
                </p>
                <Link :href="route('elections.municipales.resultats.index')" class="inline-block mt-6 px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition">
                    Retour aux résultats
                </Link>
            </div>

            <!-- Filtres + Grille -->
            <template v-else>
                <div class="flex flex-wrap gap-4 mb-6">
                    <select v-model="sortBy" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm">
                        <option value="population">Tri par population</option>
                        <option value="changement">Changements d'abord</option>
                    </select>
                    <select v-model="filterNuance" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm">
                        <option :value="null">Toutes les nuances</option>
                        <option v-for="n in nuances" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <span class="text-sm text-gray-500 dark:text-gray-400 self-center ml-auto">{{ sortedVilles.length }} villes</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="maire in sortedVilles"
                        :key="maire.code_commune"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg transition-shadow"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ maire.commune }}</h3>
                            <span class="text-xs text-gray-400">{{ formatNumber(maire.population) }} hab.</span>
                        </div>

                        <!-- Nouveau maire -->
                        <Link :href="maire.url" class="flex items-center gap-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 mb-3 group hover:border-emerald-400 dark:hover:border-emerald-600 transition">
                            <div class="w-10 h-10 rounded-full bg-emerald-200 dark:bg-emerald-800 flex items-center justify-center text-lg flex-shrink-0">
                                {{ maire.photo ? '' : '👤' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm truncate group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                                    {{ maire.nom_complet }}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <NuanceBadge v-if="maire.nuance" :nuance="maire.nuance" size="xs" />
                                    <span v-if="maire.score" class="text-xs text-gray-500">{{ maire.score?.toFixed(1) }}%</span>
                                    <span v-if="maire.tour === 2" class="text-xs text-amber-600 font-medium">T2</span>
                                </div>
                            </div>
                            <span v-if="maire.reelu" class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                Réélu
                            </span>
                            <span v-else class="px-2 py-0.5 text-xs font-medium rounded-full bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/50 dark:text-fuchsia-300">
                                Nouveau
                            </span>
                        </Link>

                        <!-- Prédécesseur -->
                        <div v-if="maire.predecesseur && !maire.reelu" class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-sm flex-shrink-0">
                                👤
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                    Succède à <span class="font-medium text-gray-700 dark:text-gray-300">{{ maire.predecesseur.nom_complet }}</span>
                                </div>
                                <NuanceBadge v-if="maire.predecesseur.nuance" :nuance="maire.predecesseur.nuance" size="xs" class="mt-0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="sortedVilles.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    Aucun résultat avec ces filtres.
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
