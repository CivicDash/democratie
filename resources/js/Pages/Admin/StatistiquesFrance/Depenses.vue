<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import { ref, watch, computed } from 'vue'

const props = defineProps({
    annee: Number,
    anneesDisponibles: Array,
    data: Object,
})

const selectedAnnee = ref(props.annee)

const form = useForm({
    education_billions_euros: props.data?.education_billions_euros || '',
    health_billions_euros: props.data?.health_billions_euros || '',
    defense_billions_euros: props.data?.defense_billions_euros || '',
    social_protection_billions_euros: props.data?.social_protection_billions_euros || '',
    public_order_billions_euros: props.data?.public_order_billions_euros || '',
    general_services_billions_euros: props.data?.general_services_billions_euros || '',
    economic_affairs_billions_euros: props.data?.economic_affairs_billions_euros || '',
    environment_billions_euros: props.data?.environment_billions_euros || '',
    housing_billions_euros: props.data?.housing_billions_euros || '',
    culture_billions_euros: props.data?.culture_billions_euros || '',
    total_spending_billions_euros: props.data?.total_spending_billions_euros || '',
})

watch(selectedAnnee, () => {
    router.get(route('admin.stats-france.depenses'), { annee: selectedAnnee.value })
})

const calculatedTotal = computed(() => {
    const values = [
        form.education_billions_euros,
        form.health_billions_euros,
        form.defense_billions_euros,
        form.social_protection_billions_euros,
        form.public_order_billions_euros,
        form.general_services_billions_euros,
        form.economic_affairs_billions_euros,
        form.environment_billions_euros,
        form.housing_billions_euros,
        form.culture_billions_euros,
    ];
    return values.reduce((sum, val) => sum + (parseFloat(val) || 0), 0).toFixed(1);
})

const useCalculatedTotal = () => {
    form.total_spending_billions_euros = calculatedTotal.value;
}

const submit = () => {
    form.put(route('admin.stats-france.depenses.update', selectedAnnee.value))
}

const formatNumber = (num) => {
    if (!num) return '-'
    return new Intl.NumberFormat('fr-FR').format(num)
}

const depenseFields = [
    { key: 'social_protection_billions_euros', label: 'Protection sociale', icon: '🏥', description: 'Retraites, chômage, aides sociales' },
    { key: 'health_billions_euros', label: 'Santé', icon: '⚕️', description: 'Hôpitaux, assurance maladie' },
    { key: 'education_billions_euros', label: 'Éducation', icon: '📚', description: 'Enseignement, recherche' },
    { key: 'defense_billions_euros', label: 'Défense', icon: '🛡️', description: 'Armée, sécurité nationale' },
    { key: 'public_order_billions_euros', label: 'Ordre public', icon: '👮', description: 'Police, justice, prisons' },
    { key: 'general_services_billions_euros', label: 'Services généraux', icon: '🏛️', description: 'Administration, dette' },
    { key: 'economic_affairs_billions_euros', label: 'Affaires économiques', icon: '💼', description: 'Transports, industrie, agriculture' },
    { key: 'environment_billions_euros', label: 'Environnement', icon: '🌱', description: 'Protection environnement' },
    { key: 'housing_billions_euros', label: 'Logement', icon: '🏠', description: 'Aides au logement, urbanisme' },
    { key: 'culture_billions_euros', label: 'Culture', icon: '🎭', description: 'Loisirs, culture, sport' },
]
</script>

<template>
    <Head title="Dépenses publiques - Admin" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">
                    Statistiques France
                </Link>
                <span>→</span>
                <span class="text-gray-900 dark:text-white">Dépenses publiques</span>
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        💰 Dépenses publiques
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Dépenses consolidées (État + Sécu + Collectivités)
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
                    <!-- Champs par catégorie -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div 
                            v-for="field in depenseFields" 
                            :key="field.key"
                            class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xl">{{ field.icon }}</span>
                                <label class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ field.label }}
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ field.description }}</p>
                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.1"
                                    v-model="form[field.key]"
                                    class="w-full px-3 py-2 pr-16 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="0.0"
                                />
                                <span class="absolute right-3 top-2.5 text-sm text-gray-500">Md€</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block font-medium text-blue-800 dark:text-blue-300 mb-1">
                                    💵 Total des dépenses
                                </label>
                                <p class="text-sm text-blue-600 dark:text-blue-400">
                                    Total calculé: {{ calculatedTotal }} Md€
                                    <button 
                                        type="button" 
                                        @click="useCalculatedTotal"
                                        class="ml-2 text-xs underline hover:no-underline"
                                    >
                                        Utiliser ce total
                                    </button>
                                </p>
                            </div>
                            <div class="relative w-40">
                                <input
                                    type="number"
                                    step="0.1"
                                    v-model="form.total_spending_billions_euros"
                                    class="w-full px-3 py-2 pr-12 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-lg font-bold"
                                    placeholder="0.0"
                                />
                                <span class="absolute right-3 top-3 text-sm text-gray-500">Md€</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between items-center pt-4 border-t dark:border-gray-700">
                        <Link 
                            :href="route('admin.stats-france.index')"
                            class="text-gray-600 dark:text-gray-400 hover:text-gray-800"
                        >
                            ← Retour
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                        >
                            {{ form.processing ? 'Enregistrement...' : '💾 Enregistrer' }}
                        </button>
                    </div>
                </form>
            </Card>

            <!-- Info -->
            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    💡 <strong>Source :</strong> Ces données sont consolidées (État + Sécurité sociale + Collectivités territoriales). 
                    Les montants sont en milliards d'euros.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
