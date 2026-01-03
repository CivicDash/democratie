<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    notifications: Object,
    stats: Object,
    filter: String,
    categories: Object,
});

const breadcrumbs = [
    { label: 'Accueil', url: '/' },
    { label: 'Notifications' },
];

const loading = ref({});

// Marquer comme lue
const markAsRead = async (notification) => {
    loading.value[notification.id] = true;
    
    try {
        await fetch(route('notifications.read', notification.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        router.reload({ only: ['notifications', 'stats'] });
    } finally {
        loading.value[notification.id] = false;
    }
};

// Acquitter
const acknowledge = async (notification) => {
    loading.value[notification.id] = true;
    
    try {
        await fetch(route('notifications.acknowledge', notification.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        router.reload({ only: ['notifications', 'stats'] });
    } finally {
        loading.value[notification.id] = false;
    }
};

// Marquer comme traitée
const markAsActioned = async (notification) => {
    loading.value[notification.id] = true;
    
    try {
        await fetch(route('notifications.action', notification.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ action_type: 'completed' }),
        });
        router.reload({ only: ['notifications', 'stats'] });
    } finally {
        loading.value[notification.id] = false;
    }
};

// Tout marquer comme lu
const markAllAsRead = async () => {
    await fetch(route('notifications.read-all'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    router.reload({ only: ['notifications', 'stats'] });
};

// Supprimer
const deleteNotification = async (notification) => {
    if (!confirm('Supprimer cette notification ?')) return;
    
    loading.value[notification.id] = true;
    
    try {
        await fetch(route('notifications.destroy', notification.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        router.reload({ only: ['notifications', 'stats'] });
    } finally {
        loading.value[notification.id] = false;
    }
};

// Formater la date
const formatDate = (date) => {
    return new Date(date).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Obtenir la couleur du statut
const getStatusColor = (notification) => {
    if (notification.actioned_at) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (notification.acknowledged_at) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    if (notification.read_at) return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
};

const getStatusLabel = (notification) => {
    if (notification.actioned_at) return 'Traitée';
    if (notification.acknowledged_at) return 'Acquittée';
    if (notification.read_at) return 'Lue';
    return 'Non lue';
};
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <!-- Hero Banner -->
        <section class="relative overflow-hidden bg-gradient-to-br from-amber-600 via-orange-600 to-rose-600">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-4xl">
                            🔔
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">
                                Centre de notifications
                            </h1>
                            <p class="text-amber-100">
                                Gérez vos alertes et restez informé
                            </p>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="flex flex-wrap gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                            <div class="text-2xl font-bold text-white">{{ stats.unread }}</div>
                            <div class="text-amber-200 text-xs uppercase">Non lues</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                            <div class="text-2xl font-bold text-white">{{ stats.pending }}</div>
                            <div class="text-amber-200 text-xs uppercase">À traiter</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                            <div class="text-2xl font-bold text-white">{{ stats.this_week }}</div>
                            <div class="text-amber-200 text-xs uppercase">Cette semaine</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Actions bar -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex gap-2">
                        <Link
                            :href="route('notifications.index')"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                filter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            Toutes
                        </Link>
                        <Link
                            :href="route('notifications.index', { filter: 'unread' })"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                filter === 'unread' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            Non lues ({{ stats.unread }})
                        </Link>
                        <Link
                            :href="route('notifications.index', { filter: 'unacknowledged' })"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                filter === 'unacknowledged' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            À acquitter
                        </Link>
                    </div>
                    
                    <div class="flex gap-2">
                        <button
                            v-if="stats.unread > 0"
                            @click="markAllAsRead"
                            class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 transition"
                        >
                            ✓ Tout marquer comme lu
                        </button>
                        <Link
                            :href="route('notifications.preferences')"
                            class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 transition"
                        >
                            ⚙️ Préférences
                        </Link>
                    </div>
                </div>

                <!-- Liste des notifications -->
                <div class="space-y-3">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        :class="[
                            'bg-white dark:bg-gray-800 rounded-xl shadow-sm border p-4 transition',
                            notification.read_at ? 'border-gray-200 dark:border-gray-700' : 'border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-900/10'
                        ]"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icône -->
                            <div class="text-3xl">
                                {{ notification.data.icon || '🔔' }}
                            </div>
                            
                            <!-- Contenu -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ notification.data.title }}
                                    </h3>
                                    <span :class="['px-2 py-0.5 text-xs rounded-full', getStatusColor(notification)]">
                                        {{ getStatusLabel(notification) }}
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">
                                    {{ notification.data.message }}
                                </p>
                                
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <span>{{ formatDate(notification.created_at) }}</span>
                                    <span v-if="notification.data.category" class="flex items-center gap-1">
                                        {{ categories[notification.data.category]?.icon }}
                                        {{ categories[notification.data.category]?.label }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex flex-col gap-1">
                                <a
                                    v-if="notification.data.action_url"
                                    :href="notification.data.action_url"
                                    class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-center"
                                >
                                    Voir →
                                </a>
                                
                                <button
                                    v-if="!notification.read_at"
                                    @click="markAsRead(notification)"
                                    :disabled="loading[notification.id]"
                                    class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 transition"
                                >
                                    👁️ Lue
                                </button>
                                
                                <button
                                    v-if="notification.read_at && !notification.acknowledged_at"
                                    @click="acknowledge(notification)"
                                    :disabled="loading[notification.id]"
                                    class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition"
                                >
                                    ✓ Acquitter
                                </button>
                                
                                <button
                                    v-if="notification.acknowledged_at && !notification.actioned_at"
                                    @click="markAsActioned(notification)"
                                    :disabled="loading[notification.id]"
                                    class="px-3 py-1.5 text-xs bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition"
                                >
                                    ✅ Traiter
                                </button>
                                
                                <button
                                    @click="deleteNotification(notification)"
                                    :disabled="loading[notification.id]"
                                    class="px-3 py-1.5 text-xs text-red-500 hover:text-red-700 transition"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="notifications.data.length === 0"
                        class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl"
                    >
                        <div class="text-6xl mb-4">🎉</div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                            Aucune notification
                        </h3>
                        <p class="text-gray-500">
                            {{ filter === 'unread' ? 'Toutes vos notifications sont lues !' : 'Vous n\'avez pas encore de notifications.' }}
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.links && notifications.last_page > 1" class="mt-6 flex justify-center gap-2">
                    <Link
                        v-for="link in notifications.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                            'px-3 py-2 rounded-lg text-sm',
                            link.active ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
