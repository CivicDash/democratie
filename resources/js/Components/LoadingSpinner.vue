<script setup>
defineProps({
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
});

const sizeClasses = {
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
};
</script>

<template>
    <div class="flex justify-center items-center">
        <div 
            class="animate-spin rounded-full border-4 border-gray-300 border-t-indigo-600 dark:border-gray-600 dark:border-t-indigo-400" 
            :class="sizeClasses[size]"
        ></div>
    </div>
</template>

