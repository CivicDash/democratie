<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Card from '@/Components/Card.vue'
import { ref, watch } from 'vue'

// Simple debounce function
const debounce = (fn, delay) => {
    let timeoutId
    return (...args) => {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => fn(...args), delay)
    }
}

const props = defineProps({
    communes: Array,
    query: String,
    departement: String,
    departements: Array,
    stats: Object,
})

const searchQuery = ref(props.query || '')
const selectedDep = ref(props.departement || '')

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Communes', current: true, icon: '🏘️' },
]

const search = debounce(() => {
    router.get(route('communes.index'), {
        q: searchQuery.value || undefined,
        departement: selectedDep.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}, 300)

watch([searchQuery, selectedDep], search)

const formatNumber = (num) => {
    if (!num) return '-'
    return new Intl.NumberFormat('fr-FR').format(num)
}
</script>

<template>
    <Head title="Communes de France" />
    
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <Breadcrumb :items="breadcrumbs" class="mb-6" />

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    🏘️ Communes de France
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Recherchez une commune pour voir son maire, ses représentants et son budget
                </p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <Card class="bg-gradient-to-br from-blue-500 to-blue-600 text-white">
                    <div class="text-3xl font-bold">{{ formatNumber(stats.total_communes) }}</div>
                    <div class="text-blue-100">Communes en base</div>
                </Card>
                <Card class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white">
                    <div class="text-3xl font-bold">{{ formatNumber(stats.total_population) }}</div>
                    <div class="text-emerald-100">Habitants</div>
                </Card>
            </div>

            <!-- Recherche -->
            <Card class="mb-8">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            🔍 Rechercher une commune
                        </label>
                        <input 
                            v-model="searchQuery"
                            type="text"
                            placeholder="Nom de ville ou code postal (ex: Paris, 75001)"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-lg"
                        />
                    </div>
                    <div class="md:w-64">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            📍 Département
                        </label>
                        <select 
                            v-model="selectedDep"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Tous les départements</option>
                            <option v-for="dep in departements" :key="dep.code" :value="dep.code">
                                {{ dep.code }} - {{ dep.nom }}
                            </option>
                        </select>
                    </div>
                </div>
            </Card>

            <!-- Résultats -->
            <div v-if="communes.length > 0">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ communes.length }} commune(s) trouvée(s)
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link 
                        v-for="commune in communes" 
                        :key="commune.id"
                        :href="commune.url"
                        class="block bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition p-4 border border-gray-200 dark:border-gray-700 hover:border-blue-500"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                                    {{ commune.nom }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ commune.code_postal }} · {{ commune.departement_nom }}
                                </p>
                            </div>
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ commune.code_insee }}
                            </span>
                        </div>
                        
                        <div class="mt-3 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span>👥 {{ commune.population_formate }}</span>
                            <span v-if="commune.region_nom">📍 {{ commune.region_nom }}</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Aucun résultat -->
            <div v-else-if="searchQuery || selectedDep" class="text-center py-12">
                <div class="text-6xl mb-4">🏘️</div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Aucune commune trouvée
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Essayez une autre recherche ou un autre département
                </p>
            </div>

            <!-- Message initial -->
            <div v-else class="text-center py-12">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Recherchez une commune
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Entrez au moins 2 caractères ou sélectionnez un département
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
