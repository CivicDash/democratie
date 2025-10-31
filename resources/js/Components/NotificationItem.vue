<template>
    <div
        class="p-4 hover:bg-gray-50 transition-colors cursor-pointer relative"
        :class="{
            'bg-blue-50': !notification.read_at,
            'opacity-75': notification.read_at,
        }"
        @click="$emit('click')"
    >
        <div class="flex items-start gap-3">
            <!-- Icône -->
            <div
                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-2xl"
                :class="iconBgClass"
            >
                {{ notification.icon || getDefaultIcon(notification.type) }}
            </div>

            <!-- Contenu -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-semibold text-gray-900 line-clamp-2">
                        {{ notification.title }}
                    </h4>
                    <button
                        @click.stop="$emit('delete')"
                        class="flex-shrink-0 p-1 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <p v-if="notification.message" class="text-sm text-gray-600 mt-1 line-clamp-2">
                    {{ notification.message }}
                </p>

                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs text-gray-500">
                        {{ formatTimeAgo(notification.created_at) }}
                    </span>

                    <span
                        v-if="!notification.read_at"
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                        Nouveau
                    </span>

                    <span
                        v-if="notification.priority === 'high' || notification.priority === 'urgent'"
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                    >
                        {{ notification.priority === 'urgent' ? 'Urgent' : 'Important' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Badge non lu -->
        <div
            v-if="!notification.read_at"
            class="absolute top-4 left-1 w-2 h-2 bg-blue-600 rounded-full"
        ></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    notification: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['mark-as-read', 'delete', 'click']);

const iconBgClass = computed(() => {
    const priority = props.notification.priority;
    const type = props.notification.type;

    if (priority === 'urgent') return 'bg-red-100';
    if (priority === 'high') return 'bg-orange-100';
    
    switch (type) {
        case 'new_thematique': return 'bg-purple-100';
        case 'new_groupe': return 'bg-blue-100';
        case 'new_vote': return 'bg-green-100';
        case 'new_legislation': return 'bg-indigo-100';
        case 'vote_result': return 'bg-yellow-100';
        case 'alert': return 'bg-red-100';
        case 'system': return 'bg-gray-100';
        default: return 'bg-gray-100';
    }
});

const getDefaultIcon = (type) => {
    const icons = {
        'new_thematique': '🏷️',
        'new_groupe': '🏛️',
        'new_vote': '🗳️',
        'new_legislation': '📜',
        'vote_result': '📊',
        'alert': '⚠️',
        'system': '⚙️',
    };
    return icons[type] || '🔔';
};

const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'À l\'instant';
    if (seconds < 3600) return `Il y a ${Math.floor(seconds / 60)} min`;
    if (seconds < 86400) return `Il y a ${Math.floor(seconds / 3600)} h`;
    if (seconds < 604800) return `Il y a ${Math.floor(seconds / 86400)} j`;
    
    return date.toLocaleDateString('fr-FR', { 
        day: 'numeric',
        month: 'short',
    });
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

