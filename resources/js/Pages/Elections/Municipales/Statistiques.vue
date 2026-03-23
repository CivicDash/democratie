<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NuancesChart from '@/Components/Municipales/NuancesChart.vue';
import PariteDonut from '@/Components/Municipales/PariteDonut.vue';
import ComparisonBar from '@/Components/Municipales/ComparisonBar.vue';

const props = defineProps({
    stats_nationales: Object,
    stats_departements: Object,
});

const activeTab = ref('national');
const selectedDept = ref(null);

const stats = props.stats_nationales;
const participation = stats?.participation;
const communes = stats?.communes;
const nuances = stats?.nuances;
const parite = stats?.parite_maires;
const renouvellement = stats?.renouvellement;

const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';

const deptCodes = Object.keys(props.stats_departements || {}).sort();
</script>

<template>
    <Head title="Statistiques Municipales 2026" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- En-tête -->
            <div class="mb-8">
                <nav class="mb-4 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <Link :href="route('elections.municipales.resultats.index')" class="hover:text-indigo-600">Résultats</Link>
                    <span>/</span>
                    <span class="text-gray-900 dark:text-gray-100 font-medium">Statistiques</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Statistiques Municipales 2026</h1>
            </div>

            <!-- Onglets -->
            <div class="flex gap-4 mb-8 border-b border-gray-200 dark:border-gray-700">
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

            <!-- Sélecteur département -->
            <div v-if="activeTab === 'departement'" class="mb-6">
                <select v-model="selectedDept" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm">
                    <option :value="null">Choisir un département</option>
                    <option v-for="code in deptCodes" :key="code" :value="code">{{ code }}</option>
                </select>
            </div>

            <!-- Contenu national -->
            <div v-if="activeTab === 'national' && stats">
                <!-- Compteurs -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatNumber(communes?.total) }}</div>
                        <div class="text-sm text-gray-500 mt-1">communes</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ participation?.t1?.taux?.toFixed(1) || '-' }}%</div>
                        <div class="text-sm text-gray-500 mt-1">participation T1</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                        <div class="text-3xl font-bold text-emerald-600">{{ formatNumber(communes?.elues_t1) }}</div>
                        <div class="text-sm text-gray-500 mt-1">élues T1</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                        <div class="text-3xl font-bold text-amber-600">{{ formatNumber(communes?.second_tour) }}</div>
                        <div class="text-sm text-gray-500 mt-1">second tour</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                        <div class="text-3xl font-bold text-pink-600">{{ parite?.taux_femmes?.toFixed(1) || '-' }}%</div>
                        <div class="text-sm text-gray-500 mt-1">femmes maires</div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <NuancesChart :nuances="nuances" />
                    <PariteDonut v-if="parite" :hommes="parite.hommes" :femmes="parite.femmes" :taux-femmes="parite.taux_femmes" />
                </div>

                <!-- Renouvellement -->
                <div v-if="renouvellement" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">
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

                    <!-- Barre visuelle -->
                    <div class="mt-4 flex h-4 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 transition-all" :style="{ width: renouvellement.taux_reelection + '%' }"></div>
                        <div class="bg-indigo-500 transition-all" :style="{ width: (100 - renouvellement.taux_reelection) + '%' }"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>Réélus</span>
                        <span>Nouveaux</span>
                    </div>
                </div>
            </div>

            <!-- Contenu département -->
            <div v-if="activeTab === 'departement' && selectedDept && stats_departements[selectedDept]">
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
            <div v-else-if="activeTab === 'departement' && !selectedDept" class="text-center py-12 text-gray-500 dark:text-gray-400">
                Sélectionnez un département pour afficher ses statistiques.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
