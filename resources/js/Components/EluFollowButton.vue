<template>
    <div class="inline-flex items-center gap-2">
        <!-- Bouton principal -->
        <button
            @click="handleClick"
            :disabled="loading"
            class="relative inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
            :class="buttonClasses"
        >
            <!-- Icône -->
            <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            
            <svg v-else-if="!currentlyFollowing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            
            <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>

            <span>{{ buttonText }}</span>
        </button>

        <!-- Bouton préférences (si suivi) -->
        <button
            v-if="currentlyFollowing && showPreferences"
            @click="openPreferencesModal"
            class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition"
            title="Paramètres de notification"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>
    </div>

    <!-- Modal des préférences -->
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="closeModal"
            >
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                🔔 Préférences de suivi
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ eluName }}</p>
                        </div>
                        <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Préférences de notification -->
                    <div class="p-4 space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Choisissez les activités pour lesquelles vous souhaitez être notifié(e) :
                        </p>

                        <!-- Types d'activités -->
                        <div class="space-y-3">
                            <label
                                v-for="(activity, key) in activityTypes"
                                :key="key"
                                class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition"
                            >
                                <input
                                    type="checkbox"
                                    v-model="localPreferences[`notify_${key}`]"
                                    class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span>{{ activity.icon }}</span>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ activity.label }}</span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ activity.description }}</p>
                                </div>
                            </label>
                        </div>

                        <!-- Séparateur -->
                        <hr class="border-gray-200 dark:border-gray-700" />

                        <!-- Canaux de notification -->
                        <div class="space-y-3">
                            <h4 class="font-medium text-gray-800 dark:text-gray-200">📨 Canaux de notification</h4>
                            
                            <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="localPreferences.notify_site"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <div>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">🔔 Sur le site</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Notification dans le centre de notifications</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="localPreferences.notify_email"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <div>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">📧 Par email</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Recevoir un email pour chaque notification</p>
                                </div>
                            </label>

                            <!-- Fréquence email -->
                            <div v-if="localPreferences.notify_email" class="ml-7 space-y-2">
                                <label class="text-sm text-gray-600 dark:text-gray-400">Fréquence :</label>
                                <select
                                    v-model="localPreferences.email_frequency"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                                >
                                    <option value="instant">Immédiat</option>
                                    <option value="daily">Résumé quotidien</option>
                                    <option value="weekly">Résumé hebdomadaire</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-between gap-3 p-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            @click="unfollowFromModal"
                            class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                        >
                            Ne plus suivre
                        </button>
                        <div class="flex gap-2">
                            <button
                                @click="closeModal"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                Annuler
                            </button>
                            <button
                                @click="savePreferences"
                                :disabled="saving"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                            >
                                {{ saving ? 'Sauvegarde...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useEluFollow } from '@/Composables/useEluFollow';

const props = defineProps({
    eluType: {
        type: String,
        required: true,
        validator: (value) => ['depute', 'senateur', 'maire', 'ministre'].includes(value),
    },
    eluId: {
        type: String,
        required: true,
    },
    eluName: {
        type: String,
        default: '',
    },
    initialFollowing: {
        type: Boolean,
        default: null,
    },
    showPreferences: {
        type: Boolean,
        default: true,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const emit = defineEmits(['followed', 'unfollowed', 'error', 'preferences-updated']);

const {
    isAuthenticated,
    followElu,
    unfollowElu,
    checkFollowStatus,
    updatePreferences,
    activityTypes,
    setFollowState,
} = useEluFollow();

const loading = ref(false);
const currentlyFollowing = ref(false);
const showModal = ref(false);
const saving = ref(false);
const currentData = ref(null);

// Préférences locales pour le modal
const localPreferences = ref({
    notify_votes: true,
    notify_interventions: true,
    notify_amendements: false,
    notify_propositions: true,
    notify_rapports: false,
    notify_commissions: false,
    notify_actualites: true,
    notify_site: true,
    notify_email: false,
    email_frequency: 'instant',
});

const buttonText = computed(() => {
    if (loading.value) return 'Chargement...';
    return currentlyFollowing.value ? 'Suivi ✓' : 'Suivre';
});

const buttonClasses = computed(() => {
    const sizeClasses = {
        sm: 'text-sm px-3 py-1.5',
        md: 'px-4 py-2',
        lg: 'text-lg px-5 py-2.5',
    };

    const base = sizeClasses[props.size];

    if (currentlyFollowing.value) {
        return `${base} bg-green-50 text-green-700 border-2 border-green-200 hover:bg-green-100 hover:border-green-300 focus:ring-green-500 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800`;
    }
    return `${base} bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500`;
});

const handleClick = async () => {
    if (!isAuthenticated.value) {
        window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }

    loading.value = true;

    try {
        if (currentlyFollowing.value) {
            await unfollowElu(props.eluType, props.eluId);
            currentlyFollowing.value = false;
            emit('unfollowed');
        } else {
            const result = await followElu(props.eluType, props.eluId);
            currentlyFollowing.value = true;
            currentData.value = result?.data;
            emit('followed', result);
        }
    } catch (error) {
        emit('error', error);
    } finally {
        loading.value = false;
    }
};

const openPreferencesModal = () => {
    // Charger les préférences actuelles
    if (currentData.value) {
        localPreferences.value = {
            notify_votes: currentData.value.notify_votes ?? true,
            notify_interventions: currentData.value.notify_interventions ?? true,
            notify_amendements: currentData.value.notify_amendements ?? false,
            notify_propositions: currentData.value.notify_propositions ?? true,
            notify_rapports: currentData.value.notify_rapports ?? false,
            notify_commissions: currentData.value.notify_commissions ?? false,
            notify_actualites: currentData.value.notify_actualites ?? true,
            notify_site: currentData.value.notify_site ?? true,
            notify_email: currentData.value.notify_email ?? false,
            email_frequency: currentData.value.email_frequency ?? 'instant',
        };
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const savePreferences = async () => {
    saving.value = true;
    try {
        await updatePreferences(props.eluType, props.eluId, localPreferences.value);
        emit('preferences-updated', localPreferences.value);
        closeModal();
    } catch (error) {
        emit('error', error);
    } finally {
        saving.value = false;
    }
};

const unfollowFromModal = async () => {
    loading.value = true;
    closeModal();
    
    try {
        await unfollowElu(props.eluType, props.eluId);
        currentlyFollowing.value = false;
        emit('unfollowed');
    } catch (error) {
        emit('error', error);
    } finally {
        loading.value = false;
    }
};

// Initialisation
onMounted(async () => {
    if (props.initialFollowing !== null) {
        currentlyFollowing.value = props.initialFollowing;
    } else if (isAuthenticated.value) {
        const status = await checkFollowStatus(props.eluType, props.eluId);
        currentlyFollowing.value = status.is_following;
        currentData.value = status.data;
    }
});

// Synchroniser avec le composable
watch(currentlyFollowing, (val) => {
    setFollowState(props.eluType, props.eluId, val);
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.95) translateY(10px);
}
</style>
