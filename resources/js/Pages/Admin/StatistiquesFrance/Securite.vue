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
    total_crimes: props.data?.total_crimes || '',
    violent_crimes: props.data?.violent_crimes || '',
    property_crimes: props.data?.property_crimes || '',
    police_count: props.data?.police_count || '',
    gendarmes_count: props.data?.gendarmes_count || '',
    prison_population: props.data?.prison_population || '',
    crime_resolution_rate: props.data?.crime_resolution_rate || '',
})

watch(selectedAnnee, () => {
    router.get(route('admin.stats-france.securite'), { annee: selectedAnnee.value })
})

const submit = () => {
    form.put(route('admin.stats-france.securite.update', selectedAnnee.value))
}
</script>

<template>
    <Head title="Sécurité - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">Statistiques France</Link>
                <span>→</span>
                <span class="text-gray-900 dark:text-white">Sécurité</span>
            </div>

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">👮 Sécurité</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Criminalité, forces de l'ordre, justice</p>
                </div>
                <select v-model="selectedAnnee" class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                    <option v-for="year in anneesDisponibles" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>

            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total crimes/délits</label>
                            <input type="number" v-model="form.total_crimes" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Crimes violents</label>
                            <input type="number" v-model="form.violent_crimes" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Atteintes aux biens</label>
                            <input type="number" v-model="form.property_crimes" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Effectifs police</label>
                            <input type="number" v-model="form.police_count" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Effectifs gendarmerie</label>
                            <input type="number" v-model="form.gendarmes_count" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Population carcérale</label>
                            <input type="number" v-model="form.prison_population" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taux d'élucidation (%)</label>
                            <input type="number" step="0.1" v-model="form.crime_resolution_rate" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
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
