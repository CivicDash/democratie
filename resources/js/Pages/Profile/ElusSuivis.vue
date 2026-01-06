<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { useEluFollow } from '@/Composables/useEluFollow';

const props = defineProps({
    followedElus: Array,
    stats: Object,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Mon profil', href: route('profile.edit'), icon: '👤' },
    { label: 'Élus suivis', icon: '🔔' },
];

const { unfollowElu, updatePreferences, activityTypes } = useEluFollow();

// États
const loading = ref({});
const editingPrefs = ref(null);
const localPrefs = ref({});
const filter = ref('all');
const searchQuery = ref('');

// Filtres par type d'élu
const eluTypeLabels = {
    depute: { label: 'Députés', icon: '🔵', color: 'blue' },
    senateur: { label: 'Sénateurs', icon: '🔴', color: 'red' },
    maire: { label: 'Maires', icon: '🏘️', color: 'green' },
    ministre: { label: 'Ministres', icon: '🏛️', color: 'purple' },
};

// Liste filtrée
const filteredElus = computed(() => {
    let list = props.followedElus || [];

    // Filtre par type
    if (filter.value !== 'all') {
        list = list.filter(e => e.elu_type === filter.value);
    }

    // Recherche
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(e => 
            e.elu_nom?.toLowerCase().includes(q) ||
            e.elu_groupe?.toLowerCase().includes(q) ||
            e.elu_circonscription?.toLowerCase().includes(q)
        );
    }

    return list;
});

// Stats par type
const statsByType = computed(() => {
    const stats = {};
    (props.followedElus || []).forEach(e => {
        stats[e.elu_type] = (stats[e.elu_type] || 0) + 1;
    });
    return stats;
});

// Ouvrir l'éditeur de préférences
const openPrefsEditor = (elu) => {
    editingPrefs.value = elu.id;
    localPrefs.value = {
        notify_votes: elu.notify_votes,
        notify_interventions: elu.notify_interventions,
        notify_amendements: elu.notify_amendements,
        notify_propositions: elu.notify_propositions,
        notify_rapports: elu.notify_rapports,
        notify_commissions: elu.notify_commissions,
        notify_actualites: elu.notify_actualites,
        notify_site: elu.notify_site,
        notify_email: elu.notify_email,
        email_frequency: elu.email_frequency,
    };
};

// Sauvegarder les préférences
const savePrefs = async (elu) => {
    loading.value[elu.id] = true;
    try {
        await updatePreferences(elu.elu_type, elu.elu_id, localPrefs.value);
        editingPrefs.value = null;
        // Refresh the page to show updated data
        router.reload({ only: ['followedElus'] });
    } catch (error) {
        console.error('Erreur:', error);
    } finally {
        loading.value[elu.id] = false;
    }
};

// Ne plus suivre
const unfollow = async (elu) => {
    if (!confirm(`Êtes-vous sûr de ne plus vouloir suivre ${elu.elu_nom} ?`)) return;

    loading.value[elu.id] = true;
    try {
        await unfollowElu(elu.elu_type, elu.elu_id);
        router.reload({ only: ['followedElus', 'stats'] });
    } catch (error) {
        console.error('Erreur:', error);
    } finally {
        loading.value[elu.id] = false;
    }
};

// URL du profil de l'élu
const getEluUrl = (elu) => {
    switch (elu.elu_type) {
        case 'depute':
            return route('representants.deputes.show', elu.elu_id);
        case 'senateur':
            return route('representants.senateurs.show', elu.elu_id);
        case 'maire':
            return null; // Pas de page dédiée pour les maires pour l'instant
        case 'ministre':
            return route('gouvernement.ministre', elu.elu_id);
        default:
            return null;
    }
};

// Nombre de préférences actives
const getActivePrefsCount = (elu) => {
    let count = 0;
    if (elu.notify_votes) count++;
    if (elu.notify_interventions) count++;
    if (elu.notify_amendements) count++;
    if (elu.notify_propositions) count++;
    if (elu.notify_rapports) count++;
    if (elu.notify_commissions) count++;
    if (elu.notify_actualites) count++;
    return count;
};
</script>

<template>
    <Head title="Élus suivis" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="py-6">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 flex items-center gap-3">
                        🔔 Mes élus suivis
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Gérez les élus que vous suivez et personnalisez vos préférences de notification.
                    </p>
                </div>

                <!-- Stats globales -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <button
                        @click="filter = 'all'"
                        :class="[
                            'p-4 rounded-xl text-left transition border-2',
                            filter === 'all'
                                ? 'bg-blue-50 border-blue-500 dark:bg-blue-900/30 dark:border-blue-500'
                                : 'bg-white dark:bg-gray-800 border-transparent hover:border-gray-200 dark:hover:border-gray-700'
                        ]"
                    >
                        <span class="text-2xl">👥</span>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200 mt-1">
                            {{ followedElus?.length || 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                    </button>

                    <button
                        v-for="(info, type) in eluTypeLabels"
                        :key="type"
                        @click="filter = type"
                        :class="[
                            'p-4 rounded-xl text-left transition border-2',
                            filter === type
                                ? `bg-${info.color}-50 border-${info.color}-500 dark:bg-${info.color}-900/30`
                                : 'bg-white dark:bg-gray-800 border-transparent hover:border-gray-200 dark:hover:border-gray-700'
                        ]"
                    >
                        <span class="text-2xl">{{ info.icon }}</span>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200 mt-1">
                            {{ statsByType[type] || 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ info.label }}</p>
                    </button>
                </div>

                <!-- Recherche -->
                <div class="mb-6">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Rechercher par nom, groupe politique, circonscription..."
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <!-- Liste vide -->
                <div v-if="!followedElus?.length" class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl">
                    <span class="text-6xl">🔔</span>
                    <h3 class="mt-4 text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Vous ne suivez aucun élu pour le moment
                    </h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        Explorez les profils des députés et sénateurs pour les suivre et être notifié de leurs activités.
                    </p>
                    <div class="mt-6 flex justify-center gap-4">
                        <Link
                            :href="route('representants.deputes.index')"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            🔵 Voir les députés
                        </Link>
                        <Link
                            :href="route('representants.senateurs.index')"
                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                        >
                            🔴 Voir les sénateurs
                        </Link>
                    </div>
                </div>

                <!-- Liste des élus suivis -->
                <div v-else class="space-y-4">
                    <div
                        v-for="elu in filteredElus"
                        :key="elu.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                    >
                        <!-- Info principale -->
                        <div class="p-4 flex items-start gap-4">
                            <!-- Photo -->
                            <div class="flex-shrink-0">
                                <img
                                    v-if="elu.elu_photo_url"
                                    :src="elu.elu_photo_url"
                                    :alt="elu.elu_nom"
                                    class="w-16 h-16 rounded-full object-cover"
                                />
                                <div v-else class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl">
                                    {{ eluTypeLabels[elu.elu_type]?.icon }}
                                </div>
                            </div>

                            <!-- Infos -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <Link
                                        v-if="getEluUrl(elu)"
                                        :href="getEluUrl(elu)"
                                        class="text-lg font-semibold text-gray-800 dark:text-gray-200 hover:text-blue-600 transition"
                                    >
                                        {{ elu.elu_nom }}
                                    </Link>
                                    <span v-else class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                        {{ elu.elu_nom }}
                                    </span>
                                    <span :class="`px-2 py-0.5 text-xs rounded-full bg-${eluTypeLabels[elu.elu_type]?.color}-100 text-${eluTypeLabels[elu.elu_type]?.color}-700 dark:bg-${eluTypeLabels[elu.elu_type]?.color}-900/30 dark:text-${eluTypeLabels[elu.elu_type]?.color}-400`">
                                        {{ eluTypeLabels[elu.elu_type]?.label }}
                                    </span>
                                </div>
                                <p v-if="elu.elu_groupe" class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ elu.elu_groupe }}
                                </p>
                                <p v-if="elu.elu_circonscription" class="text-sm text-gray-500 dark:text-gray-500">
                                    📍 {{ elu.elu_circonscription }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    Suivi depuis {{ new Date(elu.followed_at).toLocaleDateString('fr-FR') }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="openPrefsEditor(elu)"
                                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    title="Modifier les préférences"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                                <button
                                    @click="unfollow(elu)"
                                    :disabled="loading[elu.id]"
                                    class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-gray-400 hover:text-red-600"
                                    title="Ne plus suivre"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Préférences actuelles (résumé) -->
                        <div v-if="editingPrefs !== elu.id" class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center gap-4 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Notifications :</span>
                            <div class="flex flex-wrap gap-2">
                                <span v-if="elu.notify_votes" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded text-xs">🗳️ Votes</span>
                                <span v-if="elu.notify_interventions" class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded text-xs">🎤 Interventions</span>
                                <span v-if="elu.notify_propositions" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded text-xs">📜 Propositions</span>
                                <span v-if="elu.notify_amendements" class="px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded text-xs">📝 Amendements</span>
                                <span v-if="elu.notify_actualites" class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 rounded text-xs">📰 Actualités</span>
                            </div>
                            <span class="text-gray-400 dark:text-gray-500">|</span>
                            <span v-if="elu.notify_site" class="text-gray-600 dark:text-gray-400">🔔 Site</span>
                            <span v-if="elu.notify_email" class="text-gray-600 dark:text-gray-400">📧 Email ({{ elu.email_frequency === 'instant' ? 'immédiat' : elu.email_frequency === 'daily' ? 'quotidien' : 'hebdo' }})</span>
                        </div>

                        <!-- Éditeur de préférences (expandé) -->
                        <div v-else class="p-4 bg-blue-50 dark:bg-blue-900/20 border-t border-blue-200 dark:border-blue-800">
                            <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-4">⚙️ Modifier les préférences de notification</h4>
                            
                            <!-- Types d'activités -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                <label
                                    v-for="(activity, key) in activityTypes"
                                    :key="key"
                                    class="flex items-center gap-2 p-2 rounded-lg bg-white dark:bg-gray-800 cursor-pointer hover:shadow-sm transition"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="localPrefs[`notify_${key}`]"
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm">{{ activity.icon }} {{ activity.label }}</span>
                                </label>
                            </div>

                            <!-- Canaux -->
                            <div class="flex flex-wrap gap-4 mb-4">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="localPrefs.notify_site" class="w-4 h-4 rounded" />
                                    <span>🔔 Site</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="localPrefs.notify_email" class="w-4 h-4 rounded" />
                                    <span>📧 Email</span>
                                </label>
                                <select
                                    v-if="localPrefs.notify_email"
                                    v-model="localPrefs.email_frequency"
                                    class="px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                >
                                    <option value="instant">Immédiat</option>
                                    <option value="daily">Quotidien</option>
                                    <option value="weekly">Hebdomadaire</option>
                                </select>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-2">
                                <button
                                    @click="editingPrefs = null"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="savePrefs(elu)"
                                    :disabled="loading[elu.id]"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                                >
                                    {{ loading[elu.id] ? 'Sauvegarde...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aucun résultat de recherche -->
                <div v-if="followedElus?.length && !filteredElus.length" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
                    <span class="text-4xl">🔍</span>
                    <p class="mt-3 text-gray-500 dark:text-gray-400">
                        Aucun élu ne correspond à votre recherche.
                    </p>
                    <button
                        @click="searchQuery = ''; filter = 'all'"
                        class="mt-4 text-blue-600 hover:underline"
                    >
                        Réinitialiser les filtres
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
