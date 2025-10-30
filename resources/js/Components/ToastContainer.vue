<script setup>
import Toast from '@/Components/Toast.vue';
import { useToast } from '@/composables/useToast';

const { toasts, close } = useToast();
</script>

<template>
  <div class="toast-container">
    <TransitionGroup name="toast">
      <Toast
        v-for="toast in toasts"
        :key="toast.id"
        v-bind="toast"
        @close="close"
      />
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
  pointer-events: none;
}

.toast-container > * {
  pointer-events: auto;
}

/* Transitions */
.toast-enter-active {
  transition: all 0.3s ease-out;
}

.toast-leave-active {
  transition: all 0.3s ease-in;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.8);
}

.toast-move {
  transition: transform 0.3s ease;
}

/* Responsive */
@media (max-width: 640px) {
  .toast-container {
    top: 16px;
    right: 16px;
    left: 16px;
    align-items: center;
  }
}
</style>

