<template>
    <Teleport to="body">
        <div class="fab-container">
            <!-- Main FAB -->
            <button
                @click="toggleMenu"
                class="fab-main"
                :class="{ 'active': menuOpen }"
                aria-label="Actions rapides"
            >
                <svg 
                    v-if="!menuOpen"
                    class="fab-icon" 
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 20 20" 
                    fill="currentColor"
                >
                    <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                </svg>
                <svg 
                    v-else
                    class="fab-icon" 
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 20 20" 
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <!-- Sub Actions -->
            <Transition name="fab-menu">
                <div v-if="menuOpen" class="fab-menu">
                    <button
                        v-for="action in actions"
                        :key="action.id"
                        @click="handleAction(action)"
                        class="fab-action"
                        :style="{ transitionDelay: `${action.delay}ms` }"
                    >
                        <span class="fab-action-icon">{{ action.icon }}</span>
                        <span class="fab-action-label">{{ action.label }}</span>
                    </button>
                </div>
            </Transition>

            <!-- Backdrop -->
            <Transition name="fade">
                <div 
                    v-if="menuOpen" 
                    class="fab-backdrop"
                    @click="toggleMenu"
                ></div>
            </Transition>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    actions: {
        type: Array,
        default: () => [
            { id: 'new-topic', icon: '💬', label: 'Nouveau débat', delay: 0 },
            { id: 'new-proposal', icon: '💡', label: 'Proposition', delay: 50 },
            { id: 'search', icon: '🔍', label: 'Recherche', delay: 100 },
        ],
    },
});

const emit = defineEmits(['action']);

const menuOpen = ref(false);

const toggleMenu = () => {
    menuOpen.value = !menuOpen.value;
};

const handleAction = (action) => {
    emit('action', action.id);
    toggleMenu();
};
</script>

<style scoped>
.fab-container {
    position: fixed;
    bottom: calc(80px + env(safe-area-inset-bottom));
    right: 20px;
    z-index: 40;
}

/* Hide on desktop */
@media (min-width: 769px) {
    .fab-container {
        display: none;
    }
}

.fab-main {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4F46E5, #6366F1);
    color: white;
    border: none;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 42;
}

.fab-main:active {
    transform: scale(0.95);
}

.fab-main.active {
    background: linear-gradient(135deg, #DC2626, #B91C1C);
    transform: rotate(45deg);
}

.fab-icon {
    width: 24px;
    height: 24px;
    transition: transform 0.3s;
}

.fab-main.active .fab-icon {
    transform: rotate(-45deg);
}

.fab-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 39;
}

.fab-menu {
    position: absolute;
    bottom: 70px;
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 41;
}

.fab-action {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: white;
    border: none;
    border-radius: 28px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.fab-action:active {
    transform: scale(0.95);
}

.fab-action-icon {
    font-size: 20px;
}

.fab-action-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

/* Animations */
.fab-menu-enter-active .fab-action {
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.fab-menu-leave-active .fab-action {
    animation: slideDown 0.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideDown {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(20px);
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .fab-action {
        background: #1F2937;
        color: white;
    }
    
    .fab-action-label {
        color: #F3F4F6;
    }
}
</style>

