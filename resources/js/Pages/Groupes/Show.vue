<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';

const props = defineProps({
    groupeId: {
        type: Number,
        required: true,
    },
});

const groupe = ref(null);
const statistiques = ref(null);
const membres = ref([]);
const votesRecents = ref([]);
const thematiques = ref([]);
const loading = ref(true);

const fetchData = async () => {
    loading.value = true;
    try {
        const [groupeRes, statsRes, membresRes, votesRes] = await Promise.all([
            axios.get(`/api/groupes-parlementaires/${props.groupeId}`),
            axios.get(`/api/groupes-parlementaires/${props.groupeId}/statistiques`),
            axios.get(`/api/groupes-parlementaires/${props.groupeId}/membres`),
            axios.get(`/api/groupes-parlementaires/${props.groupeId}/votes?limit=10`),
        ]);

        groupe.value = groupeRes.data.data.groupe;
        statistiques.value = groupeRes.data.data.statistiques;
        thematiques.value = groupeRes.data.data.thematiques_favorites;
        membres.value = membresRes.data.data;
        votesRecents.value = votesRes.data.data;
    } catch (error) {
        console.error('Erreur chargement données:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head :title="groupe?.nom || 'Groupe Parlementaire'" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <LoadingSkeleton v-if="loading" type="card" :count="3" />

            <template v-else-if="groupe">
                <!-- Header -->
                <div class="mb-8">
                    <Link href="/legislation/groupes" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                        ← Retour aux groupes
                    </Link>
                    
                    <div class="flex items-start gap-6">
                        <div
                            class="w-24 h-24 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg"
                            :style="{ backgroundColor: groupe.couleur_hex }"
                        >
                            {{ groupe.sigle }}
                        </div>
                        
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ groupe.nom }}
                            </h1>
                            <div class="mt-2 space-y-1">
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ groupe.position_label }} • {{ groupe.nombre_membres }} membres
                                </p>
                                <p v-if="groupe.president_nom" class="text-sm text-gray-500 dark:text-gray-400">
                                    Président : {{ groupe.president_nom }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div v-if="statistiques" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total votes</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                            {{ statistiques.total_votes }}
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Votes pour</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">
                            {{ statistiques.pourcentage_pour }}%
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Votes contre</p>
                        <p class="text-3xl font-bold text-red-600 mt-2">
                            {{ statistiques.votes_contre }}
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Discipline moyenne</p>
                        <p class="text-3xl font-bold text-blue-600 mt-2">
                            {{ statistiques.discipline_moyenne }}%
                        </p>
                    </div>
                </div>

                <!-- Thématiques favorites -->
                <div v-if="thematiques && thematiques.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        Thématiques favorites
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="item in thematiques"
                            :key="item.thematique.id"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ item.thematique.icone }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ item.thematique.nom }}
                                </span>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ item.count }} vote{{ item.count > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Membres -->
                <div v-if="membres.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        Membres ({{ membres.length }})
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="membre in membres"
                            :key="membre.id"
                            class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                        >
                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                {{ membre.civilite }} {{ membre.prenom }} {{ membre.nom }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ membre.circonscription }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Votes récents -->
                <div v-if="votesRecents.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        Votes récents
                    </h2>
                    <div class="space-y-4">
                        <div
                            v-for="vote in votesRecents"
                            :key="vote.id"
                            class="border-l-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-r-lg"
                            :style="{ borderLeftColor: vote.position === 'pour' ? '#10b981' : vote.position === 'contre' ? '#ef4444' : '#6b7280' }"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ vote.vote_legislatif?.proposition?.titre || 'Scrutin ' + vote.vote_legislatif?.numero_scrutin }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ new Date(vote.date).toLocaleDateString('fr-FR') }}
                                    </p>
                                </div>
                                <div class="ml-4 text-right">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-sm font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800': vote.position === 'pour',
                                            'bg-red-100 text-red-800': vote.position === 'contre',
                                            'bg-gray-100 text-gray-800': vote.position === 'abstention',
                                        }"
                                    >
                                        {{ vote.position_label }}
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Discipline: {{ vote.discipline }}%
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <span>Pour: {{ vote.pour }}</span>
                                <span>Contre: {{ vote.contre }}</span>
                                <span>Abstention: {{ vote.abstention }}</span>
                                <span>Absents: {{ vote.absents }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div v-else class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">Groupe non trouvé</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

