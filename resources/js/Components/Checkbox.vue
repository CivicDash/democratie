<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },
    set(val) {
        emit('update:checked', val);
    },
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'w-4 h-4',
        md: 'w-[18px] h-[18px]',
        lg: 'w-5 h-5',
    };
    return sizes[props.size];
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        :disabled="disabled"
        :class="[
            sizeClasses,
            'rounded border-2 border-gray-300 text-blue-600 shadow-sm',
            'focus:ring-2 focus:ring-blue-500 focus:ring-offset-1',
            'dark:border-gray-600 dark:bg-gray-800 dark:focus:ring-blue-500 dark:focus:ring-offset-gray-900',
            'transition-colors duration-150 cursor-pointer',
            'checked:bg-blue-600 checked:border-blue-600',
            { 'opacity-50 cursor-not-allowed': disabled }
        ]"
    />
</template>
