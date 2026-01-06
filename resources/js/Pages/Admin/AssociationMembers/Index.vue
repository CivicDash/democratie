<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

// Simple debounce function
const debounce = (fn, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    members: Object,
    stats: Object,
    filters: Object,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Administration', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Membres Civis-Consilium', icon: '🎖️' },
];

// Recherche
const searchQuery = ref(props.filters?.search || '');
const showAddModal = ref(false);
const searchResults = ref([]);
const searchLoading = ref(false);
const addMemberForm = useForm({
    user_id: null,
    member_id: '',
});

// Recherche avec debounce
const updateSearch = debounce((value) => {
    router.get(route('admin.association.index'), {
        search: value,
        status: props.filters?.status,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(searchQuery, (value) => {
    updateSearch(value);
});

// Recherche d'utilisateurs à ajouter
const searchUsers = async (query) => {
    if (query.length < 2) {
        searchResults.value = [];
        return;
    }
    
    searchLoading.value = true;
    try {
        const response = await fetch(`/admin/association/search-users?q=${encodeURIComponent(query)}`);
        searchResults.value = await response.json();
    } catch (e) {
        console.error(e);
    } finally {
        searchLoading.value = false;
    }
};

const userSearchQuery = ref('');
watch(userSearchQuery, debounce((val) => searchUsers(val), 300));

const selectUser = (user) => {
    addMemberForm.user_id = user.id;
    userSearchQuery.value = user.name;
    searchResults.value = [];
};

const addMember = () => {
    addMemberForm.post(route('admin.association.add-member', addMemberForm.user_id), {
        onSuccess: () => {
            showAddModal.value = false;
            addMemberForm.reset();
            userSearchQuery.value = '';
        },
    });
};

const removeMember = (userId, userName) => {
    if (confirm(`Retirer ${userName} de l'association ?`)) {
        router.delete(route('admin.association.remove-member', userId));
    }
};
</script>

<template>
    <Head title="Membres Civis-Consilium" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 flex items-center gap-3">
                            🎖️ Membres Civis-Consilium
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">
                            Liez les utilisateurs à leur ID Dolibarr après validation de leur adhésion
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a
                            :href="route('admin.association.export')"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2"
                        >
                            📥 Export CSV
                        </a>
                        <button
                            @click="showAddModal = true"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                        >
                            ➕ Ajouter un membre
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center">
                        <span class="text-3xl">👥</span>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.total_members }}</p>
                        <p class="text-sm text-gray-500">Membres total</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center">
                        <span class="text-3xl">📸</span>
                        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_photos }}</p>
                        <p class="text-sm text-gray-500">Photos à valider</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center">
                        <span class="text-3xl">🆕</span>
                        <p class="text-2xl font-bold text-green-600">{{ stats.new_this_month }}</p>
                        <p class="text-sm text-gray-500">Ce mois</p>
                    </div>
                </div>

                <!-- Recherche -->
                <div class="mb-6">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Rechercher par nom, email, pseudo, ID membre..."
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <!-- Liste -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Membre</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ID Membre</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Membre depuis</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Photo</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="member in members.data" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="member.photo_url"
                                            :src="member.photo_url"
                                            class="w-10 h-10 rounded-full object-cover"
                                        />
                                        <div v-else class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg">
                                            👤
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ member.name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">@{{ member.username || 'sans pseudo' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ member.email }}
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="member.association_member_id" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-sm rounded">
                                        {{ member.association_member_id }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">-</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ member.association_member_since || '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="member.photo_status === 'pending'" class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded">⏳ En attente</span>
                                    <span v-else-if="member.photo_status === 'approved'" class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">✓ Validée</span>
                                    <span v-else-if="member.photo_status === 'rejected'" class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded">✕ Refusée</span>
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        v-if="member.is_association_member"
                                        @click="removeMember(member.id, member.name)"
                                        class="text-red-600 hover:text-red-700 text-sm"
                                    >
                                        Retirer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="members.links.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2">
                        <Link
                            v-for="link in members.links"
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'px-3 py-1 rounded text-sm',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : link.url
                                        ? 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'
                                        : 'text-gray-400 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal ajout membre -->
        <Teleport to="body">
            <div
                v-if="showAddModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="showAddModal = false"
            >
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                            ➕ Ajouter un membre
                        </h3>
                    </div>

                    <div class="p-4 space-y-4">
                        <!-- Recherche utilisateur -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Rechercher un utilisateur
                            </label>
                            <input
                                v-model="userSearchQuery"
                                type="text"
                                placeholder="Nom, email ou pseudo..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                            />
                            
                            <!-- Résultats -->
                            <div v-if="searchResults.length" class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <button
                                    v-for="user in searchResults"
                                    :key="user.id"
                                    @click="selectUser(user)"
                                    class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 text-left"
                                >
                                    <img v-if="user.photo_url" :src="user.photo_url" class="w-8 h-8 rounded-full" />
                                    <div v-else class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">👤</div>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ user.name }}</p>
                                        <p class="text-xs text-gray-500">{{ user.email }}</p>
                                    </div>
                                </button>
                            </div>
                            <p v-if="searchLoading" class="text-sm text-gray-500 mt-2">Recherche...</p>
                        </div>

                        <!-- ID Membre Dolibarr (obligatoire) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ID Membre Dolibarr <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="addMemberForm.member_id"
                                type="text"
                                required
                                placeholder="Ex: MBR-2026-001"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                                :class="{ 'border-red-500': addMemberForm.errors.member_id }"
                            />
                            <p v-if="addMemberForm.errors.member_id" class="text-sm text-red-500 mt-1">
                                {{ addMemberForm.errors.member_id }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                💡 L'ID provient de Dolibarr après validation de l'adhésion
                            </p>
                        </div>
                        
                        <!-- Info box -->
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>📋 Processus :</strong><br/>
                                1. L'utilisateur adhère via Dolibarr<br/>
                                2. Dolibarr valide et génère un ID membre<br/>
                                3. Vous liez l'utilisateur CivicDash à cet ID ici
                            </p>
                        </div>
                    </div>

                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                        <button
                            @click="showAddModal = false"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            Annuler
                        </button>
                        <button
                            @click="addMember"
                            :disabled="!addMemberForm.user_id || !addMemberForm.member_id || addMemberForm.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ addMemberForm.processing ? 'Ajout...' : 'Ajouter' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
