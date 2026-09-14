<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';

const props = defineProps({
    depute: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Députés', href: route('admin.elus.deputes.index'), icon: '🔵' },
    { label: props.depute.prenom + ' ' + props.depute.nom, current: true },
];

// Les clés portent désormais le nom des colonnes. Le formulaire envoyait auparavant
// sexe, lieu_naissance, twitter, facebook, wikipedia_resume, photo_url, email,
// site_web, circonscription, groupe_sigle et en_mandat : aucun n'est une colonne
// d'acteurs_an, et les cinq derniers n'existent nulle part — circonscription et
// groupe viennent des mandats, ils se corrigent à l'import.
const form = useForm({
    civilite: props.depute.civilite ?? '',
    prenom: props.depute.prenom ?? '',
    nom: props.depute.nom ?? '',
    date_naissance: props.depute.date_naissance?.split('T')[0] ?? '',
    ville_naissance: props.depute.ville_naissance ?? '',
    profession: props.depute.profession ?? '',
    wikipedia_url: props.depute.wikipedia_url ?? '',
    photo_wikipedia_url: props.depute.photo_wikipedia_url ?? '',
    wikipedia_extract: props.depute.wikipedia_extract ?? '',
    twitter_url: props.depute.twitter_url ?? '',
    facebook_url: props.depute.facebook_url ?? '',
    linkedin_url: props.depute.linkedin_url ?? '',
    instagram_url: props.depute.instagram_url ?? '',
});

const identite = [
    { label: 'Identifiant Assemblée', valeur: props.depute.uid },
    { label: 'Trigramme', valeur: props.depute.trigramme },
    { label: 'Catégorie socio-professionnelle', valeur: props.depute.categorie_socio_pro },
    { label: 'Déclaration HATVP', valeur: props.depute.url_hatvp },
];

const champs = [
    { nom: 'civilite', label: 'Civilité', type: 'text' },
    { nom: 'prenom', label: 'Prénom', type: 'text' },
    { nom: 'nom', label: 'Nom', type: 'text' },
    { nom: 'date_naissance', label: 'Date de naissance', type: 'date' },
    { nom: 'ville_naissance', label: 'Ville de naissance', type: 'text' },
    { nom: 'profession', label: 'Profession', type: 'text' },
    { nom: 'wikipedia_url', label: 'Page Wikipédia', type: 'url' },
    { nom: 'photo_wikipedia_url', label: 'Photographie (URL)', type: 'url' },
    { nom: 'twitter_url', label: 'X / Twitter', type: 'url' },
    { nom: 'facebook_url', label: 'Facebook', type: 'url' },
    { nom: 'linkedin_url', label: 'LinkedIn', type: 'url' },
    { nom: 'instagram_url', label: 'Instagram', type: 'url' },
];

const submit = () => {
    // L'identifiant d'un acteur est son `uid`, pas un `id` auto-incrémenté.
    form.put(route('admin.elus.deputes.update', props.depute.uid), { preserveScroll: true });
};
</script>

<template>
    <Head :title="'Admin - ' + depute.prenom + ' ' + depute.nom" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />

                <div class="flex items-center gap-6">
                    <img v-if="depute.photo_wikipedia_url"
                         :src="depute.photo_wikipedia_url"
                         :alt="depute.prenom + ' ' + depute.nom"
                         class="w-20 h-20 rounded-full object-cover border-4 border-white/20" />
                    <div v-else class="w-20 h-20 rounded-full bg-blue-800 flex items-center justify-center text-3xl"
                         aria-hidden="true">
                        👤
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            {{ depute.prenom }} {{ depute.nom }}
                        </h1>
                        <p class="text-blue-200">Député</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <Card>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Références (lecture seule)
                    </h2>
                    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div v-for="item in identite" :key="item.label">
                            <dt class="text-gray-500 dark:text-gray-400">{{ item.label }}</dt>
                            <dd class="text-gray-900 dark:text-white break-all">{{ item.valeur || '—' }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Circonscription et groupe politique proviennent des mandats : ils se
                        corrigent à l'import, pas ici.
                    </p>
                </Card>

                <Card>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div v-for="champ in champs" :key="champ.nom">
                                <label :for="`depute-${champ.nom}`"
                                       class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ champ.label }}
                                </label>
                                <input :id="`depute-${champ.nom}`"
                                       v-model="form[champ.nom]"
                                       :type="champ.type"
                                       :aria-invalid="Boolean(form.errors[champ.nom])"
                                       class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                       :class="form.errors[champ.nom] ? 'border-red-400 dark:border-red-600' : ''" />
                                <p v-if="form.errors[champ.nom]" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors[champ.nom] }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="depute-extrait"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Extrait biographique
                            </label>
                            <textarea id="depute-extrait"
                                      v-model="form.wikipedia_extract"
                                      rows="5"
                                      class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"></textarea>
                        </div>

                        <FormErrors :errors="form.errors" />

                        <div class="flex justify-between items-center pt-4 border-t dark:border-gray-700">
                            <Link :href="route('admin.elus.deputes.index')"
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
