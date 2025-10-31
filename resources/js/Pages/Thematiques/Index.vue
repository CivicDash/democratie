<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ThematiqueCard from '@/Components/ThematiqueCard.vue';
import axios from 'axios';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';

const thematiques = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const fetchThematiques = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/thematiques', {
            params: {
                principales: true,
                avec_enfants: true,
            },
        });
        thematiques.value = response.data.data;
    } catch (error) {
        console.error('Erreur chargement thématiques:', error);
    } finally {
        loading.value = false;
    }
};

const thematiquesFiltered = computed(() => {
    if (!searchQuery.value) {
        return thematiques.value;
    }
    
    const query = searchQuery.value.toLowerCase();
    return thematiques.value.filter(t =>
        t.nom.toLowerCase().includes(query) ||
        t.description?.toLowerCase().includes(query)
    );
});

const totalPropositions = computed(() => {
    return thematiques.value.reduce((sum, t) => sum + t.nb_propositions, 0);
});

onMounted(() => {
    fetchThematiques();
});
</script>

<template>
    <Head title="Thématiques Législatives" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Thématiques Législatives
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ thematiques.length }} thématiques • {{ totalPropositions }} propositions classées
                </p>
            </div>

            <!-- Recherche -->
            <div class="mb-6">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher une thématique..."
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            <!-- Loading -->
            <LoadingSkeleton v-if="loading" type="card" :count="15" />

            <!-- Grille des thématiques -->
            <div v-else-if="thematiquesFiltered.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <ThematiqueCard
                    v-for="thematique in thematiquesFiltered"
                    :key="thematique.id"
                    :thematique="thematique"
                />
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">
                    Aucune thématique trouvée
                </p>
            </div>

            <!-- Légende -->
            <div class="mt-12 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    À propos des thématiques
                </h3>
                <p class="text-gray-700 dark:text-gray-300 mb-4">
                    Les propositions de loi sont automatiquement classées dans 15 thématiques principales 
                    grâce à un système de détection par mots-clés. Chaque proposition peut avoir une thématique 
                    principale et plusieurs thématiques secondaires.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Score de confiance</p>
                        <p class="text-gray-600 dark:text-gray-400">Calculé sur 100 en fonction des mots-clés trouvés</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Thématique principale</p>
                        <p class="text-gray-600 dark:text-gray-400">Score > 40% ou meilleur score si aucune au-dessus</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Détection automatique</p>
                        <p class="text-gray-600 dark:text-gray-400">Les thématiques manuelles sont prioritaires</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

