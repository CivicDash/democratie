<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    topics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const scopeFilter = ref(props.filters?.scope || 'all');
const typeFilter = ref(props.filters?.type || 'all');

const applyFilters = () => {
    router.get(route('topics.index'), {
        search: search.value,
        scope: scopeFilter.value === 'all' ? null : scopeFilter.value,
        type: typeFilter.value === 'all' ? null : typeFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    scopeFilter.value = 'all';
    typeFilter.value = 'all';
    applyFilters();
};

const getScopeLabel = (scope) => {
    const labels = {
        national: '🇫🇷 National',
        regional: '🗺️ Régional',
        departmental: '📍 Départemental',
    };
    return labels[scope] || scope;
};

const getTypeLabel = (type) => {
    const labels = {
        debate: '💬 Débat',
        proposal: '💡 Proposition',
        question: '❓ Question',
        announcement: '📢 Annonce',
    };
    return labels[type] || type;
};

const getStatusBadge = (topic) => {
    if (topic.archived_at) return { variant: 'gray', label: '🗄️ Archivé' };
    if (topic.closed_at) return { variant: 'red', label: '🔒 Fermé' };
    if (topic.ballot_type) {
        if (topic.ballot_ends_at && new Date(topic.ballot_ends_at) < new Date()) {
            return { variant: 'indigo', label: '🗳️ Vote terminé' };
        }
        return { variant: 'blue', label: '🗳️ Vote en cours' };
    }
    return { variant: 'green', label: '✅ Ouvert' };
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
};
</script>

<template>
    <Head title="Forum Citoyen" />

    <MainLayout title="Forum Citoyen">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            📝 Forum Citoyen
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Débats, propositions et votes citoyens
                        </p>
                    </div>
                    <Link v-if="$page.props.auth.user" :href="route('topics.create')">
                        <PrimaryButton>
                            ➕ Nouveau Sujet
                        </PrimaryButton>
                    </Link>
                </div>

                <!-- Filters -->
                <Card class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <TextInput
                                v-model="search"
                                type="text"
                                placeholder="🔍 Rechercher un sujet..."
                                class="w-full"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div>
                            <select 
                                v-model="scopeFilter"
                                @change="applyFilters"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            >
                                <option value="all">Toutes les portées</option>
                                <option value="national">🇫🇷 National</option>
                                <option value="regional">🗺️ Régional</option>
                                <option value="departmental">📍 Départemental</option>
                            </select>
                        </div>
                        <div>
                            <select 
                                v-model="typeFilter"
                                @change="applyFilters"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            >
                                <option value="all">Tous les types</option>
                                <option value="debate">💬 Débat</option>
                                <option value="proposal">💡 Proposition</option>
                                <option value="question">❓ Question</option>
                                <option value="announcement">📢 Annonce</option>
                            </select>
                        </div>
                    </div>
                    <div v-if="filters?.search || (filters?.scope && filters.scope !== 'all') || (filters?.type && filters.type !== 'all')" class="mt-4">
                        <button @click="clearFilters" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                            ✖ Effacer les filtres
                        </button>
                    </div>
                </Card>

                <!-- Topics List -->
                <div v-if="topics.data.length > 0" class="space-y-4">
                    <Card v-for="topic in topics.data" :key="topic.id" padding="p-6 hover:shadow-md transition-shadow">
                        <Link :href="route('topics.show', topic.id)" class="block">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <!-- Title & Status -->
                                    <div class="flex items-start gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ topic.title }}
                                        </h3>
                                        <Badge :variant="getStatusBadge(topic).variant">
                                            {{ getStatusBadge(topic).label }}
                                        </Badge>
                                    </div>

                                    <!-- Description -->
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                        {{ topic.description }}
                                    </p>

                                    <!-- Metadata -->
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <Badge :variant="topic.scope === 'national' ? 'blue' : topic.scope === 'regional' ? 'indigo' : 'gray'" size="sm">
                                            {{ getScopeLabel(topic.scope) }}
                                        </Badge>
                                        <Badge variant="gray" size="sm">
                                            {{ getTypeLabel(topic.type) }}
                                        </Badge>
                                        <span>👤 {{ topic.author?.name || 'Anonyme' }}</span>
                                        <span>📅 {{ formatDate(topic.created_at) }}</span>
                                        <span>💬 {{ topic.posts_count || 0 }} réponses</span>
                                        <span v-if="topic.ballot_type">🗳️ {{ topic.ballots_count || 0 }} votes</span>
                                    </div>
                                </div>

                                <!-- Arrow -->
                                <div class="flex-shrink-0 ml-4 text-gray-400 dark:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </Link>
                    </Card>

                    <!-- Pagination -->
                    <div class="mt-6">
                        <Pagination :links="topics.links" />
                    </div>
                </div>

                <!-- Empty State -->
                <Card v-else>
                    <EmptyState
                        icon="📭"
                        title="Aucun sujet trouvé"
                        description="Il n'y a pas encore de sujet correspondant à vos critères."
                    >
                        <Link v-if="$page.props.auth.user" :href="route('topics.create')">
                            <PrimaryButton>
                                ➕ Créer le premier sujet
                            </PrimaryButton>
                        </Link>
                    </EmptyState>
                </Card>
            </div>
        </div>
    </MainLayout>
</template>

