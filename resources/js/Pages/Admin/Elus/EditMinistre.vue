<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});

const props = defineProps({
    personne: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Ministres', href: route('admin.elus.ministres.index'), icon: '👔' },
    { label: props.personne.nom, current: true },
];

const partis = [
    'Renaissance', 'LR', 'PS', 'MoDem', 'Horizons', 'EELV', 'PCF', 'RN', 'LFI', 'UDI', 'DVD', 'DVG', 'Sans étiquette'
];

const form = useForm({
    nom: props.personne.nom,
    prenom: props.personne.prenom,
    civilite: props.personne.civilite,
    date_naissance: props.personne.date_naissance?.split('T')[0] || '',
    profession: props.personne.profession || '',
    parti_politique: props.personne.parti_politique || '',
    photo_url: props.personne.photo_url || '',
    wikipedia_url: props.personne.wikipedia_url || '',
});

const submit = () => {
    form.put(route('admin.elus.ministres.update', props.personne.id));
};
</script>

<template>
    <Head :title="'Admin - ' + personne.prenom + ' ' + personne.nom" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                <div class="flex items-center gap-4">
                    <img 
                        v-if="personne.photo_url"
                        :src="personne.photo_url"
                        :alt="personne.nom"
                        class="w-20 h-20 rounded-full object-cover border-4 border-white"
                    />
                    <div v-else class="w-20 h-20 rounded-full bg-purple-100 flex items-center justify-center text-4xl border-4 border-white">
                        {{ personne.civilite === 'Mme' ? '👩' : '👨' }}
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ personne.prenom }} {{ personne.nom }}</h1>
                        <p class="text-purple-200">
                            {{ personne.parti_politique || 'Parti non renseigné' }}
                            <span v-if="personne.postes?.length" class="ml-2">
                                • {{ personne.postes.length }} poste(s)
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Message de succès -->
                <div 
                    v-if="flash.success" 
                    class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-300 dark:border-emerald-700 rounded-lg p-4 flex items-center gap-3"
                >
                    <span class="text-2xl">✅</span>
                    <p class="text-emerald-700 dark:text-emerald-300 font-medium">{{ flash.success }}</p>
                </div>

                <Card class="mb-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">📝 Informations personnelles</h2>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom *</label>
                                <input v-model="form.prenom" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                                <input v-model="form.nom" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Civilité</label>
                                <select v-model="form.civilite" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                    <option :value="null">-- Non spécifié --</option>
                                    <option value="M.">M.</option>
                                    <option value="Mme">Mme</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de naissance</label>
                                <input v-model="form.date_naissance" type="date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parti politique</label>
                                <select v-model="form.parti_politique" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                    <option value="">-- Aucun --</option>
                                    <option v-for="p in partis" :key="p" :value="p">{{ p }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profession</label>
                                <input v-model="form.profession" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL de la photo</label>
                            <input v-model="form.photo_url" type="url" placeholder="https://..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                        </div>

                        <!-- Aperçu photo -->
                        <div v-if="form.photo_url" class="flex items-center gap-4">
                            <img 
                                :src="form.photo_url" 
                                alt="Aperçu"
                                class="w-20 h-20 rounded-full object-cover border-2 border-gray-300"
                            />
                            <p class="text-sm text-gray-500">Aperçu de la photo</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL Wikipedia</label>
                            <input v-model="form.wikipedia_url" type="url" placeholder="https://fr.wikipedia.org/wiki/..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" />
                        </div>

                        <!-- Erreurs -->
                        <div v-if="form.errors && Object.keys(form.errors).length > 0" class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                            <p v-for="(error, key) in form.errors" :key="key" class="text-red-600 dark:text-red-400 text-sm">
                                {{ error }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('admin.elus.ministres.index')"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
                            >
                                💾 Enregistrer
                            </button>
                        </div>
                    </form>
                </Card>

                <!-- Historique des postes -->
                <Card v-if="personne.postes?.length">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">📋 Historique des postes</h2>
                    <div class="space-y-3">
                        <div 
                            v-for="poste in personne.postes" 
                            :key="poste.id"
                            :class="[
                                'p-3 rounded-lg border',
                                poste.date_fin ? 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' : 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700'
                            ]"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ poste.fonction }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ poste.gouvernement?.nom_complet || 'Gouvernement inconnu' }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        📅 {{ poste.date_debut?.split('T')[0] }}
                                        <span v-if="poste.date_fin"> → {{ poste.date_fin?.split('T')[0] }}</span>
                                        <span v-else class="text-emerald-600"> → En cours</span>
                                    </p>
                                </div>
                                <span 
                                    v-if="!poste.date_fin"
                                    class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400 text-xs rounded-full"
                                >
                                    Actif
                                </span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
