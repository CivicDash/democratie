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
    hospitals_count: props.data?.hospitals_count || '',
    hospital_beds: props.data?.hospital_beds || '',
    doctors_count: props.data?.doctors_count || '',
    nurses_count: props.data?.nurses_count || '',
    health_spending_billions: props.data?.health_spending_billions || '',
    health_spending_per_capita: props.data?.health_spending_per_capita || '',
})

watch(selectedAnnee, () => {
    router.get(route('admin.stats-france.sante'), { annee: selectedAnnee.value })
})

const submit = () => {
    form.put(route('admin.stats-france.sante.update', selectedAnnee.value))
}
</script>

<template>
    <Head title="Santé - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">Statistiques France</Link>
                <span>→</span>
                <span class="text-gray-900 dark:text-white">Santé</span>
            </div>

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">⚕️ Santé</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Hôpitaux, personnels, dépenses de santé</p>
                </div>
                <select v-model="selectedAnnee" class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                    <option v-for="year in anneesDisponibles" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>

            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre d'hôpitaux</label>
                            <input type="number" v-model="form.hospitals_count" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de lits</label>
                            <input type="number" v-model="form.hospital_beds" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de médecins</label>
                            <input type="number" v-model="form.doctors_count" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre d'infirmiers</label>
                            <input type="number" v-model="form.nurses_count" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dépenses santé (Md€)</label>
                            <input type="number" step="0.1" v-model="form.health_spending_billions" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dépenses/habitant (€)</label>
                            <input type="number" v-model="form.health_spending_per_capita" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
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
