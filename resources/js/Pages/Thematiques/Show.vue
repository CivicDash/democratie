<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';

const props = defineProps({
    code: {
        type: String,
        required: true,
    },
});

const thematique = ref(null);
const statistiques = ref(null);
const propositions = ref([]);
const groupesActifs = ref([]);
const loading = ref(true);

const fetchData = async () => {
    loading.value = true;
    try {
        const [thematiqueRes, propositionsRes] = await Promise.all([
            axios.get(`/api/thematiques/${props.code}`),
            axios.get(`/api/thematiques/${props.code}/propositions?limit=20`),
        ]);

        thematique.value = thematiqueRes.data.data.thematique;
        statistiques.value = thematiqueRes.data.data.statistiques;
        groupesActifs.value = thematiqueRes.data.data.groupes_actifs;
        propositions.value = propositionsRes.data.data;
    } catch (error) {
        console.error('Erreur chargement données:', error);
    } finally {
        loading.value = false;
    }
};

const getStatusBadgeClass = (statut) => {
    const classes = {
        'adoptee': 'bg-green-100 text-green-800',
        'rejetee': 'bg-red-100 text-red-800',
        'en_discussion': 'bg-blue-100 text-blue-800',
        'depose': 'bg-gray-100 text-gray-800',
    };
    return classes[statut] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head :title="thematique?.nom || 'Thématique'" />

    <AuthenticatedLayout>
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <LoadingSkeleton v-if="loading" type="card" :count="3" />

            <template v-else-if="thematique">
                <!-- Header -->
                <div class="mb-8">
                    <Link href="/legislation/thematiques" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                        ← Retour aux thématiques
                    </Link>
                    
                    <div class="flex items-start gap-6">
                        <div
                            class="w-24 h-24 rounded-full flex items-center justify-center text-5xl shadow-lg"
                            :style="{ backgroundColor: thematique.couleur_hex + '20', color: thematique.couleur_hex }"
                        >
                            {{ thematique.icone }}
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ thematique.nom }}
                                </h1>
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ thematique.code }}
                                </span>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ thematique.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div v-if="statistiques" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                            {{ statistiques.total_propositions }}
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Adoptées</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">
                            {{ statistiques.propositions_adoptees }}
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Rejetées</p>
                        <p class="text-3xl font-bold text-red-600 mt-2">
                            {{ statistiques.propositions_rejetees }}
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">En cours</p>
                        <p class="text-3xl font-bold text-blue-600 mt-2">
                            {{ statistiques.propositions_en_cours }}
                        </p>
                    </div>
                </div>

                <!-- Groupes actifs -->
                <div v-if="groupesActifs && groupesActifs.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        Groupes les plus actifs
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="item in groupesActifs"
                            :key="item.groupe.id"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                    :style="{ backgroundColor: item.groupe.couleur_hex }"
                                >
                                    {{ item.groupe.sigle.substring(0, 2) }}
                                </div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ item.groupe.nom }}
                                </span>
                            </div>
                            <div class="flex gap-3 text-sm">
                                <span class="text-green-600">{{ item.votes_pour }} pour</span>
                                <span class="text-red-600">{{ item.votes_contre }} contre</span>
                                <span class="text-gray-500">{{ item.votes_abstention }} abs.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Propositions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Propositions récentes ({{ propositions.length }})
                        </h2>
                    </div>
                    
                    <div v-if="propositions.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <Link
                            v-for="proposition in propositions"
                            :key="proposition.id"
                            :href="`/legislation/${proposition.id}`"
                            class="block p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-block px-2 py-1 text-xs font-medium rounded"
                                            :class="getStatusBadgeClass(proposition.statut)"
                                        >
                                            {{ proposition.statut }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ proposition.source === 'assemblee' ? 'Assemblée' : 'Sénat' }} • 
                                            {{ proposition.numero }}
                                        </span>
                                        <span
                                            v-if="proposition.pivot.est_principal"
                                            class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded"
                                        >
                                            ⭐ Principal
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                        {{ proposition.titre }}
                                    </h3>
                                    
                                    <p v-if="proposition.resume" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ proposition.resume }}
                                    </p>
                                </div>
                                
                                <div class="text-right text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ new Date(proposition.date_depot).toLocaleDateString('fr-FR') }}
                                </div>
                            </div>
                        </Link>
                    </div>
                    
                    <div v-else class="p-12 text-center text-gray-500 dark:text-gray-400">
                        Aucune proposition pour cette thématique
                    </div>
                </div>
            </template>

            <div v-else class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">Thématique non trouvée</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

