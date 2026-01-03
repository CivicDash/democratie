<script setup>
import { computed } from 'vue';
import ReferencePreview from './ReferencePreview.vue';

const props = defineProps({
    content: { type: String, required: true },
    enablePreviews: { type: Boolean, default: true },
});

// Patterns de références supportés
const referencePatterns = [
    { type: 'depute', pattern: /@depute:([a-zA-Z0-9_-]+)/gi },
    { type: 'senateur', pattern: /@senateur:([a-zA-Z0-9_-]+)/gi },
    { type: 'maire', pattern: /@maire:([a-zA-Z0-9_-]+)/gi },
    { type: 'loi', pattern: /@loi:([a-zA-Z0-9_-]+)/gi },
    { type: 'scrutin', pattern: /@scrutin:([a-zA-Z0-9_-]+)/gi },
    { type: 'amendement', pattern: /@amendement:([a-zA-Z0-9_-]+)/gi },
];

// Transformer le contenu en segments (texte et références)
const segments = computed(() => {
    let text = props.content;
    const result = [];
    let lastIndex = 0;

    // Collecter toutes les références avec leurs positions
    const allMatches = [];
    
    for (const { type, pattern } of referencePatterns) {
        // Reset le regex
        pattern.lastIndex = 0;
        let match;
        while ((match = pattern.exec(text)) !== null) {
            allMatches.push({
                type,
                identifier: match[1],
                start: match.index,
                end: match.index + match[0].length,
                fullMatch: match[0],
            });
        }
    }

    // Trier par position
    allMatches.sort((a, b) => a.start - b.start);

    // Construire les segments
    for (const match of allMatches) {
        // Ajouter le texte avant la référence
        if (match.start > lastIndex) {
            result.push({
                type: 'text',
                content: text.substring(lastIndex, match.start),
            });
        }

        // Ajouter la référence
        result.push({
            type: 'reference',
            refType: match.type,
            identifier: match.identifier,
            fullMatch: match.fullMatch,
        });

        lastIndex = match.end;
    }

    // Ajouter le texte restant
    if (lastIndex < text.length) {
        result.push({
            type: 'text',
            content: text.substring(lastIndex),
        });
    }

    return result;
});

// Labels des types
function getTypeLabel(type) {
    return {
        depute: 'Député',
        senateur: 'Sénateur',
        maire: 'Maire',
        loi: 'Loi',
        scrutin: 'Scrutin',
        amendement: 'Amendement',
    }[type] || 'Référence';
}

function getTypeIcon(type) {
    return {
        depute: '👤',
        senateur: '🏛️',
        maire: '🏘️',
        loi: '📜',
        scrutin: '🗳️',
        amendement: '📝',
    }[type] || '📋';
}

function getTypeColor(type) {
    return {
        depute: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        senateur: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        maire: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        loi: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        scrutin: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        amendement: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    }[type] || 'bg-gray-100 text-gray-700';
}
</script>

<template>
    <span class="rich-content">
        <template v-for="(segment, index) in segments" :key="index">
            <!-- Texte normal -->
            <span v-if="segment.type === 'text'" class="whitespace-pre-wrap">{{ segment.content }}</span>

            <!-- Référence avec preview -->
            <ReferencePreview
                v-else-if="segment.type === 'reference' && enablePreviews"
                :type="segment.refType"
                :identifier="segment.identifier"
            >
                <span 
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-sm font-medium cursor-pointer hover:opacity-80 transition"
                    :class="getTypeColor(segment.refType)"
                >
                    {{ getTypeIcon(segment.refType) }} 
                    {{ getTypeLabel(segment.refType) }}: {{ segment.identifier }}
                </span>
            </ReferencePreview>

            <!-- Référence sans preview -->
            <span 
                v-else-if="segment.type === 'reference'"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-sm font-medium"
                :class="getTypeColor(segment.refType)"
            >
                {{ getTypeIcon(segment.refType) }} 
                {{ getTypeLabel(segment.refType) }}: {{ segment.identifier }}
            </span>
        </template>
    </span>
</template>

<style scoped>
.rich-content {
    word-wrap: break-word;
}
</style>
