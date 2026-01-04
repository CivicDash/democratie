<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Écrivez votre message... Utilisez @ pour mentionner quelqu\'un',
    },
    rows: {
        type: Number,
        default: 4,
    },
    maxLength: {
        type: Number,
        default: 5000,
    },
});

const emit = defineEmits(['update:modelValue']);

const textareaRef = ref(null);
const content = ref(props.modelValue);
const showSuggestions = ref(false);
const suggestions = ref([]);
const selectedIndex = ref(0);
const loading = ref(false);
const mentionQuery = ref('');
const mentionStart = ref(0);

// Sync avec v-model
watch(() => props.modelValue, (val) => {
    content.value = val;
});

watch(content, (val) => {
    emit('update:modelValue', val);
});

// Détecter si on est en train de taper une mention
const checkForMention = () => {
    const textarea = textareaRef.value;
    if (!textarea) return;

    const cursorPos = textarea.selectionStart;
    const textBeforeCursor = content.value.substring(0, cursorPos);
    
    // Chercher le dernier @ non suivi d'un espace
    const mentionMatch = textBeforeCursor.match(/@(\w*)$/);
    
    if (mentionMatch) {
        mentionQuery.value = mentionMatch[1];
        mentionStart.value = cursorPos - mentionMatch[0].length;
        
        if (mentionQuery.value.length >= 1) {
            fetchSuggestions(mentionQuery.value);
        } else {
            showSuggestions.value = false;
        }
    } else {
        showSuggestions.value = false;
        suggestions.value = [];
    }
};

// Récupérer les suggestions d'utilisateurs
const fetchSuggestions = debounce(async (query) => {
    if (query.length < 1) return;
    
    loading.value = true;
    try {
        const response = await axios.get('/api/mentions/suggest', {
            params: { q: query },
        });
        suggestions.value = response.data.suggestions || [];
        showSuggestions.value = suggestions.value.length > 0;
        selectedIndex.value = 0;
    } catch (e) {
        console.error('Erreur suggestions:', e);
        suggestions.value = [];
        showSuggestions.value = false;
    } finally {
        loading.value = false;
    }
}, 200);

// Insérer une mention
const insertMention = (user) => {
    const textarea = textareaRef.value;
    const before = content.value.substring(0, mentionStart.value);
    const after = content.value.substring(textarea.selectionStart);
    
    const mention = `@${user.name} `;
    content.value = before + mention + after;
    
    showSuggestions.value = false;
    suggestions.value = [];
    
    // Replacer le curseur après la mention
    nextTick(() => {
        const newPos = mentionStart.value + mention.length;
        textarea.focus();
        textarea.setSelectionRange(newPos, newPos);
    });
};

// Gestion du clavier
const handleKeydown = (e) => {
    if (!showSuggestions.value) return;
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1);
            break;
        case 'ArrowUp':
            e.preventDefault();
            selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
            break;
        case 'Enter':
            if (suggestions.value.length > 0) {
                e.preventDefault();
                insertMention(suggestions.value[selectedIndex.value]);
            }
            break;
        case 'Escape':
            showSuggestions.value = false;
            break;
        case 'Tab':
            if (suggestions.value.length > 0) {
                e.preventDefault();
                insertMention(suggestions.value[selectedIndex.value]);
            }
            break;
    }
};

// Fermer suggestions au clic extérieur
const handleBlur = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};

const charCount = computed(() => content.value.length);
const isOverLimit = computed(() => charCount.value > props.maxLength);
</script>

<template>
    <div class="relative">
        <textarea
            ref="textareaRef"
            v-model="content"
            :rows="rows"
            :maxlength="maxLength + 100"
            :placeholder="placeholder"
            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 resize-y pr-16"
            :class="{ 'border-red-500': isOverLimit }"
            @input="checkForMention"
            @keydown="handleKeydown"
            @blur="handleBlur"
        ></textarea>

        <!-- Compteur de caractères -->
        <div class="absolute bottom-2 right-3 text-xs" :class="isOverLimit ? 'text-red-500' : 'text-gray-400'">
            {{ charCount }} / {{ maxLength }}
        </div>

        <!-- Suggestions de mentions -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showSuggestions"
                class="absolute z-20 mt-1 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div v-if="loading" class="px-4 py-3 text-center text-gray-500">
                    <span class="animate-spin inline-block mr-2">⏳</span>
                    Recherche...
                </div>
                
                <div v-else-if="suggestions.length === 0" class="px-4 py-3 text-center text-gray-500 text-sm">
                    Aucun utilisateur trouvé
                </div>
                
                <div v-else class="max-h-48 overflow-y-auto">
                    <button
                        v-for="(user, index) in suggestions"
                        :key="user.id"
                        type="button"
                        class="w-full px-4 py-2 text-left flex items-center gap-3 transition-colors"
                        :class="index === selectedIndex 
                            ? 'bg-indigo-50 dark:bg-indigo-900/30' 
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                        @mousedown.prevent="insertMention(user)"
                        @mouseenter="selectedIndex = index"
                    >
                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-indigo-400 to-purple-400 flex items-center justify-center text-white text-sm font-bold">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ user.name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ user.mention }}
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Aide -->
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            💡 Utilisez <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">@nom</code> pour mentionner un utilisateur
        </p>
    </div>
</template>
