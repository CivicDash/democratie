<script setup>
import { ref, computed } from 'vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const selectedPosition = ref(null);
const content = ref(props.modelValue);

const positions = [
    { value: 'for', label: 'Pour', icon: '👍', color: 'emerald', description: 'Je soutiens cette proposition' },
    { value: 'against', label: 'Contre', icon: '👎', color: 'rose', description: 'Je m\'oppose à cette proposition' },
    { value: 'neutral', label: 'Neutre', icon: '🤔', color: 'slate', description: 'Je souhaite apporter une nuance' },
];

const canSubmit = computed(() => {
    return selectedPosition.value && content.value.length >= 20;
});

function selectPosition(value) {
    selectedPosition.value = value;
}

function submit() {
    if (!canSubmit.value) return;
    
    emit('submit', {
        content: content.value,
        debate_position: selectedPosition.value,
    });
    
    // Reset
    content.value = '';
    selectedPosition.value = null;
}
</script>

<template>
    <div class="debate-comment-form bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Étape 1: Choisir sa position -->
        <div class="p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                ⚔️ Quelle est votre position ?
            </h4>
            
            <div class="grid grid-cols-3 gap-3">
                <button
                    v-for="pos in positions"
                    :key="pos.value"
                    @click="selectPosition(pos.value)"
                    type="button"
                    :disabled="disabled"
                    class="p-3 rounded-xl border-2 transition-all text-center"
                    :class="[
                        selectedPosition === pos.value
                            ? `border-${pos.color}-500 bg-${pos.color}-50 dark:bg-${pos.color}-900/30`
                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500',
                    ]"
                >
                    <span class="text-2xl block mb-1">{{ pos.icon }}</span>
                    <span class="font-medium text-sm" :class="selectedPosition === pos.value ? `text-${pos.color}-700 dark:text-${pos.color}-400` : 'text-gray-700 dark:text-gray-300'">
                        {{ pos.label }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Étape 2: Rédiger son argument -->
        <div class="p-4">
            <div v-if="selectedPosition" class="mb-3">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium"
                    :class="{
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': selectedPosition === 'for',
                        'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': selectedPosition === 'against',
                        'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400': selectedPosition === 'neutral',
                    }"
                >
                    {{ positions.find(p => p.value === selectedPosition)?.icon }}
                    Argument {{ positions.find(p => p.value === selectedPosition)?.label }}
                </span>
            </div>

            <RichTextEditor
                v-model="content"
                :rows="4"
                :min-length="20"
                :max-length="2000"
                :placeholder="selectedPosition ? 'Développez votre argument...' : 'Choisissez d\'abord votre position ↑'"
                :disabled="!selectedPosition || disabled"
                :show-toolbar="!!selectedPosition"
            />

            <div class="mt-4 flex justify-end">
                <button
                    @click="submit"
                    :disabled="!canSubmit || disabled"
                    class="px-6 py-2 rounded-xl font-semibold text-white transition-all"
                    :class="canSubmit ? 'bg-amber-600 hover:bg-amber-700' : 'bg-gray-300 cursor-not-allowed'"
                >
                    📤 Publier mon argument
                </button>
            </div>
        </div>
    </div>
</template>
