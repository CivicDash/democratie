<template>
    <div class="relative" ref="bellContainer">
        <!-- Bouton cloche avec badge -->
        <button
            @click="toggleDropdown"
            class="relative p-2 text-gray-600 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg transition-all hover:bg-gray-100"
            :class="{ 'bg-blue-50': isOpen }"
            aria-label="Notifications"
        >
            <!-- Icône cloche -->
            <svg
                class="w-6 h-6"
                :class="{ 'animate-bounce': hasNewNotifications && !isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <!-- Badge avec nombre de notifications non lues -->
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown des notifications -->
        <NotificationDropdown
            v-if="isOpen"
            :notifications="notifications"
            :loading="loading"
            @mark-as-read="handleMarkAsRead"
            @mark-all-as-read="handleMarkAllAsRead"
            @delete="handleDelete"
            @close="isOpen = false"
            @load-more="loadNotifications"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import NotificationDropdown from './NotificationDropdown.vue';

const props = defineProps({
    autoRefresh: {
        type: Boolean,
        default: true,
    },
    refreshInterval: {
        type: Number,
        default: 30000, // 30 secondes
    },
});

const emit = defineEmits(['notification-clicked']);

// État
const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);
const bellContainer = ref(null);
let refreshTimer = null;

// Computed
const hasNewNotifications = computed(() => unreadCount.value > 0);

// Méthodes
const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        loadNotifications();
    }
};

const loadNotifications = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/notifications?per_page=20', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            const data = await response.json();
            notifications.value = data.notifications || [];
        }
    } catch (error) {
        console.error('Erreur lors du chargement des notifications:', error);
    } finally {
        loading.value = false;
    }
};

const loadUnreadCount = async () => {
    try {
        const response = await fetch('/api/notifications/unread-count', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            const data = await response.json();
            unreadCount.value = data.count || 0;
        }
    } catch (error) {
        console.error('Erreur lors du chargement du compteur:', error);
    }
};

const handleMarkAsRead = async (notificationId) => {
    try {
        const response = await fetch(`/api/notifications/${notificationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            // Mettre à jour localement
            const notification = notifications.value.find(n => n.id === notificationId);
            if (notification) {
                notification.read_at = new Date().toISOString();
            }
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error('Erreur lors du marquage comme lu:', error);
    }
};

const handleMarkAllAsRead = async () => {
    try {
        const response = await fetch('/api/notifications/mark-all-as-read', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            // Mettre à jour localement
            notifications.value.forEach(n => {
                n.read_at = new Date().toISOString();
            });
            unreadCount.value = 0;
        }
    } catch (error) {
        console.error('Erreur lors du marquage de toutes comme lues:', error);
    }
};

const handleDelete = async (notificationId) => {
    try {
        const response = await fetch(`/api/notifications/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            // Supprimer localement
            const index = notifications.value.findIndex(n => n.id === notificationId);
            if (index !== -1) {
                const wasUnread = !notifications.value[index].read_at;
                notifications.value.splice(index, 1);
                if (wasUnread) {
                    unreadCount.value = Math.max(0, unreadCount.value - 1);
                }
            }
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
    }
};

const getAuthToken = () => {
    // Récupérer le token d'authentification (adapter selon votre implémentation)
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
};

// Fermer le dropdown si on clique à l'extérieur
const handleClickOutside = (event) => {
    if (bellContainer.value && !bellContainer.value.contains(event.target)) {
        isOpen.value = false;
    }
};

// Lifecycle
onMounted(() => {
    loadUnreadCount();

    // Rafraîchir automatiquement
    if (props.autoRefresh) {
        refreshTimer = setInterval(() => {
            loadUnreadCount();
            if (isOpen.value) {
                loadNotifications();
            }
        }, props.refreshInterval);
    }

    // Écouter les clics à l'extérieur
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
/* Animations personnalisées si nécessaire */
</style>

