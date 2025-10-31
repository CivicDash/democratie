<template>
    <Transition name="fade-slide">
        <button
            v-if="isVisible"
            @click="scrollToTop"
            class="scroll-to-top"
            aria-label="Retour en haut"
        >
            <svg 
                class="scroll-icon" 
                xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 20 20" 
                fill="currentColor"
            >
                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
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
    left: 20px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F3F4F6, #E5E7EB);
    border: 2px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 40;
    transition: all 0.3s;
}

.scroll-to-top:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.scroll-to-top:active {
    transform: scale(0.95);
}

.scroll-icon {
    width: 24px;
    height: 24px;
    color: #6B7280;
}

/* Hide on desktop */
@media (min-width: 769px) {
    .scroll-to-top {
        display: none;
    }
}

/* Transitions */
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

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .scroll-to-top {
        background: linear-gradient(135deg, #374151, #1F2937);
        border-color: #111827;
    }
    
    .scroll-icon {
        color: #F3F4F6;
    }
}
</style>

