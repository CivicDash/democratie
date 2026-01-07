<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import ToastContainer from "@/Components/ToastContainer.vue";
import ConfirmContainer from "@/Components/ConfirmContainer.vue";
import NotificationBell from "@/Components/NotificationBell.vue";
import TourMenu from "@/Components/GuidedTour/TourMenu.vue";
import TourOverlay from "@/Components/GuidedTour/TourOverlay.vue";
// BottomNav retiré - le burger menu est suffisant
import ScrollToTop from "@/Components/ScrollToTop.vue";
import AppFooter from "@/Components/AppFooter.vue";
import MegaMenuLink from "@/Components/MegaMenuLink.vue";
import CommandPalette from "@/Components/CommandPalette.vue";
import KeyboardShortcutsHelp from "@/Components/KeyboardShortcutsHelp.vue";
import GlobalSearch from "@/Components/GlobalSearch.vue";
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
        router.visit(route("search", { q: searchQuery.value }));
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
            <nav data-tour="navigation" class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800 sticky top-0 z-40">
                <div class="mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 100%;">
                    <div class="flex h-16 justify-between">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <img src="/images/logo.png" alt="CivicDash" class="block h-10 w-auto" />
                                </Link>
                            </div>

                            <!-- Navigation Links - Desktop - Structure aérée -->
                            <div class="hidden xl:flex xl:items-center xl:ms-6 xl:space-x-1">
                                
                                <!-- 📍 MES REPRÉSENTANTS - Lien direct prominent -->
                                <NavLink 
                                    :href="route('representants.mes-representants')" 
                                    :active="route().current('representants.mes-representants')"
                                    class="!px-3"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <span>📍</span>
                                        <span>Mes Élus</span>
                                    </span>
                                </NavLink>
                                
                                <!-- 💡 PARTICIPATION -->
                                <Dropdown align="left" width="72">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('participation.*') || route().current('budget.*') ? 'border-emerald-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            💡 Participation
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-3 space-y-1">
                                            <MegaMenuLink
                                                :href="route('participation.ideas.index')"
                                                icon="💬"
                                                title="Idées Citoyennes"
                                                description="Propositions & débats"
                                            />
                                            <MegaMenuLink
                                                :href="route('participation.ideas.create')"
                                                icon="✨"
                                                title="Nouvelle Proposition"
                                                description="Partagez votre idée"
                                            />
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
                                            <MegaMenuLink
                                                :href="route('budget.index')"
                                                icon="💰"
                                                title="Budget Participatif"
                                                description="Répartissez le budget"
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- 🏛️ ASSEMBLÉE NATIONALE -->
                                <Dropdown align="left" width="72">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('representants.deputes.*') || route().current('legislation.scrutins.*') || route().current('questions.*') ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            <img src="/images/Logo_de_l'Assemblée_nationale_française.svg" alt="AN" class="w-4 h-4 object-contain me-1.5" />
                                            Assemblée
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-3 space-y-1">
                                            <MegaMenuLink
                                                :href="route('representants.deputes.index')"
                                                icon="👥"
                                                title="Députés"
                                                description="577 élus"
                                                badge="577"
                                                badge-color="indigo"
                                            />
                                            <MegaMenuLink
                                                :href="route('legislation.scrutins.index')"
                                                icon="🗳️"
                                                title="Scrutins"
                                                description="Votes publics"
                                            />
                                            <MegaMenuLink
                                                :href="route('questions.index')"
                                                icon="❓"
                                                title="Questions au Gouv."
                                                description="Interpellations"
                                            />
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
                                            <MegaMenuLink
                                                :href="route('legislation.groupes.index')"
                                                icon="🎨"
                                                title="Groupes politiques"
                                                description="AN & Sénat"
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- 🏰 SÉNAT -->
                                <Dropdown align="left" width="72">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('representants.senateurs.*') || route().current('legislation.scrutins-senat.*') ? 'border-rose-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            <img src="/images/Logo_du_Sénat_Republique_française.svg" alt="Sénat" class="w-4 h-4 object-contain me-1.5" />
                                            Sénat
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-3 space-y-1">
                                            <MegaMenuLink
                                                :href="route('representants.senateurs.index')"
                                                icon="👥"
                                                title="Sénateurs"
                                                description="348 élus"
                                                badge="348"
                                                badge-color="rose"
                                            />
                                            <MegaMenuLink
                                                :href="route('legislation.scrutins-senat.index')"
                                                icon="🗳️"
                                                title="Scrutins"
                                                description="Votes publics"
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- 🏛️ GOUVERNEMENT -->
                                <Dropdown align="left" width="72">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('gouvernement.*') ? 'border-amber-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            <img 
                                                src="/images/Logo_de_la_présidence_de_la_République_(2018).svg" 
                                                alt="" 
                                                class="w-5 h-5 mr-1.5"
                                            />
                                            Gouvernement
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-3 space-y-1">
                                            <MegaMenuLink
                                                :href="route('gouvernement.president')"
                                                icon="🏛️"
                                                title="Président de la République"
                                                description="Chef de l'État"
                                            />
                                            <MegaMenuLink
                                                :href="route('gouvernement.index')"
                                                icon="👔"
                                                title="Composition du Gouvernement"
                                                description="Ministres et ministères"
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- 📅 CALENDRIER UNIFIÉ - Lien direct -->
                                <NavLink 
                                    :href="route('parlement.calendrier.index')" 
                                    :active="route().current('parlement.calendrier.*')"
                                    class="!px-3"
                                >
                                    📅 Calendrier
                                </NavLink>
                                
                                <!-- ⚖️ LÉGISLATION -->
                                <Dropdown align="left" width="72">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('legislation.*') || route().current('lois.*') || route().current('tags.*') ? 'border-sky-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            ⚖️ Législation
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-3 space-y-1">
                                            <MegaMenuLink
                                                :href="route('lois.index')"
                                                icon="📜"
                                                title="Lois"
                                                description="Parcours législatif"
                                            />
                                            <MegaMenuLink
                                                :href="route('tags.index')"
                                                icon="🏷️"
                                                title="Thématiques"
                                                description="Par domaine"
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                                
                                <!-- 📊 DONNÉES -->
                                <Dropdown align="left" width="72">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                            :class="route().current('statistics.*') || route().current('documents.*') || route().current('parlement.comparaison') ? 'border-violet-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                        >
                                            📊 Données
                                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="p-3 space-y-1">
                                            <MegaMenuLink
                                                :href="route('statistics.france')"
                                                icon="🗺️"
                                                title="Statistiques Pays"
                                                description="Démographie, économie, société"
                                            />
                                            <MegaMenuLink
                                                :href="route('statistics.villes')"
                                                icon="🏘️"
                                                title="Statistiques Villes"
                                                description="Communes, populations, budgets"
                                            />
                                            <MegaMenuLink
                                                :href="route('statistics.regions.index')"
                                                icon="🗺️"
                                                title="Statistiques Régions"
                                                description="18 régions, départements, indicateurs"
                                            />
                                            <MegaMenuLink
                                                :href="route('budget-etat.index')"
                                                icon="💰"
                                                title="Statistiques État"
                                                description="Budget, recettes, dépenses"
                                            />
                                            <MegaMenuLink
                                                :href="route('donnees.gouvernements')"
                                                icon="🏛️"
                                                title="Statistiques Gouvernement"
                                                description="Ministres, ministères"
                                            />
                                            <MegaMenuLink
                                                :href="route('parlement.comparaison')"
                                                icon="👔"
                                                title="Statistiques Élus"
                                                description="Députés, sénateurs, maires"
                                            />
                                            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
                                            <MegaMenuLink
                                                :href="route('documents.index')"
                                                icon="📄"
                                                title="Documents Publics"
                                                description="Officiels vérifiés"
                                            />
                                        </div>
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
                                
                                <!-- Administration (si admin) -->
                                <NavLink
                                    v-if="$page.props.auth.user.roles?.includes('admin')"
                                    :href="route('admin.dashboard')"
                                    :active="route().current('admin.*')"
                                >
                                    ⚙️ Admin
                                </NavLink>
                                
                                <!-- Espace Élu (si élu vérifié) -->
                                <NavLink
                                    v-if="$page.props.auth.user.is_verified_elu"
                                    :href="route('elu.dashboard')"
                                    :active="route().current('elu.*')"
                                    class="!text-purple-600 dark:!text-purple-400 font-semibold"
                                >
                                    🏛️ Espace Élu
                                </NavLink>
                            </div>
                        </div>

                        <!-- Right side: Search + Actions -->
                        <div class="hidden lg:flex lg:items-center lg:space-x-4">
                            <!-- Search Bar Desktop avec suggestions -->
                            <GlobalSearch data-tour="search" placeholder="Rechercher élus, lois, idées..." />
                            
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
                            
                            <!-- Tour Guide Menu -->
                            <TourMenu />
                            
                            <!-- Notification Bell -->
                            <NotificationBell data-tour="notifications" />
                            
                            <!-- User Dropdown -->
                            <Dropdown align="right" width="48" data-tour="user-menu">
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
                                    <DropdownLink :href="route('profile.elus-suivis')">
                                        🔔 Élus suivis
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
                    <GlobalSearch 
                        placeholder="Rechercher élus, lois, idées..." 
                        @close="showMobileSearch = false"
                    />
                </div>

                <!-- Responsive Navigation Menu (Accordion Style) -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="lg:hidden border-t border-gray-200 dark:border-gray-700 max-h-[calc(100vh-64px)] overflow-y-auto"
                >
                    <div class="space-y-1 pb-3 pt-2 px-3">
                        <!-- 🏠 Accueil -->
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            🏠 Accueil
                        </ResponsiveNavLink>
                        
                        <!-- 📍 MES ÉLUS - Lien direct -->
                        <ResponsiveNavLink :href="route('representants.mes-representants')" class="font-medium bg-indigo-50 dark:bg-indigo-900/30">
                            📍 Mes Élus
                        </ResponsiveNavLink>
                        
                        <!-- 💡 PARTICIPATION -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2 mt-2">
                            <button
                                @click="toggleSection('participation')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg"
                            >
                                <span>💡 Participation</span>
                                <svg :class="{ 'rotate-180': expandedSection === 'participation' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'participation'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('participation.ideas.index')">💬 Idées Citoyennes</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('participation.ideas.create')" class="text-emerald-600 dark:text-emerald-400 font-medium">✨ Nouvelle Proposition</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('budget.index')">💰 Budget Participatif</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- 🏛️ ASSEMBLÉE NATIONALE -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('an')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg"
                            >
                                <span class="flex items-center gap-2">
                                    <img src="/images/Logo_de_l'Assemblée_nationale_française.svg" alt="AN" class="w-4 h-4" />
                                    Assemblée Nationale
                                </span>
                                <svg :class="{ 'rotate-180': expandedSection === 'an' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'an'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('representants.deputes.index')">👥 Députés (577)</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('legislation.scrutins.index')">🗳️ Scrutins</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('questions.index')">❓ Questions au Gouv.</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('legislation.groupes.index')">🎨 Groupes politiques</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- 🏰 SÉNAT -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('senat')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg"
                            >
                                <span class="flex items-center gap-2">
                                    <img src="/images/Logo_du_Sénat_Republique_française.svg" alt="Sénat" class="w-4 h-4" />
                                    Sénat
                                </span>
                                <svg :class="{ 'rotate-180': expandedSection === 'senat' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'senat'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('representants.senateurs.index')">👥 Sénateurs (348)</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('legislation.scrutins-senat.index')">🗳️ Scrutins</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- 🏛️ GOUVERNEMENT -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('gouvernement')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg"
                            >
                                <span class="flex items-center gap-2">
                                    <img src="/images/Logo_de_la_présidence_de_la_République_(2018).svg" alt="" class="w-5 h-5" />
                                    Gouvernement
                                </span>
                                <svg :class="{ 'rotate-180': expandedSection === 'gouvernement' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'gouvernement'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('gouvernement.president')">🏛️ Président de la République</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('gouvernement.index')">👔 Composition du gouvernement</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- 📅 CALENDRIER UNIFIÉ - Lien direct -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <ResponsiveNavLink :href="route('parlement.calendrier.index')">
                                📅 Calendrier
                            </ResponsiveNavLink>
                        </div>
                        
                        <!-- ⚖️ LÉGISLATION -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('legislation')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-sky-50 dark:hover:bg-sky-900/20 rounded-lg"
                            >
                                <span>⚖️ Législation</span>
                                <svg :class="{ 'rotate-180': expandedSection === 'legislation' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'legislation'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('lois.index')">📜 Lois</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('tags.index')">🏷️ Thématiques</ResponsiveNavLink>
                            </div>
                        </div>
                        
                        <!-- 📊 DONNÉES -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                @click="toggleSection('donnees')"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg"
                            >
                                <span>📊 Données</span>
                                <svg :class="{ 'rotate-180': expandedSection === 'donnees' }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div v-show="expandedSection === 'donnees'" class="pl-4 space-y-1 mt-1">
                                <ResponsiveNavLink :href="route('statistics.france')">🗺️ Statistiques Pays</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('budget-etat.index')">💰 Statistiques État</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('donnees.gouvernements')">🏛️ Statistiques Gouvernement</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('parlement.comparaison')">👔 Statistiques Élus</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('statistics.villes')">🏘️ Statistiques Villes</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('statistics.regions.index')">🗺️ Statistiques Régions</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('documents.index')">📄 Documents Publics</ResponsiveNavLink>
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
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user.roles?.includes('admin')"
                                :href="route('admin.dashboard')"
                            >
                                ⚙️ Administration
                            </ResponsiveNavLink>
                        </div>
                        
                        <!-- Espace Élu (si élu vérifié) -->
                        <div
                            v-if="$page.props.auth.user.is_verified_elu"
                            class="border-t border-purple-200 dark:border-purple-700 pt-2"
                        >
                            <ResponsiveNavLink :href="route('elu.dashboard')" class="text-purple-600 dark:text-purple-400">
                                🏛️ Espace Élu
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('elu.interpellations')">
                                📬 Interpellations
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('elu.stats')">
                                📊 Mes Statistiques
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
                            <ResponsiveNavLink :href="route('profile.elus-suivis')">🔔 Élus suivis</ResponsiveNavLink>
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
        <!-- BottomNav retiré - burger menu suffisant -->
        
        <!-- Scroll to Top -->
        <ScrollToTop />
        
        <!-- Tour Overlay (visite guidée) -->
        <TourOverlay />
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
