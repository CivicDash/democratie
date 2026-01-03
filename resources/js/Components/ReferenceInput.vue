<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Rédigez votre message...' },
    rows: { type: Number, default: 4 },
    maxLength: { type: Number, default: 10000 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'reference-inserted']);

const textareaRef = ref(null);
const showSuggestions = ref(false);
const suggestions = ref([]);
const isLoading = ref(false);
const selectedIndex = ref(0);
const mentionQuery = ref('');
const mentionType = ref(null);
const mentionStartPos = ref(0);

// Patterns reconnus
const mentionTriggers = {
    '@depute:': 'depute',
    '@senateur:': 'senateur',
    '@maire:': 'maire',
    '@loi:': 'loi',
    '@scrutin:': 'scrutin',
};

// Local value
const localValue = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

// Détecter une mention en cours de saisie
function checkForMention(e) {
    const textarea = textareaRef.value;
    if (!textarea) return;

    const cursorPos = textarea.selectionStart;
    const textBeforeCursor = localValue.value.substring(0, cursorPos);

    // Chercher un pattern @type: suivi de texte
    for (const [trigger, type] of Object.entries(mentionTriggers)) {
        const triggerIndex = textBeforeCursor.lastIndexOf(trigger);
        if (triggerIndex !== -1) {
            const afterTrigger = textBeforeCursor.substring(triggerIndex + trigger.length);
            // Si pas d'espace après le trigger, on est en train de taper une mention
            if (!afterTrigger.includes(' ') && afterTrigger.length >= 0) {
                mentionType.value = type;
                mentionQuery.value = afterTrigger;
                mentionStartPos.value = triggerIndex;
                
                if (afterTrigger.length >= 2) {
                    searchSuggestions(type, afterTrigger);
                } else {
                    showSuggestions.value = false;
                }
                return;
            }
        }
    }

    // Pas de mention détectée
    showSuggestions.value = false;
    mentionType.value = null;
}

// Rechercher des suggestions
const searchSuggestions = debounce(async (type, query) => {
    if (!query || query.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    isLoading.value = true;
    selectedIndex.value = 0;

    try {
        const endpoint = getSearchEndpoint(type);
        const separator = endpoint.includes('?') ? '&' : '?';
        const response = await fetch(`${endpoint}${separator}q=${encodeURIComponent(query)}&limit=5`);
        const data = await response.json();
        
        suggestions.value = formatSuggestions(type, data);
        showSuggestions.value = suggestions.value.length > 0;
    } catch (e) {
        console.error('Erreur recherche suggestions:', e);
        suggestions.value = [];
        showSuggestions.value = false;
    } finally {
        isLoading.value = false;
    }
}, 300);

function getSearchEndpoint(type) {
    // Utilise l'API de recherche globale avec filtre par catégorie
    const categoryMap = {
        depute: 'deputes',
        senateur: 'senateurs',
        maire: 'maires',
        loi: 'lois',
        scrutin: 'all', // Pas de catégorie spécifique pour scrutins
    };
    const category = categoryMap[type] || 'all';
    return `/api/search?category=${category}`;
}

function formatSuggestions(type, data) {
    // Le GlobalSearchController retourne { results: { [category]: [...] } }
    let items = [];
    
    if (data.results) {
        // Si c'est un objet avec des catégories
        if (typeof data.results === 'object' && !Array.isArray(data.results)) {
            // Récupérer les items de la catégorie correspondante
            const categoryMap = {
                depute: 'deputes',
                senateur: 'senateurs',
                maire: 'maires',
                loi: 'lois',
                scrutin: 'all',
            };
            const categoryKey = categoryMap[type];
            items = data.results[categoryKey] || [];
            
            // Aplatir si c'est un objet
            if (!Array.isArray(items)) {
                items = Object.values(data.results).flat();
            }
        } else {
            items = data.results;
        }
    } else if (Array.isArray(data)) {
        items = data;
    }
    
    return items.slice(0, 5).map(item => {
        // L'API GlobalSearch retourne déjà un format unifié avec title, subtitle, photo_url, url
        return {
            id: item.id || item.uid || item.matricule || item.loicod,
            label: item.title || item.nom_complet || `${item.prenom || ''} ${item.nom || ''}`.trim(),
            sublabel: item.subtitle || item.groupe_politique || item.circonscription,
            photo: item.photo_url,
            icon: item.icon || getDefaultIcon(type),
        };
    });
}

function getDefaultIcon(type) {
    return {
        depute: '👤',
        senateur: '🏛️',
        maire: '🏘️',
        loi: '📜',
        scrutin: '🗳️',
    }[type] || '📋';
}

// Insérer la référence sélectionnée
function insertReference(suggestion) {
    const textarea = textareaRef.value;
    if (!textarea || !mentionType.value) return;

    const trigger = `@${mentionType.value}:`;
    const currentValue = localValue.value;
    
    // Texte avant le trigger + référence complète + texte après le curseur
    const beforeTrigger = currentValue.substring(0, mentionStartPos.value);
    const afterCursor = currentValue.substring(textarea.selectionStart);
    
    const newValue = `${beforeTrigger}${trigger}${suggestion.id} ${afterCursor}`;
    localValue.value = newValue;

    // Fermer les suggestions
    showSuggestions.value = false;
    mentionType.value = null;
    mentionQuery.value = '';

    // Repositionner le curseur
    nextTick(() => {
        const newPos = beforeTrigger.length + trigger.length + suggestion.id.length + 1;
        textarea.focus();
        textarea.setSelectionRange(newPos, newPos);
    });

    emit('reference-inserted', {
        type: mentionType.value,
        id: suggestion.id,
        label: suggestion.label,
    });
}

// Navigation clavier dans les suggestions
function handleKeydown(e) {
    if (!showSuggestions.value) return;

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            selectedIndex.value = (selectedIndex.value + 1) % suggestions.value.length;
            break;
        case 'ArrowUp':
            e.preventDefault();
            selectedIndex.value = selectedIndex.value === 0 
                ? suggestions.value.length - 1 
                : selectedIndex.value - 1;
            break;
        case 'Enter':
        case 'Tab':
            if (suggestions.value[selectedIndex.value]) {
                e.preventDefault();
                insertReference(suggestions.value[selectedIndex.value]);
            }
            break;
        case 'Escape':
            showSuggestions.value = false;
            break;
    }
}

// Fermer suggestions au clic dehors
function handleClickOutside(e) {
    if (!textareaRef.value?.contains(e.target)) {
        showSuggestions.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Icônes des types
const typeIcons = {
    depute: { icon: '👤', color: 'text-blue-600', label: 'Député' },
    senateur: { icon: '🏛️', color: 'text-rose-600', label: 'Sénateur' },
    maire: { icon: '🏘️', color: 'text-amber-600', label: 'Maire' },
    loi: { icon: '📜', color: 'text-indigo-600', label: 'Loi' },
    scrutin: { icon: '🗳️', color: 'text-emerald-600', label: 'Scrutin' },
};

const currentTypeInfo = computed(() => {
    return typeIcons[mentionType.value] || { icon: '📋', color: 'text-gray-600', label: 'Référence' };
});
</script>

<template>
    <div class="relative">
        <!-- Aide mentions -->
        <div class="mb-2 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>💡 Mentions :</span>
            <span 
                v-for="(info, key) in typeIcons" 
                :key="key" 
                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded"
                :class="info.color"
            >
                @{{ key }}:
            </span>
        </div>

        <!-- Textarea -->
        <textarea
            ref="textareaRef"
            v-model="localValue"
            :placeholder="placeholder"
            :rows="rows"
            :maxlength="maxLength"
            :disabled="disabled"
            @input="checkForMention"
            @keydown="handleKeydown"
            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 resize-y"
        ></textarea>

        <!-- Dropdown suggestions -->
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div 
                v-if="showSuggestions"
                class="absolute z-50 mt-1 w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <!-- Header -->
                <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 flex items-center gap-2">
                    <span :class="currentTypeInfo.color">{{ currentTypeInfo.icon }}</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Chercher un {{ currentTypeInfo.label.toLowerCase() }}
                    </span>
                    <span class="ml-auto text-xs text-gray-400">
                        "{{ mentionQuery }}"
                    </span>
                </div>

                <!-- Loading -->
                <div v-if="isLoading" class="p-4 text-center text-gray-500">
                    <div class="animate-spin inline-block w-5 h-5 border-2 border-indigo-500 border-t-transparent rounded-full"></div>
                </div>

                <!-- Liste des suggestions -->
                <ul v-else class="max-h-64 overflow-y-auto">
                    <li 
                        v-for="(suggestion, index) in suggestions" 
                        :key="suggestion.id"
                        @click="insertReference(suggestion)"
                        @mouseenter="selectedIndex = index"
                        class="px-3 py-2 cursor-pointer flex items-center gap-3 transition"
                        :class="index === selectedIndex ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    >
                        <!-- Photo ou icône -->
                        <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                            <img 
                                v-if="suggestion.photo"
                                :src="suggestion.photo"
                                :alt="suggestion.label"
                                class="w-full h-full object-cover"
                            />
                            <span v-else class="text-lg">{{ suggestion.icon }}</span>
                        </div>

                        <!-- Infos -->
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">
                                {{ suggestion.label }}
                            </p>
                            <p v-if="suggestion.sublabel" class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ suggestion.sublabel }}
                            </p>
                        </div>

                        <!-- ID qui sera inséré -->
                        <span class="text-xs text-gray-400 font-mono">
                            {{ suggestion.id }}
                        </span>
                    </li>

                    <li v-if="suggestions.length === 0" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                        Aucun résultat pour "{{ mentionQuery }}"
                    </li>
                </ul>

                <!-- Footer -->
                <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 text-xs text-gray-500 flex items-center gap-3">
                    <span>↑↓ Naviguer</span>
                    <span>⏎ Sélectionner</span>
                    <span>⎋ Fermer</span>
                </div>
            </div>
        </Transition>

        <!-- Compteur -->
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ localValue.length }} / {{ maxLength }} caractères</span>
            <slot name="footer"></slot>
        </div>
    </div>
</template>
