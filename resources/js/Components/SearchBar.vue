<template>
  <div class="search-bar-container" ref="container">
    <!-- Search Input -->
    <div class="search-input-wrapper">
      <span class="search-icon">🔍</span>
      <input
        ref="input"
        v-model="query"
        @input="onInput"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.enter.prevent="onEnter"
        @keydown.esc="closeSuggestions"
        @focus="onFocus"
        type="text"
        :placeholder="placeholder"
        class="search-input"
        autocomplete="off"
      />
      <button
        v-if="query"
        @click="clearSearch"
        class="clear-button"
        type="button"
      >
        ✕
      </button>
    </div>

    <!-- Suggestions Dropdown -->
    <transition name="fade">
      <div v-if="showSuggestions && suggestions.length > 0" class="suggestions-dropdown">
        <div
          v-for="(suggestion, index) in suggestions"
          :key="suggestion.id"
          @click="selectSuggestion(suggestion)"
          @mouseenter="selectedIndex = index"
          :class="['suggestion-item', { active: selectedIndex === index }]"
        >
          <span class="suggestion-icon">{{ getTypeIcon(suggestion.type) }}</span>
          <div class="suggestion-content">
            <p class="suggestion-title" v-html="highlightMatch(suggestion.title)"></p>
            <span class="suggestion-type">{{ getTypeLabel(suggestion.type) }}</span>
          </div>
        </div>
      </div>
    </transition>

    <!-- Loading -->
    <div v-if="loading" class="search-loading">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Rechercher des sujets, posts, documents...',
  },
  minChars: {
    type: Number,
    default: 2,
  },
  debounceMs: {
    type: Number,
    default: 300,
  },
  autoFocus: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['search', 'select']);

const container = ref(null);
const input = ref(null);
const query = ref('');
const suggestions = ref([]);
const showSuggestions = ref(false);
const loading = ref(false);
const selectedIndex = ref(-1);
let debounceTimeout = null;

/**
 * Fetch autocomplete suggestions
 */
const fetchSuggestions = async () => {
  if (query.value.length < props.minChars) {
    suggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  loading.value = true;

  try {
    const response = await axios.get('/api/search/autocomplete', {
      params: {
        q: query.value,
        limit: 5,
      },
    });

    if (response.data.success) {
      suggestions.value = response.data.suggestions;
      showSuggestions.value = true;
      selectedIndex.value = -1;
    }
  } catch (error) {
    console.error('Erreur autocomplete:', error);
    suggestions.value = [];
  } finally {
    loading.value = false;
  }
};

/**
 * Handle input with debounce
 */
const onInput = () => {
  clearTimeout(debounceTimeout);
  
  if (query.value.length < props.minChars) {
    suggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  debounceTimeout = setTimeout(() => {
    fetchSuggestions();
  }, props.debounceMs);
};

/**
 * Handle focus
 */
const onFocus = () => {
  if (query.value.length >= props.minChars && suggestions.value.length > 0) {
    showSuggestions.value = true;
  }
};

/**
 * Navigate down in suggestions
 */
const navigateDown = () => {
  if (selectedIndex.value < suggestions.value.length - 1) {
    selectedIndex.value++;
  }
};

/**
 * Navigate up in suggestions
 */
const navigateUp = () => {
  if (selectedIndex.value > 0) {
    selectedIndex.value--;
  }
};

/**
 * Handle Enter key
 */
const onEnter = () => {
  if (selectedIndex.value >= 0 && suggestions.value[selectedIndex.value]) {
    selectSuggestion(suggestions.value[selectedIndex.value]);
  } else {
    // Full search
    performSearch();
  }
};

/**
 * Select a suggestion
 */
const selectSuggestion = (suggestion) => {
  emit('select', suggestion);
  router.visit(suggestion.url);
  query.value = '';
  suggestions.value = [];
  showSuggestions.value = false;
};

/**
 * Perform full search
 */
const performSearch = () => {
  if (query.value.trim().length < props.minChars) return;

  emit('search', query.value);
  router.visit(`/search?q=${encodeURIComponent(query.value)}`);
  closeSuggestions();
};

/**
 * Clear search
 */
const clearSearch = () => {
  query.value = '';
  suggestions.value = [];
  showSuggestions.value = false;
  selectedIndex.value = -1;
  input.value?.focus();
};

/**
 * Close suggestions
 */
const closeSuggestions = () => {
  showSuggestions.value = false;
  selectedIndex.value = -1;
};

/**
 * Get type icon
 */
const getTypeIcon = (type) => {
  const icons = {
    'question': '❓',
    'proposal': '💡',
    'debate': '💬',
    'announcement': '📢',
  };
  return icons[type] || '📝';
};

/**
 * Get type label
 */
const getTypeLabel = (type) => {
  const labels = {
    'question': 'Question',
    'proposal': 'Proposition',
    'debate': 'Débat',
    'announcement': 'Annonce',
  };
  return labels[type] || type;
};

/**
 * Highlight matching text
 */
const highlightMatch = (text) => {
  if (!query.value) return text;
  
  const regex = new RegExp(`(${query.value})`, 'gi');
  return text.replace(regex, '<strong>$1</strong>');
};

/**
 * Click outside to close
 */
const handleClickOutside = (event) => {
  if (container.value && !container.value.contains(event.target)) {
    closeSuggestions();
  }
};

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  
  if (props.autoFocus) {
    input.value?.focus();
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  clearTimeout(debounceTimeout);
});

// Expose methods
defineExpose({
  focus: () => input.value?.focus(),
  clear: clearSearch,
});
</script>

<style scoped>
.search-bar-container {
  position: relative;
  width: 100%;
  max-width: 600px;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 16px;
  font-size: 1.25rem;
  opacity: 0.5;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 12px 48px 12px 48px;
  font-size: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  transition: all 0.2s;
  outline: none;
}

.search-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-button {
  position: absolute;
  right: 12px;
  width: 28px;
  height: 28px;
  border: none;
  background: #e2e8f0;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  color: #64748b;
  font-size: 0.875rem;
}

.clear-button:hover {
  background: #cbd5e0;
  color: #1e293b;
}

.suggestions-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  max-height: 400px;
  overflow-y: auto;
  z-index: 1000;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f1f5f9;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-item:hover,
.suggestion-item.active {
  background: #f8fafc;
}

.suggestion-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.suggestion-content {
  flex: 1;
  min-width: 0;
}

.suggestion-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 500;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.suggestion-title strong {
  color: #3b82f6;
  font-weight: 700;
}

.suggestion-type {
  font-size: 0.8rem;
  color: #64748b;
}

.search-loading {
  position: absolute;
  right: 48px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

/* Responsive */
@media (max-width: 640px) {
  .search-input {
    padding: 10px 40px 10px 40px;
    font-size: 0.9rem;
  }

  .suggestion-title {
    font-size: 0.875rem;
  }
}
</style>

