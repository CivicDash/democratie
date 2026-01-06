<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    stats: Object,
    bannedWords: Object,
    niceWords: Object,
    recentLogs: Array,
    categories: Object,
    severities: Object,
    niceCategories: Object,
    filters: Object,
});

// Filtres de recherche
const searchBanned = ref(props.filters?.search_banned || '');
const filterCategory = ref(props.filters?.category || '');
const filterSeverity = ref(props.filters?.severity || '');
const searchNice = ref(props.filters?.search_nice || '');
const filterNiceCategory = ref(props.filters?.nice_category || '');

// Debounce pour la recherche
let searchTimeout = null;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.moderation.words'), {
            search_banned: searchBanned.value || undefined,
            category: filterCategory.value || undefined,
            severity: filterSeverity.value || undefined,
            search_nice: searchNice.value || undefined,
            nice_category: filterNiceCategory.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

const clearBannedFilters = () => {
    searchBanned.value = '';
    filterCategory.value = '';
    filterSeverity.value = '';
    applyFilters();
};

const clearNiceFilters = () => {
    searchNice.value = '';
    filterNiceCategory.value = '';
    applyFilters();
};

// Navigation de pagination
const goToPage = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};

const activeTab = ref('banned'); // banned, nice, logs, test

// Modal ajout mot banni
const showAddBannedModal = ref(false);
const bannedForm = useForm({
    word: '',
    category: 'insulte',
    severity: 'medium',
    is_regex: false,
    notes: '',
});

const submitBanned = () => {
    bannedForm.post(route('admin.moderation.banned.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddBannedModal.value = false;
            bannedForm.reset();
        },
    });
};

// Modal ajout mot gentil
const showAddNiceModal = ref(false);
const niceForm = useForm({
    word: '',
    category: 'compliment',
});

const submitNice = () => {
    niceForm.post(route('admin.moderation.nice.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddNiceModal.value = false;
            niceForm.reset();
        },
    });
};

// Test de modération
const testContent = ref('');
const testResult = ref(null);
const testLoading = ref(false);

const testModeration = async () => {
    if (!testContent.value) return;
    testLoading.value = true;
    
    try {
        const response = await fetch(route('admin.moderation.test'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ content: testContent.value }),
        });
        testResult.value = await response.json();
    } catch (error) {
        console.error(error);
    } finally {
        testLoading.value = false;
    }
};

// Supprimer un mot banni
const deleteBanned = (id) => {
    if (confirm('Supprimer ce mot banni ?')) {
        router.delete(route('admin.moderation.banned.destroy', id), {
            preserveScroll: true,
        });
    }
};

// Supprimer un mot gentil
const deleteNice = (id) => {
    if (confirm('Supprimer ce mot gentil ?')) {
        router.delete(route('admin.moderation.nice.destroy', id), {
            preserveScroll: true,
        });
    }
};

// Seed les mots par défaut
const seedDefaults = () => {
    if (confirm('Initialiser avec les mots par défaut ?')) {
        router.post(route('admin.moderation.seed'), {}, {
            preserveScroll: true,
        });
    }
};

// Couleurs par sévérité
const severityColors = {
    low: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    medium: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    high: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

// Couleurs par catégorie
const categoryColors = {
    insulte: 'bg-red-100 text-red-700',
    spam: 'bg-gray-100 text-gray-700',
    politique_extreme: 'bg-purple-100 text-purple-700',
    sexisme: 'bg-pink-100 text-pink-700',
    racisme: 'bg-black text-white',
    violence: 'bg-red-200 text-red-800',
    general: 'bg-blue-100 text-blue-700',
};
</script>

<template>
    <Head title="Modération - Mots" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    🛡️ Modération - Gestion des mots
                </h2>
                <button
                    @click="seedDefaults"
                    class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                >
                    🌱 Initialiser par défaut
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-red-600">{{ stats.total_banned_words }}</div>
                        <div class="text-sm text-gray-500">Mots bannis</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-emerald-600">{{ stats.total_nice_words }}</div>
                        <div class="text-sm text-gray-500">Mots gentils</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-amber-600">{{ stats.replacements_today }}</div>
                        <div class="text-sm text-gray-500">Remplacements aujourd'hui</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-indigo-600">{{ stats.replacements_week }}</div>
                        <div class="text-sm text-gray-500">Cette semaine</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow mb-6">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex -mb-px">
                            <button
                                @click="activeTab = 'banned'"
                                :class="[
                                    'px-6 py-3 text-sm font-medium border-b-2 transition',
                                    activeTab === 'banned'
                                        ? 'border-red-500 text-red-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                🚫 Mots bannis ({{ bannedWords.total }})
                            </button>
                            <button
                                @click="activeTab = 'nice'"
                                :class="[
                                    'px-6 py-3 text-sm font-medium border-b-2 transition',
                                    activeTab === 'nice'
                                        ? 'border-emerald-500 text-emerald-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                💖 Mots gentils ({{ niceWords.total }})
                            </button>
                            <button
                                @click="activeTab = 'logs'"
                                :class="[
                                    'px-6 py-3 text-sm font-medium border-b-2 transition',
                                    activeTab === 'logs'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                📋 Historique
                            </button>
                            <button
                                @click="activeTab = 'test'"
                                :class="[
                                    'px-6 py-3 text-sm font-medium border-b-2 transition',
                                    activeTab === 'test'
                                        ? 'border-amber-500 text-amber-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                🧪 Tester
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Tab: Mots bannis -->
                        <div v-if="activeTab === 'banned'">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Liste des mots bannis
                                </h3>
                                <button
                                    @click="showAddBannedModal = true"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                                >
                                    + Ajouter
                                </button>
                            </div>

                            <!-- Barre de recherche et filtres -->
                            <div class="flex flex-col md:flex-row gap-3 mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex-1">
                                    <input
                                        v-model="searchBanned"
                                        @input="applyFilters"
                                        type="text"
                                        placeholder="🔍 Rechercher un mot..."
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500"
                                    />
                                </div>
                                <select
                                    v-model="filterCategory"
                                    @change="applyFilters"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="">Toutes catégories</option>
                                    <option v-for="(label, key) in categories" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                                <select
                                    v-model="filterSeverity"
                                    @change="applyFilters"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="">Toutes sévérités</option>
                                    <option v-for="(label, key) in severities" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                                <button
                                    v-if="searchBanned || filterCategory || filterSeverity"
                                    @click="clearBannedFilters"
                                    class="px-3 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    ✕ Effacer
                                </button>
                            </div>

                            <!-- Infos pagination -->
                            <div class="text-sm text-gray-500 mb-2">
                                Affichage de {{ bannedWords.from || 0 }} à {{ bannedWords.to || 0 }} sur {{ bannedWords.total }} mots
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mot</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sévérité</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actif</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="word in bannedWords.data" :key="word.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-4 py-3 font-mono text-sm">{{ word.word }}</td>
                                            <td class="px-4 py-3">
                                                <span :class="['px-2 py-1 text-xs rounded', categoryColors[word.category] || 'bg-gray-100']">
                                                    {{ categories[word.category] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span :class="['px-2 py-1 text-xs rounded', severityColors[word.severity]]">
                                                    {{ severities[word.severity] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span :class="word.is_active ? 'text-emerald-500' : 'text-gray-400'">
                                                    {{ word.is_active ? '✅' : '❌' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    @click="deleteBanned(word.id)"
                                                    class="text-red-600 hover:text-red-800"
                                                >
                                                    🗑️
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div v-if="bannedWords.last_page > 1" class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="text-sm text-gray-500">
                                    Page {{ bannedWords.current_page }} sur {{ bannedWords.last_page }}
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="goToPage(bannedWords.first_page_url)"
                                        :disabled="!bannedWords.prev_page_url"
                                        class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        ⏮️
                                    </button>
                                    <button
                                        @click="goToPage(bannedWords.prev_page_url)"
                                        :disabled="!bannedWords.prev_page_url"
                                        class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        ◀️ Précédent
                                    </button>
                                    <button
                                        @click="goToPage(bannedWords.next_page_url)"
                                        :disabled="!bannedWords.next_page_url"
                                        class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        Suivant ▶️
                                    </button>
                                    <button
                                        @click="goToPage(bannedWords.last_page_url)"
                                        :disabled="!bannedWords.next_page_url"
                                        class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        ⏭️
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Mots gentils -->
                        <div v-if="activeTab === 'nice'">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Liste des mots gentils de remplacement
                                </h3>
                                <button
                                    @click="showAddNiceModal = true"
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
                                >
                                    + Ajouter 💖
                                </button>
                            </div>

                            <!-- Barre de recherche et filtres -->
                            <div class="flex flex-col md:flex-row gap-3 mb-4 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                <div class="flex-1">
                                    <input
                                        v-model="searchNice"
                                        @input="applyFilters"
                                        type="text"
                                        placeholder="🔍 Rechercher un mot gentil..."
                                        class="w-full px-4 py-2 border border-pink-300 dark:border-pink-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-pink-500"
                                    />
                                </div>
                                <select
                                    v-model="filterNiceCategory"
                                    @change="applyFilters"
                                    class="px-3 py-2 border border-pink-300 dark:border-pink-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="">Toutes catégories</option>
                                    <option v-for="(label, key) in niceCategories" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                                <button
                                    v-if="searchNice || filterNiceCategory"
                                    @click="clearNiceFilters"
                                    class="px-3 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    ✕ Effacer
                                </button>
                            </div>

                            <!-- Infos pagination -->
                            <div class="text-sm text-gray-500 mb-2">
                                Affichage de {{ niceWords.from || 0 }} à {{ niceWords.to || 0 }} sur {{ niceWords.total }} mots
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                <div
                                    v-for="word in niceWords.data"
                                    :key="word.id"
                                    class="group relative p-3 bg-gradient-to-br from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 rounded-lg border border-pink-200 dark:border-pink-800"
                                >
                                    <div class="text-center text-lg">{{ word.word }}</div>
                                    <div class="text-center text-xs text-gray-500 mt-1">{{ niceCategories[word.category] }}</div>
                                    <button
                                        @click="deleteNice(word.id)"
                                        class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="niceWords.last_page > 1" class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="text-sm text-gray-500">
                                    Page {{ niceWords.current_page }} sur {{ niceWords.last_page }}
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="goToPage(niceWords.first_page_url)"
                                        :disabled="!niceWords.prev_page_url"
                                        class="px-3 py-1 rounded border border-pink-300 dark:border-pink-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-pink-100 dark:hover:bg-pink-900/30"
                                    >
                                        ⏮️
                                    </button>
                                    <button
                                        @click="goToPage(niceWords.prev_page_url)"
                                        :disabled="!niceWords.prev_page_url"
                                        class="px-3 py-1 rounded border border-pink-300 dark:border-pink-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-pink-100 dark:hover:bg-pink-900/30"
                                    >
                                        ◀️ Précédent
                                    </button>
                                    <button
                                        @click="goToPage(niceWords.next_page_url)"
                                        :disabled="!niceWords.next_page_url"
                                        class="px-3 py-1 rounded border border-pink-300 dark:border-pink-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-pink-100 dark:hover:bg-pink-900/30"
                                    >
                                        Suivant ▶️
                                    </button>
                                    <button
                                        @click="goToPage(niceWords.last_page_url)"
                                        :disabled="!niceWords.next_page_url"
                                        class="px-3 py-1 rounded border border-pink-300 dark:border-pink-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-pink-100 dark:hover:bg-pink-900/30"
                                    >
                                        ⏭️
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Historique -->
                        <div v-if="activeTab === 'logs'">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Derniers remplacements
                            </h3>

                            <div class="space-y-2">
                                <div
                                    v-for="log in recentLogs"
                                    :key="log.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                >
                                    <div class="flex items-center gap-4">
                                        <span class="text-red-500 line-through font-mono">{{ log.original_word }}</span>
                                        <span class="text-gray-400">→</span>
                                        <span class="text-emerald-500 font-medium">{{ log.replacement_word }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ new Date(log.created_at).toLocaleString('fr-FR') }}
                                    </div>
                                </div>

                                <div v-if="recentLogs.length === 0" class="text-center text-gray-500 py-8">
                                    Aucun remplacement récent
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Tester -->
                        <div v-if="activeTab === 'test'">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                🧪 Tester la modération
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="test-content" value="Texte à tester" />
                                    <textarea
                                        id="test-content"
                                        v-model="testContent"
                                        rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Entrez un texte pour tester la modération..."
                                    ></textarea>
                                </div>

                                <button
                                    @click="testModeration"
                                    :disabled="testLoading || !testContent"
                                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition disabled:opacity-50"
                                >
                                    {{ testLoading ? 'Analyse...' : '🔍 Analyser' }}
                                </button>

                                <div v-if="testResult" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-3">
                                    <div class="flex items-center gap-2">
                                        <span :class="testResult.modified ? 'text-amber-500' : 'text-emerald-500'">
                                            {{ testResult.modified ? '⚠️ Modifié' : '✅ Aucun problème' }}
                                        </span>
                                        <span v-if="testResult.blocked" class="text-red-500">🚫 Contenu bloqué</span>
                                    </div>

                                    <div v-if="testResult.replacements > 0">
                                        <div class="text-sm text-gray-500 mb-2">{{ testResult.replacements }} remplacement(s)</div>
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded border">
                                            <div class="text-xs text-gray-500 mb-1">Original :</div>
                                            <div class="text-red-500 line-through">{{ testResult.original }}</div>
                                        </div>
                                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded border border-emerald-200 mt-2">
                                            <div class="text-xs text-gray-500 mb-1">Résultat :</div>
                                            <div class="text-emerald-700 dark:text-emerald-400">{{ testResult.content }}</div>
                                        </div>
                                    </div>

                                    <div v-if="testResult.details.length > 0" class="text-sm">
                                        <div class="text-gray-500 mb-1">Détails :</div>
                                        <ul class="list-disc list-inside space-y-1">
                                            <li v-for="(detail, i) in testResult.details" :key="i">
                                                <span class="text-red-500">"{{ detail.original }}"</span>
                                                → <span class="text-emerald-500">"{{ detail.replacement }}"</span>
                                                <span class="text-gray-400">({{ detail.category }})</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Ajouter mot banni -->
        <Modal :show="showAddBannedModal" @close="showAddBannedModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    🚫 Ajouter un mot banni
                </h2>

                <form @submit.prevent="submitBanned" class="space-y-4">
                    <div>
                        <InputLabel for="banned-word" value="Mot ou expression" />
                        <TextInput
                            id="banned-word"
                            v-model="bannedForm.word"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="bannedForm.errors.word" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="banned-category" value="Catégorie" />
                            <select
                                id="banned-category"
                                v-model="bannedForm.category"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option v-for="(label, key) in categories" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="banned-severity" value="Sévérité" />
                            <select
                                id="banned-severity"
                                v-model="bannedForm.severity"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option v-for="(label, key) in severities" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="banned-notes" value="Notes (optionnel)" />
                        <textarea
                            id="banned-notes"
                            v-model="bannedForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showAddBannedModal = false"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                        >
                            Annuler
                        </button>
                        <PrimaryButton :disabled="bannedForm.processing">
                            Ajouter
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal: Ajouter mot gentil -->
        <Modal :show="showAddNiceModal" @close="showAddNiceModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    💖 Ajouter un mot gentil
                </h2>

                <form @submit.prevent="submitNice" class="space-y-4">
                    <div>
                        <InputLabel for="nice-word" value="Mot ou emoji" />
                        <TextInput
                            id="nice-word"
                            v-model="niceForm.word"
                            type="text"
                            class="mt-1 block w-full text-2xl text-center"
                            placeholder="🌈 ou petit chaton"
                            required
                        />
                        <InputError :message="niceForm.errors.word" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="nice-category" value="Catégorie" />
                        <select
                            id="nice-category"
                            v-model="niceForm.category"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                        >
                            <option v-for="(label, key) in niceCategories" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showAddNiceModal = false"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="niceForm.processing"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Ajouter 💖
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
