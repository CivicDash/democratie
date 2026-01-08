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
    recettesParType: Array,
    recettesConsolidees: Object,
    perimetres: Array,
    urssafData: Object,
    salairesFrance: Object,
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

// Formater les nombres
const formatNumber = (num) => {
    if (!num && num !== 0) return '-';
    return new Intl.NumberFormat('fr-FR').format(num);
};

// Calcul du total pour le graphique
const totalBudget = computed(() => {
    return props.missions.reduce((sum, m) => sum + (m.credits_cp || 0), 0);
});

// Missions avec pourcentages (déjà calculés côté backend via part_pct)
const missionsPct = computed(() => {
    return props.missions.map(m => ({
        ...m,
        pct: m.part_pct || 0,
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
    <Head title="Statistiques État" />

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
                            Statistiques État {{ annee }}
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
                <div class="flex flex-wrap gap-2 mb-6">
                    <button 
                        @click="changeVue('perimetres')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'perimetres' 
                                ? 'bg-indigo-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        🎯 Vue d'ensemble
                    </button>
                    <button 
                        @click="changeVue('missions')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'missions' 
                                ? 'bg-emerald-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        📊 Dépenses État
                    </button>
                    <button 
                        @click="changeVue('recettes')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'recettes' 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        💰 Recettes (consolidé)
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
                    <button 
                        v-if="urssafData"
                        @click="changeVue('emploi')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'emploi' 
                                ? 'bg-teal-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        👷 Emploi & Cotisations
                    </button>
                </div>

                <!-- Vue Périmètres (Vue d'ensemble) -->
                <div v-if="selectedVue === 'perimetres'" class="space-y-6">
                    <!-- Explication -->
                    <Card>
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 mb-6">
                            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center gap-2">
                                <span>ℹ️</span> Comprendre les finances publiques
                            </h3>
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                Les finances publiques françaises sont réparties entre <strong>3 périmètres distincts</strong> : 
                                l'État (PLF), la Sécurité sociale (PLFSS), et les Collectivités locales.
                            </p>
                        </div>

                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🎯 Les 3 périmètres des finances publiques
                        </h2>
                        
                        <div class="grid md:grid-cols-3 gap-6">
                            <div 
                                v-for="perimetre in perimetres" 
                                :key="perimetre.id"
                                class="relative p-6 rounded-xl border-2 hover:shadow-lg transition"
                                :style="{ borderColor: perimetre.color }"
                            >
                                <div class="text-4xl mb-3">{{ perimetre.icon }}</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ perimetre.nom }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    {{ perimetre.description }}
                                </p>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Recettes</span>
                                        <span class="font-semibold text-green-600">{{ perimetre.recettes }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Dépenses</span>
                                        <span class="font-semibold text-blue-600">{{ perimetre.depenses }}</span>
                                    </div>
                                    <div v-if="perimetre.deficit_secu" class="flex justify-between pt-1 border-t border-gray-200 dark:border-gray-700">
                                        <span class="text-sm text-gray-500">Solde</span>
                                        <span 
                                            class="font-semibold"
                                            :class="perimetre.deficit_secu.is_positive ? 'text-green-600' : 'text-red-600'"
                                        >
                                            {{ perimetre.deficit_secu.formate }}
                                        </span>
                                    </div>
                                </div>
                                <div 
                                    class="absolute top-0 left-0 w-full h-1 rounded-t-xl"
                                    :style="{ backgroundColor: perimetre.color }"
                                ></div>
                            </div>
                        </div>
                    </Card>

                    <!-- Total consolidé -->
                    <Card v-if="recettesConsolidees">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📊 Total Finances Publiques ({{ recettesConsolidees.annee || annee }})
                        </h2>
                        <div class="grid md:grid-cols-4 gap-4">
                            <div class="p-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl text-white">
                                <div class="text-3xl font-bold">{{ recettesConsolidees.total_formate }}</div>
                                <div class="text-indigo-100">Total recettes</div>
                            </div>
                            <div class="p-4 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl text-white">
                                <div class="text-2xl font-bold">{{ recettesConsolidees.etat_formate }}</div>
                                <div class="text-blue-100">🏛️ État</div>
                            </div>
                            <div class="p-4 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl text-white">
                                <div class="text-2xl font-bold">{{ recettesConsolidees.securite_sociale_formate }}</div>
                                <div class="text-emerald-100">🏥 Sécu</div>
                            </div>
                            <div class="p-4 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl text-white">
                                <div class="text-2xl font-bold">{{ recettesConsolidees.collectivites_formate }}</div>
                                <div class="text-amber-100">🏘️ Collectivités</div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Vue Recettes consolidées -->
                <div v-if="selectedVue === 'recettes'" class="space-y-6">
                    <Card>
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                💰 Recettes par type d'impôt ({{ recettesConsolidees?.annee || annee }})
                            </h2>
                            <span class="text-sm text-gray-500">
                                Total : {{ recettesConsolidees?.total_formate || 'N/A' }}
                            </span>
                        </div>

                        <!-- Barres de recettes -->
                        <div class="space-y-4">
                            <div 
                                v-for="recette in recettesParType" 
                                :key="recette.label"
                                class="relative"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">{{ recette.icon }}</span>
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ recette.label }}
                                            </span>
                                            <span 
                                                class="ml-2 px-2 py-0.5 text-xs rounded"
                                                :class="{
                                                    'bg-blue-100 text-blue-700': recette.perimetre === 'État',
                                                    'bg-green-100 text-green-700': recette.perimetre === 'Sécurité sociale',
                                                    'bg-amber-100 text-amber-700': recette.perimetre === 'Collectivités',
                                                    'bg-gray-100 text-gray-700': recette.perimetre === 'Divers',
                                                }"
                                            >
                                                {{ recette.perimetre }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-gray-900 dark:text-gray-100">
                                            {{ recette.value.toFixed(1) }} Md€
                                        </span>
                                        <span class="ml-2 text-sm text-gray-500">
                                            ({{ recette.pct }}%)
                                        </span>
                                    </div>
                                </div>
                                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                        :style="{ 
                                            width: Math.max(recette.pct, 2) + '%', 
                                            backgroundColor: recette.color,
                                        }"
                                    >
                                        <span v-if="recette.pct > 10" class="text-xs text-white font-medium">
                                            {{ recette.pct }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ recette.description }}
                                </div>
                            </div>
                        </div>

                        <!-- Note explicative -->
                        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                            <h3 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">
                                ⚠️ Note importante
                            </h3>
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                Ces données représentent les <strong>recettes totales des administrations publiques</strong> (État + Sécu + Collectivités).
                            </p>
                        </div>
                    </Card>

                    <!-- Répartition par périmètre -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📈 Répartition par périmètre
                        </h2>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                <div class="text-2xl font-bold text-blue-600">{{ recettesConsolidees?.etat_formate }}</div>
                                <div class="text-blue-700 dark:text-blue-300 font-medium">🏛️ État</div>
                                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">TVA, IR, IS, TICPE...</div>
                            </div>
                            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                                <div class="text-2xl font-bold text-emerald-600">{{ recettesConsolidees?.securite_sociale_formate }}</div>
                                <div class="text-emerald-700 dark:text-emerald-300 font-medium">🏥 Sécurité sociale</div>
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Cotisations, CSG...</div>
                            </div>
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                                <div class="text-2xl font-bold text-amber-600">{{ recettesConsolidees?.collectivites_formate }}</div>
                                <div class="text-amber-700 dark:text-amber-300 font-medium">🏘️ Collectivités</div>
                                <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">Taxe foncière, CFE...</div>
                            </div>
                        </div>
                    </Card>
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
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ ministere.nb_programmes }} programmes
                                    </span>
                                    <span class="text-xs px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded">
                                        {{ ministere.part_pct }}%
                                    </span>
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

                <!-- Vue Emploi & Cotisations (URSSAF) -->
                <div v-if="selectedVue === 'emploi' && urssafData" class="space-y-6">
                    <!-- Stats globales -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card class="bg-gradient-to-br from-teal-500 to-teal-600 text-white">
                            <div class="text-3xl font-bold">{{ urssafData.total_effectifs_formate }}</div>
                            <div class="text-teal-100">Salariés du secteur privé</div>
                            <div class="text-sm text-teal-200 mt-1">Données {{ urssafData.annee }}</div>
                        </Card>
                        <Card class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white">
                            <div class="text-3xl font-bold">{{ urssafData.total_masse_salariale_formate }}</div>
                            <div class="text-emerald-100">Masse salariale annuelle</div>
                            <div class="text-sm text-emerald-200 mt-1">Base des cotisations</div>
                        </Card>
                        <Card class="bg-gradient-to-br from-blue-500 to-blue-600 text-white">
                            <div class="text-3xl font-bold">{{ urssafData.salaire_moyen_mensuel }}</div>
                            <div class="text-blue-100">Salaire moyen brut</div>
                            <div class="text-sm text-blue-200 mt-1">Calculé: masse / effectifs</div>
                        </Card>
                    </div>

                    <!-- Encadré salaires INSEE (médian vs moyen) -->
                    <Card v-if="salairesFrance" class="border-2 border-amber-200 dark:border-amber-800 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <span>💡</span> Salaires nets en France ({{ salairesFrance.annee }})
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Source : {{ salairesFrance.source }}
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded-full text-xs font-medium">
                                INSEE
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Salaire médian -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border-2 border-green-200 dark:border-green-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-2xl">📊</span>
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/40 px-2 py-0.5 rounded">
                                        Plus représentatif
                                    </span>
                                </div>
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ salairesFrance.salaire_median_formate }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Salaire <strong>médian</strong> net mensuel
                                </div>
                                <p class="text-xs text-gray-500 mt-3 leading-relaxed">
                                    {{ salairesFrance.info }}
                                </p>
                            </div>

                            <!-- Salaire moyen -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-2xl">📈</span>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                                        Tiré par les hauts salaires
                                    </span>
                                </div>
                                <div class="text-3xl font-bold text-gray-700 dark:text-gray-300">
                                    {{ salairesFrance.salaire_moyen_formate }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Salaire <strong>moyen</strong> net mensuel
                                </div>
                                <p class="text-xs text-gray-500 mt-3">
                                    +{{ salairesFrance.ecart_moyen_median_pct }}% au-dessus du médian
                                </p>
                            </div>
                        </div>

                        <!-- Distribution déciles -->
                        <div v-if="salairesFrance.d1 && salairesFrance.d9" class="bg-white dark:bg-slate-800 rounded-xl p-4 mb-6">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">📉 Distribution des salaires</h3>
                            <div class="flex items-center justify-between gap-4">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-red-600">{{ salairesFrance.d1_formate }}</div>
                                    <div class="text-xs text-gray-500">10% gagnent moins (D1)</div>
                                </div>
                                <div class="flex-1 h-4 bg-gradient-to-r from-red-200 via-amber-200 via-green-200 to-blue-200 rounded-full relative">
                                    <div class="absolute left-1/2 -translate-x-1/2 -top-1 w-0.5 h-6 bg-green-600"></div>
                                    <div class="absolute left-1/2 -translate-x-1/2 top-6 text-xs font-medium text-green-600">Médian</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600">{{ salairesFrance.d9_formate }}</div>
                                    <div class="text-xs text-gray-500">10% gagnent plus (D9)</div>
                                </div>
                            </div>
                            <div class="text-center mt-4 text-sm text-gray-500">
                                Rapport interdécile D9/D1 : <strong>{{ salairesFrance.rapport_interdecile }}</strong>
                            </div>
                        </div>

                        <!-- Par catégorie -->
                        <div v-if="salairesFrance.par_categorie?.length" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div 
                                v-for="cat in salairesFrance.par_categorie" 
                                :key="cat.categorie"
                                class="bg-white dark:bg-slate-800 rounded-lg p-3 text-center"
                            >
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ cat.salaire_median_formate }}
                                </div>
                                <div class="text-xs text-gray-500">{{ cat.categorie }}</div>
                                <div class="text-xs text-gray-400">(médian)</div>
                            </div>
                        </div>
                    </Card>

                    <!-- Top secteurs -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🏭 Top 10 des secteurs par effectifs ({{ urssafData.annee }})
                        </h2>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="(secteur, index) in urssafData.top_secteurs" 
                                :key="secteur.code"
                                class="relative"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-gray-400 w-6">{{ index + 1 }}</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                            {{ secteur.secteur }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-semibold text-teal-600 dark:text-teal-400">
                                            {{ formatNumber(secteur.effectifs) }} salariés
                                        </span>
                                        <span class="text-gray-500 text-sm ml-2">
                                            ({{ secteur.masse_salariale_md }} Md€)
                                        </span>
                                    </div>
                                </div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-gradient-to-r from-teal-500 to-teal-400 rounded-full transition-all duration-500"
                                        :style="{ width: (secteur.effectifs / urssafData.top_secteurs[0].effectifs * 100) + '%' }"
                                    ></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Salaire moyen : {{ formatNumber(secteur.salaire_moyen) }} €/mois
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Évolution emploi -->
                    <Card v-if="urssafData.evolution?.length > 1">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📈 Évolution de l'emploi privé
                        </h2>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 font-semibold">Année</th>
                                        <th class="text-right py-3 px-4 font-semibold">Effectifs</th>
                                        <th class="text-right py-3 px-4 font-semibold">Masse salariale</th>
                                        <th class="text-right py-3 px-4 font-semibold">Évolution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(e, idx) in urssafData.evolution" 
                                        :key="e.annee"
                                        class="border-b border-gray-100 dark:border-gray-800"
                                    >
                                        <td class="py-3 px-4 font-medium">{{ e.annee }}</td>
                                        <td class="py-3 px-4 text-right text-teal-600 dark:text-teal-400">
                                            {{ e.effectifs_millions }} M
                                        </td>
                                        <td class="py-3 px-4 text-right text-emerald-600 dark:text-emerald-400">
                                            {{ e.masse_salariale_md }} Md€
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <span 
                                                v-if="idx > 0"
                                                :class="[
                                                    'px-2 py-1 rounded text-sm',
                                                    e.effectifs > urssafData.evolution[idx-1].effectifs
                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                                ]"
                                            >
                                                {{ e.effectifs > urssafData.evolution[idx-1].effectifs ? '+' : '' }}{{ 
                                                    ((e.effectifs - urssafData.evolution[idx-1].effectifs) / urssafData.evolution[idx-1].effectifs * 100).toFixed(1) 
                                                }}%
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <!-- Explication -->
                    <Card class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">ℹ️</span>
                            <div>
                                <h3 class="font-semibold text-teal-900 dark:text-teal-100 mb-2">
                                    Comprendre ces données
                                </h3>
                                <p class="text-sm text-teal-800 dark:text-teal-200">
                                    Ces données proviennent de l'URSSAF et concernent le <strong>secteur privé uniquement</strong>.
                                    Les cotisations sociales prélevées sur ces salaires financent la Sécurité sociale 
                                    (environ 540 Md€/an), soit plus d'un tiers des recettes publiques totales.
                                </p>
                                <a 
                                    href="https://open.urssaf.fr/" 
                                    target="_blank" 
                                    class="text-teal-600 hover:underline text-sm mt-2 inline-block"
                                >
                                    Source : open.urssaf.fr →
                                </a>
                            </div>
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
                        <template v-if="urssafData">
                            | Emploi : 
                            <a href="https://open.urssaf.fr/" target="_blank" class="text-teal-600 hover:underline">
                                open.urssaf.fr
                            </a>
                        </template>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
