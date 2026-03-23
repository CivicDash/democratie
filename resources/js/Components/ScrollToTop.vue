<template>
    <Transition name="fade-slide">
        <button
            v-if="isVisible"
            @click="scrollToTop"
            class="scroll-to-top"
            :aria-label="'Retour en haut de page'"
            title="Retour en haut"
        >
            <svg
                class="scroll-icon"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
            </svg>
            <span class="scroll-label">Haut</span>
        </button>
    </Transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    threshold: {
        type: Number,
        default: 300,
    },
});

const isVisible = ref(false);

const handleScroll = () => {
    isVisible.value = window.scrollY > props.threshold;
};

const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.scroll-to-top {
    position: fixed;
    bottom: calc(90px + env(safe-area-inset-bottom));
    right: 16px;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 10px 14px;
    border-radius: 24px;
    background: linear-gradient(135deg, #4F46E5, #6366F1);
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
    cursor: pointer;
    z-index: 40;
    transition: all 0.3s;
    color: white;
}

.scroll-to-top:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
    background: linear-gradient(135deg, #4338CA, #4F46E5);
}

.scroll-to-top:active {
    transform: scale(0.95);
}

.scroll-to-top:focus-visible {
    outline: 2px solid #818CF8;
    outline-offset: 2px;
}

.scroll-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.scroll-label {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.02em;
    line-height: 1;
}

@media (min-width: 769px) {
    .scroll-to-top {
        display: none;
    }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

@media (prefers-color-scheme: dark) {
    .scroll-to-top {
        background: linear-gradient(135deg, #6366F1, #818CF8);
        border-color: rgba(255, 255, 255, 0.15);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
    }

    .scroll-to-top:hover {
        background: linear-gradient(135deg, #4F46E5, #6366F1);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
    }
}
</style>
