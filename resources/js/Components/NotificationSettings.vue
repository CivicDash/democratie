<template>
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <!-- En-tête -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Préférences de notifications
            </h2>
            <p class="text-blue-100 text-sm mt-1">Personnalisez vos alertes et notifications</p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-8 text-center">
            <div class="inline-block w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-3 text-gray-600">Chargement...</p>
        </div>

        <!-- Contenu -->
        <div v-else class="p-6 space-y-6">
            <!-- Actions rapides -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                    <h3 class="font-semibold text-gray-900">Actions rapides</h3>
                    <p class="text-sm text-gray-600">Activer ou désactiver toutes les notifications</p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="toggleAllNotifications(true)"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                    >
                        Tout activer
                    </button>
                    <button
                        @click="toggleAllNotifications(false)"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium"
                    >
                        Tout désactiver
                    </button>
                    <button
                        @click="resetPreferences"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-sm font-medium"
                    >
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Types de notifications -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Types de notifications</h3>
                
                <div v-for="(item, key) in notificationTypes" :key="key" class="flex items-start gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                    <div class="flex items-center h-6">
                        <input
                            :id="`notify-${key}`"
                            type="checkbox"
                            v-model="preferences.notifications[key]"
                            @change="savePreferences"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                    </div>
                    <div class="flex-1">
                        <label :for="`notify-${key}`" class="flex items-center gap-2 font-medium text-gray-900 cursor-pointer">
                            <span class="text-2xl">{{ item.icon }}</span>
                            <span>{{ item.label }}</span>
                        </label>
                        <p class="text-sm text-gray-600 mt-1 ml-10">{{ item.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Canaux de notification -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Canaux</h3>
                
                <div class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg">
                    <input
                        id="channel-in-app"
                        type="checkbox"
                        v-model="preferences.channels.in_app"
                        @change="savePreferences"
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <label for="channel-in-app" class="flex-1 cursor-pointer">
                        <span class="font-medium text-gray-900">🔔 Notifications in-app</span>
                        <p class="text-sm text-gray-600">Recevoir des notifications dans l'application</p>
                    </label>
                </div>

                <div class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg">
                    <input
                        id="channel-email"
                        type="checkbox"
                        v-model="preferences.channels.email"
                        @change="savePreferences"
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <label for="channel-email" class="flex-1 cursor-pointer">
                        <span class="font-medium text-gray-900">📧 Notifications par email</span>
                        <p class="text-sm text-gray-600">Recevoir des emails de notification</p>
                    </label>
                </div>
            </div>

            <!-- Fréquence des emails -->
            <div v-if="preferences.channels.email" class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Fréquence des emails</h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label
                        v-for="freq in emailFrequencies"
                        :key="freq.value"
                        class="relative"
                    >
                        <input
                            type="radio"
                            :value="freq.value"
                            v-model="preferences.email_frequency"
                            @change="savePreferences"
                            class="sr-only peer"
                        />
                        <div class="px-4 py-3 text-center border-2 border-gray-300 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-gray-50">
                            <div class="text-2xl mb-1">{{ freq.icon }}</div>
                            <div class="font-medium text-sm">{{ freq.label }}</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Heures calmes -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Heures calmes</h3>
                <p class="text-sm text-gray-600">Désactiver les notifications pendant certaines heures</p>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Début</label>
                        <input
                            type="time"
                            v-model="quietHoursStart"
                            @change="saveQuietHours"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fin</label>
                        <input
                            type="time"
                            v-model="quietHoursEnd"
                            @change="saveQuietHours"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Groupement -->
            <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <input
                    id="group-similar"
                    type="checkbox"
                    v-model="preferences.group_similar"
                    @change="savePreferences"
                    class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <label for="group-similar" class="flex-1 cursor-pointer">
                    <span class="font-medium text-blue-900">Grouper les notifications similaires</span>
                    <p class="text-sm text-blue-700">Éviter le spam en regroupant les notifications du même type</p>
                </label>
            </div>

            <!-- Message de sauvegarde -->
            <Transition name="fade">
                <div v-if="showSaveMessage" class="p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2 text-green-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-medium">Préférences enregistrées !</span>
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';

const loading = ref(true);
const showSaveMessage = ref(false);

const preferences = reactive({
    notifications: {
        new_reply: true,
        new_vote_on_topic: true,
        legislative_vote_result: true,
        mention: true,
        vote_on_my_proposal: true,
        new_thematique_proposition: false,
        system_announcement: true,
        followed_topic_update: true,
        followed_legislation_update: true,
    },
    channels: {
        in_app: true,
        email: false,
    },
    email_frequency: 'instant',
    quiet_hours: {
        start: null,
        end: null,
    },
    group_similar: true,
});

const quietHoursStart = ref('');
const quietHoursEnd = ref('');

const notificationTypes = {
    new_reply: {
        icon: '💬',
        label: 'Nouvelles réponses',
        description: 'Notifier quand quelqu\'un répond à une conversation que je suis',
    },
    new_vote_on_topic: {
        icon: '👍',
        label: 'Nouveaux votes',
        description: 'Notifier des nouveaux votes sur les sujets que je suis',
    },
    legislative_vote_result: {
        icon: '🏛️',
        label: 'Résultats législatifs',
        description: 'Notifier des résultats de votes à l\'Assemblée/Sénat',
    },
    mention: {
        icon: '👤',
        label: 'Mentions',
        description: 'Notifier quand quelqu\'un me mentionne',
    },
    vote_on_my_proposal: {
        icon: '⭐',
        label: 'Votes sur mes propositions',
        description: 'Notifier quand quelqu\'un vote sur mes propositions citoyennes',
    },
    new_thematique_proposition: {
        icon: '📢',
        label: 'Nouvelles propositions thématiques',
        description: 'Notifier des nouvelles propositions dans les thématiques suivies',
    },
    system_announcement: {
        icon: '📣',
        label: 'Annonces système',
        description: 'Recevoir les annonces importantes de la plateforme',
    },
    followed_topic_update: {
        icon: '🔔',
        label: 'Mise à jour de sujets suivis',
        description: 'Notifier des mises à jour sur les sujets que je suis',
    },
    followed_legislation_update: {
        icon: '📋',
        label: 'Mise à jour de législations suivies',
        description: 'Notifier des mises à jour sur les législations que je suis',
    },
};

const emailFrequencies = [
    { value: 'instant', label: 'Instantané', icon: '⚡' },
    { value: 'daily', label: 'Quotidien', icon: '📅' },
    { value: 'weekly', label: 'Hebdomadaire', icon: '📆' },
    { value: 'never', label: 'Jamais', icon: '🚫' },
];

const loadPreferences = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/notification-preferences', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success && data.preferences) {
                Object.assign(preferences, data.preferences);
                
                // Convertir les heures pour l'input time
                if (preferences.quiet_hours.start) {
                    quietHoursStart.value = preferences.quiet_hours.start.substring(0, 5);
                }
                if (preferences.quiet_hours.end) {
                    quietHoursEnd.value = preferences.quiet_hours.end.substring(0, 5);
                }
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des préférences:', error);
    } finally {
        loading.value = false;
    }
};

const savePreferences = async () => {
    try {
        const response = await fetch('/api/notification-preferences', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
            body: JSON.stringify({
                notify_new_reply: preferences.notifications.new_reply,
                notify_new_vote_on_topic: preferences.notifications.new_vote_on_topic,
                notify_legislative_vote_result: preferences.notifications.legislative_vote_result,
                notify_mention: preferences.notifications.mention,
                notify_vote_on_my_proposal: preferences.notifications.vote_on_my_proposal,
                notify_new_thematique_proposition: preferences.notifications.new_thematique_proposition,
                notify_system_announcement: preferences.notifications.system_announcement,
                notify_followed_topic_update: preferences.notifications.followed_topic_update,
                notify_followed_legislation_update: preferences.notifications.followed_legislation_update,
                channel_in_app: preferences.channels.in_app,
                channel_email: preferences.channels.email,
                email_frequency: preferences.email_frequency,
                group_similar_notifications: preferences.group_similar,
            }),
        });

        if (response.ok) {
            showSaveMessage.value = true;
            setTimeout(() => {
                showSaveMessage.value = false;
            }, 3000);
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        alert('Erreur lors de la sauvegarde des préférences');
    }
};

const saveQuietHours = async () => {
    try {
        const response = await fetch('/api/notification-preferences', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
            body: JSON.stringify({
                quiet_hours_start: quietHoursStart.value ? `${quietHoursStart.value}:00` : null,
                quiet_hours_end: quietHoursEnd.value ? `${quietHoursEnd.value}:00` : null,
            }),
        });

        if (response.ok) {
            showSaveMessage.value = true;
            setTimeout(() => {
                showSaveMessage.value = false;
            }, 3000);
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde des heures calmes:', error);
    }
};

const toggleAllNotifications = async (enabled) => {
    try {
        const response = await fetch('/api/notification-preferences/toggle-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
            body: JSON.stringify({ enabled }),
        });

        if (response.ok) {
            await loadPreferences();
            showSaveMessage.value = true;
            setTimeout(() => {
                showSaveMessage.value = false;
            }, 3000);
        }
    } catch (error) {
        console.error('Erreur lors du toggle all:', error);
        alert('Erreur lors de la mise à jour');
    }
};

const resetPreferences = async () => {
    if (!confirm('Êtes-vous sûr de vouloir réinitialiser toutes vos préférences ?')) {
        return;
    }

    try {
        const response = await fetch('/api/notification-preferences/reset', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            await loadPreferences();
            showSaveMessage.value = true;
            setTimeout(() => {
                showSaveMessage.value = false;
            }, 3000);
        }
    } catch (error) {
        console.error('Erreur lors de la réinitialisation:', error);
        alert('Erreur lors de la réinitialisation');
    }
};

const getAuthToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
};

onMounted(() => {
    loadPreferences();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>

