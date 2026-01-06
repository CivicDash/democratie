<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    recette: Object,
    nextYear: Number,
})

const isEdit = !!props.recette

const form = useForm({
    year: props.recette?.year ?? props.nextYear ?? new Date().getFullYear(),
    total_billions_euros: props.recette?.total_billions_euros ?? null,
    tva_billions_euros: props.recette?.tva_billions_euros ?? null,
    income_tax_billions_euros: props.recette?.income_tax_billions_euros ?? null,
    corporate_tax_billions_euros: props.recette?.corporate_tax_billions_euros ?? null,
    property_tax_billions_euros: props.recette?.property_tax_billions_euros ?? null,
    housing_tax_billions_euros: props.recette?.housing_tax_billions_euros ?? null,
    fuel_tax_billions_euros: props.recette?.fuel_tax_billions_euros ?? null,
    social_contributions_billions_euros: props.recette?.social_contributions_billions_euros ?? null,
    other_taxes_billions_euros: props.recette?.other_taxes_billions_euros ?? null,
})

const submit = () => {
    if (isEdit) {
        form.put(route('admin.finances.recettes.update', props.recette.id))
    } else {
        form.post(route('admin.finances.recettes.store'))
    }
}

const calculatedTotal = () => {
    const fields = [
        'tva_billions_euros',
        'income_tax_billions_euros',
        'corporate_tax_billions_euros',
        'property_tax_billions_euros',
        'housing_tax_billions_euros',
        'fuel_tax_billions_euros',
        'social_contributions_billions_euros',
        'other_taxes_billions_euros',
    ]
    
    const sum = fields.reduce((acc, field) => acc + (parseFloat(form[field]) || 0), 0)
    return sum > 0 ? sum : null
}

const taxeFields = [
    { key: 'tva_billions_euros', label: 'TVA', description: 'Taxe sur la valeur ajoutée' },
    { key: 'income_tax_billions_euros', label: 'Impôt sur le revenu', description: 'IR des ménages' },
    { key: 'corporate_tax_billions_euros', label: 'Impôt sur les sociétés', description: 'IS des entreprises' },
    { key: 'social_contributions_billions_euros', label: 'Cotisations sociales', description: 'CSG, CRDS, cotisations retraite...' },
    { key: 'property_tax_billions_euros', label: 'Taxe foncière', description: 'Impôts sur les propriétés' },
    { key: 'housing_tax_billions_euros', label: 'Taxe d\'habitation', description: 'Supprimée progressivement' },
    { key: 'fuel_tax_billions_euros', label: 'TICPE', description: 'Taxe sur les carburants' },
    { key: 'other_taxes_billions_euros', label: 'Autres taxes', description: 'Droits de succession, ISF/IFI...' },
]
</script>

<template>
    <Head :title="isEdit ? `Modifier Recettes ${recette.year}` : 'Nouvelles Recettes'" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <Link 
                    :href="route('admin.finances.index')"
                    class="text-blue-600 hover:underline text-sm mb-2 inline-block"
                >
                    ← Retour aux finances
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ isEdit ? `Modifier Recettes ${recette.year}` : 'Nouvelles Recettes Consolidées' }}
                </h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    Recettes fiscales de l'ensemble des administrations publiques
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-6">
                <!-- Année -->
                <div class="max-w-xs">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Année *
                    </label>
                    <input 
                        v-model="form.year"
                        type="number"
                        min="2000"
                        max="2100"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        :disabled="isEdit"
                    />
                    <p v-if="form.errors.year" class="text-red-500 text-sm mt-1">{{ form.errors.year }}</p>
                </div>

                <!-- Total -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">
                        Total des recettes (Md€)
                    </label>
                    <input 
                        v-model="form.total_billions_euros"
                        type="number"
                        step="0.1"
                        class="w-full px-4 py-2 border border-blue-300 dark:border-blue-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-lg font-semibold"
                        placeholder="ex: 1501.6"
                    />
                    <p v-if="calculatedTotal()" class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                        💡 Total calculé des composantes : {{ calculatedTotal().toFixed(1) }} Md€
                    </p>
                </div>

                <!-- Taxes détaillées -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Détail par type de recette
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="field in taxeFields" :key="field.key">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ field.label }}
                            </label>
                            <input 
                                v-model="form[field.key]"
                                type="number"
                                step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                :placeholder="field.description"
                            />
                        </div>
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

            <!-- Info -->
            <div class="mt-8 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
                <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                    ⚠️ Comprendre les périmètres
                </h3>
                <div class="text-sm text-yellow-700 dark:text-yellow-300 space-y-2">
                    <p>
                        <strong>Budget de l'État (~500 Md€)</strong> : Uniquement le gouvernement central (PLF)
                    </p>
                    <p>
                        <strong>Recettes consolidées (~1500 Md€)</strong> : État + Sécu + Collectivités
                    </p>
                    <p>
                        Les cotisations sociales représentent la majeure partie de la différence.
                    </p>
                </div>
            </div>

            <!-- Sources -->
            <div class="mt-4 bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">📚 Sources recommandées</h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>
                        <a href="https://www.insee.fr/fr/statistiques/2381418" target="_blank" class="text-blue-600 hover:underline">
                            INSEE - Recettes des administrations publiques
                        </a>
                    </li>
                    <li>
                        <a href="https://www.securite-sociale.fr/les-comptes-de-la-securite-sociale" target="_blank" class="text-blue-600 hover:underline">
                            Sécurité Sociale - Comptes annuels
                        </a>
                    </li>
                    <li>
                        <a href="https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000053226384" target="_blank" class="text-blue-600 hover:underline">
                            PLFSS 2026 - Legifrance
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
