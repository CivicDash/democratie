<script setup>
const props = defineProps({
    variant: {
        type: String,
        default: 'gray', // gray, blue, green, yellow, red, indigo
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
});

const variantClasses = {
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
};

const sizeClasses = {
    sm: 'text-xs px-2 py-0.5',
    md: 'text-sm px-2.5 py-0.5',
    lg: 'text-base px-3 py-1',
};
</script>

<template>
    <span 
        class="inline-flex items-center font-medium rounded-full" 
        :class="[variantClasses[variant], sizeClasses[size]]"
    >
        <slot />
    </span>
</template>

