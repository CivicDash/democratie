<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import ToastContainer from "@/Components/ToastContainer.vue";
import ConfirmContainer from "@/Components/ConfirmContainer.vue";
import NotificationBell from "@/Components/NotificationBell.vue";
import BottomNav from "@/Components/BottomNav.vue";
import ScrollToTop from "@/Components/ScrollToTop.vue";
import AppFooter from "@/Components/AppFooter.vue";
import MegaMenuLink from "@/Components/MegaMenuLink.vue";
import CommandPalette from "@/Components/CommandPalette.vue";
import KeyboardShortcutsHelp from "@/Components/KeyboardShortcutsHelp.vue";
import { Link, router } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);
const showMobileSearch = ref(false);
const searchQuery = ref("");
const showCommandPalette = ref(false);
const showKeyboardHelp = ref(false);

// Dark Mode Management
const isDarkMode = ref(false);

const applyTheme = () => {
    if (isDarkMode.value) {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }
};

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem("theme", isDarkMode.value ? "dark" : "light");
    applyTheme();
};

// Recherche rapide
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.visit(route("search.results", { q: searchQuery.value }));
        searchQuery.value = "";
        showMobileSearch.value = false;
    }
};

// Sections du menu mobile (accordéon)
const expandedSection = ref(null);
const toggleSection = (section) => {
    expandedSection.value = expandedSection.value === section ? null : section;
};

// Référence pour le champ de recherche desktop
const searchInputDesktop = ref(null);

// Raccourcis clavier
const handleKeyboardShortcuts = (e) => {
    // Ignorer si on est dans un champ de saisie (sauf pour certains raccourcis)
    const isInputFocused = ["INPUT", "TEXTAREA", "SELECT"].includes(document.activeElement?.tagName);
    
    // Ctrl+K ou Cmd+K : Ouvrir la CommandPalette
    if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        showCommandPalette.value = true;
        return;
    }

    // Échap : Fermer tout
    if (e.key === "Escape") {
        showCommandPalette.value = false;
        showKeyboardHelp.value = false;
        showingNavigationDropdown.value = false;
        showMobileSearch.value = false;
        expandedSection.value = null;
        document.activeElement?.blur();
        return;
    }

    // Ne pas traiter les autres raccourcis si on est dans un input
    if (isInputFocused) return;

    // / : Ouvrir la CommandPalette
    if (e.key === "/") {
        e.preventDefault();
        showCommandPalette.value = true;
        return;
    }

    // ? : Afficher l'aide des raccourcis
    if (e.key === "?" || (e.shiftKey && e.key === "/")) {
        e.preventDefault();
        showKeyboardHelp.value = !showKeyboardHelp.value;
        return;
    }

    // Séquences G + lettre (style GitHub)
    if (e.key === "g") {
        window._lastKeyG = Date.now();
        return;
    }

    if (window._lastKeyG && Date.now() - window._lastKeyG < 500) {
        window._lastKeyG = null;
        switch (e.key.toLowerCase()) {
            case "h":
                e.preventDefault();
                router.visit(route("dashboard"));
                break;
            case "d":
                e.preventDefault();
                router.visit(route("representants.deputes.index"));
                break;
            case "s":
                e.preventDefault();
                router.visit(route("representants.senateurs.index"));
                break;
            case "l":
                e.preventDefault();
                router.visit(route("legislation.index"));
                break;
        }
    }
};

onMounted(() => {
    // Dark mode init
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
        isDarkMode.value = savedTheme === "dark";
    } else {
        isDarkMode.value = window.matchMedia("(prefers-color-scheme: dark)").matches;
    }
    applyTheme();
    
    // Écouter les raccourcis clavier
    window.addEventListener("keydown", handleKeyboardShortcuts);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyboardShortcuts);
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800 sticky top-0 z-40">
                <div class="mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 100%;">
                    <div class="flex h-16 justify-between">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <img src="/images/logo.png" alt="CivicDash" class="block h-10 w-auto" />
                                </Link>
                            </div>

                            <!-- Navigation Links - Desktop -->
                            <div class="hidden lg:flex lg:items-center lg:ms-8 lg:space-x-1">
                                <!-- Dashboard -->
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    🏠 Accueil
                                </NavLink>
                                
                                <!-- PARLEMENT -->
                                <Dropdown align="left" width="80">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('representants.*') || route().current('parlement.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            🏛️ Parlement
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-2">
                                            <MegaMenuLink
                                                :href="route('representants.mes-representants')"
                                                icon="📍"
                                                title="Mes Représentants"
                                                description="Trouvez vos élus par code postal"
                                            />
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
                                            <MegaMenuLink
                                                :href="route('representants.deputes.index')"
                                                icon="👥"
                                                title="Députés"
                                                description="Assemblée Nationale"
                                                badge="577"
                                                badge-color="indigo"
                                            />
                                            <MegaMenuLink
                                                :href="route('representants.senateurs.index')"
                                                icon="🏰"
                                                title="Sénateurs"
                                                description="Sénat de la République"
                                                badge="348"
                                                badge-color="indigo"
                                            />
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
                                            <MegaMenuLink
                                                :href="route('legislation.groupes.index')"
                                                icon="🎨"
                                                title="Groupes Parlementaires"
                                                description="Répartition politique"
                                            />
                                            <MegaMenuLink
                                                :href="route('parlement.comparaison')"
                                                icon="📊"
                                                title="Comparaison AN / Sénat"
                                                description="Visualisez les deux chambres"
                                            />
                                            <MegaMenuLink
                                                :href="route('parlement.calendrier.index')"
                                                icon="📅"
                                                title="Calendrier Législatif"
                                                description="Agenda des réunions AN"
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- LÉGISLATION -->
                                <Dropdown align="left" width="80">
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
                                        <div class="p-2">
                                            <MegaMenuLink
                                                :href="route('legislation.scrutins.index')"
                                                icon="🗳️"
                                                title="Scrutins Publics"
                                                description="Votes de l'Assemblée et du Sénat"
                                            />
                                            <MegaMenuLink
                                                :href="route('legislation.index')"
                                                icon="📜"
                                                title="Dossiers Législatifs"
                                                description="Textes de loi en cours et adoptés"
                                            />
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
                                            <MegaMenuLink
                                                :href="route('tags.index')"
                                                icon="🏷️"
                                                title="Explorer par Thème"
                                                description="Santé, Éducation, Environnement..."
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- PARTICIPATION -->
                                <Dropdown align="left" width="80">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('topics.*') || route().current('budget.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            💬 Participation
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-2">
                                            <MegaMenuLink
                                                :href="route('topics.index')"
                                                icon="📝"
                                                title="Forum Citoyen"
                                                description="Débats et discussions"
                                            />
                                            <MegaMenuLink
                                                :href="route('topics.trending')"
                                                icon="🔥"
                                                title="Sujets Tendances"
                                                description="Les plus populaires"
                                                badge="Hot"
                                                badge-color="red"
                                            />
                                            <MegaMenuLink
                                                :href="route('topics.create')"
                                                icon="➕"
                                                title="Créer un Débat"
                                                description="Lancez une discussion"
                                            />
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
                                            <MegaMenuLink
                                                :href="route('budget.index')"
                                                icon="💰"
                                                title="Budget Participatif"
                                                description="Répartissez le budget de l'État"
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- DONNÉES -->
                                <Dropdown align="left" width="56">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('statistics.*') || route().current('documents.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            📊 Données
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('statistics.france')">
                                            🗺️ Statistiques France
                                        </DropdownLink>
                                        <DropdownLink :href="route('documents.index')">
                                            📄 Documents Publics
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                                
                                <!-- Modération (si modérateur/admin) -->
                                <NavLink
                                    v-if="$page.props.auth.user.roles?.includes('moderator') || $page.props.auth.user.roles?.includes('admin')"
                                    :href="route('moderation.dashboard')"
                                    :active="route().current('moderation.*')"
                                >
                                    🛡️ Modération
                                </NavLink>
                            </div>
                        </div>

                        <!-- Right side: Search + Actions -->
                        <div class="hidden lg:flex lg:items-center lg:space-x-4">
                            <!-- Search Bar Desktop -->
                            <div class="relative">
                                <form @submit.prevent="handleSearch" class="flex items-center">
                                    <div class="relative">
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Rechercher..."
                                            class="w-48 xl:w-64 pl-10 pr-16 py-2 text-sm bg-gray-100 dark:bg-gray-700 border-0 rounded-full focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-all"
                                        />
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <!-- Raccourci clavier -->
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <kbd class="hidden xl:inline-flex items-center px-1.5 py-0.5 text-xs font-medium text-gray-400 dark:text-gray-500 bg-gray-200 dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500">
                                                ⌘K
                                            </kbd>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Dark Mode Toggle -->
                            <button
                                @click="toggleDarkMode"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                                :title="isDarkMode ? 'Mode clair' : 'Mode sombre'"
                            >
                                <svg v-if="!isDarkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                            
                            <!-- Notification Bell -->
                            <NotificationBell />
                            
                            <!-- User Dropdown -->
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-full border border-transparent bg-gray-100 dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 transition hover:bg-gray-200 dark:hover:bg-gray-600"
                                    >
                                        <span class="hidden sm:inline">{{ $page.props.auth.user.name }}</span>
                                        <span class="sm:hidden">👤</span>
                                        <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        👤 Mon Profil
                                    </DropdownLink>
                                    <DropdownLink :href="route('profile.gamification')">
                                        🏆 Mes Succès
                                    </DropdownLink>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        🚪 Déconnexion
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Mobile: Right actions -->
                        <div class="flex items-center gap-2 lg:hidden">
                            <!-- Search toggle mobile -->
                            <button
                                @click="showMobileSearch = !showMobileSearch"
                                class="inline-flex items-center justify-center rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                            
                            <!-- Dark Mode Toggle Mobile -->
                            <button
                                @click="toggleDarkMode"
                                class="inline-flex items-center justify-center rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <svg v-if="!isDarkMode" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                            
                            <NotificationBell />
                            
                            <!-- Hamburger -->
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Search Bar (expandable) -->
                <div v-if="showMobileSearch" class="lg:hidden border-t border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-800">
                    <form @submit.prevent="handleSearch" class="flex gap-2">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher un député, scrutin, dossier..."
                            class="flex-1 pl-4 pr-4 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-full focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-gray-100"
                            autofocus
                        />
                        <button
                            type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-full text-sm font-medium hover:bg-indigo-700"
                        >
                            🔍
                        </button>
                    </form>
                </div>

                <!-- Responsive Navigation Menu (Accordion Style) -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="lg:hidden border-t border-gray-200 dark:border-gray-700"
                >
                    <div class="space-y-1 pb-3 pt-2 px-3">
                        <!-- Dashboard -->
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            🏠 Accueil
                        </ResponsiveNavLink>
                        
                        <!-- PARLEMENT Section -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2 mt-2">
                            <button
                                @click="toggleSection('parlement')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                <span>🏛️ Parlement</span>
                                <svg :class="{ 'rotate-180': expandedSection === 'parlement' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'parlement'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('representants.mes-representants')">📍 Mes Représentants</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('representants.deputes.index')">👥 Députés</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('representants.senateurs.index')">🏰 Sénateurs</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('legislation.groupes.index')">🎨 Groupes</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('parlement.comparaison')">📊 Comparaison</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('parlement.calendrier.index')">📅 Calendrier</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- LÉGISLATION Section -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('legislation')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                <span>📋 Législation</span>
                                <svg :class="{ 'rotate-180': expandedSection === 'legislation' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'legislation'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('legislation.scrutins.index')">🗳️ Scrutins</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('legislation.index')">📜 Dossiers</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('tags.index')">🏷️ Thèmes</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- PARTICIPATION Section -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('participation')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                <span>💬 Participation</span>
                                <svg :class="{ 'rotate-180': expandedSection === 'participation' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'participation'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('topics.index')">📝 Forum Citoyen</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('topics.trending')">🔥 Tendances</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('topics.create')">➕ Créer un Débat</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('budget.index')">💰 Budget Participatif</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- DONNÉES Section -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('donnees')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                <span>📊 Données</span>
                                <svg :class="{ 'rotate-180': expandedSection === 'donnees' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'donnees'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('statistics.france')">🗺️ Statistiques France</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('documents.index')">📄 Documents</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- Modération (if moderator/admin) -->
                        <div
                            v-if="$page.props.auth.user.roles?.includes('moderator') || $page.props.auth.user.roles?.includes('admin')"
                            class="border-t border-gray-100 dark:border-gray-700 pt-2"
                        >
                            <ResponsiveNavLink :href="route('moderation.dashboard')">
                                🛡️ Modération
                            </ResponsiveNavLink>
                        </div>
                    </div>

                    <!-- User Section Mobile -->
                    <div class="border-t border-gray-200 dark:border-gray-600 pb-3 pt-4 px-3">
                        <div class="flex items-center gap-3 px-3 mb-3">
                            <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">👤 Mon Profil</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('profile.gamification')">🏆 Mes Succès</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                🚪 Déconnexion
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow dark:bg-gray-800">
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
        
        <!-- Scroll to Top -->
        <ScrollToTop />
    </div>

    <!-- Global Toast Notifications -->
    <ToastContainer />

    <!-- Global Confirm Modals -->
    <ConfirmContainer />

    <!-- Command Palette (Cmd+K) -->
    <CommandPalette v-model="showCommandPalette" />

    <!-- Keyboard Shortcuts Help (?) -->
    <KeyboardShortcutsHelp v-model="showKeyboardHelp" />
</template>
