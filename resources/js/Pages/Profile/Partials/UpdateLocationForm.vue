<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    profile: Object,
});

const form = useForm({
    city_name: props.profile?.city_name || '',
    postal_code: props.profile?.postal_code || '',
    circonscription: props.profile?.circonscription || '',
});

// Autocomplétion
const searchQuery = ref('');
const searchResults = ref([]);
const showResults = ref(false);
const isSearching = ref(false);
const searchTimeout = ref(null);

// Rechercher dans l'API des codes postaux
const searchPostalCodes = async () => {
    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        showResults.value = false;
        return;
    }

    isSearching.value = true;

    try {
        const response = await axios.get('/api/postal-codes/search', {
            params: { q: searchQuery.value }
        });

        searchResults.value = response.data.results || [];
        showResults.value = searchResults.value.length > 0;
    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

// Debounce pour éviter trop de requêtes
watch(searchQuery, () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }

    searchTimeout.value = setTimeout(() => {
        searchPostalCodes();
    }, 300);
});

// Sélectionner un résultat
const selectResult = (result) => {
    form.city_name = result.city_name;
    form.postal_code = result.postal_code;
    form.circonscription = result.circonscription;
    
    searchQuery.value = result.label;
    showResults.value = false;
};

// Fermer les résultats si on clique ailleurs
const closeResults = () => {
    setTimeout(() => {
        showResults.value = false;
    }, 200);
};

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                📍 Ma Localisation
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Renseignez votre localisation pour découvrir vos représentants politiques (député et sénateurs).
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- Recherche avec autocomplétion -->
            <div class="relative">
                <InputLabel for="search" value="🔍 Rechercher ma ville ou mon code postal" />
                
                <div class="relative mt-1">
                    <input
                        id="search"
                        type="text"
                        v-model="searchQuery"
                        @focus="showResults = searchResults.length > 0"
                        @blur="closeResults"
                        placeholder="Ex: 75001, Paris, Lyon..."
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                    />
                    
                    <!-- Loader -->
                    <div v-if="isSearching" class="absolute right-3 top-3">
                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Résultats de recherche -->
                <div
                    v-if="showResults && searchResults.length > 0"
                    class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base overflow-auto focus:outline-none sm:text-sm border border-gray-200 dark:border-gray-700"
                >
                    <button
                        v-for="result in searchResults"
                        :key="result.id"
                        type="button"
                        @mousedown="selectResult(result)"
                        class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ result.postal_code }}</span>
                                <span class="text-gray-600 dark:text-gray-400 ml-2">{{ result.city_name }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-500">{{ result.department_name }}</span>
                        </div>
                    </button>
                </div>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    💡 Tapez votre code postal ou le nom de votre ville pour remplir automatiquement les champs
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Ville (rempli automatiquement) -->
                <div>
                    <InputLabel for="city_name" value="Ville / Commune" />

                    <TextInput
                        id="city_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.city_name"
                        placeholder="Ex: Paris, Lyon, Marseille..."
                        readonly
                    />

                    <InputError class="mt-2" :message="form.errors.city_name" />
                </div>

                <!-- Code postal (rempli automatiquement) -->
                <div>
                    <InputLabel for="postal_code" value="Code Postal" />

                    <TextInput
                        id="postal_code"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.postal_code"
                        placeholder="Ex: 75001"
                        maxlength="5"
                        readonly
                    />

                    <InputError class="mt-2" :message="form.errors.postal_code" />
                </div>
            </div>

            <!-- Circonscription (rempli automatiquement) -->
            <div>
                <InputLabel for="circonscription" value="Circonscription Législative" />

                <TextInput
                    id="circonscription"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.circonscription"
                    placeholder="Ex: 75-01 (Département-Numéro)"
                    readonly
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    ℹ️ Rempli automatiquement en fonction de votre ville
                </p>

                <InputError class="mt-2" :message="form.errors.circonscription" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    💾 Enregistrer
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        ✅ Localisation enregistrée
                    </p>
                </Transition>
            </div>

            <!-- Info -->
            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-600 p-4 rounded">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">✨</span>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <p class="font-semibold mb-1">Remplissage automatique</p>
                        <p>
                            Utilisez la barre de recherche ci-dessus pour trouver votre ville ou votre code postal. 
                            Tous les champs seront automatiquement remplis, y compris votre circonscription législative !
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>

