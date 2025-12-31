<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    placeholder: { type: String, default: 'Rechercher...' },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

// State
const query = ref('');
const results = ref([]);
const categories = ref([]);
const isLoading = ref(false);
const isOpen = ref(false);
const selectedIndex = ref(-1);
const inputRef = ref(null);
const containerRef = ref(null);

// Debounce
let searchTimeout = null;

// Fetch suggestions
async function fetchSuggestions() {
    if (query.value.length < 2) {
        results.value = [];
        categories.value = [];
        isOpen.value = false;
        return;
    }

    isLoading.value = true;
    
    try {
        const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query.value)}`);
        const data = await response.json();
        
        results.value = data.results || [];
        categories.value = data.categories || [];
        isOpen.value = results.value.length > 0;
        selectedIndex.value = -1;
    } catch (error) {
        console.error('Search error:', error);
        results.value = [];
    } finally {
        isLoading.value = false;
    }
}

// Watch query changes with debounce
watch(query, () => {
    clearTimeout(searchTimeout);
    if (query.value.length >= 2) {
        searchTimeout = setTimeout(fetchSuggestions, 200);
    } else {
        results.value = [];
        isOpen.value = false;
    }
});

// Keyboard navigation
function handleKeydown(event) {
    if (!isOpen.value) return;

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            selectedIndex.value = Math.min(selectedIndex.value + 1, results.value.length - 1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            selectedIndex.value = Math.max(selectedIndex.value - 1, -1);
            break;
        case 'Enter':
            event.preventDefault();
            if (selectedIndex.value >= 0 && results.value[selectedIndex.value]) {
                navigateToResult(results.value[selectedIndex.value]);
            } else {
                performFullSearch();
            }
            break;
        case 'Escape':
            closeDropdown();
            break;
    }
}

function navigateToResult(result) {
    if (result.url && result.url !== '#') {
        router.visit(result.url);
        closeDropdown();
    }
}

function performFullSearch() {
    if (query.value.trim()) {
        router.visit(`/recherche?q=${encodeURIComponent(query.value)}`);
        closeDropdown();
    }
}

function closeDropdown() {
    isOpen.value = false;
    selectedIndex.value = -1;
    emit('close');
}

function handleFocus() {
    if (query.value.length >= 2 && results.value.length > 0) {
        isOpen.value = true;
    }
}

// Click outside to close
function handleClickOutside(event) {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
}

// Note: Ctrl+K is handled by CommandPalette component
// This search bar is for inline searching in the header

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    clearTimeout(searchTimeout);
});

// Group results by category for display
const groupedResults = computed(() => {
    const groups = {};
    results.value.forEach(r => {
        if (!groups[r.type]) {
            groups[r.type] = {
                type: r.type,
                category: r.category,
                icon: r.icon,
                items: [],
            };
        }
        groups[r.type].items.push(r);
    });
    return Object.values(groups);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Search Input -->
        <div class="relative">
            <input
                ref="inputRef"
                v-model="query"
                type="text"
                :placeholder="placeholder"
                @focus="handleFocus"
                @keydown="handleKeydown"
                :class="[
                    'w-full pl-10 pr-16 py-2 text-sm border-0 rounded-full transition-all',
                    'bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-gray-600',
                    'text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400',
                    compact ? 'w-40' : 'w-48 xl:w-64'
                ]"
            />
            <!-- Search Icon -->
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg v-if="!isLoading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg v-else class="w-4 h-4 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <!-- Keyboard Shortcut -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <kbd class="hidden xl:inline-flex items-center px-1.5 py-0.5 text-xs font-medium text-gray-400 dark:text-gray-500 bg-gray-200 dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500">
                    ⌘K
                </kbd>
            </div>
        </div>

        <!-- Results Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen && results.length > 0"
                class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50 max-h-[70vh] overflow-y-auto"
            >
                <!-- Grouped Results -->
                <div v-for="(group, groupIndex) in groupedResults" :key="group.type">
                    <!-- Category Header -->
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ group.icon }} {{ group.category }}
                        </span>
                    </div>
                    
                    <!-- Results -->
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <button
                            v-for="(result, resultIndex) in group.items"
                            :key="`${result.type}-${result.id}`"
                            @click="navigateToResult(result)"
                            @mouseenter="selectedIndex = results.indexOf(result)"
                            :class="[
                                'w-full flex items-center gap-3 px-4 py-3 text-left transition-colors',
                                results.indexOf(result) === selectedIndex
                                    ? 'bg-indigo-50 dark:bg-indigo-900/30'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            ]"
                        >
                            <!-- Photo/Icon -->
                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center">
                                <img 
                                    v-if="result.photo_url" 
                                    :src="result.photo_url" 
                                    :alt="result.title"
                                    class="w-full h-full object-cover"
                                    @error="$event.target.style.display = 'none'"
                                />
                                <span v-else class="text-xl">{{ result.icon }}</span>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ result.title }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ result.subtitle }}
                                </p>
                            </div>
                            
                            <!-- Arrow -->
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Footer: Full Search Link -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600">
                    <button
                        @click="performFullSearch"
                        class="w-full flex items-center justify-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium"
                    >
                        <span>🔍</span>
                        <span>Voir tous les résultats pour "{{ query }}"</span>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- No Results -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="query.length >= 2 && !isLoading && results.length === 0 && isOpen"
                class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 text-center z-50"
            >
                <div class="text-3xl mb-2">🔍</div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Aucun résultat pour "{{ query }}"
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Essayez avec d'autres mots-clés
                </p>
            </div>
        </Transition>
    </div>
</template>
