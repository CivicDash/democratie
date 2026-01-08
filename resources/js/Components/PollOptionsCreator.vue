<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    minOptions: { type: Number, default: 2 },
    maxOptions: { type: Number, default: 6 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

// Options locales
const options = ref(
    props.modelValue.length > 0 
        ? [...props.modelValue] 
        : [
            { label: '', icon: '👍' },
            { label: '', icon: '👎' },
        ]
);

// Icônes suggérées
const suggestedIcons = ['👍', '👎', '🤔', '❌', '✅', '⭐', '🔴', '🟢', '🟡', '🔵', '⚪', '🟣'];

// Sync avec le parent
watch(options, (newVal) => {
    emit('update:modelValue', newVal.filter(o => o.label.trim() !== ''));
}, { deep: true });

watch(() => props.modelValue, (newVal) => {
    if (JSON.stringify(newVal) !== JSON.stringify(options.value.filter(o => o.label.trim()))) {
        options.value = newVal.length > 0 ? [...newVal] : [{ label: '', icon: '👍' }, { label: '', icon: '👎' }];
    }
});

// Ajouter une option
function addOption() {
    if (options.value.length < props.maxOptions) {
        const nextIcon = suggestedIcons[options.value.length % suggestedIcons.length];
        options.value.push({ label: '', icon: nextIcon });
    }
}

// Supprimer une option
function removeOption(index) {
    if (options.value.length > props.minOptions) {
        options.value.splice(index, 1);
    }
}

// Changer l'icône
function changeIcon(index) {
    const currentIdx = suggestedIcons.indexOf(options.value[index].icon);
    const nextIdx = (currentIdx + 1) % suggestedIcons.length;
    options.value[index].icon = suggestedIcons[nextIdx];
}

// Validation
const isValid = computed(() => {
    const filledOptions = options.value.filter(o => o.label.trim() !== '');
    return filledOptions.length >= props.minOptions;
});

const validOptionsCount = computed(() => {
    return options.value.filter(o => o.label.trim() !== '').length;
});
</script>

<template>
    <div class="poll-options-creator">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    📊 Options de réponse
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Ajoutez entre {{ minOptions }} et {{ maxOptions }} choix de réponse
                </p>
            </div>
            <div class="text-sm">
                <span :class="isValid ? 'text-emerald-600' : 'text-amber-600'">
                    {{ validOptionsCount }} / {{ minOptions }} minimum
                </span>
            </div>
        </div>

        <!-- Liste des options -->
        <div class="space-y-3">
            <div 
                v-for="(option, index) in options" 
                :key="index"
                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700"
            >
                <!-- Numéro -->
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ index + 1 }}
                </span>

                <!-- Icône (cliquable pour changer) -->
                <button
                    type="button"
                    @click="changeIcon(index)"
                    class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                    :disabled="disabled"
                    title="Cliquez pour changer l'icône"
                >
                    {{ option.icon }}
                </button>

                <!-- Input texte -->
                <input
                    v-model="option.label"
                    type="text"
                    :placeholder="`Option ${index + 1}...`"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    :disabled="disabled"
                    maxlength="200"
                >

                <!-- Bouton supprimer -->
                <button
                    v-if="options.length > minOptions"
                    type="button"
                    @click="removeOption(index)"
                    class="flex-shrink-0 p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    :disabled="disabled"
                    title="Supprimer cette option"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Bouton ajouter -->
        <button
            v-if="options.length < maxOptions"
            type="button"
            @click="addOption"
            class="mt-4 w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-indigo-500 hover:text-indigo-500 dark:hover:border-indigo-400 dark:hover:text-indigo-400 transition-colors flex items-center justify-center gap-2"
            :disabled="disabled"
        >
            <span class="text-lg">+</span>
            <span>Ajouter une option</span>
        </button>

        <!-- Suggestions rapides -->
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <div class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2">
                💡 Suggestions de sondages
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="options = [{ label: 'Oui', icon: '✅' }, { label: 'Non', icon: '❌' }]"
                    class="px-3 py-1 text-sm bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                    :disabled="disabled"
                >
                    Oui / Non
                </button>
                <button
                    type="button"
                    @click="options = [{ label: 'Pour', icon: '👍' }, { label: 'Contre', icon: '👎' }, { label: 'Sans opinion', icon: '🤔' }]"
                    class="px-3 py-1 text-sm bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                    :disabled="disabled"
                >
                    Pour / Contre / Neutre
                </button>
                <button
                    type="button"
                    @click="options = [{ label: 'Très favorable', icon: '⭐' }, { label: 'Plutôt favorable', icon: '👍' }, { label: 'Plutôt défavorable', icon: '👎' }, { label: 'Très défavorable', icon: '❌' }]"
                    class="px-3 py-1 text-sm bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                    :disabled="disabled"
                >
                    Échelle 4 niveaux
                </button>
            </div>
        </div>

        <!-- Message de validation -->
        <div v-if="!isValid" class="mt-3 text-sm text-amber-600 dark:text-amber-400">
            ⚠️ Veuillez remplir au moins {{ minOptions }} options
        </div>
    </div>
</template>

<style scoped>
.poll-options-creator input:focus {
    outline: none;
}
</style>
