<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (v) => ['topic', 'post', 'comment'].includes(v),
    },
    id: {
        type: [Number, String],
        required: true,
    },
    size: {
        type: String,
        default: 'sm',
    },
});

const emit = defineEmits(['reported']);

const showModal = ref(false);
const selectedReason = ref('');
const description = ref('');
const loading = ref(false);
const success = ref(false);
const error = ref('');

const reasons = ref([
    { key: 'spam', label: 'Spam / Publicité', icon: '🚫' },
    { key: 'harassment', label: 'Harcèlement', icon: '😠' },
    { key: 'hate_speech', label: 'Discours haineux / Racisme', icon: '🚨' },
    { key: 'violence', label: 'Incitation à la violence', icon: '⚠️' },
    { key: 'misinformation', label: 'Désinformation', icon: '❌' },
    { key: 'inappropriate', label: 'Contenu inapproprié', icon: '🔞' },
    { key: 'off_topic', label: 'Hors sujet', icon: '📌' },
    { key: 'impersonation', label: 'Usurpation d\'identité', icon: '👤' },
    { key: 'personal_data', label: 'Données personnelles exposées', icon: '🔒' },
    { key: 'other', label: 'Autre', icon: '❓' },
]);

const buttonClass = computed(() => {
    const base = 'inline-flex items-center gap-1 text-gray-400 hover:text-red-500 transition-colors';
    return props.size === 'sm' 
        ? `${base} text-xs` 
        : `${base} text-sm px-2 py-1`;
});

const openModal = () => {
    showModal.value = true;
    error.value = '';
    success.value = false;
};

const closeModal = () => {
    showModal.value = false;
    selectedReason.value = '';
    description.value = '';
};

const submitReport = async () => {
    if (!selectedReason.value) {
        error.value = 'Veuillez sélectionner une raison.';
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const response = await axios.post('/api/reports', {
            type: props.type,
            id: props.id,
            reason: selectedReason.value,
            description: description.value || null,
        });

        success.value = true;
        emit('reported', response.data);

        setTimeout(() => {
            closeModal();
        }, 2000);
    } catch (e) {
        if (e.response?.status === 409) {
            error.value = 'Vous avez déjà signalé ce contenu.';
        } else if (e.response?.status === 403) {
            error.value = e.response.data.message || 'Action non autorisée.';
        } else {
            error.value = 'Une erreur est survenue. Veuillez réessayer.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div>
        <!-- Bouton de signalement -->
        <button
            type="button"
            @click="openModal"
            :class="buttonClass"
            title="Signaler ce contenu"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
            </svg>
            <span v-if="size !== 'sm'">Signaler</span>
        </button>

        <!-- Modal de signalement -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 bg-black/50" @click="closeModal"></div>

                        <!-- Modal -->
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6">
                            <div v-if="!success">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    🚩 Signaler ce contenu
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Merci de nous aider à maintenir un espace de discussion respectueux.
                                </p>

                                <!-- Raisons -->
                                <div class="space-y-2 mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Pourquoi signalez-vous ce contenu ?
                                    </label>
                                    <div class="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto">
                                        <label
                                            v-for="reason in reasons"
                                            :key="reason.key"
                                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition"
                                            :class="selectedReason === reason.key 
                                                ? 'bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-700' 
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-700 border border-transparent'"
                                        >
                                            <input
                                                type="radio"
                                                v-model="selectedReason"
                                                :value="reason.key"
                                                class="text-red-600 focus:ring-red-500"
                                            />
                                            <span class="text-lg">{{ reason.icon }}</span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ reason.label }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Description optionnelle -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Détails (optionnel)
                                    </label>
                                    <textarea
                                        v-model="description"
                                        rows="3"
                                        maxlength="1000"
                                        placeholder="Décrivez brièvement le problème..."
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                    ></textarea>
                                </div>

                                <!-- Erreur -->
                                <p v-if="error" class="text-red-600 text-sm mb-4">
                                    {{ error }}
                                </p>

                                <!-- Actions -->
                                <div class="flex gap-3 justify-end">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="button"
                                        @click="submitReport"
                                        :disabled="loading || !selectedReason"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="loading">Envoi...</span>
                                        <span v-else>Envoyer le signalement</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Message de succès -->
                            <div v-else class="text-center py-6">
                                <div class="text-5xl mb-4">✅</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                    Signalement envoyé
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Merci pour votre contribution à la modération.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
