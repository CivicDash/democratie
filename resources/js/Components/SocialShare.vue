<template>
    <div class="inline-flex items-center gap-2">
        <button
            v-if="compact"
            @click="isOpen = !isOpen"
            class="relative p-2 text-gray-600 hover:text-blue-600 hover:bg-gray-100 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="buttonClass"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
        </button>

        <!-- Version étendue -->
        <div v-else class="flex items-center gap-2" :class="buttonClass">
            <span v-if="showLabel" class="text-sm font-medium text-gray-700">{{ label }}</span>
            
            <button
                v-for="platform in platforms"
                :key="platform.name"
                @click="share(platform.name)"
                :title="`Partager sur ${platform.label}`"
                class="p-2 rounded-lg transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-1"
                :class="platform.bgClass"
                :style="{ backgroundColor: platform.color }"
            >
                <component :is="platform.icon" class="w-5 h-5 text-white" />
            </button>

            <button
                @click="copyLink"
                title="Copier le lien"
                class="p-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-1"
            >
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </button>
        </div>

        <!-- Dropdown pour mode compact -->
        <div
            v-if="compact && isOpen"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50 p-2 animate-fade-in"
            style="top: 100%;"
        >
            <button
                v-for="platform in platforms"
                :key="platform.name"
                @click="share(platform.name)"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors text-left"
            >
                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" :style="{ backgroundColor: platform.color }">
                    <component :is="platform.icon" class="w-5 h-5 text-white" />
                </div>
                <span class="text-sm font-medium text-gray-700">{{ platform.label }}</span>
            </button>

            <hr class="my-2 border-gray-200" />

            <button
                @click="copyLink"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors text-left"
            >
                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-gray-200">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700">Copier le lien</span>
            </button>
        </div>

        <!-- Toast de confirmation -->
        <Transition name="fade">
            <div
                v-if="showToast"
                class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 z-50 animate-fade-in"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Lien copié dans le presse-papier !</span>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, h } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    url: {
        type: String,
        default: () => window.location.href,
    },
    hashtags: {
        type: Array,
        default: () => [],
    },
    compact: {
        type: Boolean,
        default: false,
    },
    showLabel: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
        default: 'Partager',
    },
    buttonClass: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['shared', 'copied']);

const isOpen = ref(false);
const showToast = ref(false);

// Icônes SVG en tant que composants
const TwitterIcon = h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { d: 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z' }),
]);

const FacebookIcon = h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' }),
]);

const LinkedInIcon = h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { d: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z' }),
]);

const EmailIcon = h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' }),
]);

const platforms = [
    {
        name: 'twitter',
        label: 'Twitter',
        color: '#1DA1F2',
        icon: TwitterIcon,
    },
    {
        name: 'facebook',
        label: 'Facebook',
        color: '#1877F2',
        icon: FacebookIcon,
    },
    {
        name: 'linkedin',
        label: 'LinkedIn',
        color: '#0A66C2',
        icon: LinkedInIcon,
    },
    {
        name: 'email',
        label: 'Email',
        color: '#EA4335',
        icon: EmailIcon,
    },
];

const share = (platform) => {
    const encodedUrl = encodeURIComponent(props.url);
    const encodedTitle = encodeURIComponent(props.title);
    const encodedDescription = encodeURIComponent(props.description);
    const hashtags = props.hashtags.join(',');

    let shareUrl = '';

    switch (platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}${hashtags ? `&hashtags=${hashtags}` : ''}`;
            break;
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
            break;
        case 'email':
            shareUrl = `mailto:?subject=${encodedTitle}&body=${encodedDescription}%0A%0A${encodedUrl}`;
            break;
    }

    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
        emit('shared', platform);
    }

    if (props.compact) {
        isOpen.value = false;
    }
};

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(props.url);
        showToast.value = true;
        setTimeout(() => {
            showToast.value = false;
        }, 3000);
        emit('copied');
    } catch (error) {
        console.error('Erreur lors de la copie:', error);
        alert('Impossible de copier le lien');
    }

    if (props.compact) {
        isOpen.value = false;
    }
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>

