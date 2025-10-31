<template>
    <div class="pull-to-refresh" ref="containerRef">
        <div 
            class="pull-indicator"
            :class="{ 'active': pullDistance > threshold, 'refreshing': isRefreshing }"
            :style="{ transform: `translateY(${Math.min(pullDistance, maxPullDistance)}px)` }"
        >
            <div class="indicator-icon">
                <svg 
                    v-if="!isRefreshing"
                    class="refresh-icon"
                    :class="{ 'rotated': pullDistance > threshold }"
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 20 20" 
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" transform="rotate(90 10 10)" />
                </svg>
                <div v-else class="spinner"></div>
            </div>
            <span class="indicator-text">
                {{ isRefreshing ? 'Actualisation...' : (pullDistance > threshold ? 'Relâcher pour actualiser' : 'Tirer pour actualiser') }}
            </span>
        </div>
        
        <div class="content">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    threshold: {
        type: Number,
        default: 80,
    },
    maxPullDistance: {
        type: Number,
        default: 150,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['refresh']);

const containerRef = ref(null);
const pullDistance = ref(0);
const startY = ref(0);
const isPulling = ref(false);
const isRefreshing = ref(false);

const onTouchStart = (e) => {
    if (props.disabled || isRefreshing.value) return;
    
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    
    // Only trigger if at the top of the page
    if (scrollTop === 0) {
        startY.value = e.touches[0].clientY;
        isPulling.value = true;
    }
};

const onTouchMove = (e) => {
    if (!isPulling.value || props.disabled || isRefreshing.value) return;
    
    const currentY = e.touches[0].clientY;
    const diff = currentY - startY.value;
    
    if (diff > 0) {
        pullDistance.value = Math.min(diff * 0.5, props.maxPullDistance);
        
        // Prevent default scroll behavior
        if (pullDistance.value > 10) {
            e.preventDefault();
        }
    }
};

const onTouchEnd = async () => {
    if (!isPulling.value || props.disabled) return;
    
    isPulling.value = false;
    
    if (pullDistance.value > props.threshold && !isRefreshing.value) {
        isRefreshing.value = true;
        
        try {
            await emit('refresh');
        } finally {
            setTimeout(() => {
                isRefreshing.value = false;
                pullDistance.value = 0;
            }, 500);
        }
    } else {
        pullDistance.value = 0;
    }
};

onMounted(() => {
    const container = containerRef.value;
    if (container) {
        container.addEventListener('touchstart', onTouchStart, { passive: true });
        container.addEventListener('touchmove', onTouchMove, { passive: false });
        container.addEventListener('touchend', onTouchEnd);
    }
});

onUnmounted(() => {
    const container = containerRef.value;
    if (container) {
        container.removeEventListener('touchstart', onTouchStart);
        container.removeEventListener('touchmove', onTouchMove);
        container.removeEventListener('touchend', onTouchEnd);
    }
});
</script>

<style scoped>
.pull-to-refresh {
    position: relative;
    overflow: hidden;
}

.pull-indicator {
    position: absolute;
    top: -60px;
    left: 0;
    right: 0;
    height: 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10;
}

.pull-indicator.refreshing {
    transform: translateY(60px) !important;
}

.indicator-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.refresh-icon {
    width: 24px;
    height: 24px;
    color: #6366F1;
    transition: transform 0.3s;
}

.refresh-icon.rotated {
    transform: rotate(180deg);
}

.spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #E5E7EB;
    border-top-color: #6366F1;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.indicator-text {
    font-size: 13px;
    font-weight: 600;
    color: #6B7280;
}

.content {
    position: relative;
}

/* Hide on desktop */
@media (min-width: 769px) {
    .pull-indicator {
        display: none;
    }
}
</style>

