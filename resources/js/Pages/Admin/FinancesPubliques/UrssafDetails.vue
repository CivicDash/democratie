<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    data: Array,
    annee: Number,
    anneesDisponibles: Array,
    totaux: Object,
})

const selectedAnnee = ref(props.annee)

const changeAnnee = (annee) => {
    selectedAnnee.value = annee
    router.get(route('admin.finances.urssaf'), { annee }, { preserveState: true })
}

const formatNumber = (num) => {
    if (!num && num !== 0) return '-'
    return new Intl.NumberFormat('fr-FR').format(num)
}

const formatMontant = (montant) => {
    if (!montant && montant !== 0) return '-'
    const md = montant / 1000000000
    if (md >= 1) {
        return md.toFixed(1).replace('.', ',') + ' Md€'
    }
    const m = montant / 1000000
    return m.toFixed(0).replace('.', ',') + ' M€'
}

const sortedData = computed(() => {
    return [...props.data].sort((a, b) => (b.effectif || 0) - (a.effectif || 0))
})
</script>

<template>
    <Head title="Données URSSAF - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <Link 
                    :href="route('admin.finances.index')"
                    class="text-blue-600 hover:underline text-sm mb-2 inline-block"
                >
                    ← Retour aux finances
                </Link>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            🏥 Données URSSAF
                        </h1>
                        <p class="mt-1 text-gray-600 dark:text-gray-400">
                            Effectifs salariés et masse salariale par secteur d'activité
                        </p>
                    </div>
                    
                    <!-- Sélecteur d'année -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Année :</span>
                        <select 
                            v-model="selectedAnnee"
                            @change="changeAnnee(selectedAnnee)"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="a in anneesDisponibles" :key="a" :value="a">
                                {{ a }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Stats globales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                    <div class="text-3xl font-bold">{{ formatNumber(totaux.effectifs) }}</div>
                    <div class="text-blue-100">Salariés</div>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                    <div class="text-3xl font-bold">{{ formatMontant(totaux.masse_salariale) }}</div>
                    <div class="text-green-100">Masse salariale</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                    <div class="text-3xl font-bold">{{ formatNumber(totaux.etablissements) }}</div>
                    <div class="text-purple-100">Établissements</div>
                </div>
            </div>

            <!-- Tableau des secteurs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Détail par secteur d'activité ({{ annee }})
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Secteur</th>
                                <th class="px-4 py-3 text-left font-medium">Code NAF</th>
                                <th class="px-4 py-3 text-right font-medium">Effectifs</th>
                                <th class="px-4 py-3 text-right font-medium">Masse salariale</th>
                                <th class="px-4 py-3 text-right font-medium">Établissements</th>
                                <th class="px-4 py-3 text-right font-medium">Salaire moy.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr 
                                v-for="(row, index) in sortedData" 
                                :key="row.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-750"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ row.secteur_libelle || '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                                    {{ row.secteur_code }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ formatNumber(row.effectif) }}
                                </td>
                                <td class="px-4 py-3 text-right text-green-600">
                                    {{ formatMontant(row.masse_salariale) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ formatNumber(row.nombre) }}
                                </td>
                                <td class="px-4 py-3 text-right text-blue-600">
                                    {{ row.effectif && row.masse_salariale 
                                        ? formatNumber(Math.round(row.masse_salariale / row.effectif / 12)) + ' €/mois'
                                        : '-' 
                                    }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-700 font-semibold">
                            <tr>
                                <td class="px-4 py-3" colspan="2">Total</td>
                                <td class="px-4 py-3 text-right">{{ formatNumber(totaux.effectifs) }}</td>
                                <td class="px-4 py-3 text-right text-green-600">{{ formatMontant(totaux.masse_salariale) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatNumber(totaux.etablissements) }}</td>
                                <td class="px-4 py-3 text-right text-blue-600">
                                    {{ totaux.effectifs && totaux.masse_salariale 
                                        ? formatNumber(Math.round(totaux.masse_salariale / totaux.effectifs / 12)) + ' €/mois'
                                        : '-' 
                                    }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Source -->
            <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">ℹ️</span>
                    <div>
                        <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">
                            Source des données
                        </h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                            Ces données proviennent de l'API Open Data URSSAF et concernent uniquement le secteur privé.
                        </p>
                        <a 
                            href="https://open.urssaf.fr/" 
                            target="_blank" 
                            class="text-blue-600 hover:underline text-sm"
                        >
                            Accéder à open.urssaf.fr →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
