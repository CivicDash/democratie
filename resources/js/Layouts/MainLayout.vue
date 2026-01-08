<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import CommandPalette from '@/Components/CommandPalette.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);
const showRepresentantsMenu = ref(false);
const showLegislationMenu = ref(false);
const showCommandPalette = ref(false);

const logout = () => {
    router.post(route('logout'));
};

// Vérifier si l'utilisateur est authentifié
const page = usePage();
const user = computed(() => page.props.auth?.user);

// Fermer les menus quand on clique ailleurs
const closeMenus = () => {
    showRepresentantsMenu.value = false;
    showLegislationMenu.value = false;
};

// Gestion des raccourcis clavier
const handleKeydown = (e) => {
    // Ctrl+K ou Cmd+K : Ouvrir la CommandPalette
    if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        showCommandPalette.value = true;
    }
    // / : Ouvrir la CommandPalette (si pas dans un input)
    if (e.key === "/" && !["INPUT", "TEXTAREA"].includes(document.activeElement?.tagName)) {
        e.preventDefault();
        showCommandPalette.value = true;
    }
};

onMounted(() => {
    document.addEventListener('click', closeMenus);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeMenus);
    document.removeEventListener('keydown', handleKeydown);
});

// Vérifier si une route est active
const isActive = (pattern) => {
    return page.url.startsWith(pattern);
};
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
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

                            <!-- Desktop Navigation -->
                            <div class="hidden lg:flex items-center gap-1">
                                
                                <!-- Mes Représentants (Dropdown) -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="showRepresentantsMenu = !showRepresentantsMenu; showLegislationMenu = false"
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="isActive('/representants') || isActive('/deputes') || isActive('/senateurs') 
                                            ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300' 
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="text-lg">👥</span>
                                        Représentants
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showRepresentantsMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            v-show="showRepresentantsMenu"
                                            class="absolute left-0 mt-2 w-72 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-2 z-50"
                                        >
                                            <Link 
                                                :href="route('representants.mes-representants')" 
                                                class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700"
                                            >
                                                <span class="text-xl">📍</span>
                                                <div>
                                                    <div class="font-medium text-slate-900 dark:text-white">Mes Représentants</div>
                                                    <div class="text-xs text-slate-500">Par code postal</div>
                                                </div>
                                            </Link>
                                            
                                            <hr class="my-2 border-slate-200 dark:border-slate-700">
                                            
                                            <!-- Assemblée Nationale - Submenu -->
                                            <div class="relative group/an">
                                                <div class="flex items-center justify-between px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer border-l-2 border-transparent hover:border-indigo-500">
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-lg">🏛️</span>
                                                        <span class="font-medium text-slate-900 dark:text-white">Assemblée Nationale</span>
                                                    </div>
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </div>
                                                <!-- Submenu AN -->
                                                <div class="absolute left-full top-0 ml-1 w-60 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 py-2 opacity-0 invisible group-hover/an:opacity-100 group-hover/an:visible transition-all duration-150">
                                                    <Link 
                                                        :href="route('representants.deputes.index')" 
                                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                    >
                                                        <span class="text-lg">👥</span>
                                                        <div>
                                                            <div class="text-slate-700 dark:text-slate-300 font-medium">Députés</div>
                                                            <div class="text-xs text-slate-500">577 représentants</div>
                                                        </div>
                                                    </Link>
                                                    <Link 
                                                        :href="route('questions.index')" 
                                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                    >
                                                        <span class="text-lg">❓</span>
                                                        <div>
                                                            <div class="text-slate-700 dark:text-slate-300 font-medium">Questions au Gouvernement</div>
                                                            <div class="text-xs text-slate-500">Interpellations des députés</div>
                                                        </div>
                                                    </Link>
                                                    <Link 
                                                        :href="route('legislation.scrutins.index')" 
                                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                    >
                                                        <span class="text-lg">🗳️</span>
                                                        <div>
                                                            <div class="text-slate-700 dark:text-slate-300 font-medium">Votes et scrutins</div>
                                                            <div class="text-xs text-slate-500">Votes en séance publique</div>
                                                        </div>
                                                    </Link>
                                                </div>
                                            </div>
                                            
                                            <!-- Sénat - Submenu -->
                                            <div class="relative group/senat">
                                                <div class="flex items-center justify-between px-4 py-2.5 hover:bg-rose-50 dark:hover:bg-rose-900/30 cursor-pointer border-l-2 border-transparent hover:border-rose-500">
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-lg">🔴</span>
                                                        <span class="font-medium text-slate-900 dark:text-white">Sénat</span>
                                                    </div>
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </div>
                                                <!-- Submenu Sénat -->
                                                <div class="absolute left-full top-0 ml-1 w-60 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 py-2 opacity-0 invisible group-hover/senat:opacity-100 group-hover/senat:visible transition-all duration-150">
                                                    <Link 
                                                        :href="route('representants.senateurs.index')" 
                                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                    >
                                                        <span class="text-lg">👥</span>
                                                        <div>
                                                            <div class="text-slate-700 dark:text-slate-300 font-medium">Sénateurs</div>
                                                            <div class="text-xs text-slate-500">348 représentants</div>
                                                        </div>
                                                    </Link>
                                                    <Link 
                                                        :href="route('legislation.scrutins-senat.index')" 
                                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                                    >
                                                        <span class="text-lg">🗳️</span>
                                                        <div>
                                                            <div class="text-slate-700 dark:text-slate-300 font-medium">Scrutins</div>
                                                            <div class="text-xs text-slate-500">Votes au Sénat</div>
                                                        </div>
                                                    </Link>
                                                </div>
                                            </div>
                                            
                                            <hr class="my-2 border-slate-200 dark:border-slate-700">
                                            <Link 
                                                :href="route('legislation.groupes.index')" 
                                                class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700"
                                            >
                                                <span class="text-lg">🎭</span>
                                                <span class="text-slate-700 dark:text-slate-300">Groupes politiques</span>
                                            </Link>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- Législation (Dropdown) -->
                                <div class="relative" @click.stop>
                                    <button
                                        @click="showLegislationMenu = !showLegislationMenu; showRepresentantsMenu = false"
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="isActive('/legislation') || isActive('/lois') 
                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' 
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="text-lg">📜</span>
                                        Législation
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showLegislationMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            v-show="showLegislationMenu"
                                            class="absolute left-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-2 z-50"
                                        >
                                            <Link 
                                                :href="route('legislation.hub')" 
                                                class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700"
                                            >
                                                <span class="text-xl">🔍</span>
                                                <div>
                                                    <div class="font-medium text-slate-900 dark:text-white">Explorer</div>
                                                    <div class="text-xs text-slate-500">Recherche et thématiques</div>
                                                </div>
                                            </Link>
                                            <hr class="my-2 border-slate-200 dark:border-slate-700">
                                            <Link 
                                                :href="route('lois.index')" 
                                                class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700"
                                            >
                                                <span class="text-lg">📋</span>
                                                <span class="text-slate-700 dark:text-slate-300">Lois en cours</span>
                                            </Link>
                                            <Link 
                                                :href="route('legislation.scrutins.index')" 
                                                class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700"
                                            >
                                                <span class="text-lg">🗳️</span>
                                                <span class="text-slate-700 dark:text-slate-300">Scrutins</span>
                                            </Link>
                                            <Link 
                                                :href="route('legislation.thematiques.index')" 
                                                class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700"
                                            >
                                                <span class="text-lg">🏷️</span>
                                                <span class="text-slate-700 dark:text-slate-300">Thématiques</span>
                                            </Link>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- Calendrier -->
                                <Link 
                                    :href="route('parlement.calendrier.index')" 
                                    class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                    :class="isActive('/parlement/calendrier') 
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' 
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                >
                                    <span class="text-lg">📅</span>
                                    Calendrier
                                </Link>

                                <!-- Participation -->
                                <Link 
                                    :href="route('participation.ideas.index')" 
                                    class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                    :class="isActive('/participation') 
                                        ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300' 
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                >
                                    <span class="text-lg">💡</span>
                                    Idées citoyennes
                                </Link>
                            </div>
                        </div>

                        <!-- Right side: User Menu -->
                        <div class="flex items-center gap-4">
                            <!-- Search button -->
                            <Link 
                                :href="route('search')" 
                                class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </Link>

                            <!-- User Menu (Desktop) -->
                            <div class="hidden sm:flex sm:items-center">
                                <div v-if="user" class="relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button type="button" class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 dark:border-slate-600 text-sm font-medium rounded-lg text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors">
                                                <span class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                                    {{ user.name?.charAt(0).toUpperCase() }}
                                                </span>
                                                <span class="hidden md:inline">{{ user.name }}</span>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('profile.edit')">
                                                <span class="flex items-center gap-2">
                                                    <span>👤</span> Mon Profil
                                                </span>
                                            </DropdownLink>
                                            <DropdownLink v-if="user.roles?.includes('moderator') || user.roles?.includes('admin')" :href="route('moderation.dashboard')">
                                                <span class="flex items-center gap-2">
                                                    <span>🚨</span> Modération
                                                </span>
                                            </DropdownLink>
                                            <DropdownLink v-if="user.roles?.includes('admin')" :href="route('admin.dashboard')">
                                                <span class="flex items-center gap-2">
                                                    <span>👑</span> Administration
                                                </span>
                                            </DropdownLink>
                                            <hr class="my-1 border-slate-200 dark:border-slate-600">
                                            <DropdownLink :href="route('logout')" method="post" as="button">
                                                <span class="flex items-center gap-2 text-red-600 dark:text-red-400">
                                                    <span>🚪</span> Déconnexion
                                                </span>
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

                            <!-- Mobile Hamburger -->
                            <button 
                                @click="showingNavigationDropdown = !showingNavigationDropdown" 
                                class="lg:hidden inline-flex items-center justify-center p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="lg:hidden border-t border-slate-200 dark:border-slate-700">
                    <div class="px-4 py-4 space-y-2">
                        <!-- Mes Représentants -->
                        <Link :href="route('representants.mes-representants')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>📍</span> Mes Représentants
                        </Link>
                        
                        <!-- Assemblée Nationale -->
                        <div class="flex items-center gap-2 mt-4 mb-2">
                            <img src="/images/Logo_de_l'Assemblée_nationale_française.svg" alt="AN" class="w-4 h-4 object-contain" />
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assemblée Nationale</span>
                        </div>
                        <Link :href="route('representants.deputes.index')" class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span class="flex items-center gap-2"><span>👥</span> Députés</span>
                            <span class="text-xs text-slate-400">577</span>
                        </Link>
                        <Link :href="route('questions.index')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>❓</span> Questions au Gouv.
                        </Link>
                        <Link :href="route('legislation.scrutins.index')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>🗳️</span> Scrutins publics
                        </Link>
                        
                        <!-- Sénat -->
                        <div class="flex items-center gap-2 mt-4 mb-2">
                            <img src="/images/Logo_du_Sénat_Republique_française.svg" alt="Sénat" class="w-4 h-4 object-contain" />
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sénat</span>
                        </div>
                        <Link :href="route('representants.senateurs.index')" class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span class="flex items-center gap-2"><span>👥</span> Sénateurs</span>
                            <span class="text-xs text-slate-400">348</span>
                        </Link>
                        <Link :href="route('legislation.scrutins-senat.index')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>🗳️</span> Scrutins
                        </Link>
                        
                        <!-- Autres Parlementaires -->
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-2">🎭 Parlement</div>
                        <Link :href="route('legislation.groupes.index')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>🎨</span> Groupes politiques
                        </Link>
                        <Link :href="route('parlement.comparaison')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>📊</span> Statistiques Élus
                        </Link>
                        
                        <!-- Législation -->
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-2">📜 Législation</div>
                        <Link :href="route('legislation.hub')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>🔍</span> Explorer
                        </Link>
                        <Link :href="route('lois.index')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>⚖️</span> Lois en cours
                        </Link>
                        
                        <!-- Autres -->
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-2">Autres</div>
                        <Link :href="route('parlement.calendrier.index')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>📅</span> Calendrier
                        </Link>
                        <Link :href="route('participation.ideas.index')" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span>💡</span> Idées citoyennes
                        </Link>
                    </div>

                    <!-- Mobile User section -->
                    <div class="border-t border-slate-200 dark:border-slate-700 px-4 py-4">
                        <div v-if="user">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                    {{ user.name?.charAt(0).toUpperCase() }}
                                </span>
                                <div>
                                    <div class="font-medium text-slate-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-sm text-slate-500">{{ user.email }}</div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <Link :href="route('profile.edit')" class="block px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                                    👤 Profil
                                </Link>
                                <Link v-if="user.roles?.includes('admin')" :href="route('admin.dashboard')" class="block px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                                    👑 Administration
                                </Link>
                                <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-3 py-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    🚪 Déconnexion
                                </Link>
                            </div>
                        </div>
                        <div v-else class="flex gap-2">
                            <Link :href="route('login')" class="flex-1 text-center px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300">
                                Connexion
                            </Link>
                            <Link :href="route('register')" class="flex-1 text-center px-4 py-2 bg-indigo-600 text-white rounded-lg">
                                Inscription
                            </Link>
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
                                Plateforme citoyenne pour comprendre et suivre la démocratie française.
                            </p>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">Représentants</h4>
                            <ul class="space-y-2 text-sm">
                                <li><Link :href="route('representants.deputes.index')" class="hover:text-white transition-colors">Députés</Link></li>
                                <li><Link :href="route('representants.senateurs.index')" class="hover:text-white transition-colors">Sénateurs</Link></li>
                                <li><Link :href="route('questions.index')" class="hover:text-white transition-colors">Questions au Gouvernement</Link></li>
                                <li><Link :href="route('legislation.groupes.index')" class="hover:text-white transition-colors">Groupes politiques</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">Législation</h4>
                            <ul class="space-y-2 text-sm">
                                <li><Link :href="route('lois.index')" class="hover:text-white transition-colors">Lois en cours</Link></li>
                                <li><Link :href="route('legislation.scrutins.index')" class="hover:text-white transition-colors">Scrutins</Link></li>
                                <li><Link :href="route('legislation.thematiques.index')" class="hover:text-white transition-colors">Thématiques</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">À propos</h4>
                            <ul class="space-y-2 text-sm">
                                <li><a href="/about" class="hover:text-white transition-colors">À propos</a></li>
                                <li><a href="/privacy" class="hover:text-white transition-colors">Confidentialité</a></li>
                                <li><a href="https://github.com/CivicDash/democratie" target="_blank" class="hover:text-white transition-colors">GitHub</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-8 pt-8 border-t border-slate-700 text-center text-sm text-slate-500">
                        © {{ new Date().getFullYear() }} CivicDash • Open Source (AGPL-3.0)
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Command Palette (Ctrl+K) -->
    <CommandPalette v-model="showCommandPalette" />
</template>
