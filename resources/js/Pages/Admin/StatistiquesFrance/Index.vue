<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import { ref } from 'vue'

const props = defineProps({
    annee: Number,
    anneesDisponibles: Array,
    statsCategories: Object,
    stats: Object,
})

const selectedAnnee = ref(props.annee)
const showNewYearModal = ref(false)
const newYear = ref(new Date().getFullYear() + 1)

const changeYear = () => {
    router.get(route('admin.stats-france.index'), { annee: selectedAnnee.value })
}

const createYear = () => {
    router.post(route('admin.stats-france.create-year'), { annee: newYear.value })
    showNewYearModal.value = false
}

const formatNumber = (num) => {
    if (!num) return '-'
    return new Intl.NumberFormat('fr-FR').format(num)
}
</script>

<template>
    <Head title="Statistiques France - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        📊 Statistiques France
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Source unique de données pour le tableau de bord national
                    </p>
                </div>
                
                <div class="flex items-center gap-4">
                    <select 
                        v-model="selectedAnnee" 
                        @change="changeYear"
                        class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                    >
                        <option v-for="year in anneesDisponibles" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                    
                    <button 
                        @click="showNewYearModal = true"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
                    >
                        + Nouvelle année
                    </button>
                </div>
            </div>

            <!-- Stats overview -->
            <div class="grid grid-cols-3 gap-4 mb-8">
                <Card class="bg-gradient-to-br from-blue-500 to-blue-600 text-white">
                    <div class="text-3xl font-bold">{{ stats.total_annees }}</div>
                    <div class="text-blue-100">Années disponibles</div>
                </Card>
                <Card class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white">
                    <div class="text-3xl font-bold">{{ stats.derniere_annee }}</div>
                    <div class="text-emerald-100">Dernière année</div>
                </Card>
                <Card class="bg-gradient-to-br from-purple-500 to-purple-600 text-white">
                    <div class="text-3xl font-bold">{{ stats.categories }}</div>
                    <div class="text-purple-100">Catégories de données</div>
                </Card>
            </div>

            <!-- Catégories -->
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📋 Catégories de données
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link 
                    v-for="(cat, key) in statsCategories" 
                    :key="key"
                    :href="route(cat.route, { annee: selectedAnnee })"
                    class="block bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition p-6 border border-gray-200 dark:border-gray-700 hover:border-blue-500"
                >
                    <div class="flex items-center gap-4">
                        <div class="text-4xl">{{ cat.icon }}</div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                                {{ cat.label }}
                            </h3>
                            <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
                                <span>{{ cat.count }} entrées</span>
                                <span v-if="cat.lastYear">Dernière : {{ cat.lastYear }}</span>
                            </div>
                        </div>
                        <span class="text-gray-400">→</span>
                    </div>
                </Link>
            </div>

            <!-- Info box -->
            <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">💡</span>
                    <div>
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100">
                            Source unique de vérité
                        </h3>
                        <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                            Toutes les données modifiées ici sont automatiquement reflétées sur :
                        </p>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 mt-2 list-disc list-inside">
                            <li><strong>Statistiques Pays</strong> (/statistiques/france)</li>
                            <li><strong>Statistiques État</strong> (/budget-etat)</li>
                            <li><strong>Dashboard utilisateur</strong> (widgets)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal nouvelle année -->
        <div v-if="showNewYearModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-96 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    Créer une nouvelle année
                </h3>
                <input 
                    v-model="newYear"
                    type="number"
                    min="2000"
                    max="2030"
                    class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 mb-4"
                />
                <div class="flex gap-3 justify-end">
                    <button 
                        @click="showNewYearModal = false"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800"
                    >
                        Annuler
                    </button>
                    <button 
                        @click="createYear"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
                    >
                        Créer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
