<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CommuneResultCard from '@/Components/Municipales/CommuneResultCard.vue';
import NuanceBadge from '@/Components/Municipales/NuanceBadge.vue';
import NuancesChart from '@/Components/Municipales/NuancesChart.vue';
import PariteDonut from '@/Components/Municipales/PariteDonut.vue';

const props = defineProps({
    stats_nationales: Object,
    top_communes: Array,
    stats_departements: Object,
});

const activeTab = ref('national');
const selectedDept = ref(null);

const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';

const participation = props.stats_nationales?.participation ?? {};
const communes = props.stats_nationales?.communes ?? {};
const nuances = props.stats_nationales?.nuances ?? {};
const parite = props.stats_nationales?.parite_maires;
const renouvellement = props.stats_nationales?.renouvellement;

const topNuances = computed(() => {
    return Object.entries(nuances)
        .filter(([, v]) => v.communes_gagnees > 0)
        .sort((a, b) => b[1].communes_gagnees - a[1].communes_gagnees)
        .slice(0, 8);
});

const totalVoix = computed(() =>
    Object.values(nuances).reduce((sum, v) => sum + (v.voix_total || 0), 0)
);

const deptCodes = Object.keys(props.stats_departements || {}).sort();
</script>

<template>
    <Head title="Résultats Municipales 2026" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-emerald-900 via-teal-900 to-cyan-900 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid-res" width="8" height="8" patternUnits="userSpaceOnUse">
                            <path d="M 8 0 L 0 0 0 8" fill="none" stroke="currentColor" stroke-width="0.3"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid-res)" />
                </svg>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        Résultats Municipales <span class="text-emerald-300">2026</span>
                    </h1>
                    <p class="text-lg text-teal-200 max-w-2xl mx-auto mb-10">
                        Résultats commune par commune, participation, maires élus et analyse des nuances politiques.
                    </p>

                    <!-- Compteurs -->
                    <div v-if="stats_nationales?.communes || stats_nationales?.participation" class="grid grid-cols-2 md:grid-cols-5 gap-4 max-w-4xl mx-auto">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-5 border border-white/20">
                            <div class="text-3xl font-bold text-white">{{ formatNumber(communes.total) }}</div>
                            <div class="text-sm text-teal-200">communes</div>
                        </div>
                        <div class="bg-emerald-500/20 backdrop-blur-sm rounded-xl px-4 py-5 border border-emerald-400/30">
                            <div class="text-3xl font-bold text-emerald-300">{{ formatNumber(communes.elues_t1) }}</div>
                            <div class="text-sm text-emerald-200">élues au T1</div>
                        </div>
                        <div class="bg-amber-500/20 backdrop-blur-sm rounded-xl px-4 py-5 border border-amber-400/30">
                            <div class="text-3xl font-bold text-amber-300">{{ formatNumber(communes.second_tour) }}</div>
                            <div class="text-sm text-amber-200">second tour</div>
                        </div>
                        <div class="bg-cyan-500/20 backdrop-blur-sm rounded-xl px-4 py-5 border border-cyan-400/30">
                            <div class="text-3xl font-bold text-cyan-300">{{ participation?.t1?.taux?.toFixed(1) || '-' }}%</div>
                            <div class="text-sm text-cyan-200">participation T1</div>
                        </div>
                        <div class="bg-pink-500/20 backdrop-blur-sm rounded-xl px-4 py-5 border border-pink-400/30">
                            <div class="text-3xl font-bold text-pink-300">{{ parite?.taux_femmes?.toFixed(1) || '-' }}%</div>
                            <div class="text-sm text-pink-200">femmes maires</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation secondaire -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex gap-6 overflow-x-auto py-3">
                    <Link :href="route('elections.municipales.resultats.transition')" class="text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 whitespace-nowrap">
                        Transition maires
                    </Link>
                    <Link :href="route('elections.municipales.index')" class="text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 whitespace-nowrap">
                        Municipales 2026
                    </Link>
                </div>
            </div>
        </div>

        <!-- Contenu -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
            <!-- État vide -->
            <div v-if="!stats_nationales && !top_communes?.length" class="text-center py-16">
                <div class="text-6xl mb-4">📊</div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Statistiques en cours de calcul</h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Les résultats sont en cours d'importation et de traitement. Revenez dans quelques instants pour consulter les données complètes.
                </p>
            </div>

            <template v-else>
                <!-- Onglets National / Par département -->
                <div class="flex gap-4 border-b border-gray-200 dark:border-gray-700">
                    <button
                        @click="activeTab = 'national'; selectedDept = null"
                        :class="['pb-3 px-1 text-sm font-medium border-b-2 transition', activeTab === 'national' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
                    >
                        National
                    </button>
                    <button
                        @click="activeTab = 'departement'"
                        :class="['pb-3 px-1 text-sm font-medium border-b-2 transition', activeTab === 'departement' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
                    >
                        Par département
                    </button>
                </div>

                <!-- === ONGLET NATIONAL === -->
                <template v-if="activeTab === 'national'">
                    <!-- Participation détaillée -->
                    <section v-if="participation?.t1" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-5">Participation au premier tour</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Inscrits</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(participation.t1.inscrits) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Votants</div>
                                <div class="text-2xl font-bold text-emerald-600">{{ formatNumber(participation.t1.votants) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Participation</div>
                                <div class="text-2xl font-bold text-indigo-600">{{ participation.t1.taux?.toFixed(1) }}%</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Abstention</div>
                                <div class="text-2xl font-bold text-amber-600">{{ participation.t1.abstention?.toFixed(1) }}%</div>
                            </div>
                        </div>
                        <div class="mt-4 flex h-3 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                            <div class="bg-emerald-500 transition-all" :style="{ width: participation.t1.taux + '%' }"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>{{ participation.t1.taux?.toFixed(1) }}% votants</span>
                            <span>{{ participation.t1.abstention?.toFixed(1) }}% abstention</span>
                        </div>
                    </section>

                    <!-- Graphiques nuances + parité -->
                    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <NuancesChart v-if="nuances && Object.keys(nuances).length" :nuances="nuances" />
                        <PariteDonut v-if="parite" :hommes="parite.hommes" :femmes="parite.femmes" :taux-femmes="parite.taux_femmes" />
                    </section>

                    <!-- Renouvellement -->
                    <section v-if="renouvellement" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Renouvellement</h3>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-3xl font-bold text-emerald-600">{{ formatNumber(renouvellement.sortants_reelus) }}</div>
                                <div class="text-sm text-gray-500">maires réélus</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-indigo-600">{{ formatNumber(renouvellement.nouveaux) }}</div>
                                <div class="text-sm text-gray-500">nouveaux maires</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ renouvellement.taux_reelection?.toFixed(1) }}%</div>
                                <div class="text-sm text-gray-500">taux de réélection</div>
                            </div>
                        </div>
                        <div class="mt-4 flex h-4 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 transition-all" :style="{ width: renouvellement.taux_reelection + '%' }"></div>
                            <div class="bg-indigo-500 transition-all" :style="{ width: (100 - renouvellement.taux_reelection) + '%' }"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Réélus</span>
                            <span>Nouveaux</span>
                        </div>
                    </section>

                    <!-- Nuances politiques - cartes -->
                    <section v-if="topNuances.length > 0">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Nuances politiques - communes gagnées</h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div
                                v-for="[code, data] in topNuances"
                                :key="code"
                                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <NuanceBadge :nuance="code" size="sm" />
                                    <span class="text-xs text-gray-400">{{ data.listes_total }} listes</span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(data.communes_gagnees) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">communes gagnées</div>
                                <div class="mt-2 flex h-1.5 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <div class="bg-indigo-500 rounded-full transition-all" :style="{ width: Math.min(100, (data.voix_total / totalVoix) * 100 * 4) + '%' }"></div>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">{{ formatNumber(data.voix_total) }} voix</div>
                            </div>
                        </div>
                    </section>

                    <!-- Grandes villes -->
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-5">Grandes villes</h2>
                        <div v-if="top_communes?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <CommuneResultCard
                                v-for="commune in top_communes"
                                :key="commune.code_commune"
                                :code-commune="commune.code_commune"
                                :nom-commune="commune.nom_commune"
                                :code-departement="commune.code_departement"
                                :taux-participation="commune.taux_participation"
                                :statut-commune="commune.statut_commune"
                                :statut-libelle="commune.statut_libelle"
                                :liste-gagnante="commune.liste_gagnante"
                            />
                        </div>
                        <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                            Aucune commune avec résultats pour le moment.
                        </div>
                    </section>
                </template>

                <!-- === ONGLET PAR DEPARTEMENT === -->
                <template v-if="activeTab === 'departement'">
                    <div class="mb-6">
                        <select v-model="selectedDept" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm">
                            <option :value="null">Choisir un département</option>
                            <option v-for="code in deptCodes" :key="code" :value="code">{{ code }}</option>
                        </select>
                    </div>

                    <div v-if="selectedDept && stats_departements[selectedDept]">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <NuancesChart :nuances="stats_departements[selectedDept].nuances" />
                            <PariteDonut
                                v-if="stats_departements[selectedDept].parite_maires"
                                :hommes="stats_departements[selectedDept].parite_maires.hommes"
                                :femmes="stats_departements[selectedDept].parite_maires.femmes"
                                :taux-femmes="stats_departements[selectedDept].parite_maires.taux_femmes"
                            />
                        </div>
                    </div>
                    <div v-else-if="!selectedDept" class="text-center py-12 text-gray-500 dark:text-gray-400">
                        Sélectionnez un département pour afficher ses statistiques.
                    </div>
                </template>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
