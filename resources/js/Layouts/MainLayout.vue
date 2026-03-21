<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import MegaMenuLink from '@/Components/MegaMenuLink.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import InstitutionsMegaMenu from '@/Components/Navigation/InstitutionsMegaMenu.vue';
import BottomTabBar from '@/Components/Navigation/BottomTabBar.vue';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import { useNavigation } from '@/composables/useNavigation';

defineProps({
    title: String,
});

const { user, isAuthenticated, mesElus, institutions, legislatif, agir, comprendre } = useNavigation();

const showCommandPalette = ref(false);

const openDropdown = ref(null);
function toggleDropdown(key) {
    openDropdown.value = openDropdown.value === key ? null : key;
}
function closeDropdowns() {
    openDropdown.value = null;
}

useKeyboardShortcuts({
    onOpenPalette: () => { showCommandPalette.value = true; },
    onCloseAll: () => {
        showCommandPalette.value = false;
        openDropdown.value = null;
        document.activeElement?.blur();
    },
});

const onDocClick = () => { openDropdown.value = null; };
onMounted(() => { document.addEventListener('click', onDocClick); });
onUnmounted(() => { document.removeEventListener('click', onDocClick); });
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-20 lg:pb-0">
            <!-- Navigation -->
            <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <!-- Left side: Logo + Main nav -->
                        <div class="flex items-center">
                            <!-- Logo -->
                            <Link :href="route('home')" class="flex items-center gap-2 mr-8">
                                <span class="text-2xl">🏛️</span>
                                <span class="font-bold text-xl text-slate-900 dark:text-white hidden sm:inline">
                                    CivicDash
                                </span>
                            </Link>

                            <!-- Desktop Navigation - 5 mega-sections -->
                            <div class="hidden lg:flex items-center gap-1">

                                <!-- 1. MES ELUS -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('mes-elus')"
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="mesElus.isActive
                                            ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300'
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="text-lg">📍</span>
                                        Mes Elus
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'mes-elus' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 translate-y-1"
                                    >
                                        <div
                                            v-show="openDropdown === 'mes-elus'"
                                            class="absolute left-0 mt-2 w-72 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-2 z-50"
                                            @click="closeDropdowns"
                                        >
                                            <Link
                                                v-for="item in mesElus.items"
                                                :key="item.href"
                                                :href="item.href"
                                                class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                            >
                                                <span class="text-lg">{{ item.icon }}</span>
                                                <div>
                                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.title }}</div>
                                                    <div v-if="item.description" class="text-xs text-slate-500">{{ item.description }}</div>
                                                </div>
                                            </Link>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- 2. INSTITUTIONS (mega-menu) -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('institutions')"
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="institutions.isActive
                                            ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300'
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="text-lg">🏛️</span>
                                        Institutions
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'institutions' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 translate-y-1"
                                    >
                                        <div
                                            v-show="openDropdown === 'institutions'"
                                            class="absolute left-0 mt-2 rounded-xl shadow-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 z-50"
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
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="legislatif.isActive
                                            ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300'
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="text-lg">⚖️</span>
                                        Legislatif
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'legislatif' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 translate-y-1"
                                    >
                                        <div
                                            v-show="openDropdown === 'legislatif'"
                                            class="absolute left-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-2 z-50"
                                            @click="closeDropdowns"
                                        >
                                            <template v-for="item in legislatif.items" :key="item.href || 'div'">
                                                <hr v-if="item.divider" class="my-2 border-slate-200 dark:border-slate-700" />
                                                <Link
                                                    v-else
                                                    :href="item.href"
                                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                >
                                                    <span class="text-lg">{{ item.icon }}</span>
                                                    <div>
                                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.title }}</div>
                                                        <div v-if="item.description" class="text-xs text-slate-500">{{ item.description }}</div>
                                                    </div>
                                                </Link>
                                            </template>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- 4. AGIR -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('agir')"
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="agir.isActive
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="text-lg">💡</span>
                                        Agir
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'agir' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 translate-y-1"
                                    >
                                        <div
                                            v-show="openDropdown === 'agir'"
                                            class="absolute left-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-2 z-50"
                                            @click="closeDropdowns"
                                        >
                                            <template v-for="item in agir.items" :key="item.href || 'div'">
                                                <hr v-if="item.divider" class="my-2 border-slate-200 dark:border-slate-700" />
                                                <Link
                                                    v-else
                                                    :href="item.href"
                                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                >
                                                    <span class="text-lg">{{ item.icon }}</span>
                                                    <div>
                                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.title }}</div>
                                                        <div v-if="item.description" class="text-xs text-slate-500">{{ item.description }}</div>
                                                    </div>
                                                    <span
                                                        v-if="item.badge"
                                                        class="ml-auto text-xs font-medium px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300"
                                                    >{{ item.badge }}</span>
                                                </Link>
                                            </template>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- 5. COMPRENDRE -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="toggleDropdown('comprendre')"
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="comprendre.isActive
                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="text-lg">🎓</span>
                                        Comprendre
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'comprendre' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 translate-y-1"
                                    >
                                        <div
                                            v-show="openDropdown === 'comprendre'"
                                            class="absolute left-0 mt-2 w-72 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-2 z-50"
                                            @click="closeDropdowns"
                                        >
                                            <template v-for="item in comprendre.items" :key="item.href || 'div'">
                                                <hr v-if="item.divider" class="my-2 border-slate-200 dark:border-slate-700" />
                                                <Link
                                                    v-else
                                                    :href="item.href"
                                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                >
                                                    <span class="text-lg">{{ item.icon }}</span>
                                                    <div>
                                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.title }}</div>
                                                        <div v-if="item.description" class="text-xs text-slate-500">{{ item.description }}</div>
                                                    </div>
                                                </Link>
                                            </template>
                                        </div>
                                    </Transition>
                                </div>
                            </div>
                        </div>

                        <!-- Right side -->
                        <div class="flex items-center gap-2 sm:gap-4">
                            <!-- Search button -->
                            <button
                                @click="showCommandPalette = true"
                                class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                aria-label="Recherche"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>

                            <!-- User Menu (Desktop) -->
                            <div class="hidden sm:flex sm:items-center">
                                <div v-if="user" class="relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button type="button" class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 dark:border-slate-600 text-sm font-medium rounded-lg text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors">
                                                <span class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs">
                                                    {{ (user.display_name || user.name)?.charAt(0).toUpperCase() }}
                                                </span>
                                                <span class="hidden md:inline">{{ user.display_name || user.name }}</span>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('profile.edit')">
                                                <span class="flex items-center gap-2"><span>👤</span> Mon Profil</span>
                                            </DropdownLink>
                                            <DropdownLink v-if="user.roles?.includes('moderator') || user.roles?.includes('admin')" :href="route('moderation.dashboard')">
                                                <span class="flex items-center gap-2"><span>🛡️</span> Moderation</span>
                                            </DropdownLink>
                                            <DropdownLink v-if="user.roles?.includes('admin')" :href="route('admin.dashboard')">
                                                <span class="flex items-center gap-2"><span>⚙️</span> Administration</span>
                                            </DropdownLink>
                                            <hr class="my-1 border-slate-200 dark:border-slate-600">
                                            <DropdownLink :href="route('logout')" method="post" as="button">
                                                <span class="flex items-center gap-2 text-red-600 dark:text-red-400"><span>🚪</span> Deconnexion</span>
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <Link
                                        :href="route('login')"
                                        class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors"
                                    >
                                        Connexion
                                    </Link>
                                    <Link
                                        :href="route('register')"
                                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                                    >
                                        Inscription
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <!-- Footer -->
            <footer class="bg-slate-800 dark:bg-slate-900 text-slate-300 py-12 mt-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        <div>
                            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                                <span class="text-xl">🏛️</span> CivicDash
                            </h3>
                            <p class="text-sm text-slate-400">
                                Plateforme citoyenne pour comprendre et suivre la democratie francaise.
                            </p>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">Representants</h4>
                            <ul class="space-y-2 text-sm">
                                <li><Link :href="route('representants.deputes.index')" class="hover:text-white transition-colors">Deputes</Link></li>
                                <li><Link :href="route('representants.senateurs.index')" class="hover:text-white transition-colors">Senateurs</Link></li>
                                <li><Link :href="route('questions.index')" class="hover:text-white transition-colors">Questions au Gouvernement</Link></li>
                                <li><Link :href="route('legislation.groupes.index')" class="hover:text-white transition-colors">Groupes politiques</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">Legislation</h4>
                            <ul class="space-y-2 text-sm">
                                <li><Link :href="route('lois.index')" class="hover:text-white transition-colors">Lois en cours</Link></li>
                                <li><Link :href="route('legislation.scrutins.index')" class="hover:text-white transition-colors">Scrutins</Link></li>
                                <li><Link :href="route('tags.index')" class="hover:text-white transition-colors">Thematiques</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">A propos</h4>
                            <ul class="space-y-2 text-sm">
                                <li><a href="/about" class="hover:text-white transition-colors">A propos</a></li>
                                <li><a href="/privacy" class="hover:text-white transition-colors">Confidentialite</a></li>
                                <li><a href="https://github.com/CivicDash/democratie" target="_blank" class="hover:text-white transition-colors">GitHub</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-8 pt-8 border-t border-slate-700 text-center text-sm text-slate-500">
                        &copy; {{ new Date().getFullYear() }} CivicDash &bull; Open Source (AGPL-3.0)
                    </div>
                </div>
            </footer>
        </div>

        <!-- Bottom Tab Bar (mobile only) -->
        <BottomTabBar />
    </div>

    <!-- Command Palette (Ctrl+K) -->
    <CommandPalette v-model="showCommandPalette" />
</template>
