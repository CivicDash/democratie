<template>
    <div 
        ref="cardRef"
        class="swipeable-card"
        :class="{ 'swiping': isSwiping, 'swiped-left': swipedLeft, 'swiped-right': swipedRight }"
        :style="{ transform: `translateX(${translateX}px)` }"
        @touchstart="onTouchStart"
        @touchmove="onTouchMove"
        @touchend="onTouchEnd"
        @click="!isSwiping && $emit('click')"
    >
        <!-- Card Content -->
        <div class="card-content">
            <slot />
        </div>
        
        <!-- Left Action (revealed when swiping right) -->
        <div v-if="$slots.leftAction" class="action-left" :style="{ opacity: leftActionOpacity }">
            <slot name="leftAction" />
        </div>
        
        <!-- Right Action (revealed when swiping left) -->
        <div v-if="$slots.rightAction" class="action-right" :style="{ opacity: rightActionOpacity }">
            <slot name="rightAction" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    swipeThreshold: {
        type: Number,
        default: 100,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['swipeLeft', 'swipeRight', 'click']);

const cardRef = ref(null);
const startX = ref(0);
const currentX = ref(0);
const translateX = ref(0);
const isSwiping = ref(false);
const swipedLeft = ref(false);
const swipedRight = ref(false);

const leftActionOpacity = computed(() => {
    return Math.min(Math.abs(translateX.value) / props.swipeThreshold, 1);
});

const rightActionOpacity = computed(() => {
    return Math.min(Math.abs(translateX.value) / props.swipeThreshold, 1);
});

const onTouchStart = (e) => {
    if (props.disabled) return;
    startX.value = e.touches[0].clientX;
    isSwiping.value = false;
};

const onTouchMove = (e) => {
    if (props.disabled) return;
    currentX.value = e.touches[0].clientX;
    const diff = currentX.value - startX.value;
    
    if (Math.abs(diff) > 10) {
        isSwiping.value = true;
    }
    
    translateX.value = diff;
};

const onTouchEnd = () => {
    if (props.disabled) return;
    
    const diff = currentX.value - startX.value;
    
    // Swipe Left
    if (diff < -props.swipeThreshold) {
        swipedLeft.value = true;
        emit('swipeLeft');
        setTimeout(() => {
            translateX.value = 0;
            swipedLeft.value = false;
            isSwiping.value = false;
        }, 300);
    }
    // Swipe Right
    else if (diff > props.swipeThreshold) {
        swipedRight.value = true;
        emit('swipeRight');
        setTimeout(() => {
            translateX.value = 0;
            swipedRight.value = false;
            isSwiping.value = false;
        }, 300);
    }
    // Reset
    else {
        translateX.value = 0;
        isSwiping.value = false;
    }
};
</script>

<style scoped>
.swipeable-card {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.1s ease-out;
    cursor: pointer;
    user-select: none;
    -webkit-user-select: none;
}

.swipeable-card.swiping {
    transition: none;
}

.card-content {
    position: relative;
    z-index: 1;
    background: white;
}

.action-left,
.action-right {
    position: absolute;
    top: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    z-index: 0;
    transition: opacity 0.2s;
}

.action-left {
    left: 0;
    background: linear-gradient(90deg, #10B981, #059669);
}

.action-right {
    right: 0;
    background: linear-gradient(90deg, #EF4444, #DC2626);
}

.swipeable-card.swiped-left .card-content {
    animation: swipeOut 0.3s ease-out;
}

.swipeable-card.swiped-right .card-content {
    animation: swipeOut 0.3s ease-out;
}

@keyframes swipeOut {
    to {
        opacity: 0;
        transform: scale(0.8);
    }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .swipeable-card {
        background: #1F2937;
    }
    
    .card-content {
        background: #1F2937;
    }
}
</style>

