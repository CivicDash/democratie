<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Gouvernements', href: route('admin.gouvernement.index'), icon: '🏛️' },
    { label: 'Nouveau', current: true, icon: '➕' },
];

const form = useForm({
    nom: '',
    premier_ministre: '',
    president: 'Emmanuel Macron',
    date_debut: new Date().toISOString().split('T')[0],
    date_fin: null,
    actif: true,
});

const submit = () => {
    form.post(route('admin.gouvernement.store'));
};
</script>

<template>
    <Head title="Admin - Nouveau Gouvernement" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    ➕ Nouveau Gouvernement
                </h1>
                <p class="text-blue-200 mt-2">
                    Créez un nouveau gouvernement et ajoutez ses membres
                </p>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <Card>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Nom du gouvernement -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nom du gouvernement *
                            </label>
                            <input
                                v-model="form.nom"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                                placeholder="Ex: Gouvernement Lecornu"
                                required
                            />
                            <p v-if="form.errors.nom" class="mt-1 text-sm text-red-600">{{ form.errors.nom }}</p>
                        </div>

                        <!-- Premier ministre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Premier ministre *
                            </label>
                            <input
                                v-model="form.premier_ministre"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                                placeholder="Ex: Sébastien Lecornu"
                                required
                            />
                            <p v-if="form.errors.premier_ministre" class="mt-1 text-sm text-red-600">{{ form.errors.premier_ministre }}</p>
                        </div>

                        <!-- Président -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Président de la République *
                            </label>
                            <input
                                v-model="form.president"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                                placeholder="Ex: Emmanuel Macron"
                                required
                            />
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Date de début *
                                </label>
                                <input
                                    v-model="form.date_debut"
                                    type="date"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Date de fin (optionnel)
                                </label>
                                <input
                                    v-model="form.date_fin"
                                    type="date"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <!-- Actif -->
                        <div class="flex items-center gap-3">
                            <input
                                v-model="form.actif"
                                type="checkbox"
                                id="actif"
                                class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500"
                            />
                            <label for="actif" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Gouvernement actif (désactive les autres)
                            </label>
                        </div>

                        <!-- Boutons -->
                        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link
                                :href="route('admin.gouvernement.index')"
                                class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 transition disabled:opacity-50"
                            >
                                <span v-if="form.processing">Création...</span>
                                <span v-else>✓ Créer le gouvernement</span>
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
