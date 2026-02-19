<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    nuances_politiques: Array,
});

// Recherche de commune
const communeSearch = ref('');
const communeResults = ref([]);
const selectedCommune = ref(null);
const isSearching = ref(false);

// Debounce search
let searchTimeout = null;
watch(communeSearch, (newVal) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (newVal.length < 2) {
        communeResults.value = [];
        return;
    }
    
    isSearching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/api/communes/search?q=${encodeURIComponent(newVal)}`);
            const data = await response.json();
            communeResults.value = (Array.isArray(data) ? data : (data.data || [])).slice(0, 10);
        } catch (e) {
            communeResults.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

const selectCommune = (commune) => {
    selectedCommune.value = commune;
    form.commune_code_insee = commune.code_insee;
    form.commune_nom = commune.nom;
    form.departement_code = commune.departement_code || commune.code_insee?.substring(0, 2) || '';
    communeSearch.value = `${commune.nom} (${form.departement_code})`;
    communeResults.value = [];
};

const form = useForm({
    commune_code_insee: '',
    commune_nom: '',
    departement_code: '',
    nom_liste: '',
    nuance_politique: '',
    parti_principal: '',
    slogan: '',
    description: '',
    couleur_principale: '#6366F1',
    email_contact: '',
    telephone_contact: '',
    site_web: '',
    facebook_url: '',
    twitter_url: '',
    instagram_url: '',
    youtube_url: '',
    tiktok_url: '',
    resume_programme: '',
});

const submit = () => {
    form.post(route('elections.municipales.espace-candidat.store-liste'));
};
</script>

<template>
    <Head title="Créer une liste - Élections Municipales" />

    <AuthenticatedLayout>
        <div class="bg-gradient-to-r from-indigo-600 to-fuchsia-600">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Link
                    :href="route('elections.municipales.espace-candidat.index')"
                    class="text-indigo-200 hover:text-white text-sm mb-2 inline-block"
                >
                    ← Retour à l'espace candidat
                </Link>
                <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                    ➕ Créer une nouvelle liste
                </h1>
                <p class="text-indigo-100 mt-1">
                    Remplissez les informations de votre liste électorale
                </p>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Commune -->
                <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        📍 Commune
                    </h2>

                    <div class="relative">
                        <InputLabel for="commune" value="Rechercher votre commune *" />
                        <TextInput
                            id="commune"
                            v-model="communeSearch"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Tapez le nom de votre commune..."
                            autocomplete="off"
                        />
                        
                        <!-- Résultats de recherche -->
                        <div
                            v-if="communeResults.length > 0"
                            class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        >
                            <button
                                v-for="commune in communeResults"
                                :key="commune.code_insee"
                                type="button"
                                @click="selectCommune(commune)"
                                class="w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                            >
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ commune.nom }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ commune.departement_code || commune.code_insee?.substring(0, 2) }} - {{ commune.departement_nom || '' }}
                                </div>
                            </button>
                        </div>

                        <p v-if="isSearching" class="mt-2 text-sm text-gray-500">
                            Recherche en cours...
                        </p>
                    </div>

                    <div v-if="selectedCommune" class="mt-4 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-700">
                        <p class="text-green-700 dark:text-green-300 font-medium flex items-center gap-2">
                            ✅ Commune sélectionnée : {{ selectedCommune.nom }} ({{ form.departement_code }})
                        </p>
                    </div>

                    <InputError :message="form.errors.commune_code_insee" class="mt-2" />
                </section>

                <!-- Informations de la liste -->
                <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        🏛️ Informations de la liste
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <InputLabel for="nom_liste" value="Nom de la liste *" />
                            <TextInput
                                id="nom_liste"
                                v-model="form.nom_liste"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ex: Ensemble pour Strasbourg"
                                required
                            />
                            <InputError :message="form.errors.nom_liste" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="nuance" value="Nuance politique" />
                            <select
                                id="nuance"
                                v-model="form.nuance_politique"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Sélectionner...</option>
                                <option v-for="nuance in nuances_politiques" :key="nuance.code" :value="nuance.code">
                                    {{ nuance.label }} ({{ nuance.code }})
                                </option>
                            </select>
                            <InputError :message="form.errors.nuance_politique" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="parti" value="Parti principal (optionnel)" />
                            <TextInput
                                id="parti"
                                v-model="form.parti_principal"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ex: Les Républicains"
                            />
                            <InputError :message="form.errors.parti_principal" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="slogan" value="Slogan (optionnel)" />
                            <TextInput
                                id="slogan"
                                v-model="form.slogan"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ex: Pour une ville plus verte et solidaire"
                            />
                            <InputError :message="form.errors.slogan" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="description" value="Description de la liste (optionnel)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Présentez votre projet pour la commune..."
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="couleur" value="Couleur principale" />
                            <div class="flex items-center gap-3 mt-1">
                                <input
                                    id="couleur"
                                    v-model="form.couleur_principale"
                                    type="color"
                                    class="h-10 w-20 rounded border border-gray-300 dark:border-gray-700 cursor-pointer"
                                />
                                <TextInput
                                    v-model="form.couleur_principale"
                                    type="text"
                                    class="flex-1"
                                    placeholder="#6366F1"
                                />
                            </div>
                            <InputError :message="form.errors.couleur_principale" class="mt-2" />
                        </div>
                    </div>
                </section>

                <!-- Contact -->
                <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        📧 Contact
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="email" value="Email de contact" />
                            <TextInput
                                id="email"
                                v-model="form.email_contact"
                                type="email"
                                class="mt-1 block w-full"
                                placeholder="contact@maliste.fr"
                            />
                            <InputError :message="form.errors.email_contact" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="telephone" value="Téléphone" />
                            <TextInput
                                id="telephone"
                                v-model="form.telephone_contact"
                                type="tel"
                                class="mt-1 block w-full"
                                placeholder="06 12 34 56 78"
                            />
                            <InputError :message="form.errors.telephone_contact" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="site_web" value="Site web" />
                            <TextInput
                                id="site_web"
                                v-model="form.site_web"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://www.maliste.fr"
                            />
                            <InputError :message="form.errors.site_web" class="mt-2" />
                        </div>
                    </div>
                </section>

                <!-- Réseaux sociaux -->
                <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        📱 Réseaux sociaux
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="facebook" value="Facebook" />
                            <TextInput
                                id="facebook"
                                v-model="form.facebook_url"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://facebook.com/maliste"
                            />
                            <InputError :message="form.errors.facebook_url" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="twitter" value="Twitter / X" />
                            <TextInput
                                id="twitter"
                                v-model="form.twitter_url"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://twitter.com/maliste"
                            />
                            <InputError :message="form.errors.twitter_url" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="instagram" value="Instagram" />
                            <TextInput
                                id="instagram"
                                v-model="form.instagram_url"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://instagram.com/maliste"
                            />
                            <InputError :message="form.errors.instagram_url" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="youtube" value="YouTube" />
                            <TextInput
                                id="youtube"
                                v-model="form.youtube_url"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://youtube.com/@maliste"
                            />
                            <InputError :message="form.errors.youtube_url" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="tiktok" value="TikTok" />
                            <TextInput
                                id="tiktok"
                                v-model="form.tiktok_url"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://tiktok.com/@maliste"
                            />
                            <InputError :message="form.errors.tiktok_url" class="mt-2" />
                        </div>
                    </div>
                </section>

                <!-- Programme -->
                <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        📋 Programme
                    </h2>

                    <div>
                        <InputLabel for="resume" value="Résumé du programme (500 caractères max)" />
                        <textarea
                            id="resume"
                            v-model="form.resume_programme"
                            rows="4"
                            maxlength="500"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Résumez les points clés de votre programme..."
                        ></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ form.resume_programme?.length || 0 }}/500 caractères
                        </p>
                        <InputError :message="form.errors.resume_programme" class="mt-2" />
                    </div>

                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        💡 Vous pourrez ajouter votre programme complet en PDF après la création de la liste.
                    </p>
                </section>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('elections.municipales.espace-candidat.index')"
                        class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !selectedCommune || !form.nom_liste"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">⏳ Création...</span>
                        <span v-else>✅ Créer la liste</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
