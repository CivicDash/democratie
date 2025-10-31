<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';

const props = defineProps({
    source: {
        type: String,
        default: 'assemblee',
    },
});

const groupes = ref([]);
const loading = ref(true);
const selectedPosition = ref('all');

const fetchGroupes = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/groupes-parlementaires', {
            params: {
                source: props.source,
                actif: true,
            },
        });
        groupes.value = response.data.data;
    } catch (error) {
        console.error('Erreur chargement groupes:', error);
    } finally {
        loading.value = false;
    }
};

const groupesFiltered = computed(() => {
    if (selectedPosition.value === 'all') {
        return groupes.value;
    }
    return groupes.value.filter(g => g.position_politique === selectedPosition.value);
});

const totalSieges = computed(() => {
    return groupes.value.reduce((sum, g) => sum + g.nombre_membres, 0);
});

const positions = [
    { value: 'all', label: 'Tous' },
    { value: 'extreme_gauche', label: 'Extrême gauche' },
    { value: 'gauche', label: 'Gauche' },
    { value: 'centre', label: 'Centre' },
    { value: 'droite', label: 'Droite' },
    { value: 'extreme_droite', label: 'Extrême droite' },
];

onMounted(() => {
    fetchGroupes();
});
</script>

<template>
    <Head title="Groupes Parlementaires" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Groupes Parlementaires
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ source === 'assemblee' ? 'Assemblée Nationale' : 'Sénat' }} - {{ totalSieges }} sièges
                </p>
            </div>

            <!-- Filtres -->
            <div class="mb-6 flex gap-2 overflow-x-auto">
                <button
                    v-for="position in positions"
                    :key="position.value"
                    @click="selectedPosition = position.value"
                    class="px-4 py-2 rounded-lg whitespace-nowrap transition-colors"
                    :class="selectedPosition === position.value
                        ? 'bg-blue-600 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                >
                    {{ position.label }}
                </button>
            </div>

            <!-- Loading -->
            <LoadingSkeleton v-if="loading" type="card" :count="6" />

            <!-- Grille des groupes -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="groupe in groupesFiltered"
                    :key="groupe.id"
                    :href="`/legislation/groupes/${groupe.id}`"
                    class="block bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden border-l-4"
                    :style="{ borderLeftColor: groupe.couleur_hex }"
                >
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ groupe.nom }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ groupe.sigle }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold"
                                :style="{ backgroundColor: groupe.couleur_hex }"
                            >
                                {{ groupe.sigle.substring(0, 2) }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Membres</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ groupe.nombre_membres }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Position</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ groupe.position_label }}
                                </span>
                            </div>
                        </div>

                        <div v-if="groupe.president_nom" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Président : {{ groupe.president_nom }}
                            </p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="!loading && groupesFiltered.length === 0" class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">
                    Aucun groupe trouvé pour ce filtre
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

