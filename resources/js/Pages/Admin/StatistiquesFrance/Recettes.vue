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
    income_tax_billions_euros: props.data?.income_tax_billions_euros || '',
    corporate_tax_billions_euros: props.data?.corporate_tax_billions_euros || '',
    vat_billions_euros: props.data?.vat_billions_euros || '',
    social_contributions_billions_euros: props.data?.social_contributions_billions_euros || '',
    other_taxes_billions_euros: props.data?.other_taxes_billions_euros || '',
    total_revenue_billions_euros: props.data?.total_revenue_billions_euros || '',
})

watch(selectedAnnee, () => {
    router.get(route('admin.stats-france.recettes'), { annee: selectedAnnee.value })
})

const submit = () => {
    form.put(route('admin.stats-france.recettes.update', selectedAnnee.value))
}
</script>

<template>
    <Head title="Recettes - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">Statistiques France</Link>
                <span>→</span>
                <span class="text-gray-900 dark:text-white">Recettes</span>
            </div>

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">💰 Recettes publiques</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Impôts, taxes, cotisations sociales</p>
                </div>
                <select v-model="selectedAnnee" class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                    <option v-for="year in anneesDisponibles" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>

            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Impôt sur le revenu (Md€)</label>
                            <input type="number" step="0.1" v-model="form.income_tax_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Impôt sur les sociétés (Md€)</label>
                            <input type="number" step="0.1" v-model="form.corporate_tax_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">TVA (Md€)</label>
                            <input type="number" step="0.1" v-model="form.vat_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cotisations sociales (Md€)</label>
                            <input type="number" step="0.1" v-model="form.social_contributions_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Autres taxes (Md€)</label>
                            <input type="number" step="0.1" v-model="form.other_taxes_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total recettes (Md€)</label>
                            <input type="number" step="0.1" v-model="form.total_revenue_billions_euros" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 font-bold" />
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
