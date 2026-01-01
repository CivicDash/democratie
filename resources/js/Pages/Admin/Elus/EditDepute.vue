<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    depute: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Députés', href: route('admin.elus.deputes.index'), icon: '🔵' },
    { label: props.depute.prenom + ' ' + props.depute.nom, current: true },
];

const form = useForm({
    nom: props.depute.nom || '',
    prenom: props.depute.prenom || '',
    sexe: props.depute.sexe || null,
    date_naissance: props.depute.date_naissance || null,
    lieu_naissance: props.depute.lieu_naissance || '',
    profession: props.depute.profession || '',
    photo_url: props.depute.photo_url || '',
    twitter: props.depute.twitter || '',
    facebook: props.depute.facebook || '',
    email: props.depute.email || '',
    site_web: props.depute.site_web || '',
    wikipedia_url: props.depute.wikipedia_url || '',
    wikipedia_resume: props.depute.wikipedia_resume || '',
    circonscription: props.depute.circonscription || '',
    groupe_sigle: props.depute.groupe_sigle || '',
    en_mandat: props.depute.en_mandat ?? true,
});

const submit = () => {
    form.put(route('admin.elus.deputes.update', props.depute.id));
};
</script>

<template>
    <Head :title="'Admin - ' + depute.prenom + ' ' + depute.nom" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex items-center gap-6">
                    <img 
                        v-if="depute.photo_url"
                        :src="depute.photo_url" 
                        :alt="depute.nom"
                        class="w-20 h-20 rounded-full object-cover border-4 border-white/20"
                    />
                    <div v-else class="w-20 h-20 rounded-full bg-blue-800 flex items-center justify-center text-3xl">
                        👤
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            {{ depute.prenom }} {{ depute.nom }}
                        </h1>
                        <p class="text-blue-200">
                            Député • {{ depute.groupe_sigle || 'Groupe non défini' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <Card class="mb-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">
                            👤 Informations personnelles
                        </h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom</label>
                                <input
                                    v-model="form.prenom"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                <input
                                    v-model="form.nom"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sexe</label>
                                <select
                                    v-model="form.sexe"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                >
                                    <option :value="null">Non précisé</option>
                                    <option value="M">Homme</option>
                                    <option value="F">Femme</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de naissance</label>
                                <input
                                    v-model="form.date_naissance"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lieu de naissance</label>
                                <input
                                    v-model="form.lieu_naissance"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profession</label>
                                <input
                                    v-model="form.profession"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card class="mb-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🏛️ Mandat
                        </h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Circonscription</label>
                                <input
                                    v-model="form.circonscription"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Groupe politique</label>
                                <input
                                    v-model="form.groupe_sigle"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div class="md:col-span-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.en_mandat"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Actuellement en mandat</span>
                                </label>
                            </div>
                        </div>
                    </Card>

                    <Card class="mb-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🔗 Liens et contacts
                        </h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📷 URL de la photo</label>
                                <input
                                    v-model="form.photo_url"
                                    type="url"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📧 Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🐦 Twitter</label>
                                <input
                                    v-model="form.twitter"
                                    type="text"
                                    placeholder="@username"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📘 Facebook</label>
                                <input
                                    v-model="form.facebook"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🌐 Site web</label>
                                <input
                                    v-model="form.site_web"
                                    type="url"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📚 Wikipedia</label>
                                <input
                                    v-model="form.wikipedia_url"
                                    type="url"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card class="mb-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📝 Biographie Wikipedia
                        </h2>
                        <textarea
                            v-model="form.wikipedia_resume"
                            rows="6"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                            placeholder="Résumé biographique..."
                        ></textarea>
                    </Card>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <Link
                            :href="route('admin.elus.deputes.index')"
                            class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
                        >
                            ← Retour à la liste
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                        >
                            <span v-if="form.processing">Enregistrement...</span>
                            <span v-else>✓ Enregistrer les modifications</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
