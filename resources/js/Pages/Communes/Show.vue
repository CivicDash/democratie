<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Card from '@/Components/Card.vue'

const props = defineProps({
    commune: Object,
    maire: Object,
    senateurs: Array,
    deputes: Array,
    budgets: Array,
    communesVoisines: Array,
    breadcrumbs: Array,
})

const formatNumber = (num) => {
    if (!num) return '-'
    return new Intl.NumberFormat('fr-FR').format(num)
}
</script>

<template>
    <Head :title="commune.nom + ' - Commune'" />
    
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <Breadcrumb :items="breadcrumbs" class="mb-6" />

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 text-white mb-8">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-4xl">🏘️</span>
                            <h1 class="text-4xl font-bold">{{ commune.nom }}</h1>
                        </div>
                        <div class="flex items-center gap-4 text-blue-100">
                            <span>📮 {{ commune.code_postal }}</span>
                            <span>📍 {{ commune.departement_nom }}</span>
                            <span v-if="commune.region_nom">🗺️ {{ commune.region_nom }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold">{{ commune.population_formate }}</div>
                        <div class="text-blue-200 text-sm">Population</div>
                    </div>
                </div>

                <!-- Badges -->
                <div class="flex gap-2 mt-4">
                    <span v-if="commune.est_chef_lieu_departement" class="px-3 py-1 bg-yellow-500 text-yellow-900 rounded-full text-sm font-medium">
                        🏛️ Préfecture
                    </span>
                    <span v-if="commune.est_chef_lieu_region" class="px-3 py-1 bg-purple-500 text-white rounded-full text-sm font-medium">
                        ⭐ Chef-lieu de région
                    </span>
                    <span v-if="commune.zone_montagne" class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        🏔️ Zone montagne
                    </span>
                    <span v-if="commune.zone_rurale" class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        🌾 Zone rurale
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Informations -->
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            📊 Informations
                        </h2>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ commune.code_insee }}</div>
                                <div class="text-sm text-gray-500">Code INSEE</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ commune.superficie || '-' }}</div>
                                <div class="text-sm text-gray-500">km²</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">{{ commune.densite || '-' }}</div>
                                <div class="text-sm text-gray-500">hab/km²</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-lg font-bold text-orange-600">{{ commune.codes_postaux?.join(', ') || commune.code_postal }}</div>
                                <div class="text-sm text-gray-500">Code(s) postal</div>
                            </div>
                        </div>

                        <div v-if="commune.epci_nom" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="text-sm text-blue-600 dark:text-blue-400">Intercommunalité</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ commune.epci_nom }}</div>
                        </div>
                    </Card>

                    <!-- Budgets -->
                    <Card v-if="budgets.length > 0">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            💰 Budget communal
                        </h2>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Année</th>
                                        <th class="px-4 py-2 text-right">Recettes fonct.</th>
                                        <th class="px-4 py-2 text-right">Dépenses fonct.</th>
                                        <th class="px-4 py-2 text-right">Dette</th>
                                        <th class="px-4 py-2 text-right">€/hab</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="budget in budgets" :key="budget.annee">
                                        <td class="px-4 py-2 font-medium">{{ budget.annee }}</td>
                                        <td class="px-4 py-2 text-right text-green-600">{{ budget.recettes_fonctionnement }}</td>
                                        <td class="px-4 py-2 text-right text-blue-600">{{ budget.depenses_fonctionnement }}</td>
                                        <td class="px-4 py-2 text-right text-red-600">{{ budget.dette }}</td>
                                        <td class="px-4 py-2 text-right">{{ budget.euros_par_habitant ? budget.euros_par_habitant + ' €' : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-sm text-gray-500">
                            Source : OFGL (Observatoire des Finances et de la Gestion publique Locales)
                        </div>
                    </Card>

                    <Card v-else class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">📊</span>
                            <div>
                                <h3 class="font-semibold text-amber-900 dark:text-amber-100">
                                    Budget non disponible
                                </h3>
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    Les données budgétaires de cette commune ne sont pas encore importées.
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Colonne latérale -->
                <div class="space-y-8">
                    <!-- Maire -->
                    <Card>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            👔 Maire
                        </h2>
                        
                        <div v-if="maire" class="text-center">
                            <div class="w-20 h-20 mx-auto bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-3xl mb-3">
                                {{ maire.sexe === 'F' ? '👩' : '👨' }}
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                                {{ maire.nom }}
                            </h3>
                            <p v-if="maire.profession" class="text-sm text-gray-500 mb-3">
                                {{ maire.profession }}
                            </p>
                            <Link 
                                :href="maire.url"
                                class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                            >
                                Voir la fiche →
                            </Link>
                        </div>
                        
                        <div v-else class="text-center text-gray-500 py-4">
                            <span class="text-3xl">👤</span>
                            <p class="mt-2">Information non disponible</p>
                        </div>
                    </Card>

                    <!-- Sénateurs -->
                    <Card v-if="senateurs.length > 0">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            🏛️ Sénateurs du département
                        </h2>
                        
                        <div class="space-y-3">
                            <Link 
                                v-for="senateur in senateurs" 
                                :key="senateur.matricule"
                                :href="senateur.url"
                                class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <img 
                                    v-if="senateur.photo_url"
                                    :src="senateur.photo_url" 
                                    :alt="senateur.nom"
                                    class="w-10 h-10 rounded-full object-cover"
                                />
                                <div v-else class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    👤
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">
                                        {{ senateur.nom }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ senateur.groupe }}
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </Card>

                    <!-- Députés -->
                    <Card v-if="deputes.length > 0">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            🏛️ Député(s) de la circonscription
                        </h2>
                        
                        <div class="space-y-3">
                            <Link 
                                v-for="depute in deputes" 
                                :key="depute.uid"
                                :href="depute.url"
                                class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <img 
                                    v-if="depute.photo_url"
                                    :src="depute.photo_url" 
                                    :alt="depute.nom"
                                    class="w-10 h-10 rounded-full object-cover"
                                />
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">
                                        {{ depute.nom }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ depute.groupe }}
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </Card>

                    <!-- Communes voisines -->
                    <Card v-if="communesVoisines.length > 0">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            🏘️ Communes similaires
                        </h2>
                        
                        <div class="space-y-2">
                            <Link 
                                v-for="c in communesVoisines" 
                                :key="c.id"
                                :href="c.url"
                                class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <span class="font-medium text-gray-900 dark:text-white text-sm">
                                    {{ c.nom }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ c.population_formate }}
                                </span>
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
