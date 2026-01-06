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
    co2_emissions_mt: props.data?.co2_emissions_mt || '',
    renewable_energy_percent: props.data?.renewable_energy_percent || '',
    recycling_rate: props.data?.recycling_rate || '',
    protected_areas_percent: props.data?.protected_areas_percent || '',
    air_quality_index: props.data?.air_quality_index || '',
})

watch(selectedAnnee, () => {
    router.get(route('admin.stats-france.environnement'), { annee: selectedAnnee.value })
})

const submit = () => {
    form.put(route('admin.stats-france.environnement.update', selectedAnnee.value))
}
</script>

<template>
    <Head title="Environnement - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">Statistiques France</Link>
                <span>→</span>
                <span class="text-gray-900 dark:text-white">Environnement</span>
            </div>

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🌱 Environnement</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Émissions CO2, énergies renouvelables, recyclage</p>
                </div>
                <select v-model="selectedAnnee" class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                    <option v-for="year in anneesDisponibles" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>

            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Émissions CO2 (Mt)</label>
                            <input type="number" step="0.1" v-model="form.co2_emissions_mt" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Énergies renouvelables (%)</label>
                            <input type="number" step="0.1" v-model="form.renewable_energy_percent" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taux de recyclage (%)</label>
                            <input type="number" step="0.1" v-model="form.recycling_rate" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aires protégées (%)</label>
                            <input type="number" step="0.1" v-model="form.protected_areas_percent" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Indice qualité de l'air</label>
                            <input type="number" v-model="form.air_quality_index" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t dark:border-gray-700">
                        <Link :href="route('admin.stats-france.index')" class="text-gray-600 hover:text-gray-800">← Retour</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            {{ form.processing ? 'Enregistrement...' : '💾 Enregistrer' }}
                        </button>
                    </div>
                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
