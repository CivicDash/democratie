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
    gdp_billions_euros: props.data?.gdp_billions_euros || '',
    gdp_growth_percent: props.data?.gdp_growth_percent || '',
    unemployment_rate: props.data?.unemployment_rate || '',
    inflation_rate: props.data?.inflation_rate || '',
    public_debt_billions_euros: props.data?.public_debt_billions_euros || '',
    public_debt_percent_gdp: props.data?.public_debt_percent_gdp || '',
})

watch(selectedAnnee, () => {
    router.get(route('admin.stats-france.economie'), { annee: selectedAnnee.value })
})

const submit = () => {
    form.put(route('admin.stats-france.economie.update', selectedAnnee.value))
}
</script>

<template>
    <Head title="Économie - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">Statistiques France</Link>
                <span>→</span>
                <span class="text-gray-900 dark:text-white">Économie</span>
            </div>

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📈 Économie</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">PIB, croissance, chômage, inflation</p>
                </div>
                <select v-model="selectedAnnee" class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                    <option v-for="year in anneesDisponibles" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>

            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIB (Md€)</label>
                            <input type="number" step="0.1" v-model="form.gdp_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Croissance (%)</label>
                            <input type="number" step="0.1" v-model="form.gdp_growth_percent" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taux de chômage (%)</label>
                            <input type="number" step="0.1" v-model="form.unemployment_rate" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inflation (%)</label>
                            <input type="number" step="0.1" v-model="form.inflation_rate" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dette publique (Md€)</label>
                            <input type="number" step="0.1" v-model="form.public_debt_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dette (% PIB)</label>
                            <input type="number" step="0.1" v-model="form.public_debt_percent_gdp" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
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
