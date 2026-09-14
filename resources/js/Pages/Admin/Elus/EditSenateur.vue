<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';

const props = defineProps({
    senateur: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Sénateurs', href: route('admin.elus.senateurs.index'), icon: '🔴' },
    { label: props.senateur.nom, current: true },
];

// `senateurs` est une vue SQL non modifiable : seul l'enrichissement Wikipédia,
// qui vit dans sa propre table, est éditable ici.
const form = useForm({
    wikipedia_url: props.senateur.wikipedia_url ?? '',
    photo_wikipedia_url: props.senateur.wikipedia_photo ?? props.senateur.photo_wikipedia_url ?? '',
    wikipedia_extract: props.senateur.wikipedia_extract ?? '',
});

const identite = [
    { label: 'Civilité', valeur: props.senateur.civilite },
    { label: 'Prénom', valeur: props.senateur.prenom },
    { label: 'Nom', valeur: props.senateur.nom },
    { label: 'Date de naissance', valeur: props.senateur.date_naissance?.split('T')[0] },
    { label: 'Circonscription', valeur: props.senateur.circonscription },
    { label: 'Groupe politique', valeur: props.senateur.groupe_politique },
    { label: 'Profession', valeur: props.senateur.profession },
    { label: 'Matricule', valeur: props.senateur.matricule },
];

const submit = () => {
    form.put(route('admin.elus.senateurs.update', props.senateur.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="'Admin - ' + senateur.prenom + ' ' + senateur.nom" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-red-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                <div class="flex items-center gap-4">
                    <img v-if="senateur.wikipedia_photo || senateur.photo_wikipedia_url"
                         :src="senateur.wikipedia_photo || senateur.photo_wikipedia_url"
                         :alt="senateur.prenom + ' ' + senateur.nom"
                         class="w-20 h-20 rounded-full object-cover border-4 border-white" />
                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ senateur.prenom }} {{ senateur.nom }}</h1>
                        <p class="text-red-200">{{ senateur.circonscription }}</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <div class="rounded-lg border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <p class="text-amber-900 dark:text-amber-100 text-sm">
                        <strong>L'identité vient de l'import Sénat</strong> et ne se corrige pas ici :
                        la table des sénateurs est une vue construite à partir des données officielles,
                        que la base refuse de modifier. Une erreur sur le nom, le groupe ou la
                        circonscription se corrige à la source, puis se propage à la prochaine
                        synchronisation.
                    </p>
                    <p class="text-amber-800 dark:text-amber-200 text-xs mt-2">
                        Auparavant ce formulaire acceptait ces champs et affichait « Sénateur mis à
                        jour » — sans rien enregistrer.
                    </p>
                </div>

                <Card>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Données officielles (lecture seule)
                    </h2>
                    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div v-for="item in identite" :key="item.label">
                            <dt class="text-gray-500 dark:text-gray-400">{{ item.label }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ item.valeur || '—' }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                        Enrichissement Wikipédia
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Ces trois champs sont les seuls réellement modifiables depuis cet écran.
                    </p>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label for="senateur-wikipedia-url"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Page Wikipédia
                            </label>
                            <input id="senateur-wikipedia-url"
                                   v-model="form.wikipedia_url"
                                   type="url"
                                   placeholder="https://fr.wikipedia.org/wiki/…"
                                   class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>

                        <div>
                            <label for="senateur-photo-url"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Photographie (URL)
                            </label>
                            <input id="senateur-photo-url"
                                   v-model="form.photo_wikipedia_url"
                                   type="url"
                                   class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
                        </div>

                        <div>
                            <label for="senateur-extrait"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Extrait biographique
                            </label>
                            <textarea id="senateur-extrait"
                                      v-model="form.wikipedia_extract"
                                      rows="5"
                                      class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"></textarea>
                        </div>

                        <FormErrors :errors="form.errors" />

                        <div class="flex justify-between items-center pt-4 border-t dark:border-gray-700">
                            <Link :href="route('admin.elus.senateurs.index')"
                                  class="text-gray-600 hover:text-gray-800 dark:text-gray-300">
                                ← Retour
                            </Link>
                            <button type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
