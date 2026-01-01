<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    annee: Number,
    vue: String,
    anneesDisponibles: Array,
    budgetAnnuel: Object,
    missions: Array,
    ministeres: Array,
    evolution: Array,
    stats: Object,
});

const selectedVue = ref(props.vue);
const selectedAnnee = ref(props.annee);

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Budget de l\'État', current: true, icon: '💰' },
];

// Changer l'année
const changeAnnee = (annee) => {
    router.visit(route('budget-etat.index', { annee, vue: selectedVue.value }), {
        preserveState: true,
    });
};

// Changer la vue
const changeVue = (vue) => {
    selectedVue.value = vue;
    router.visit(route('budget-etat.index', { annee: selectedAnnee.value, vue }), {
        preserveState: true,
    });
};

// Calcul du total pour le graphique
const totalBudget = computed(() => {
    return props.missions.reduce((sum, m) => sum + (m.credits_cp || 0), 0);
});

// Pourcentage pour chaque mission
const missionsPct = computed(() => {
    return props.missions.map(m => ({
        ...m,
        pct: totalBudget.value > 0 ? ((m.credits_cp / totalBudget.value) * 100).toFixed(1) : 0,
    }));
});

// Top 5 missions
const topMissions = computed(() => missionsPct.value.slice(0, 5));

// Format des montants
const formatMd = (montant) => {
    if (!montant) return 'N/A';
    const md = montant / 1_000_000_000;
    return md.toFixed(1).replace('.', ',') + ' Md€';
};
</script>

<template>
    <Head title="Budget de l'État" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-900 to-slate-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                            <span class="text-4xl">💰</span>
                            Budget de l'État {{ annee }}
                        </h1>
                        <p class="text-emerald-200 text-lg">
                            Projet de Loi de Finances - Répartition des crédits par mission et ministère
                        </p>
                    </div>
                    
                    <!-- Sélecteur d'année -->
                    <div class="flex items-center gap-4">
                        <select 
                            v-model="selectedAnnee"
                            @change="changeAnnee(selectedAnnee)"
                            class="bg-white/10 border border-white/20 text-white rounded-lg px-4 py-2 backdrop-blur-sm"
                        >
                            <option v-for="a in anneesDisponibles" :key="a" :value="a" class="text-gray-900">
                                {{ a }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Stats clés -->
                <div v-if="budgetAnnuel" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-white">{{ budgetAnnuel.depenses }}</div>
                        <div class="text-emerald-200 text-sm">Dépenses</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-white">{{ budgetAnnuel.recettes }}</div>
                        <div class="text-emerald-200 text-sm">Recettes</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-red-300">{{ budgetAnnuel.deficit }}</div>
                        <div class="text-emerald-200 text-sm">Déficit</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-amber-300">{{ budgetAnnuel.dette_pib }}%</div>
                        <div class="text-emerald-200 text-sm">Dette/PIB</div>
                    </div>
                </div>

                <!-- Indicateurs Maastricht -->
                <div v-if="budgetAnnuel" class="flex flex-wrap gap-3 mt-4">
                    <span class="px-3 py-1 bg-white/10 rounded-full text-white text-sm">
                        {{ budgetAnnuel.sante_indicateur }}
                    </span>
                    <span class="px-3 py-1 bg-white/10 rounded-full text-white text-sm">
                        {{ budgetAnnuel.dette_indicateur }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Tabs de vue -->
                <div class="flex gap-2 mb-6">
                    <button 
                        @click="changeVue('missions')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'missions' 
                                ? 'bg-emerald-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        📊 Par Mission
                    </button>
                    <button 
                        @click="changeVue('ministeres')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'ministeres' 
                                ? 'bg-emerald-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        🏢 Par Ministère
                    </button>
                    <button 
                        @click="changeVue('evolution')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'evolution' 
                                ? 'bg-emerald-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        📈 Évolution
                    </button>
                </div>

                <!-- Vue Missions -->
                <div v-if="selectedVue === 'missions'" class="space-y-6">
                    <!-- Top 5 en barres -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🏆 Top 5 des missions budgétaires
                        </h2>
                        <div class="space-y-4">
                            <div v-for="(mission, idx) in topMissions" :key="mission.id" class="relative">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ idx + 1 }}. {{ mission.libelle }}
                                    </span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ mission.credits_cp_formate }}
                                    </span>
                                </div>
                                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{ 
                                            width: mission.pct + '%', 
                                            backgroundColor: mission.couleur,
                                            minWidth: '2%'
                                        }"
                                    ></div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ mission.pct }}% du budget total
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Toutes les missions -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📋 Toutes les missions ({{ stats.nb_missions }})
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Mission</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Crédits CP</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Programmes</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Part</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="mission in missionsPct" 
                                        :key="mission.id"
                                        class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
                                    >
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-3">
                                                <div 
                                                    class="w-3 h-3 rounded-full"
                                                    :style="{ backgroundColor: mission.couleur }"
                                                ></div>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ mission.libelle }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-gray-700 dark:text-gray-300">
                                            {{ mission.credits_cp_formate }}
                                        </td>
                                        <td class="py-3 px-4 text-right text-gray-600 dark:text-gray-400">
                                            {{ mission.nb_programmes }}
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded text-sm">
                                                {{ mission.pct }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <td class="py-3 px-4 font-bold text-gray-900 dark:text-gray-100">Total</td>
                                        <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-gray-100">
                                            {{ stats.total_cp_formate }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-gray-100">
                                            {{ stats.nb_programmes }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-gray-100">100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Vue Ministères -->
                <div v-if="selectedVue === 'ministeres'" class="space-y-6">
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🏢 Budget par Ministère
                        </h2>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="ministere in ministeres" 
                                :key="ministere.id"
                                class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-lg transition"
                                :style="{ borderLeftColor: ministere.couleur, borderLeftWidth: '4px' }"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <span 
                                        class="px-2 py-1 text-xs font-bold text-white rounded"
                                        :style="{ backgroundColor: ministere.couleur }"
                                    >
                                        {{ ministere.sigle }}
                                    </span>
                                </div>
                                <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2 line-clamp-2">
                                    {{ ministere.nom }}
                                </h3>
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ ministere.budget_formate }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ ministere.nb_programmes }} programmes
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Vue Évolution -->
                <div v-if="selectedVue === 'evolution'" class="space-y-6">
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📈 Évolution du budget (2020-{{ annee }})
                        </h2>
                        
                        <!-- Tableau d'évolution -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Année</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Recettes</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Dépenses</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Déficit</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Déficit/PIB</th>
                                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Dette/PIB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="e in evolution" 
                                        :key="e.annee"
                                        :class="[
                                            'border-b border-gray-100 dark:border-gray-800',
                                            e.annee === annee ? 'bg-emerald-50 dark:bg-emerald-900/20' : ''
                                        ]"
                                    >
                                        <td class="py-3 px-4 font-bold text-gray-900 dark:text-gray-100">
                                            {{ e.annee }}
                                        </td>
                                        <td class="py-3 px-4 text-right text-green-600 dark:text-green-400">
                                            {{ e.recettes }} Md€
                                        </td>
                                        <td class="py-3 px-4 text-right text-blue-600 dark:text-blue-400">
                                            {{ e.depenses }} Md€
                                        </td>
                                        <td class="py-3 px-4 text-right text-red-600 dark:text-red-400 font-medium">
                                            {{ e.deficit }} Md€
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <span :class="[
                                                'px-2 py-1 rounded text-sm',
                                                Math.abs(e.deficit_pib) <= 3 
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                            ]">
                                                {{ e.deficit_pib }}%
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <span :class="[
                                                'px-2 py-1 rounded text-sm',
                                                e.dette_pib <= 60 
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                                    : e.dette_pib <= 100
                                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                            ]">
                                                {{ e.dette_pib }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Légende Maastricht -->
                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                📘 Critères de Maastricht
                            </h3>
                            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                <li>• <strong>Déficit/PIB</strong> : doit être inférieur à 3% (🟢 conforme si ≤ 3%)</li>
                                <li>• <strong>Dette/PIB</strong> : doit être inférieur à 60% (🟢 conforme si ≤ 60%)</li>
                            </ul>
                        </div>
                    </Card>
                </div>

                <!-- Source des données -->
                <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        📊 Données issues du Projet de Loi de Finances (PLF) {{ annee }} - 
                        <a href="https://www.budget.gouv.fr/" target="_blank" class="text-emerald-600 hover:underline">
                            budget.gouv.fr
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
