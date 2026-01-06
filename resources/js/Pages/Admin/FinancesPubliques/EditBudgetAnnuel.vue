<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    budget: Object,
    nextYear: Number,
})

const isEdit = !!props.budget

const form = useForm({
    annee: props.budget?.annee ?? props.nextYear ?? new Date().getFullYear(),
    recettes_nettes: props.budget?.recettes_nettes ?? null,
    depenses_nettes: props.budget?.depenses_nettes ?? null,
    deficit: props.budget?.deficit ?? null,
    dette: props.budget?.dette ?? null,
    dette_pib_pct: props.budget?.dette_pib_pct ?? null,
    deficit_pib_pct: props.budget?.deficit_pib_pct ?? null,
})

const submit = () => {
    if (isEdit) {
        form.put(route('admin.finances.budget-annuel.update', props.budget.id))
    } else {
        form.post(route('admin.finances.budget-annuel.store'))
    }
}

const calculatedDeficit = () => {
    if (form.recettes_nettes && form.depenses_nettes) {
        return form.recettes_nettes - form.depenses_nettes
    }
    return null
}
</script>

<template>
    <Head :title="isEdit ? `Modifier Budget ${budget.annee}` : 'Nouveau Budget'" />
    
    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <Link 
                    :href="route('admin.finances.index')"
                    class="text-blue-600 hover:underline text-sm mb-2 inline-block"
                >
                    ← Retour aux finances
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ isEdit ? `Modifier Budget ${budget.annee}` : 'Nouveau Budget Annuel' }}
                </h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    {{ isEdit ? 'Corriger les données du budget' : 'Ajouter un nouveau budget annuel' }}
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-6">
                <!-- Année -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Année *
                    </label>
                    <input 
                        v-model="form.annee"
                        type="number"
                        min="2000"
                        max="2100"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        :disabled="isEdit"
                    />
                    <p v-if="form.errors.annee" class="text-red-500 text-sm mt-1">{{ form.errors.annee }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Recettes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Recettes nettes (Md€)
                        </label>
                        <input 
                            v-model="form.recettes_nettes"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ex: 355.0"
                        />
                        <p class="text-xs text-gray-500 mt-1">En milliards d'euros</p>
                    </div>

                    <!-- Dépenses -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Dépenses nettes (Md€)
                        </label>
                        <input 
                            v-model="form.depenses_nettes"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ex: 512.0"
                        />
                        <p class="text-xs text-gray-500 mt-1">En milliards d'euros</p>
                    </div>
                </div>

                <!-- Déficit calculé -->
                <div v-if="calculatedDeficit() !== null" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-center gap-2">
                        <span class="text-yellow-600">💡</span>
                        <span class="text-sm text-yellow-800 dark:text-yellow-200">
                            Déficit calculé : <strong>{{ calculatedDeficit().toFixed(1) }} Md€</strong>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Déficit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Déficit (Md€)
                        </label>
                        <input 
                            v-model="form.deficit"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ex: -157.0"
                        />
                    </div>

                    <!-- Dette -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Dette totale (Md€)
                        </label>
                        <input 
                            v-model="form.dette"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ex: 3100.0"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Dette/PIB -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Dette / PIB (%)
                        </label>
                        <input 
                            v-model="form.dette_pib_pct"
                            type="number"
                            step="0.1"
                            min="0"
                            max="500"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ex: 113.6"
                        />
                    </div>

                    <!-- Déficit/PIB -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Déficit / PIB (%)
                        </label>
                        <input 
                            v-model="form.deficit_pib_pct"
                            type="number"
                            step="0.1"
                            min="-50"
                            max="50"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ex: -5.6"
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Link 
                        :href="route('admin.finances.index')"
                        class="text-gray-600 hover:underline"
                    >
                        Annuler
                    </Link>
                    
                    <button 
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Enregistrement...' : (isEdit ? 'Mettre à jour' : 'Créer') }}
                    </button>
                </div>
            </form>

            <!-- Sources -->
            <div class="mt-8 bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">📚 Sources recommandées</h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>
                        <a href="https://www.budget.gouv.fr/budget-etat" target="_blank" class="text-blue-600 hover:underline">
                            budget.gouv.fr - Budget de l'État
                        </a>
                    </li>
                    <li>
                        <a href="https://www.economie.gouv.fr/plf" target="_blank" class="text-blue-600 hover:underline">
                            economie.gouv.fr - Projet de Loi de Finances
                        </a>
                    </li>
                    <li>
                        <a href="https://www.insee.fr/fr/statistiques/2549709" target="_blank" class="text-blue-600 hover:underline">
                            INSEE - Comptes nationaux
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
