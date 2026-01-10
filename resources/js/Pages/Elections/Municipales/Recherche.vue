<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    listes: Object,
    filters: Object,
});

const search = ref(props.filters.q || '');
const departement = ref(props.filters.departement || '');

let searchTimeout = null;
const performSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('elections.municipales.recherche'), {
            q: search.value,
            departement: departement.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 400);
};

watch([search, departement], performSearch);
</script>

<template>
    <Head title="Rechercher une liste - Élections Municipales" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-indigo-900 via-purple-800 to-fuchsia-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Link
                    :href="route('elections.municipales.index')"
                    class="text-indigo-200 hover:text-white text-sm mb-4 inline-block"
                >
                    ← Retour aux municipales
                </Link>
                
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    🔍 Rechercher les candidats
                </h1>
                <p class="text-xl text-indigo-200 mb-8">
                    Trouvez les listes et candidats dans votre commune
                </p>

                <!-- Barre de recherche -->
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <TextInput
                            v-model="search"
                            type="text"
                            placeholder="Nom de la commune ou de la liste..."
                            class="w-full text-lg py-4 px-6"
                        />
                    </div>
                    <select
                        v-model="departement"
                        class="px-6 py-4 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option value="">Tous les départements</option>
                        <option value="01">01 - Ain</option>
                        <option value="02">02 - Aisne</option>
                        <option value="03">03 - Allier</option>
                        <!-- ... autres départements à compléter -->
                        <option value="67">67 - Bas-Rhin</option>
                        <option value="68">68 - Haut-Rhin</option>
                        <option value="69">69 - Rhône</option>
                        <option value="75">75 - Paris</option>
                        <option value="92">92 - Hauts-de-Seine</option>
                        <option value="93">93 - Seine-Saint-Denis</option>
                        <option value="94">94 - Val-de-Marne</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Résultats -->
            <div v-if="listes.data.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <span class="text-5xl">🔍</span>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-4 mb-2">
                    Aucune liste trouvée
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Essayez avec un autre nom de commune ou attendez que des listes soient inscrites.
                </p>
                <Link
                    :href="route('elections.municipales.espace-candidat.index')"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 transition"
                >
                    🚀 Inscrire ma liste
                </Link>
            </div>

            <div v-else class="space-y-4">
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ listes.total }} liste(s) trouvée(s)
                </p>

                <Link
                    v-for="liste in listes.data"
                    :key="liste.uuid"
                    :href="route('elections.municipales.liste', liste.uuid)"
                    class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition group"
                >
                    <div class="flex items-start gap-6">
                        <div
                            class="w-16 h-16 rounded-xl flex-shrink-0 flex items-center justify-center text-2xl"
                            :style="{ backgroundColor: liste.couleur + '20' }"
                        >
                            🏛️
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4 mb-2">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                        {{ liste.nom_liste }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        📍 {{ liste.commune_nom }} ({{ liste.departement_code }})
                                    </p>
                                </div>
                                <Badge
                                    v-if="liste.nuance_politique"
                                    class="text-xs"
                                    :style="{ backgroundColor: liste.couleur + '30', color: liste.couleur }"
                                >
                                    {{ liste.nuance_politique }}
                                </Badge>
                            </div>
                            
                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span v-if="liste.tete_de_liste">
                                    👤 {{ liste.tete_de_liste }}
                                </span>
                                <span>👥 {{ liste.nombre_candidats }} candidats</span>
                            </div>
                        </div>

                        <div class="flex-shrink-0 text-gray-400 group-hover:text-indigo-500 transition">
                            →
                        </div>
                    </div>
                </Link>

                <!-- Pagination -->
                <div v-if="listes.last_page > 1" class="flex justify-center gap-2 mt-8">
                    <Link
                        v-for="page in listes.last_page"
                        :key="page"
                        :href="listes.path + '?page=' + page + '&q=' + (filters.q || '') + '&departement=' + (filters.departement || '')"
                        :class="[
                            'px-4 py-2 rounded-lg',
                            page === listes.current_page
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        {{ page }}
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
