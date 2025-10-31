<template>
    <div class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50 animate-fade-in">
        <!-- En-tête -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
            <div class="flex items-center gap-2">
                <button
                    v-if="hasUnread"
                    @click="$emit('mark-all-as-read')"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors"
                >
                    Tout marquer comme lu
                </button>
                <button
                    @click="$emit('close')"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Liste des notifications -->
        <div class="max-h-96 overflow-y-auto">
            <!-- Loading -->
            <div v-if="loading" class="p-8 text-center">
                <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="mt-2 text-sm text-gray-500">Chargement...</p>
            </div>

            <!-- Liste -->
            <div v-else-if="notifications.length > 0" class="divide-y divide-gray-100">
                <NotificationItem
                    v-for="notification in notifications"
                    :key="notification.id"
                    :notification="notification"
                    @mark-as-read="$emit('mark-as-read', notification.id)"
                    @delete="$emit('delete', notification.id)"
                    @click="handleNotificationClick(notification)"
                />
            </div>

            <!-- Vide -->
            <div v-else class="p-8 text-center">
                <div class="text-6xl mb-2">🔔</div>
                <p class="text-gray-500 font-medium">Aucune notification</p>
                <p class="text-sm text-gray-400 mt-1">Vous êtes à jour !</p>
            </div>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0" class="p-3 border-t border-gray-200 bg-gray-50">
            <button
                @click="$emit('load-more')"
                class="w-full text-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors"
            >
                Voir toutes les notifications
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import NotificationItem from './NotificationItem.vue';

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['mark-as-read', 'mark-all-as-read', 'delete', 'close', 'load-more']);

const hasUnread = computed(() => {
    return props.notifications.some(n => !n.read_at);
});

const handleNotificationClick = (notification) => {
    // Marquer comme lu
    if (!notification.read_at) {
        emit('mark-as-read', notification.id);
    }

    // Rediriger si un lien est disponible
    if (notification.link) {
        window.location.href = notification.link;
    }
};
</script>

<style scoped>
/* Scrollbar personnalisé */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

