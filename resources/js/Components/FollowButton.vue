<template>
    <button
        @click="toggleFollow"
        :disabled="loading"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
        :class="buttonClasses"
    >
        <svg v-if="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
                v-if="!isFollowing"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />
            <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
            />
        </svg>

        <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        <span>{{ buttonText }}</span>
    </button>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ['App\\Models\\Topic', 'App\\Models\\PropositionLoi', 'App\\Models\\Post', 'App\\Models\\ThematiqueLegislation'].includes(value),
    },
    id: {
        type: [String, Number],
        required: true,
    },
    followText: {
        type: String,
        default: 'Suivre',
    },
    followingText: {
        type: String,
        default: 'Suivi',
    },
});

const emit = defineEmits(['followed', 'unfollowed', 'error']);

const isFollowing = ref(false);
const loading = ref(false);

const buttonText = computed(() => {
    if (loading.value) return 'Chargement...';
    return isFollowing.value ? props.followingText : props.followText;
});

const buttonClasses = computed(() => {
    if (isFollowing.value) {
        return 'bg-green-50 text-green-700 border-2 border-green-200 hover:bg-green-100 hover:border-green-300 focus:ring-green-500';
    }
    return 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500';
});

const toggleFollow = async () => {
    loading.value = true;

    try {
        const endpoint = isFollowing.value ? '/api/follows/unfollow' : '/api/follows/follow';
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
            body: JSON.stringify({
                type: props.type,
                id: props.id,
            }),
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la mise à jour du suivi');
        }

        const data = await response.json();

        if (data.success) {
            isFollowing.value = !isFollowing.value;
            emit(isFollowing.value ? 'followed' : 'unfollowed');
        } else {
            throw new Error(data.message || 'Erreur inconnue');
        }
    } catch (error) {
        console.error('Erreur lors du toggle follow:', error);
        emit('error', error);
        alert('Erreur lors de la mise à jour du suivi');
    } finally {
        loading.value = false;
    }
};

const checkFollowStatus = async () => {
    try {
        const response = await fetch(`/api/follows/check?type=${encodeURIComponent(props.type)}&id=${props.id}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,
            },
        });

        if (response.ok) {
            const data = await response.json();
            isFollowing.value = data.is_following || false;
        }
    } catch (error) {
        console.error('Erreur lors de la vérification du suivi:', error);
    }
};

const getAuthToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
};

onMounted(() => {
    checkFollowStatus();
});
</script>

<style scoped>
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

