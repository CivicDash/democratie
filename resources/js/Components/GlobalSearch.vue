<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Rechercher un député, un scrutin, un dossier...',
  },
  showFilters: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['select']);

const query = ref('');
const isOpen = ref(false);
const isLoading = ref(false);
const results = ref({});
const suggestions = ref([]);
const selectedTags = ref([]);
const selectedTypes = ref([]);

const availableTypes = [
  { value: 'deputes', label: 'Députés', icon: '👤' },
  { value: 'senateurs', label: 'Sénateurs', icon: '👔' },
  { value: 'scrutins', label: 'Scrutins', icon: '🗳️' },
  { value: 'dossiers', label: 'Dossiers', icon: '📜' },
  { value: 'amendements', label: 'Amendements', icon: '📝' },
  { value: 'topics', label: 'Débats', icon: '💬' },
];

const totalResults = computed(() => {
  return Object.values(results.value).reduce((sum, arr) => sum + (arr?.length || 0), 0);
});

// Recherche avec debounce
const performSearch = useDebounceFn(async () => {
  if (query.value.length < 2 && selectedTags.value.length === 0) {
    results.value = {};
    isOpen.value = false;
    return;
  }

  isLoading.value = true;
  
  try {
    const params = new URLSearchParams({
      q: query.value,
      limit: 5,
    });

    selectedTypes.value.forEach(type => params.append('types[]', type));
    selectedTags.value.forEach(tag => params.append('tags[]', tag));

    const response = await fetch(`/api/search?${params}`);
    const data = await response.json();
    
    results.value = data.results;
    isOpen.value = true;
  } catch (error) {
    console.error('Erreur de recherche:', error);
  } finally {
    isLoading.value = false;
  }
}, 300);

// Suggestions avec debounce
const fetchSuggestions = useDebounceFn(async () => {
  if (query.value.length < 2) {
    suggestions.value = [];
    return;
  }

  try {
    const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query.value)}`);
    const data = await response.json();
    suggestions.value = data.suggestions;
  } catch (error) {
    console.error('Erreur suggestions:', error);
  }
}, 200);

watch(query, () => {
  performSearch();
  fetchSuggestions();
});

watch([selectedTags, selectedTypes], () => {
  if (query.value.length >= 2 || selectedTags.value.length > 0) {
    performSearch();
  }
}, { deep: true });

const selectResult = (result) => {
  if (result.url) {
    router.visit(result.url);
  }
  emit('select', result);
  isOpen.value = false;
  query.value = '';
};

const toggleType = (type) => {
  const index = selectedTypes.value.indexOf(type);
  if (index > -1) {
    selectedTypes.value.splice(index, 1);
  } else {
    selectedTypes.value.push(type);
  }
};

const getTypeIcon = (type) => {
  const typeObj = availableTypes.find(t => t.value === type);
  return typeObj?.icon || '📄';
};

const getTypeLabel = (type) => {
  const typeObj = availableTypes.find(t => t.value === type);
  return typeObj?.label || type;
};

// Fermer au clic extérieur
const closeSearch = () => {
  setTimeout(() => {
    isOpen.value = false;
  }, 200);
};
</script>

<template>
  <div class="relative w-full">
    <!-- Barre de recherche -->
    <div class="relative">
      <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
        <svg v-if="!isLoading" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <svg v-else class="w-5 h-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      
      <input
        v-model="query"
        type="text"
        :placeholder="placeholder"
        class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-gray-100"
        @focus="isOpen = query.length >= 2 || selectedTags.length > 0"
        @blur="closeSearch"
      />

      <div v-if="query" class="absolute inset-y-0 right-0 flex items-center pr-3">
        <button
          @click="query = ''; results = {}; isOpen = false"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Filtres types -->
    <div v-if="showFilters" class="mt-3 flex flex-wrap gap-2">
      <button
        v-for="type in availableTypes"
        :key="type.value"
        @click="toggleType(type.value)"
        :class="[
          'px-3 py-1.5 rounded-full text-sm font-medium transition-all',
          selectedTypes.includes(type.value)
            ? 'bg-blue-600 text-white'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
        ]"
      >
        {{ type.icon }} {{ type.label }}
      </button>
    </div>

    <!-- Résultats -->
    <div
      v-if="isOpen && (totalResults > 0 || suggestions.length > 0)"
      class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-2xl max-h-[600px] overflow-y-auto"
    >
      <!-- Suggestions rapides -->
      <div v-if="suggestions.length > 0 && query.length >= 2 && totalResults === 0" class="p-2">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 px-3 py-2">
          Suggestions
        </div>
        <button
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @click="suggestion.url ? selectResult(suggestion) : (query = suggestion.text)"
          class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
        >
          <div class="text-sm text-gray-900 dark:text-gray-100">
            {{ suggestion.text }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ suggestion.type }}
          </div>
        </button>
      </div>

      <!-- Résultats par type -->
      <div v-for="(items, type) in results" :key="type" class="border-b border-gray-200 dark:border-gray-700 last:border-0">
        <div v-if="items && items.length > 0" class="p-2">
          <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 px-3 py-2">
            <span>{{ getTypeIcon(type) }}</span>
            <span>{{ getTypeLabel(type) }}</span>
            <span class="ml-auto bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full">
              {{ items.length }}
            </span>
          </div>

          <button
            v-for="item in items"
            :key="item.id"
            @click="selectResult(item)"
            class="w-full text-left px-3 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition group"
          >
            <div class="flex items-start gap-3">
              <!-- Image (si disponible) -->
              <img
                v-if="item.image"
                :src="item.image"
                :alt="item.title"
                class="w-12 h-12 rounded-full object-cover flex-shrink-0"
              />
              
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate">
                    {{ item.title }}
                  </h4>
                  <span
                    v-if="item.badge"
                    class="px-2 py-0.5 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 flex-shrink-0"
                  >
                    {{ item.badge }}
                  </span>
                </div>
                
                <p v-if="item.subtitle" class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                  {{ item.subtitle }}
                </p>
                
                <p v-if="item.description" class="text-xs text-gray-500 dark:text-gray-500 line-clamp-2">
                  {{ item.description }}
                </p>

                <!-- Tags -->
                <div v-if="item.tags && item.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
                  <span
                    v-for="tag in item.tags.slice(0, 3)"
                    :key="tag"
                    class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                  >
                    #{{ tag }}
                  </span>
                </div>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Aucun résultat -->
      <div v-if="totalResults === 0 && query.length >= 2 && !isLoading" class="p-6 text-center">
        <div class="text-4xl mb-2">🔍</div>
        <p class="text-gray-600 dark:text-gray-400 text-sm">
          Aucun résultat pour "<strong>{{ query }}</strong>"
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

