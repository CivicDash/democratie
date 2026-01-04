<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    users: Object,
    stats: Object,
    roles: Array,
    filters: Object,
});

const page = usePage();

const search = ref(props.filters?.search || '');
const selectedRole = ref(props.filters?.role || '');
const eluStatus = ref(props.filters?.elu_status || '');

const applyFilters = () => {
    router.get(route('admin.users.index'), {
        search: search.value || undefined,
        role: selectedRole.value || undefined,
        elu_status: eluStatus.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    selectedRole.value = '';
    eluStatus.value = '';
    router.get(route('admin.users.index'));
};

const changeRole = (user, newRole) => {
    if (confirm(`Changer le rôle de ${user.name} en ${newRole} ?`)) {
        router.post(route('admin.users.change-role', user.id), {
            role: newRole,
        });
    }
};

const getRoleBadgeClass = (role) => {
    return {
        'admin': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'moderator': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        'elu': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'citizen': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    }[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const breadcrumbs = [
    { label: 'Admin', url: route('admin.dashboard') },
    { label: 'Utilisateurs', url: null },
];
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Hero Banner -->
            <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" variant="light" class="mb-4" />
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Gestion des Utilisateurs</h1>
                            <p class="mt-2 text-indigo-100">Gérez les comptes, rôles et permissions</p>
                        </div>
                        <Link :href="route('admin.users.create')" 
                              class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition">
                            + Nouveau utilisateur
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mt-6">
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.total }}</div>
                            <div class="text-sm opacity-80">Total</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.citizens }}</div>
                            <div class="text-sm opacity-80">Citoyens</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.elus }}</div>
                            <div class="text-sm opacity-80">Élus</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.moderators }}</div>
                            <div class="text-sm opacity-80">Modérateurs</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.admins }}</div>
                            <div class="text-sm opacity-80">Admins</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.demo }}</div>
                            <div class="text-sm opacity-80">Démo</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Filtres -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher par nom ou email..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <select
                            v-model="selectedRole"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            @change="applyFilters"
                        >
                            <option value="">Tous les rôles</option>
                            <option v-for="role in roles" :key="role.name" :value="role.name">
                                {{ role.label }}
                            </option>
                        </select>
                        <select
                            v-model="eluStatus"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            @change="applyFilters"
                        >
                            <option value="">Statut élu</option>
                            <option value="verified">Élus vérifiés</option>
                            <option value="pending">En attente</option>
                        </select>
                        <button 
                            @click="applyFilters"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            Filtrer
                        </button>
                        <button 
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
                        >
                            Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Utilisateur
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Rôle
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Activité
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Inscription
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-indigo-400 to-purple-400 flex items-center justify-center text-white font-bold">
                                                    {{ user.name.charAt(0).toUpperCase() }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ user.name }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ user.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            getRoleBadgeClass(user.primary_role)
                                        ]">
                                            {{ user.primary_role_label }}
                                        </span>
                                        <span v-if="user.is_verified_elu" class="ml-1 text-blue-500" title="Élu vérifié">
                                            ✓
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <span v-if="user.is_demo" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                🔒 Démo (lecture seule)
                                            </span>
                                            <span v-if="user.email_verified_at" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                ✓ Email vérifié
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                ✗ Non vérifié
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <div>{{ user.topics_count }} sujets</div>
                                        <div>{{ user.posts_count }} posts</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ user.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('admin.users.show', user.id)" 
                                                  class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                Voir
                                            </Link>
                                            <Link :href="route('admin.users.edit', user.id)" 
                                                  class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                                                Modifier
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ users.from }} - {{ users.to }} sur {{ users.total }} utilisateurs
                            </div>
                            <nav class="flex gap-1">
                                <Link 
                                    v-for="link in users.links" 
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1 rounded text-sm',
                                        link.active 
                                            ? 'bg-indigo-600 text-white' 
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600',
                                        !link.url && 'opacity-50 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
