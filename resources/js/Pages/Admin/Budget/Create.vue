<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    annees: Array,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Budget PLF', href: route('admin.budget.index'), icon: '💰' },
    { label: 'Nouveau', current: true, icon: '➕' },
];

const form = useForm({
    nom: '',
    sigle: '',
    annee: new Date().getFullYear(),
    type_loi: 'plf',
    budget_general: '',
    budgets_annexes: '',
    comptes_affectation_speciale: '',
    comptes_concours_financiers: '',
});

const submit = () => {
    form.post(route('admin.budget.store'));
};
</script>

<template>
    <Head title="Nouveau budget ministériel" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-800 to-emerald-900">
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <h1 class="text-3xl font-bold text-white mb-3 flex items-center gap-4">
                    <span class="text-4xl">➕</span>
                    Nouveau budget ministériel
                </h1>
                <p class="text-emerald-200">Ajouter un ministère au PLF</p>
            </div>
        </section>

        <!-- Contenu -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Card>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Nom du ministère -->
                        <div>
                            <InputLabel for="nom" value="Nom du ministère *" />
                            <TextInput
                                id="nom"
                                v-model="form.nom"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="ex: Armées et anciens combattants"
                            />
                            <InputError :message="form.errors.nom" class="mt-2" />
                        </div>

                        <!-- Sigle -->
                        <div>
                            <InputLabel for="sigle" value="Sigle (optionnel)" />
                            <TextInput
                                id="sigle"
                                v-model="form.sigle"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="ex: MINARM"
                            />
                            <InputError :message="form.errors.sigle" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Année -->
                            <div>
                                <InputLabel for="annee" value="Année *" />
                                <select
                                    id="annee"
                                    v-model="form.annee"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                                >
                                    <option v-for="a in annees" :key="a" :value="a">{{ a }}</option>
                                </select>
                                <InputError :message="form.errors.annee" class="mt-2" />
                            </div>

                            <!-- Type de loi -->
                            <div>
                                <InputLabel for="type_loi" value="Type de loi *" />
                                <select
                                    id="type_loi"
                                    v-model="form.type_loi"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                                >
                                    <option value="plf">PLF (Projet)</option>
                                    <option value="lfi">LFI (Initiale)</option>
                                    <option value="lfr">LFR (Rectificative)</option>
                                </select>
                                <InputError :message="form.errors.type_loi" class="mt-2" />
                            </div>
                        </div>

                        <hr class="border-gray-200 dark:border-gray-700">

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            💰 Montants (en milliards €)
                        </h3>

                        <!-- Budget général -->
                        <div>
                            <InputLabel for="budget_general" value="Budget général" />
                            <TextInput
                                id="budget_general"
                                v-model="form.budget_general"
                                type="number"
                                step="0.000001"
                                min="0"
                                class="mt-1 block w-full"
                                placeholder="ex: 51.483934654"
                            />
                            <InputError :message="form.errors.budget_general" class="mt-2" />
                        </div>

                        <!-- Budgets annexes -->
                        <div>
                            <InputLabel for="budgets_annexes" value="Budgets annexes" />
                            <TextInput
                                id="budgets_annexes"
                                v-model="form.budgets_annexes"
                                type="number"
                                step="0.000001"
                                min="0"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.budgets_annexes" class="mt-2" />
                        </div>

                        <!-- Comptes d'affectation spéciale -->
                        <div>
                            <InputLabel for="comptes_affectation_speciale" value="Comptes d'affectation spéciale" />
                            <TextInput
                                id="comptes_affectation_speciale"
                                v-model="form.comptes_affectation_speciale"
                                type="number"
                                step="0.000001"
                                min="0"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.comptes_affectation_speciale" class="mt-2" />
                        </div>

                        <!-- Comptes de concours financiers -->
                        <div>
                            <InputLabel for="comptes_concours_financiers" value="Comptes de concours financiers" />
                            <TextInput
                                id="comptes_concours_financiers"
                                v-model="form.comptes_concours_financiers"
                                type="number"
                                step="0.000001"
                                min="0"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.comptes_concours_financiers" class="mt-2" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4">
                            <Link 
                                :href="route('admin.budget.index')"
                                class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                            >
                                ← Retour
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                💾 Créer le budget
                            </PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
