<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    stats: Object,
    budgetsAnnuels: Array,
    recettesConsolidees: Array,
    urssafParAnnee: Array,
})

const formatMontant = (montant, unit = 'Md€') => {
    if (!montant && montant !== 0) return '-'
    const num = parseFloat(montant)
    if (Math.abs(num) >= 1000) {
        return (num / 1000).toFixed(1).replace('.', ',') + ' Md€'
    }
    return num.toFixed(1).replace('.', ',') + ' ' + unit
}

const formatNumber = (num) => {
    if (!num && num !== 0) return '-'
    return new Intl.NumberFormat('fr-FR').format(num)
}

const runImport = (type) => {
    if (confirm(`Lancer l'import ${type} ?`)) {
        router.post(route('admin.finances.import'), { type })
    }
}
</script>

<template>
    <Head title="Finances Publiques - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        💰 Finances Publiques
                    </h1>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">
                        Gérer les données budgétaires et fiscales
                    </p>
                </div>
                
                <div class="flex gap-2">
                    <button 
                        @click="runImport('urssaf')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                    >
                        🔄 Import URSSAF
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <div class="text-2xl font-bold text-blue-600">{{ stats.budget_annuel_count }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Budgets annuels</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <div class="text-2xl font-bold text-green-600">{{ stats.missions_count }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Missions</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <div class="text-2xl font-bold text-purple-600">{{ stats.ministeres_count }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ministères</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <div class="text-2xl font-bold text-orange-600">{{ stats.recettes_count }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Recettes consolidées</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <div class="text-2xl font-bold text-red-600">{{ stats.urssaf_count }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Données URSSAF</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Budgets Annuels -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            📊 Budget de l'État (PLF)
                        </h2>
                        <Link 
                            :href="route('admin.finances.budget-annuel.create')"
                            class="text-blue-600 hover:underline text-sm"
                        >
                            + Ajouter
                        </Link>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left">Année</th>
                                    <th class="px-4 py-2 text-right">Recettes</th>
                                    <th class="px-4 py-2 text-right">Dépenses</th>
                                    <th class="px-4 py-2 text-right">Déficit</th>
                                    <th class="px-4 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="budget in budgetsAnnuels" :key="budget.id" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="px-4 py-2 font-medium">{{ budget.annee }}</td>
                                    <td class="px-4 py-2 text-right text-green-600">{{ budget.recettes_formate }}</td>
                                    <td class="px-4 py-2 text-right text-red-600">{{ budget.depenses_formate }}</td>
                                    <td class="px-4 py-2 text-right text-orange-600">{{ budget.deficit_formate }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <Link 
                                            :href="route('admin.finances.budget-annuel.edit', budget.id)"
                                            class="text-blue-600 hover:underline"
                                        >
                                            Modifier
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recettes Consolidées -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            💵 Recettes Consolidées
                        </h2>
                        <Link 
                            :href="route('admin.finances.recettes.create')"
                            class="text-blue-600 hover:underline text-sm"
                        >
                            + Ajouter
                        </Link>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left">Année</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                    <th class="px-4 py-2 text-right">TVA</th>
                                    <th class="px-4 py-2 text-right">IR</th>
                                    <th class="px-4 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="recette in recettesConsolidees" :key="recette.id" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="px-4 py-2 font-medium">{{ recette.year }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatMontant(recette.total_billions_euros) }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatMontant(recette.tva_billions_euros) }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatMontant(recette.income_tax_billions_euros) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <Link 
                                            :href="route('admin.finances.recettes.edit', recette.id)"
                                            class="text-blue-600 hover:underline"
                                        >
                                            Modifier
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Données URSSAF -->
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        🏥 Données URSSAF (Emploi & Cotisations)
                    </h2>
                    <Link 
                        :href="route('admin.finances.urssaf')"
                        class="text-blue-600 hover:underline text-sm"
                    >
                        Voir détails →
                    </Link>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left">Année</th>
                                <th class="px-4 py-2 text-right">Secteurs</th>
                                <th class="px-4 py-2 text-right">Effectifs salariés</th>
                                <th class="px-4 py-2 text-right">Masse salariale</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="row in urssafParAnnee" :key="row.annee" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-4 py-2 font-medium">{{ row.annee }}</td>
                                <td class="px-4 py-2 text-right">{{ row.nb_secteurs }}</td>
                                <td class="px-4 py-2 text-right">{{ formatNumber(row.total_effectifs) }}</td>
                                <td class="px-4 py-2 text-right">{{ formatMontant(row.total_masse_salariale / 1000000000) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    ⚡ Actions rapides
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium mb-2">📥 Imports automatiques</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Mettre à jour les données depuis les sources officielles
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                @click="runImport('urssaf')"
                                class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm"
                            >
                                URSSAF
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium mb-2">📝 Saisie manuelle</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Ajouter des données à partir de rapports
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Link 
                                :href="route('admin.finances.budget-annuel.create')"
                                class="px-3 py-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 text-sm"
                            >
                                + Budget
                            </Link>
                            <Link 
                                :href="route('admin.finances.recettes.create')"
                                class="px-3 py-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 text-sm"
                            >
                                + Recettes
                            </Link>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium mb-2">📚 Sources</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Liens vers les données officielles
                        </p>
                        <div class="flex flex-wrap gap-2 text-sm">
                            <a href="https://www.budget.gouv.fr/" target="_blank" class="text-blue-600 hover:underline">budget.gouv.fr</a>
                            <a href="https://open.urssaf.fr/" target="_blank" class="text-blue-600 hover:underline">URSSAF</a>
                            <a href="https://www.insee.fr/" target="_blank" class="text-blue-600 hover:underline">INSEE</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
