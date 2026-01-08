<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

// Simple debounce utility (évite d'importer lodash)
function debounce(fn, delay) {
    let timeoutId = null;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Rédigez votre texte...' },
    maxLength: { type: Number, default: 5000 },
    minLength: { type: Number, default: 10 },
    rows: { type: Number, default: 6 },
    disabled: { type: Boolean, default: false },
    showToolbar: { type: Boolean, default: true },
    showPreview: { type: Boolean, default: true },
    allowedFormats: { 
        type: Array, 
        default: () => ['h1', 'h2', 'h3', 'bold', 'italic', 'underline', 'strike', 'list', 'quote', 'link', 'mention'] 
    },
});

const emit = defineEmits(['update:modelValue', 'mention-inserted']);

const textarea = ref(null);
const showPreviewPanel = ref(false);
const localValue = ref(props.modelValue);

// Sync avec v-model
watch(() => props.modelValue, (newVal) => {
    localValue.value = newVal;
});

watch(localValue, (newVal) => {
    emit('update:modelValue', newVal);
});

// Compteur de caractères
const charCount = computed(() => localValue.value.length);
const charCountClass = computed(() => {
    if (charCount.value > props.maxLength) return 'text-red-500';
    if (charCount.value > props.maxLength * 0.9) return 'text-amber-500';
    return 'text-gray-500 dark:text-gray-400';
});

// ========================================================================
// SYSTÈME DE MENTIONS AVEC AUTOCOMPLETE
// ========================================================================

const mentionState = ref({
    active: false,
    type: null,           // null = choix du type, 'depute'|'senateur'|... = recherche
    query: '',
    startPos: 0,
    suggestions: [],
    selectedIndex: 0,
    loading: false,
});

const mentionTypes = {
    depute: { 
        icon: '👤', 
        label: 'Député', 
        color: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-900/30',
        endpoint: '/api/search?category=deputes'
    },
    senateur: { 
        icon: '🏛️', 
        label: 'Sénateur', 
        color: 'text-rose-600 dark:text-rose-400',
        bgColor: 'bg-rose-50 dark:bg-rose-900/30',
        endpoint: '/api/search?category=senateurs'
    },
    maire: { 
        icon: '🏘️', 
        label: 'Maire', 
        color: 'text-amber-600 dark:text-amber-400',
        bgColor: 'bg-amber-50 dark:bg-amber-900/30',
        endpoint: '/api/elus/suggest?types[]=maire'
    },
    loi: { 
        icon: '📜', 
        label: 'Loi / Texte', 
        color: 'text-indigo-600 dark:text-indigo-400',
        bgColor: 'bg-indigo-50 dark:bg-indigo-900/30',
        endpoint: '/api/search?category=lois'
    },
    scrutin: { 
        icon: '🗳️', 
        label: 'Scrutin', 
        color: 'text-emerald-600 dark:text-emerald-400',
        bgColor: 'bg-emerald-50 dark:bg-emerald-900/30',
        endpoint: '/api/search?category=all'
    },
};

// Détecter le début d'une mention (@)
function checkForMention() {
    const el = textarea.value;
    if (!el) return;

    const cursorPos = el.selectionStart;
    const textBeforeCursor = localValue.value.substring(0, cursorPos);
    
    // Chercher le dernier @ non suivi d'un espace
    const lastAtIndex = textBeforeCursor.lastIndexOf('@');
    
    if (lastAtIndex === -1) {
        closeMentionDropdown();
        return;
    }
    
    // Texte après le @
    const afterAt = textBeforeCursor.substring(lastAtIndex + 1);
    
    // Si espace après le @, pas de mention active
    if (afterAt.includes(' ') || afterAt.includes('\n')) {
        closeMentionDropdown();
        return;
    }
    
    // Vérifier si on a déjà un type sélectionné (format @type:query)
    const colonIndex = afterAt.indexOf(':');
    
    if (colonIndex !== -1) {
        // Type déjà sélectionné, on cherche
        const type = afterAt.substring(0, colonIndex).toLowerCase();
        const query = afterAt.substring(colonIndex + 1);
        
        if (mentionTypes[type]) {
            mentionState.value.active = true;
            mentionState.value.type = type;
            mentionState.value.query = query;
            mentionState.value.startPos = lastAtIndex;
            
            if (query.length >= 2) {
                searchMentions(type, query);
            } else {
                mentionState.value.suggestions = [];
            }
            return;
        }
    }
    
    // Pas encore de type, afficher la liste des types filtrée
    mentionState.value.active = true;
    mentionState.value.type = null;
    mentionState.value.query = afterAt;
    mentionState.value.startPos = lastAtIndex;
    mentionState.value.selectedIndex = 0;
    
    // Filtrer les types selon ce qui est tapé
    if (afterAt.length > 0) {
        mentionState.value.suggestions = Object.entries(mentionTypes)
            .filter(([key, val]) => 
                key.toLowerCase().startsWith(afterAt.toLowerCase()) ||
                val.label.toLowerCase().startsWith(afterAt.toLowerCase())
            )
            .map(([key, val]) => ({
                id: key,
                type: 'category',
                label: val.label,
                icon: val.icon,
                color: val.color,
            }));
    } else {
        // Afficher tous les types
        mentionState.value.suggestions = Object.entries(mentionTypes).map(([key, val]) => ({
            id: key,
            type: 'category',
            label: val.label,
            icon: val.icon,
            color: val.color,
        }));
    }
}

// Rechercher des suggestions
const searchMentions = debounce(async (type, query) => {
    if (!query || query.length < 2) {
        mentionState.value.suggestions = [];
        return;
    }

    mentionState.value.loading = true;
    mentionState.value.selectedIndex = 0;

    try {
        const typeConfig = mentionTypes[type];
        const separator = typeConfig.endpoint.includes('?') ? '&' : '?';
        const url = `${typeConfig.endpoint}${separator}q=${encodeURIComponent(query)}&search=${encodeURIComponent(query)}&limit=8`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        mentionState.value.suggestions = formatSuggestions(type, data);
    } catch (e) {
        console.error('Erreur recherche mentions:', e);
        mentionState.value.suggestions = [];
    } finally {
        mentionState.value.loading = false;
    }
}, 250);

function formatSuggestions(type, data) {
    let items = [];
    
    // Différents formats de réponse API
    if (data.results) {
        if (Array.isArray(data.results)) {
            items = data.results;
        } else {
            // Format { deputes: [...], senateurs: [...], ... }
            const categoryKey = type + 's'; // depute -> deputes
            items = data.results[categoryKey] || Object.values(data.results).flat();
        }
    } else if (data.deputes || data.senateurs || data.maires) {
        // Format ElusSuggestionController
        items = [...(data.deputes || []), ...(data.senateurs || []), ...(data.maires || [])];
    } else if (Array.isArray(data)) {
        items = data;
    }
    
    return items.slice(0, 8).map(item => {
        const typeConfig = mentionTypes[type];
        return {
            id: item.id || item.uid || item.matricule || item.loicod,
            type: 'result',
            mentionType: type,
            label: item.title || item.nom_complet || `${item.prenom || ''} ${item.nom || ''}`.trim() || item.intitule,
            sublabel: item.subtitle || item.groupe_politique || item.groupe || item.circonscription || item.nom_commune,
            photo: item.photo_url || item.photo,
            icon: typeConfig.icon,
            color: typeConfig.color,
            bgColor: typeConfig.bgColor,
        };
    });
}

// Sélectionner une suggestion
function selectSuggestion(suggestion) {
    const el = textarea.value;
    if (!el) return;
    
    const currentValue = localValue.value;
    const cursorPos = el.selectionStart;
    const beforeMention = currentValue.substring(0, mentionState.value.startPos);
    const afterCursor = currentValue.substring(cursorPos);
    
    let insertText;
    
    if (suggestion.type === 'category') {
        // On a choisi un type, on insère @type: et on continue
        insertText = `@${suggestion.id}:`;
        localValue.value = beforeMention + insertText + afterCursor;
        
        nextTick(() => {
            const newPos = beforeMention.length + insertText.length;
            el.focus();
            el.setSelectionRange(newPos, newPos);
            // Réinitialiser pour la recherche
            mentionState.value.type = suggestion.id;
            mentionState.value.query = '';
            mentionState.value.suggestions = [];
        });
    } else {
        // On a choisi un résultat, on insère la mention complète
        insertText = `@${suggestion.mentionType}:${suggestion.id} `;
        localValue.value = beforeMention + insertText + afterCursor;
        
        closeMentionDropdown();
        
        nextTick(() => {
            const newPos = beforeMention.length + insertText.length;
            el.focus();
            el.setSelectionRange(newPos, newPos);
        });
        
        emit('mention-inserted', {
            type: suggestion.mentionType,
            id: suggestion.id,
            label: suggestion.label,
        });
    }
}

function closeMentionDropdown() {
    mentionState.value.active = false;
    mentionState.value.type = null;
    mentionState.value.query = '';
    mentionState.value.suggestions = [];
    mentionState.value.selectedIndex = 0;
    mentionState.value.loading = false;
}

// ========================================================================
// FORMATAGE BBCode/Markdown simplifié
// ========================================================================

const formats = {
    h1: { icon: 'H₁', label: 'Titre 1', prefix: '\n# ', suffix: '', shortcut: '' },
    h2: { icon: 'H₂', label: 'Titre 2', prefix: '\n## ', suffix: '', shortcut: '' },
    h3: { icon: 'H₃', label: 'Titre 3', prefix: '\n### ', suffix: '', shortcut: '' },
    bold: { icon: '𝐁', label: 'Gras', prefix: '**', suffix: '**', shortcut: 'Ctrl+B' },
    italic: { icon: '𝐼', label: 'Italique', prefix: '*', suffix: '*', shortcut: 'Ctrl+I' },
    underline: { icon: 'U̲', label: 'Souligné', prefix: '__', suffix: '__', shortcut: 'Ctrl+U' },
    strike: { icon: 'S̶', label: 'Barré', prefix: '~~', suffix: '~~', shortcut: '' },
    list: { icon: '•', label: 'Liste', prefix: '\n- ', suffix: '', shortcut: '' },
    quote: { icon: '❝', label: 'Citation', prefix: '\n> ', suffix: '', shortcut: '' },
    link: { icon: '🔗', label: 'Lien', prefix: '[', suffix: '](url)', shortcut: 'Ctrl+K' },
    mention: { icon: '@', label: 'Mention', prefix: '@', suffix: '', shortcut: '' },
};

// Appliquer un format
function applyFormat(formatKey) {
    if (!props.allowedFormats.includes(formatKey)) return;
    
    const format = formats[formatKey];
    const el = textarea.value;
    const start = el.selectionStart;
    const end = el.selectionEnd;
    const selectedText = localValue.value.substring(start, end);
    
    let newText;
    let newCursorPos;
    
    if (formatKey === 'link') {
        // Pour les liens, ouvrir un prompt
        const url = prompt('URL du lien (domaines officiels uniquement) :', 'https://');
        if (!url) return;
        
        const linkText = selectedText || 'texte du lien';
        newText = localValue.value.substring(0, start) + 
                  `[${linkText}](${url})` + 
                  localValue.value.substring(end);
        newCursorPos = start + linkText.length + url.length + 4;
    } else if (formatKey === 'mention') {
        // Pour les mentions, insérer @ et laisser l'autocomplete se déclencher
        newText = localValue.value.substring(0, start) + '@' + localValue.value.substring(end);
        newCursorPos = start + 1;
        
        localValue.value = newText;
        
        nextTick(() => {
            el.focus();
            el.setSelectionRange(newCursorPos, newCursorPos);
            checkForMention();
        });
        return;
    } else {
        // Format standard
        newText = localValue.value.substring(0, start) + 
                  format.prefix + selectedText + format.suffix + 
                  localValue.value.substring(end);
        newCursorPos = start + format.prefix.length + selectedText.length + format.suffix.length;
    }
    
    localValue.value = newText;
    
    // Remettre le focus et la position
    setTimeout(() => {
        el.focus();
        el.setSelectionRange(newCursorPos, newCursorPos);
    }, 10);
}

// Gestion du clavier
function handleKeydown(event) {
    // Navigation dans les suggestions de mention
    if (mentionState.value.active && mentionState.value.suggestions.length > 0) {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                mentionState.value.selectedIndex = 
                    (mentionState.value.selectedIndex + 1) % mentionState.value.suggestions.length;
                return;
            case 'ArrowUp':
                event.preventDefault();
                mentionState.value.selectedIndex = mentionState.value.selectedIndex === 0 
                    ? mentionState.value.suggestions.length - 1 
                    : mentionState.value.selectedIndex - 1;
                return;
            case 'Enter':
            case 'Tab':
                if (mentionState.value.suggestions[mentionState.value.selectedIndex]) {
                    event.preventDefault();
                    selectSuggestion(mentionState.value.suggestions[mentionState.value.selectedIndex]);
                }
                return;
            case 'Escape':
                event.preventDefault();
                closeMentionDropdown();
                return;
        }
    }
    
    // Raccourcis de formatage
    if (event.ctrlKey || event.metaKey) {
        switch (event.key.toLowerCase()) {
            case 'b':
                event.preventDefault();
                applyFormat('bold');
                break;
            case 'i':
                event.preventDefault();
                applyFormat('italic');
                break;
            case 'u':
                event.preventDefault();
                applyFormat('underline');
                break;
            case 'k':
                event.preventDefault();
                applyFormat('link');
                break;
        }
    }
}

// Input handler pour détecter les mentions
function handleInput() {
    checkForMention();
}

// Fermer les suggestions au clic dehors
function handleClickOutside(e) {
    const editorEl = textarea.value?.closest('.rich-text-editor');
    if (editorEl && !editorEl.contains(e.target)) {
        closeMentionDropdown();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// ========================================================================
// PRÉVISUALISATION
// ========================================================================

const previewHtml = computed(() => {
    let html = localValue.value;
    
    // Échapper le HTML
    html = html.replace(/&/g, '&amp;')
               .replace(/</g, '&lt;')
               .replace(/>/g, '&gt;');
    
    // Convertir le markdown simplifié en HTML
    
    // Titres (doivent être traités en premier, avant les autres formats)
    // ### Titre 3
    html = html.replace(/^### (.+)$/gm, '<h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mt-3 mb-1">$1</h3>');
    // ## Titre 2
    html = html.replace(/^## (.+)$/gm, '<h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mt-4 mb-2">$1</h2>');
    // # Titre 1
    html = html.replace(/^# (.+)$/gm, '<h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-4 mb-2">$1</h1>');
    
    // Gras **texte**
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    
    // Italique *texte*
    html = html.replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>');
    
    // Souligné __texte__
    html = html.replace(/__(.+?)__/g, '<u>$1</u>');
    
    // Barré ~~texte~~
    html = html.replace(/~~(.+?)~~/g, '<s>$1</s>');
    
    // Citations > texte
    html = html.replace(/^&gt; (.+)$/gm, '<blockquote class="border-l-4 border-emerald-500 pl-3 italic text-gray-600 dark:text-gray-400">$1</blockquote>');
    
    // Listes - item
    html = html.replace(/^- (.+)$/gm, '<li class="ml-4">$1</li>');
    
    // Liens [texte](url) - convertis en texte seulement (sécurité)
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, text, url) => {
        // Vérifier si le domaine est autorisé
        const allowedDomains = [
            'gouv.fr', 'assemblee-nationale.fr', 'senat.fr', 'elysee.fr',
            'insee.fr', 'europa.eu', 'legifrance.gouv.fr', 'service-public.fr',
            'vie-publique.fr', 'hatvp.fr', 'civicdash.fr'
        ];
        
        try {
            const urlObj = new URL(url);
            const domain = urlObj.hostname.toLowerCase();
            const isAllowed = allowedDomains.some(d => domain === d || domain.endsWith('.' + d));
            
            if (isAllowed) {
                return `<a href="${url}" class="text-emerald-600 hover:underline" target="_blank" rel="noopener">${text}</a>`;
            }
        } catch (e) {}
        
        return `<span class="text-gray-500">${text}</span> <span class="text-xs text-red-400">[lien non autorisé]</span>`;
    });
    
    // Mentions @type:id
    const mentionStyles = {
        depute: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        senateur: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        maire: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        loi: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        scrutin: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        amendement: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    };
    
    html = html.replace(/@(depute|senateur|maire|loi|scrutin|amendement):([a-zA-Z0-9_-]+)/gi, (match, type, id) => {
        const style = mentionStyles[type.toLowerCase()] || 'bg-gray-100 text-gray-700';
        const icon = mentionTypes[type.toLowerCase()]?.icon || '📋';
        return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-sm font-medium ${style}">${icon} ${id}</span>`;
    });
    
    // Retours à la ligne
    html = html.replace(/\n/g, '<br>');
    
    return html;
});

// ========================================================================
// AIDE
// ========================================================================

const showHelp = ref(false);

const helpContent = [
    { format: '# Titre', result: 'Titre 1', shortcut: '' },
    { format: '## Titre', result: 'Titre 2', shortcut: '' },
    { format: '### Titre', result: 'Titre 3', shortcut: '' },
    { format: '**gras**', result: 'gras', shortcut: 'Ctrl+B' },
    { format: '*italique*', result: 'italique', shortcut: 'Ctrl+I' },
    { format: '__souligné__', result: 'souligné', shortcut: 'Ctrl+U' },
    { format: '~~barré~~', result: 'barré', shortcut: '' },
    { format: '> citation', result: 'citation', shortcut: '' },
    { format: '- liste', result: 'liste', shortcut: '' },
    { format: '[texte](url)', result: 'lien', shortcut: 'Ctrl+K' },
    { format: '@depute:...', result: 'mention', shortcut: '' },
];
</script>

<template>
    <div class="rich-text-editor">
        <!-- Toolbar -->
        <div 
            v-if="showToolbar && !disabled"
            class="flex items-center gap-1 p-2 bg-gray-50 dark:bg-gray-800 border border-b-0 border-gray-200 dark:border-gray-700 rounded-t-lg"
        >
            <!-- Boutons de format -->
            <button
                v-for="(format, key) in formats"
                :key="key"
                v-show="allowedFormats.includes(key)"
                type="button"
                @click="applyFormat(key)"
                class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                :title="`${format.label}${format.shortcut ? ' (' + format.shortcut + ')' : ''}`"
            >
                <span class="text-sm font-medium">{{ format.icon }}</span>
            </button>
            
            <div class="flex-1"></div>
            
            <!-- Bouton Preview -->
            <button
                v-if="showPreview"
                type="button"
                @click="showPreviewPanel = !showPreviewPanel"
                class="px-3 py-1 text-sm rounded transition-colors"
                :class="showPreviewPanel 
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' 
                    : 'hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'"
            >
                👁️ Aperçu
            </button>
            
            <!-- Bouton Aide -->
            <button
                type="button"
                @click="showHelp = !showHelp"
                class="px-3 py-1 text-sm rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-colors"
            >
                ❓
            </button>
        </div>
        
        <!-- Aide -->
        <div 
            v-if="showHelp"
            class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-sm"
        >
            <div class="font-medium text-blue-800 dark:text-blue-300 mb-2">📖 Mise en forme</div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <div v-for="item in helpContent" :key="item.format" class="flex items-center gap-2">
                    <code class="bg-white dark:bg-gray-800 px-1 rounded text-xs">{{ item.format }}</code>
                    <span class="text-gray-600 dark:text-gray-400">→</span>
                    <span class="text-gray-800 dark:text-gray-200">{{ item.result }}</span>
                </div>
            </div>
            <div class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                ⚠️ Seuls les liens vers les sites officiels (gouv.fr, assemblee-nationale.fr, senat.fr, etc.) sont autorisés.
            </div>
        </div>
        
        <!-- Zone de saisie et preview -->
        <div class="grid" :class="showPreviewPanel ? 'md:grid-cols-2' : ''">
            <!-- Textarea avec dropdown mentions -->
            <div class="relative">
                <textarea
                    ref="textarea"
                    v-model="localValue"
                    :placeholder="placeholder"
                    :rows="rows"
                    :disabled="disabled"
                    :maxlength="maxLength + 100"
                    @keydown="handleKeydown"
                    @input="handleInput"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-y"
                    :class="[
                        showToolbar ? 'rounded-t-none' : 'rounded-t-lg',
                        showPreviewPanel ? 'rounded-br-none md:rounded-bl-lg' : 'rounded-b-lg'
                    ]"
                ></textarea>
                
                <!-- Dropdown des mentions -->
                <div 
                    v-if="mentionState.active && (mentionState.suggestions.length > 0 || mentionState.loading)"
                    class="absolute z-50 mt-1 w-72 max-h-80 overflow-auto bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700"
                    style="top: 2rem; left: 1rem;"
                >
                    <!-- En-tête -->
                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-2 text-sm">
                            <span v-if="!mentionState.type" class="text-gray-600 dark:text-gray-300">
                                🔍 Choisir un type de mention
                            </span>
                            <span v-else class="flex items-center gap-1">
                                <span>{{ mentionTypes[mentionState.type]?.icon }}</span>
                                <span :class="mentionTypes[mentionState.type]?.color">
                                    {{ mentionTypes[mentionState.type]?.label }}
                                </span>
                                <span class="text-gray-400">:</span>
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ mentionState.query || 'tapez pour rechercher...' }}
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Loading -->
                    <div v-if="mentionState.loading" class="px-4 py-3 text-center text-gray-500">
                        <span class="animate-pulse">⏳ Recherche...</span>
                    </div>
                    
                    <!-- Suggestions -->
                    <ul v-else class="py-1">
                        <li 
                            v-for="(suggestion, index) in mentionState.suggestions"
                            :key="suggestion.id"
                            @click="selectSuggestion(suggestion)"
                            @mouseenter="mentionState.selectedIndex = index"
                            class="px-3 py-2 cursor-pointer flex items-center gap-3 transition-colors"
                            :class="[
                                index === mentionState.selectedIndex 
                                    ? 'bg-emerald-50 dark:bg-emerald-900/30' 
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            ]"
                        >
                            <!-- Icon ou photo -->
                            <div class="flex-shrink-0">
                                <img 
                                    v-if="suggestion.photo" 
                                    :src="suggestion.photo" 
                                    :alt="suggestion.label"
                                    class="w-8 h-8 rounded-full object-cover"
                                >
                                <div 
                                    v-else 
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-lg"
                                    :class="suggestion.bgColor || 'bg-gray-100 dark:bg-gray-700'"
                                >
                                    {{ suggestion.icon }}
                                </div>
                            </div>
                            
                            <!-- Texte -->
                            <div class="flex-1 min-w-0">
                                <div 
                                    class="font-medium truncate"
                                    :class="suggestion.color || 'text-gray-900 dark:text-gray-100'"
                                >
                                    {{ suggestion.label }}
                                </div>
                                <div 
                                    v-if="suggestion.sublabel" 
                                    class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                >
                                    {{ suggestion.sublabel }}
                                </div>
                            </div>
                            
                            <!-- Badge type pour les catégories -->
                            <div 
                                v-if="suggestion.type === 'category'"
                                class="text-xs text-gray-400"
                            >
                                →
                            </div>
                        </li>
                    </ul>
                    
                    <!-- Message vide -->
                    <div 
                        v-if="!mentionState.loading && mentionState.suggestions.length === 0 && mentionState.type && mentionState.query.length >= 2"
                        class="px-4 py-3 text-center text-gray-500 text-sm"
                    >
                        Aucun résultat pour "{{ mentionState.query }}"
                    </div>
                    
                    <!-- Aide clavier -->
                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 text-xs text-gray-500 flex gap-3">
                        <span>↑↓ naviguer</span>
                        <span>⏎ sélectionner</span>
                        <span>⎋ fermer</span>
                    </div>
                </div>
                
                <!-- Compteur de caractères -->
                <div class="absolute bottom-2 right-2 text-xs" :class="charCountClass">
                    {{ charCount }} / {{ maxLength }}
                </div>
            </div>
            
            <!-- Preview -->
            <div 
                v-if="showPreviewPanel"
                class="p-4 border border-t-0 md:border-t md:border-l-0 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-lg md:rounded-bl-none md:rounded-r-lg overflow-auto"
                :style="{ minHeight: rows * 1.5 + 'rem' }"
            >
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Aperçu :</div>
                <div 
                    class="prose prose-sm dark:prose-invert max-w-none text-gray-800 dark:text-gray-200"
                    v-html="previewHtml || '<span class=\'text-gray-400 italic\'>Rien à afficher...</span>'"
                ></div>
            </div>
        </div>
        
        <!-- Infos -->
        <div class="flex items-center justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
            <span v-if="minLength > 0 && charCount < minLength" class="text-amber-500">
                Minimum {{ minLength }} caractères
            </span>
            <span v-else></span>
            <span>
                💡 Tapez <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">@</code> pour mentionner un élu, une loi...
            </span>
        </div>
    </div>
</template>

<style scoped>
.rich-text-editor textarea {
    font-family: inherit;
    line-height: 1.6;
}

.rich-text-editor :deep(blockquote) {
    margin: 0.5rem 0;
}

.rich-text-editor :deep(li) {
    list-style-type: disc;
}
</style>
