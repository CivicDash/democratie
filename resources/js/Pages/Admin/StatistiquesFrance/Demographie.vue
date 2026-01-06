<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import { ref, watch } from 'vue'

const props = defineProps({
    annee: Number,
    anneesDisponibles: Array,
    data: Object,
})

const selectedAnnee = ref(props.annee)

const form = useForm({
    population_total: props.data?.population_total || '',
    birth_rate: props.data?.birth_rate || '',
    death_rate: props.data?.death_rate || '',
    life_expectancy_male: props.data?.life_expectancy_male || '',
    life_expectancy_female: props.data?.life_expectancy_female || '',
    median_salary_euros: props.data?.median_salary_euros || '',
})

watch(selectedAnnee, () => {
    router.get(route('admin.stats-france.demographie'), { annee: selectedAnnee.value })
})

const submit = () => {
    form.put(route('admin.stats-france.demographie.update', selectedAnnee.value))
}

const formatNumber = (num) => {
    if (!num) return '-'
    return new Intl.NumberFormat('fr-FR').format(num)
}
</script>

<template>
    <Head title="Démographie - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">
                    Statistiques France
                </Link>
                <span>→</span>
                <span class="text-gray-900 dark:text-white">Démographie</span>
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        👥 Démographie
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Population, naissances, décès, espérance de vie
                    </p>
                </div>
                
                <select 
                    v-model="selectedAnnee"
                    class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                >
                    <option v-for="year in anneesDisponibles" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>

            <!-- Formulaire -->
            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Population totale
                            </label>
                            <input 
                                v-model="form.population_total"
                                type="number"
                                class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: 67800000"
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                Habitants au 1er janvier
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Salaire médian (€)
                            </label>
                            <input 
                                v-model="form.median_salary_euros"
                                type="number"
                                step="0.01"
                                class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: 2200"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Taux de natalité (‰)
                            </label>
                            <input 
                                v-model="form.birth_rate"
                                type="number"
                                step="0.01"
                                class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: 10.5"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Taux de mortalité (‰)
                            </label>
                            <input 
                                v-model="form.death_rate"
                                type="number"
                                step="0.01"
                                class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: 9.8"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Espérance de vie (Hommes)
                            </label>
                            <input 
                                v-model="form.life_expectancy_male"
                                type="number"
                                step="0.1"
                                class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: 79.3"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Espérance de vie (Femmes)
                            </label>
                            <input 
                                v-model="form.life_expectancy_female"
                                type="number"
                                step="0.1"
                                class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: 85.2"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t">
                        <Link 
                            :href="route('admin.stats-france.index')"
                            class="text-gray-600 hover:text-gray-800"
                        >
                            ← Retour
                        </Link>
                        
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </Card>

            <!-- Source -->
            <div class="mt-6 text-sm text-gray-500">
                <p>📊 Source : INSEE, Eurostat</p>
                <p>🔗 <a href="https://www.insee.fr/fr/statistiques" target="_blank" class="text-blue-600 hover:underline">
                    insee.fr/statistiques
                </a></p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
