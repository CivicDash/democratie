<script setup>
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import ConfirmContainer from '@/Components/ConfirmContainer.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import BottomNav from '@/Components/BottomNav.vue';
import ScrollToTop from '@/Components/ScrollToTop.vue';
import AppFooter from '@/Components/AppFooter.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// ✅ Dark Mode Management - Switch between light and dark themes
const isDarkMode = ref(false);

// Initialiser le mode depuis localStorage ou préférence système
onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        isDarkMode.value = savedTheme === 'dark';
    } else {
        // Détecter la préférence système
        isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    applyTheme();
});

// Appliquer le thème
const applyTheme = () => {
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

// Toggle le mode
const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
    applyTheme();
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav
                class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 100%;">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <img src="/images/logo.png" alt="CivicDash" class="block h-10 w-auto" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-1 sm:-my-px sm:ms-10 sm:flex items-center">
                                <!-- Dashboard -->
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    🏠 Dashboard
                                </NavLink>
                                
                                <!-- Débats citoyens -->
                                <div class="relative">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                                :class="route().current('topics.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                            >
                                                💬 Débats
                                                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('topics.index')">
                                                📝 Forum Citoyen
                                            </DropdownLink>
                                            <DropdownLink :href="route('topics.trending')">
                                                🔥 Sujets Tendances
                                            </DropdownLink>
                                            <DropdownLink :href="route('topics.create')">
                                                ➕ Créer un Sujet
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                
                                <!-- Législation -->
                                <div class="relative">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                                :class="route().current('legislation.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                            >
                                                📋 Législation
                                                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('legislation.scrutins.index')">
                                                🗳️ Scrutins
                                            </DropdownLink>
                                            <DropdownLink :href="route('legislation.index')">
                                                📜 Dossiers législatifs
                                            </DropdownLink>
                                            <DropdownLink :href="route('tags.index')">
                                                🏷️ Explorer par thème
                                            </DropdownLink>
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                            <DropdownLink :href="route('legislation.groupes.index')">
                                                🎨 Groupes Parlementaires
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                
                                <!-- Parlement -->
                                <div class="relative">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                                :class="route().current('representants.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                            >
                                                🏛️ Parlement
                                                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('representants.mes-representants')">
                                                📍 Mes Représentants
                                            </DropdownLink>
                                            <DropdownLink :href="route('representants.deputes.index')">
                                                👥 Députés
                                            </DropdownLink>
                                            <DropdownLink :href="route('representants.senateurs.index')">
                                                🏰 Sénateurs
                                            </DropdownLink>
                                            <DropdownLink :href="route('representants.regions')">
                                                📍 Par région
                                            </DropdownLink>
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                            <DropdownLink :href="route('parlement.comparaison')">
                                                📊 Statistiques AN / Sénat
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                
                                <!-- Débat Citoyen (renommé) -->
                                <div class="relative">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                                :class="route().current('vote.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                            >
                                                🗨️ Débat Citoyen
                                                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('topics.index')">
                                                💬 Topics
                                            </DropdownLink>
                                            <DropdownLink :href="route('topics.index', {filter: 'ballot'})">
                                                🗳️ Votes citoyens
                                            </DropdownLink>
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                            <DropdownLink :href="route('topics.trending')">
                                                🔥 Tendances
                                            </DropdownLink>
                                            <DropdownLink :href="route('topics.create')">
                                                ➕ Créer un sujet
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                
                                <!-- Budget -->
                                <NavLink
                                    :href="route('budget.index')"
                                    :active="route().current('budget.*')"
                                >
                                    💰 Budget
                                </NavLink>
                                
                                <!-- Statistiques France -->
                                <NavLink
                                    :href="route('statistics.france')"
                                    :active="route().current('statistics.*')"
                                >
                                    📊 Statistiques France
                                </NavLink>
                                
                                <!-- Documents -->
                                <NavLink
                                    :href="route('documents.index')"
                                    :active="route().current('documents.*')"
                                >
                                    📄 Documents
                                </NavLink>
                                
                                <!-- Modération (si modérateur) -->
                                <NavLink
                                    v-if="$page.props.auth.user.roles?.includes('moderator') || $page.props.auth.user.roles?.includes('admin')"
                                    :href="route('moderation.dashboard')"
                                    :active="route().current('moderation.*')"
                                >
                                    🛡️ Modération
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center sm:space-x-2">
                            <!-- Dark Mode Toggle -->
                            <button
                                @click="toggleDarkMode"
                                type="button"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-400 dark:focus:bg-gray-700 dark:focus:text-gray-400"
                                :title="isDarkMode ? 'Passer en mode clair' : 'Passer en mode sombre'"
                            >
                                <!-- Icône Soleil (Light Mode) -->
                                <svg v-if="!isDarkMode" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <!-- Icône Lune (Dark Mode) -->
                                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                            
                            <!-- Notification Bell -->
                            <NotificationBell />
                            
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('profile.gamification')"
                                        >
                                            🏆 Succès Plateforme
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center gap-3 sm:hidden">
                            <!-- Dark Mode Toggle (Mobile) -->
                            <button
                                @click="toggleDarkMode"
                                type="button"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400 min-w-[44px] min-h-[44px]"
                            >
                                <!-- Icône Soleil (Light Mode) -->
                                <svg v-if="!isDarkMode" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <!-- Icône Lune (Dark Mode) -->
                                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                            
                            <!-- Mobile Notification Bell -->
                            <NotificationBell />
                            
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400 min-w-[44px] min-h-[44px]"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2 px-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            🏠 Dashboard
                        </ResponsiveNavLink>
                        
                        <!-- Débats -->
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            💬 Débats Citoyens
                        </div>
                        <ResponsiveNavLink
                            :href="route('topics.index')"
                            :active="route().current('topics.*')"
                        >
                            📝 Forum Citoyen
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('topics.trending')"
                            :active="route().current('topics.trending')"
                        >
                            🔥 Sujets Tendances
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('topics.create')"
                            :active="route().current('topics.create')"
                        >
                            ➕ Créer un Sujet
                        </ResponsiveNavLink>
                        
                        <!-- Législation -->
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            📋 Législation
                        </div>
                        <ResponsiveNavLink
                            :href="route('legislation.scrutins.index')"
                            :active="route().current('legislation.scrutins.*')"
                        >
                            🗳️ Scrutins
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('legislation.index')"
                            :active="route().current('legislation.index')"
                        >
                            📜 Dossiers législatifs
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('tags.index')"
                            :active="route().current('tags.*')"
                        >
                            🏷️ Explorer par thème
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('legislation.groupes.index')"
                            :active="route().current('legislation.groupes.*')"
                        >
                            🎨 Groupes
                        </ResponsiveNavLink>
                        
                        <!-- Parlement -->
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            🏛️ Parlement
                        </div>
                        <ResponsiveNavLink
                            :href="route('representants.mes-representants')"
                            :active="route().current('representants.mes-representants')"
                        >
                            📍 Mes Représentants
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('representants.deputes.index')"
                            :active="route().current('representants.deputes.*')"
                        >
                            👥 Députés
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('representants.senateurs.index')"
                            :active="route().current('representants.senateurs.*')"
                        >
                            🏰 Sénateurs
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('representants.regions')"
                            :active="route().current('representants.regions')"
                        >
                            🗺️ Par région
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('parlement.comparaison')"
                            :active="route().current('parlement.comparaison')"
                        >
                            📊 Statistiques AN / Sénat
                        </ResponsiveNavLink>
                        
                        <!-- Débat Citoyen -->
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            🗨️ Débat Citoyen
                        </div>
                        <ResponsiveNavLink
                            :href="route('topics.index')"
                            :active="route().current('topics.index')"
                        >
                            💬 Topics
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('topics.trending')"
                            :active="route().current('topics.trending')"
                        >
                            🔥 Tendances
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('topics.create')"
                            :active="route().current('topics.create')"
                        >
                            ➕ Créer
                        </ResponsiveNavLink>
                        
                        <!-- Autres sections -->
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            🔧 Autres
                        </div>
                        <ResponsiveNavLink
                            :href="route('budget.index')"
                            :active="route().current('budget.*')"
                        >
                            💰 Budget Participatif
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('statistics.france')"
                            :active="route().current('statistics.*')"
                        >
                            📊 Statistiques France
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('documents.index')"
                            :active="route().current('documents.*')"
                        >
                            📄 Documents
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600 px-2"
                    >
                        <div class="px-4 mb-3">
                            <div
                                class="text-base font-medium text-gray-800 dark:text-gray-200"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                👤 Mon profil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('profile.gamification')">
                                🎮 Gamification
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                🚪 Déconnexion
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow dark:bg-gray-800"
                v-if="$slots.header"
            >
                <div class="mx-auto px-4 py-6 sm:px-6 lg:px-8" style="max-width: 100%;">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
            
            <!-- Footer -->
            <AppFooter />
        </div>

        <!-- Bottom Navigation (Mobile only) -->
        <BottomNav />
        
        <!-- Scroll to Top (Mobile only) -->
        <ScrollToTop />
    </div>

    <!-- Global Toast Notifications -->
    <ToastContainer />

    <!-- Global Confirm Modals -->
    <ConfirmContainer />
</template>
