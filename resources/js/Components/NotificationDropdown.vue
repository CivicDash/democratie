<template>
    <div class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50">
        <!-- Header -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between border-b border-gray-200 dark:border-gray-600">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                🔔 Notifications
            </h3>
            <div class="flex items-center gap-2">
                <button
                    v-if="hasUnread"
                    @click="$emit('mark-all-as-read')"
                    class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                >
                    Tout lire
                </button>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-4 text-center text-gray-500">
            <div class="animate-spin inline-block w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full"></div>
        </div>

        <!-- Liste -->
        <div v-else class="max-h-96 overflow-y-auto">
            <div
                v-for="notification in notifications.slice(0, 10)"
                :key="notification.id"
                :class="[
                    'px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer',
                    !notification.read_at && 'bg-amber-50/50 dark:bg-amber-900/10'
                ]"
                @click="handleNotificationClick(notification)"
            >
                <div class="flex items-start gap-3">
                    <span class="text-xl">{{ notification.icon || notification.data?.icon || '🔔' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">
                                {{ notification.title || notification.data?.title || 'Notification' }}
                            </span>
                            <span
                                v-if="!notification.read_at"
                                class="w-2 h-2 bg-amber-500 rounded-full flex-shrink-0"
                            ></span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ notification.message || notification.data?.message || '' }}
                        </p>
                        <span class="text-xs text-gray-400">
                            {{ notification.time_ago || formatTimeAgo(notification.created_at) }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <button
                            v-if="!notification.read_at"
                            @click.stop="$emit('mark-as-read', notification.id)"
                            class="text-gray-400 hover:text-gray-600 text-sm"
                            title="Marquer comme lu"
                        >
                            ✓
                        </button>
                        <button
                            @click.stop="$emit('delete', notification.id)"
                            class="text-gray-400 hover:text-red-500 text-sm"
                            title="Supprimer"
                        >
                            🗑️
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="notifications.length === 0" class="p-6 text-center text-gray-500">
                <div class="text-4xl mb-2">🎉</div>
                <p class="text-sm">Aucune notification</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
            <a
                href="/notifications"
                class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                @click="$emit('close')"
            >
                Voir toutes →
            </a>
            <a
                href="/notifications/preferences"
                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400"
                @click="$emit('close')"
            >
                ⚙️ Préférences
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

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

const hasUnread = computed(() => props.notifications.some(n => !n.read_at));

const handleNotificationClick = (notification) => {
    const actionUrl = notification.action_url || notification.data?.action_url;
    if (actionUrl) {
        emit('mark-as-read', notification.id);
        router.visit(actionUrl);
    }
};

const formatTimeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffMins < 1) return 'À l\'instant';
    if (diffMins < 60) return `Il y a ${diffMins} min`;
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    if (diffDays < 7) return `Il y a ${diffDays}j`;
    return date.toLocaleDateString('fr-FR');
};
</script>
