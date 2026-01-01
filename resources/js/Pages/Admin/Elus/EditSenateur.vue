<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    senateur: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Sénateurs', href: route('admin.elus.senateurs.index'), icon: '🔴' },
    { label: props.senateur.nom, current: true },
];

const form = useForm({
    nom: props.senateur.nom,
    prenom: props.senateur.prenom,
    civilite: props.senateur.civilite,
    date_naissance: props.senateur.date_naissance?.split('T')[0],
    profession: props.senateur.profession,
    email: props.senateur.email,
    wikipedia_url: props.senateur.wikipedia_url,
    circonscription: props.senateur.circonscription,
    groupe_politique_code: props.senateur.groupe_politique_code,
});

const submit = () => {
    form.put(route('admin.elus.senateurs.update', props.senateur.id));
};
</script>

<template>
    <Head :title="'Admin - ' + senateur.prenom + ' ' + senateur.nom" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-red-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                <div class="flex items-center gap-4">
                    <img 
                        v-if="senateur.photo_wikipedia_url"
                        :src="senateur.photo_wikipedia_url"
                        class="w-20 h-20 rounded-full object-cover border-4 border-white"
                    />
                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ senateur.prenom }} {{ senateur.nom }}</h1>
                        <p class="text-red-200">{{ senateur.circonscription }}</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <Card>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom</label>
                                <input v-model="form.prenom" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                <input v-model="form.nom" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Civilité</label>
                                <select v-model="form.civilite" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                    <option value="M.">M.</option>
                                    <option value="Mme">Mme</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de naissance</label>
                                <input v-model="form.date_naissance" type="date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profession</label>
                            <input v-model="form.profession" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wikipedia</label>
                            <input v-model="form.wikipedia_url" type="url" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('admin.elus.senateurs.index')"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
