<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import ConfirmContainer from '@/Components/ConfirmContainer.vue';
import GamificationWidget from '@/Components/GamificationWidget.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import BottomNav from '@/Components/BottomNav.vue';
import ScrollToTop from '@/Components/ScrollToTop.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
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
                                                🏛️ Législation
                                                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('legislation.index')">
                                                📜 Propositions de Loi
                                            </DropdownLink>
                                            <DropdownLink :href="route('legislation.groupes.index')">
                                                🏛️ Groupes Parlementaires
                                            </DropdownLink>
                                            <DropdownLink :href="route('legislation.thematiques.index')">
                                                🎯 Thématiques
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                
                                <!-- Vote & Scrutin -->
                                <div class="relative">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                                :class="route().current('vote.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                            >
                                                🗳️ Votes
                                                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('topics.index', {filter: 'ballot'})">
                                                🗳️ Scrutins en Cours
                                            </DropdownLink>
                                            <DropdownLink :href="route('topics.index', {filter: 'closed'})">
                                                📊 Résultats de Votes
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
                            <!-- Gamification Widget (discret) -->
                            <div class="scale-75 origin-right opacity-80 hover:opacity-100 transition">
                                <GamificationWidget />
                            </div>
                            
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
                                            🎮 Gamification
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
                            🏛️ Législation
                        </div>
                        <ResponsiveNavLink
                            :href="route('legislation.index')"
                            :active="route().current('legislation.index')"
                        >
                            📜 Propositions de Loi
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('legislation.groupes.index')"
                            :active="route().current('legislation.groupes.*')"
                        >
                            🏛️ Groupes Parlementaires
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('legislation.thematiques.index')"
                            :active="route().current('legislation.thematiques.*')"
                        >
                            🎯 Thématiques
                        </ResponsiveNavLink>
                        
                        <!-- Autres sections -->
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            🗳️ Participation
                        </div>
                        <ResponsiveNavLink
                            :href="route('budget.index')"
                            :active="route().current('budget.*')"
                        >
                            💰 Budget Participatif
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('documents.index')"
                            :active="route().current('documents.*')"
                        >
                            📄 Documents
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('search.results')"
                            :active="route().current('search.*')"
                        >
                            🔍 Recherche
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
