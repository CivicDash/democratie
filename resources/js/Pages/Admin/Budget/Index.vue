<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    budgets: Array,
    annee: Number,
    annees: Array,
    stats: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Budget PLF', current: true, icon: '💰' },
];

const selectedAnnee = ref(props.annee);

const changeAnnee = (annee) => {
    selectedAnnee.value = annee;
    router.get(route('admin.budget.index'), { annee }, { preserveState: true });
};

const formatMiliardsEuros = (montant) => {
    if (!montant) return '-';
    return montant.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Md€';
};

const confirmDelete = (budget) => {
    if (confirm(`Supprimer le budget "${budget.nom}" ?`)) {
        router.delete(route('admin.budget.destroy', budget.id));
    }
};
</script>

<template>
    <Head title="Admin - Budget PLF" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-800 to-emerald-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                            <span class="text-4xl">💰</span>
                            Budget de l'État - PLF {{ annee }}
                        </h1>
                        <p class="text-emerald-200 text-lg">
                            Gestion des Projets de Loi de Finances par ministère
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <Link 
                            :href="route('admin.budget.create')"
                            class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 font-semibold rounded-lg hover:bg-emerald-50 transition"
                        >
                            ➕ Ajouter un ministère
                        </Link>
                        <a 
                            :href="route('admin.budget.export', { annee })"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 transition"
                        >
                            📥 Exporter CSV
                        </a>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-white">{{ formatMiliardsEuros(stats.total_budget_general) }}</div>
                        <div class="text-emerald-200 text-sm">Budget général</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-white">{{ formatMiliardsEuros(stats.total_comptes_affectation) }}</div>
                        <div class="text-emerald-200 text-sm">Comptes spéciaux</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-white">{{ formatMiliardsEuros(stats.total_comptes_concours) }}</div>
                        <div class="text-emerald-200 text-sm">Concours financiers</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-2xl font-bold text-white">{{ stats.nb_ministeres }}</div>
                        <div class="text-emerald-200 text-sm">Ministères</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Sélecteur d'année -->
                <Card class="mb-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">Année :</span>
                        <div class="flex gap-2 flex-wrap">
                            <button
                                v-for="a in annees"
                                :key="a"
                                @click="changeAnnee(a)"
                                :class="[
                                    'px-4 py-2 rounded-lg font-medium transition',
                                    a === annee 
                                        ? 'bg-emerald-600 text-white' 
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                                ]"
                            >
                                {{ a }}
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Tableau des budgets -->
                <Card>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Ministère</th>
                                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Budget général</th>
                                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Budgets annexes</th>
                                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Comptes spéciaux</th>
                                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Concours fin.</th>
                                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Total</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="budget in budgets" 
                                    :key="budget.id"
                                    class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
                                >
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div 
                                                class="w-3 h-3 rounded-full" 
                                                :style="{ backgroundColor: budget.couleur }"
                                            ></div>
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ budget.nom }}
                                                </div>
                                                <div v-if="budget.sigle" class="text-xs text-gray-500">
                                                    {{ budget.sigle }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300 font-mono">
                                        {{ formatMiliardsEuros(budget.budget_general) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300 font-mono">
                                        {{ formatMiliardsEuros(budget.budgets_annexes) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300 font-mono">
                                        {{ formatMiliardsEuros(budget.comptes_affectation_speciale) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300 font-mono">
                                        {{ formatMiliardsEuros(budget.comptes_concours_financiers) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                        {{ formatMiliardsEuros(budget.budget_total) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <Link 
                                                :href="route('admin.budget.edit', budget.id)"
                                                class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                                                title="Modifier"
                                            >
                                                ✏️
                                            </Link>
                                            <button 
                                                @click="confirmDelete(budget)"
                                                class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                                title="Supprimer"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                                    <td class="py-3 px-4 text-gray-900 dark:text-gray-100">
                                        TOTAL
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-gray-100 font-mono">
                                        {{ formatMiliardsEuros(stats.total_budget_general) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-gray-100 font-mono">
                                        {{ formatMiliardsEuros(stats.total_budgets_annexes) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-gray-100 font-mono">
                                        {{ formatMiliardsEuros(stats.total_comptes_affectation) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-gray-100 font-mono">
                                        {{ formatMiliardsEuros(stats.total_comptes_concours) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-emerald-600 dark:text-emerald-400 font-mono">
                                        {{ formatMiliardsEuros(stats.total_global) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>

                <!-- Source -->
                <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        📊 Source : Projets de Loi de Finances (PLF) - 
                        <a href="https://www.budget.gouv.fr/" target="_blank" class="text-emerald-600 hover:underline">
                            budget.gouv.fr
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
