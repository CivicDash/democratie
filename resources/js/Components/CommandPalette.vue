<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
import { router } from "@inertiajs/vue3";

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

// Navigation rapide
const quickLinks = [
    { icon: "🏠", label: "Accueil", description: "Retour au dashboard", href: "/dashboard", keywords: ["home", "dashboard", "accueil"] },
    { icon: "👥", label: "Députés", description: "577 députés de l'Assemblée Nationale", href: "/representants/deputes", keywords: ["depute", "assemblee", "an"] },
    { icon: "🏰", label: "Sénateurs", description: "348 sénateurs du Sénat", href: "/representants/senateurs", keywords: ["senateur", "senat"] },
    { icon: "🗳️", label: "Scrutins", description: "Votes publics AN & Sénat", href: "/legislation/scrutins", keywords: ["vote", "scrutin", "ballot"] },
    { icon: "📜", label: "Dossiers Législatifs", description: "Projets et propositions de loi", href: "/legislation", keywords: ["loi", "dossier", "projet", "proposition"] },
    { icon: "🎨", label: "Groupes Parlementaires", description: "Hémicycle et groupes politiques", href: "/legislation/groupes", keywords: ["groupe", "parti", "politique", "hemicycle"] },
    { icon: "📊", label: "Comparaison AN / Sénat", description: "Comparer les deux chambres", href: "/parlement/comparaison", keywords: ["comparer", "statistiques"] },
    { icon: "🗺️", label: "Statistiques France", description: "Carte interactive et données", href: "/statistiques/france", keywords: ["carte", "france", "stats", "region", "departement"] },
    { icon: "💬", label: "Forum Citoyen", description: "Débats et discussions", href: "/topics", keywords: ["forum", "debat", "discussion", "topic"] },
    { icon: "💰", label: "Budget Participatif", description: "Répartition budgétaire", href: "/budget", keywords: ["budget", "argent", "depense"] },
    { icon: "📄", label: "Documents Publics", description: "Documents officiels", href: "/documents", keywords: ["document", "pdf", "officiel"] },
    { icon: "👤", label: "Mon Profil", description: "Paramètres du compte", href: "/profile", keywords: ["profil", "compte", "settings"] },
    { icon: "🏆", label: "Mes Succès", description: "Badges et gamification", href: "/profile/gamification", keywords: ["badge", "succes", "achievement", "gamification"] },
];

// Filtrage des résultats
const filteredLinks = computed(() => {
    if (!searchQuery.value.trim()) {
        return quickLinks.slice(0, 8);
    }

    const query = searchQuery.value.toLowerCase().trim();
    return quickLinks.filter((link) => {
        return (
            link.label.toLowerCase().includes(query) ||
            link.description.toLowerCase().includes(query) ||
            link.keywords.some((k) => k.includes(query))
        );
    });
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
        }
    }
);

const close = () => {
    emit("update:modelValue", false);
};

const navigate = (href) => {
    close();
    router.visit(href);
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
                navigate(filteredLinks.value[selectedIndex.value].href);
            }
            break;
        case "Escape":
            close();
            break;
    }
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
                        class="relative mx-auto max-w-xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10"
                    >
                        <!-- Search Input -->
                        <div class="flex items-center border-b border-gray-200 dark:border-gray-700 px-4">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                ref="searchInput"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Rechercher une page, un député, un scrutin..."
                                class="w-full border-0 bg-transparent py-4 px-3 text-gray-900 dark:text-gray-100 placeholder-gray-500 focus:ring-0 text-base"
                                @keydown="handleKeydown"
                            />
                            <kbd class="hidden sm:inline-flex items-center gap-1 rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                Échap
                            </kbd>
                        </div>

                        <!-- Results -->
                        <ul class="max-h-80 scroll-py-2 overflow-y-auto py-2">
                            <li v-if="filteredLinks.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucun résultat trouvé pour "{{ searchQuery }}"
                            </li>

                            <li
                                v-for="(link, index) in filteredLinks"
                                :key="link.href"
                                @click="navigate(link.href)"
                                @mouseenter="selectedIndex = index"
                                class="cursor-pointer px-4 py-3 flex items-center gap-3 transition-colors"
                                :class="selectedIndex === index ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <span class="text-2xl flex-shrink-0">{{ link.icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ link.label }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ link.description }}
                                    </p>
                                </div>
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
                            <div class="flex items-center gap-1">
                                <kbd class="rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 font-medium">?</kbd>
                                <span>Plus de raccourcis</span>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

