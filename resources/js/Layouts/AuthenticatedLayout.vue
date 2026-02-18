<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import MegaMenuLink from "@/Components/MegaMenuLink.vue";
import ToastContainer from "@/Components/ToastContainer.vue";
import ConfirmContainer from "@/Components/ConfirmContainer.vue";
import NotificationBell from "@/Components/NotificationBell.vue";
import TourMenu from "@/Components/GuidedTour/TourMenu.vue";
import TourOverlay from "@/Components/GuidedTour/TourOverlay.vue";
import ScrollToTop from "@/Components/ScrollToTop.vue";
import AppFooter from "@/Components/AppFooter.vue";
import CommandPalette from "@/Components/CommandPalette.vue";
import KeyboardShortcutsHelp from "@/Components/KeyboardShortcutsHelp.vue";
import GlobalSearch from "@/Components/GlobalSearch.vue";
import InstitutionsMegaMenu from "@/Components/Navigation/InstitutionsMegaMenu.vue";
import BottomTabBar from "@/Components/Navigation/BottomTabBar.vue";
import { Link, router } from "@inertiajs/vue3";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import { useNavigation } from "@/composables/useNavigation";

const { desktopSections, institutions, mesElus, legislatif, agir, comprendre, user: navUser } = useNavigation();

const showCommandPalette = ref(false);
const showKeyboardHelp = ref(false);

// Track which desktop dropdown is open
const openDropdown = ref(null);
function toggleDropdown(key) {
    openDropdown.value = openDropdown.value === key ? null : key;
}
function closeDropdowns() {
    openDropdown.value = null;
}

// Dark Mode
const isDarkMode = ref(false);
const applyTheme = () => {
    document.documentElement.classList.toggle("dark", isDarkMode.value);
};
const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem("theme", isDarkMode.value ? "dark" : "light");
    applyTheme();
};

useKeyboardShortcuts({
    onOpenPalette: () => { showCommandPalette.value = true; },
    onToggleHelp: () => { showKeyboardHelp.value = !showKeyboardHelp.value; },
    onCloseAll: () => {
        showCommandPalette.value = false;
        showKeyboardHelp.value = false;
        openDropdown.value = null;
        document.activeElement?.blur();
    },
    onNavigate: (routeName) => { router.visit(route(routeName)); },
});

const onDocClick = () => { openDropdown.value = null; };

onMounted(() => {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
        isDarkMode.value = savedTheme === "dark";
    } else {
        isDarkMode.value = window.matchMedia("(prefers-color-scheme: dark)").matches;
    }
    applyTheme();
    document.addEventListener("click", onDocClick);
});

onUnmounted(() => {
    document.removeEventListener("click", onDocClick);
});

const chevronSvg = `<svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>`;

function sectionActiveClass(section) {
    return section.isActive
        ? `border-${section.activeColor}-500 text-gray-900 dark:text-gray-100`
        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700';
}
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 pb-20 lg:pb-0">
            <!-- Top Navigation -->
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

                            <!-- Desktop Navigation - 5 mega-sections -->
                            <div class="hidden lg:flex lg:items-center lg:ms-6 lg:space-x-1">

                                <!-- 1. MES ELUS -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('mes-elus')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                        :class="mesElus.isActive
                                            ? 'border-indigo-500 text-gray-900 dark:text-gray-100'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                    >
                                        📍 Mes Elus
                                        <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 scale-95"
                                        enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div
                                            v-show="openDropdown === 'mes-elus'"
                                            class="absolute left-0 z-50 mt-2 w-72 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5"
                                            @click="closeDropdowns"
                                        >
                                            <div class="p-3 space-y-1">
                                                <MegaMenuLink
                                                    v-for="item in mesElus.items"
                                                    :key="item.href"
                                                    :href="item.href"
                                                    :icon="item.icon"
                                                    :title="item.title"
                                                    :description="item.description || ''"
                                                    :badge="item.badge"
                                                    :badge-color="item.badgeColor || 'gray'"
                                                />
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- 2. INSTITUTIONS (mega-menu 3 colonnes) -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('institutions')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                        :class="institutions.isActive
                                            ? 'border-sky-500 text-gray-900 dark:text-gray-100'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                    >
                                        🏛️ Institutions
                                        <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 scale-95"
                                        enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div
                                            v-show="openDropdown === 'institutions'"
                                            class="absolute left-0 z-50 mt-2 rounded-xl shadow-xl bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5"
                                            @click="closeDropdowns"
                                        >
                                            <InstitutionsMegaMenu :columns="institutions.columns" />
                                        </div>
                                    </Transition>
                                </div>

                                <!-- 3. LEGISLATIF -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('legislatif')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                        :class="legislatif.isActive
                                            ? 'border-sky-500 text-gray-900 dark:text-gray-100'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                    >
                                        ⚖️ Legislatif
                                        <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 scale-95"
                                        enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div
                                            v-show="openDropdown === 'legislatif'"
                                            class="absolute left-0 z-50 mt-2 w-72 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5"
                                            @click="closeDropdowns"
                                        >
                                            <div class="p-3 space-y-1">
                                                <template v-for="item in legislatif.items" :key="item.href || 'div'">
                                                    <div v-if="item.divider" class="border-t border-gray-100 dark:border-gray-600 my-2" />
                                                    <MegaMenuLink
                                                        v-else
                                                        :href="item.href"
                                                        :icon="item.icon"
                                                        :title="item.title"
                                                        :description="item.description || ''"
                                                        :badge="item.badge"
                                                        :badge-color="item.badgeColor || 'gray'"
                                                    />
                                                </template>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- 4. AGIR -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('agir')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                        :class="agir.isActive
                                            ? 'border-emerald-500 text-gray-900 dark:text-gray-100'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                    >
                                        💡 Agir
                                        <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 scale-95"
                                        enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div
                                            v-show="openDropdown === 'agir'"
                                            class="absolute left-0 z-50 mt-2 w-72 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5"
                                            @click="closeDropdowns"
                                        >
                                            <div class="p-3 space-y-1">
                                                <template v-for="item in agir.items" :key="item.href || 'div'">
                                                    <div v-if="item.divider" class="border-t border-gray-100 dark:border-gray-600 my-2" />
                                                    <MegaMenuLink
                                                        v-else
                                                        :href="item.href"
                                                        :icon="item.icon"
                                                        :title="item.title"
                                                        :description="item.description || ''"
                                                        :badge="item.badge"
                                                        :badge-color="item.badgeColor || 'gray'"
                                                    />
                                                </template>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- 5. COMPRENDRE -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('comprendre')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2"
                                        :class="comprendre.isActive
                                            ? 'border-amber-500 text-gray-900 dark:text-gray-100'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                                    >
                                        🎓 Comprendre
                                        <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 scale-95"
                                        enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div
                                            v-show="openDropdown === 'comprendre'"
                                            class="absolute left-0 z-50 mt-2 w-72 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5"
                                            @click="closeDropdowns"
                                        >
                                            <div class="p-3 space-y-1">
                                                <template v-for="(item, idx) in comprendre.items" :key="item.href || `div-${idx}`">
                                                    <div v-if="item.divider" class="border-t border-gray-100 dark:border-gray-600 my-2">
                                                        <p v-if="item.label" class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider pt-2 px-1">{{ item.label }}</p>
                                                    </div>
                                                    <MegaMenuLink
                                                        v-else
                                                        :href="item.href"
                                                        :icon="item.icon"
                                                        :title="item.title"
                                                        :description="item.description || ''"
                                                        :badge="item.badge"
                                                        :badge-color="item.badgeColor || 'gray'"
                                                    />
                                                </template>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- Espace Elu (si elu verifie) -->
                                <Link
                                    v-if="$page.props.auth.user.is_verified_elu"
                                    :href="route('elu.dashboard')"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition border-b-2"
                                    :class="route().current('elu.*')
                                        ? 'border-purple-500 text-purple-700 dark:text-purple-300'
                                        : 'border-transparent text-purple-600 dark:text-purple-400 hover:border-purple-300 font-semibold'"
                                >
                                    🏛️ Espace Elu
                                </Link>
                            </div>
                        </div>

                        <!-- Right side: Search + Actions (visible on all breakpoints for desktop, simplified for mobile) -->
                        <div class="flex items-center space-x-2 lg:space-x-4">
                            <!-- Desktop-only: full search + extras -->
                            <div class="hidden lg:flex lg:items-center lg:space-x-4">
                                <GlobalSearch
                                    data-tour="search"
                                    placeholder="Rechercher elus, lois, idees..."
                                    @open-palette="showCommandPalette = true"
                                />
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

                            <!-- Mobile search (opens command palette) -->
                            <button
                                @click="showCommandPalette = true"
                                class="lg:hidden inline-flex items-center justify-center rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700"
                                title="Rechercher"
                                aria-label="Recherche"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>

                            <!-- Tour Guide Menu (desktop) -->
                            <div class="hidden lg:block">
                                <TourMenu />
                            </div>

                            <!-- Notification Bell -->
                            <NotificationBell data-tour="notifications" />

                            <!-- User Dropdown -->
                            <Dropdown align="right" width="48" data-tour="user-menu" class="hidden lg:block">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-full border border-transparent bg-gray-100 dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 transition hover:bg-gray-200 dark:hover:bg-gray-600"
                                    >
                                        <span>{{ $page.props.auth.user.name }}</span>
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
                                        🔔 Elus suivis
                                    </DropdownLink>
                                    <DropdownLink :href="route('profile.gamification')">
                                        🏆 Mes Succes
                                    </DropdownLink>

                                    <template v-if="$page.props.auth.user.roles?.includes('moderator') || $page.props.auth.user.roles?.includes('admin')">
                                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                        <DropdownLink :href="route('moderation.dashboard')">
                                            🛡️ Moderation
                                        </DropdownLink>
                                    </template>

                                    <DropdownLink
                                        v-if="$page.props.auth.user.roles?.includes('admin')"
                                        :href="route('admin.dashboard')"
                                    >
                                        ⚙️ Administration
                                    </DropdownLink>

                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        🚪 Deconnexion
                                    </DropdownLink>
                                </template>
                            </Dropdown>
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

        <!-- Bottom Tab Bar (mobile only) -->
        <BottomTabBar />

        <!-- Scroll to Top -->
        <ScrollToTop />

        <!-- Tour Overlay -->
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
