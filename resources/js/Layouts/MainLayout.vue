<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const logout = () => {
    router.post(route('logout'));
};

// Vérifier si l'utilisateur est authentifié (depuis la page prop)
const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Navigation -->
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('home')">
                                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                        🏛️ CivicDash
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <Link :href="route('topics.index')" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out" :class="page.url.startsWith('/topics') ? 'border-indigo-600 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'">
                                    📝 Forum
                                </Link>
                                <Link :href="route('budget.index')" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out" :class="page.url.startsWith('/budget') ? 'border-indigo-600 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'">
                                    💰 Budget Participatif
                                </Link>
                                <Link :href="route('documents.index')" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out" :class="page.url.startsWith('/documents') ? 'border-indigo-600 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'">
                                    📄 Documents
                                </Link>
                            </div>
                        </div>

                        <!-- User Menu / Auth Links -->
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <div v-if="user" class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                                {{ user.name }}
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                                        <DropdownLink v-if="user.roles?.includes('moderator') || user.roles?.includes('admin')" :href="route('moderation.dashboard')">
                                            🚨 Modération
                                        </DropdownLink>
                                        <DropdownLink v-if="user.roles?.includes('admin')" :href="route('admin.dashboard')">
                                            👑 Administration
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Se déconnecter
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                            <div v-else class="space-x-4">
                                <Link :href="route('login')" class="text-sm text-gray-700 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                    Connexion
                                </Link>
                                <Link :href="route('register')" class="text-sm text-gray-700 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                    Inscription
                                </Link>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('topics.index')" :active="page.url.startsWith('/topics')">
                            📝 Forum
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('budget.index')" :active="page.url.startsWith('/budget')">
                            💰 Budget Participatif
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('documents.index')" :active="page.url.startsWith('/documents')">
                            📄 Documents
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div v-if="user" class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ user.name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Profil</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="user.roles?.includes('moderator') || user.roles?.includes('admin')" :href="route('moderation.dashboard')">
                                🚨 Modération
                            </ResponsiveNavLink>
                            <ResponsiveNavLink v-if="user.roles?.includes('admin')" :href="route('admin.dashboard')">
                                👑 Administration
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Se déconnecter
                            </ResponsiveNavLink>
                        </div>
                    </div>
                    <div v-else class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <ResponsiveNavLink :href="route('login')">Connexion</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('register')">Inscription</ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 py-8 mt-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">CivicDash</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Plateforme citoyenne de démocratie participative
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Liens</h3>
                            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <li><a href="/about" class="hover:text-indigo-600 dark:hover:text-indigo-400">À propos</a></li>
                                <li><a href="/privacy" class="hover:text-indigo-600 dark:hover:text-indigo-400">Confidentialité</a></li>
                                <li><a href="/terms" class="hover:text-indigo-600 dark:hover:text-indigo-400">Conditions d'utilisation</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Open Source</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Licence AGPL-3.0 • 
                                <a href="https://github.com/yourusername/civicdash" class="hover:text-indigo-600 dark:hover:text-indigo-400">GitHub</a>
                            </p>
                        </div>
                    </div>
                    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700 text-center text-sm text-gray-600 dark:text-gray-400">
                        © {{ new Date().getFullYear() }} CivicDash. Tous droits réservés.
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

