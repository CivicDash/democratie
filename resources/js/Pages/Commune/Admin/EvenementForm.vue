<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    code_insee: String,
    categories: Object,
    evenement: Object,
});

const isEdit = !!props.evenement;

const form = useForm({
    titre: props.evenement?.titre || '',
    description: props.evenement?.description || '',
    categorie: props.evenement?.categorie || 'reunion_publique',
    lieu_nom: props.evenement?.lieu_nom || '',
    lieu_adresse: props.evenement?.lieu_adresse || '',
    date_debut: props.evenement?.date_debut ? props.evenement.date_debut.slice(0, 16) : '',
    date_fin: props.evenement?.date_fin ? props.evenement.date_fin.slice(0, 16) : '',
    inscription_requise: props.evenement?.inscription_requise || false,
    places_max: props.evenement?.places_max || '',
    publie: props.evenement?.publie || false,
    image: null,
});

const submit = () => {
    if (isEdit) {
        form.post(route('commune.admin.evenements.update', [props.code_insee, props.evenement.slug]), {
            _method: 'put',
            forceFormData: true,
        });
    } else {
        form.post(route('commune.admin.evenements.store', props.code_insee), {
            forceFormData: true,
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <div class="mb-6">
                <Link :href="route('commune.admin.evenements', code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    &larr; Retour aux evenements
                </Link>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">
                    {{ isEdit ? 'Modifier l\'evenement' : 'Nouvel evenement' }}
                </h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Informations generales -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 space-y-4">
                    <h2 class="font-bold text-slate-900 dark:text-white">Informations generales</h2>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre *</label>
                        <input
                            v-model="form.titre"
                            type="text"
                            required
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-lg font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Nom de l'evenement"
                        />
                        <p v-if="form.errors.titre" class="text-sm text-red-500 mt-1">{{ form.errors.titre }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categorie</label>
                            <select
                                v-model="form.categorie"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Image</label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="form.image = $event.target.files[0]"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description *</label>
                        <textarea
                            v-model="form.description"
                            rows="8"
                            required
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            placeholder="Decrivez l'evenement..."
                        />
                        <p v-if="form.errors.description" class="text-sm text-red-500 mt-1">{{ form.errors.description }}</p>
                    </div>
                </div>

                <!-- Lieu & date -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 space-y-4">
                    <h2 class="font-bold text-slate-900 dark:text-white">Lieu & horaires</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lieu</label>
                            <input
                                v-model="form.lieu_nom"
                                type="text"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="Ex: Salle des fetes"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse</label>
                            <input
                                v-model="form.lieu_adresse"
                                type="text"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="12 rue de la Mairie, 13001 Marseille"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date et heure de debut *</label>
                            <input
                                v-model="form.date_debut"
                                type="datetime-local"
                                required
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            />
                            <p v-if="form.errors.date_debut" class="text-sm text-red-500 mt-1">{{ form.errors.date_debut }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date et heure de fin</label>
                            <input
                                v-model="form.date_fin"
                                type="datetime-local"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Inscription -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 space-y-4">
                    <h2 class="font-bold text-slate-900 dark:text-white">Inscription & publication</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.inscription_requise" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">Inscription obligatoire</span>
                            </label>
                        </div>
                        <div v-if="form.inscription_requise">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre de places</label>
                            <input
                                v-model.number="form.places_max"
                                type="number"
                                min="1"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="Illimite si vide"
                            />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.publie" class="rounded border-slate-300 text-green-600 focus:ring-green-500" />
                        <span class="text-sm text-slate-700 dark:text-slate-300">Publier immediatement</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <Link :href="route('commune.admin.evenements', code_insee)" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Enregistrement...' : (isEdit ? 'Mettre a jour' : 'Creer l\'evenement') }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
