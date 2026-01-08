<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

// Simple debounce utility
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

const searchQuery = ref("");
const searchInput = ref(null);
const selectedIndex = ref(0);
const isLoading = ref(false);
const apiResults = ref([]);

// Navigation rapide (liens statiques)
const quickLinks = [
    { icon: "🏠", label: "Accueil", description: "Retour au dashboard", href: "/dashboard", keywords: ["home", "dashboard", "accueil"], type: "navigation" },
    { icon: "👥", label: "Députés", description: "577 députés de l'Assemblée Nationale", href: "/representants/deputes", keywords: ["depute", "assemblee", "an"], type: "navigation" },
    { icon: "🔴", label: "Sénateurs", description: "348 sénateurs du Sénat", href: "/representants/senateurs", keywords: ["senateur", "senat"], type: "navigation" },
    { icon: "⚖️", label: "Lois", description: "Parcours législatif complet", href: "/lois", keywords: ["loi", "législation", "vote", "amendement"], type: "navigation" },
    { icon: "🗳️", label: "Scrutins", description: "Votes publics AN & Sénat", href: "/legislation/scrutins", keywords: ["vote", "scrutin", "ballot"], type: "navigation" },
    { icon: "💡", label: "Idées Citoyennes", description: "Propositions et discussions", href: "/participation/idees", keywords: ["idee", "proposition", "participation", "debat"], type: "navigation" },
    { icon: "🗺️", label: "Statistiques France", description: "Carte interactive et données", href: "/statistiques/france", keywords: ["carte", "france", "stats", "region", "departement"], type: "navigation" },
    { icon: "📄", label: "Documents Publics", description: "Documents officiels", href: "/documents", keywords: ["document", "pdf", "officiel"], type: "navigation" },
    { icon: "📅", label: "Calendrier", description: "Agenda parlementaire", href: "/parlement/calendrier", keywords: ["calendrier", "agenda", "seance"], type: "navigation" },
    { icon: "👤", label: "Mon Profil", description: "Paramètres du compte", href: "/profile", keywords: ["profil", "compte", "settings"], type: "navigation" },
];

// Recherche API avec debounce
const searchAPI = debounce(async (query) => {
    if (!query || query.length < 2) {
        apiResults.value = [];
        isLoading.value = false;
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get('/api/search/suggestions', {
            params: { q: query }
        });
        apiResults.value = response.data.results || [];
    } catch (error) {
        console.error('Search error:', error);
        apiResults.value = [];
    } finally {
        isLoading.value = false;
    }
}, 300);

// Combiner résultats API + liens rapides filtrés
const filteredLinks = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    
    // Si pas de recherche, afficher les liens rapides
    if (!query) {
        return quickLinks.slice(0, 8).map(link => ({
            ...link,
            title: link.label,
            subtitle: link.description,
            url: link.href,
            isNavigation: true,
        }));
    }

    // Filtrer les liens rapides qui matchent
    const matchingQuickLinks = quickLinks.filter((link) => {
        return (
            link.label.toLowerCase().includes(query) ||
            link.description.toLowerCase().includes(query) ||
            link.keywords.some((k) => k.includes(query))
        );
    }).map(link => ({
        ...link,
        title: link.label,
        subtitle: link.description,
        url: link.href,
        isNavigation: true,
        score: 50, // Score moyen pour les liens rapides
    }));

    // Combiner avec les résultats API (API en premier car plus pertinents)
    const combined = [
        ...apiResults.value.map(r => ({ ...r, isNavigation: false })),
        ...matchingQuickLinks,
    ];

    // Trier par score et limiter à 12 résultats
    return combined
        .sort((a, b) => (b.score || 0) - (a.score || 0))
        .slice(0, 12);
});

// Watch pour déclencher la recherche API
watch(searchQuery, (newQuery) => {
    if (newQuery && newQuery.length >= 2) {
        isLoading.value = true;
        searchAPI(newQuery);
    } else {
        apiResults.value = [];
        isLoading.value = false;
    }
});

// Reset de l'index quand les résultats changent
watch(filteredLinks, () => {
    selectedIndex.value = 0;
});

// Focus sur l'input quand la modale s'ouvre
watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            nextTick(() => {
                searchInput.value?.focus();
            });
        } else {
            searchQuery.value = "";
            selectedIndex.value = 0;
            apiResults.value = [];
        }
    }
);

const close = () => {
    emit("update:modelValue", false);
};

const navigate = (item) => {
    close();
    const url = item.url || item.href;
    if (url && url !== '#') {
        router.visit(url);
    }
};

const handleKeydown = (event) => {
    switch (event.key) {
        case "ArrowDown":
            event.preventDefault();
            selectedIndex.value = Math.min(selectedIndex.value + 1, filteredLinks.value.length - 1);
            break;
        case "ArrowUp":
            event.preventDefault();
            selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
            break;
        case "Enter":
            event.preventDefault();
            if (filteredLinks.value[selectedIndex.value]) {
                navigate(filteredLinks.value[selectedIndex.value]);
            }
            break;
        case "Escape":
            close();
            break;
    }
};

// Couleurs de catégorie
const getCategoryColor = (type) => {
    const colors = {
        'depute': 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30',
        'senateur': 'text-purple-600 bg-purple-100 dark:text-purple-400 dark:bg-purple-900/30',
        'loi': 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/30',
        'idee': 'text-emerald-600 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30',
        'maire': 'text-rose-600 bg-rose-100 dark:text-rose-400 dark:bg-rose-900/30',
        'navigation': 'text-slate-600 bg-slate-100 dark:text-slate-400 dark:bg-slate-700',
    };
    return colors[type] || colors['navigation'];
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close" />

                <!-- Modal -->
                <Transition
                    enter-active-class="duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="modelValue"
                        class="relative mx-auto max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10"
                    >
                        <!-- Search Input -->
                        <div class="flex items-center border-b border-gray-200 dark:border-gray-700 px-4">
                            <svg v-if="!isLoading" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <!-- Loading spinner -->
                            <svg v-else class="h-5 w-5 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <input
                                ref="searchInput"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Rechercher un député, sénateur, loi, idée..."
                                class="w-full border-0 bg-transparent py-4 px-3 text-gray-900 dark:text-gray-100 placeholder-gray-500 focus:ring-0 text-base"
                                @keydown="handleKeydown"
                            />
                            <kbd class="hidden sm:inline-flex items-center gap-1 rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                Échap
                            </kbd>
                        </div>

                        <!-- Category hint -->
                        <div v-if="!searchQuery" class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            💡 Tapez pour rechercher un <span class="text-blue-500">député</span>, <span class="text-purple-500">sénateur</span>, <span class="text-amber-500">loi</span> ou <span class="text-emerald-500">idée citoyenne</span>
                        </div>

                        <!-- Results -->
                        <ul class="max-h-96 scroll-py-2 overflow-y-auto py-2">
                            <li v-if="filteredLinks.length === 0 && !isLoading" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                <span class="text-2xl block mb-2">🔍</span>
                                Aucun résultat trouvé pour "<strong>{{ searchQuery }}</strong>"
                            </li>

                            <li
                                v-for="(item, index) in filteredLinks"
                                :key="item.url || item.href || index"
                                @click="navigate(item)"
                                @mouseenter="selectedIndex = index"
                                class="cursor-pointer px-4 py-3 flex items-center gap-3 transition-colors"
                                :class="selectedIndex === index ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <!-- Photo/Icon -->
                                <div class="flex-shrink-0">
                                    <img 
                                        v-if="item.photo_url" 
                                        :src="item.photo_url" 
                                        :alt="item.title"
                                        class="w-10 h-10 rounded-full object-cover ring-2 ring-white dark:ring-gray-700"
                                        @error="$event.target.style.display='none'"
                                    />
                                    <span v-else class="text-2xl flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700">
                                        {{ item.icon }}
                                    </span>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ item.title }}
                                        </p>
                                        <span 
                                            v-if="item.category && !item.isNavigation"
                                            class="px-1.5 py-0.5 text-xs font-medium rounded"
                                            :class="getCategoryColor(item.type)"
                                        >
                                            {{ item.category }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                        {{ item.subtitle }}
                                    </p>
                                </div>

                                <!-- Arrow indicator -->
                                <svg
                                    v-if="selectedIndex === index"
                                    class="h-5 w-5 text-indigo-500 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </li>
                        </ul>

                        <!-- Footer -->
                        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1">
                                    <kbd class="rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 font-medium">↑</kbd>
                                    <kbd class="rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 font-medium">↓</kbd>
                                    <span>Naviguer</span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd class="rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 font-medium">↵</kbd>
                                    <span>Ouvrir</span>
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="apiResults.length > 0" class="text-indigo-500">
                                    {{ apiResults.length }} résultat{{ apiResults.length > 1 ? 's' : '' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd class="rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 font-medium">Ctrl</kbd>
                                    <kbd class="rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 font-medium">K</kbd>
                                </span>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
