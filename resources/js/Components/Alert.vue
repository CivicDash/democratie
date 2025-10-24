<script setup>
const props = defineProps({
    type: {
        type: String,
        default: 'info', // info, success, warning, error
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['dismiss']);

const typeClasses = {
    info: 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
    success: 'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800',
    warning: 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-800',
    error: 'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
};

const iconMap = {
    info: 'ℹ️',
    success: '✅',
    warning: '⚠️',
    error: '❌',
};
</script>

<template>
    <div class="border rounded-lg p-4 flex items-start" :class="typeClasses[type]">
        <div class="flex-shrink-0 text-xl mr-3">
            {{ iconMap[type] }}
        </div>
        <div class="flex-1">
            <slot />
        </div>
        <button 
            v-if="dismissible" 
            @click="emit('dismiss')" 
            class="flex-shrink-0 ml-4 text-lg hover:opacity-70 transition-opacity"
        >
            ×
        </button>
    </div>
</template>

