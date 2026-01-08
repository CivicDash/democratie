<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
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
    label: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    activeColor: {
        type: String,
        default: 'blue',
        validator: (v) => ['blue', 'green', 'indigo', 'purple'].includes(v),
    },
});

const toggle = () => {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
    }
};

const sizeConfig = computed(() => {
    const configs = {
        sm: {
            track: 'w-8 h-[18px]',
            thumb: 'w-3.5 h-3.5',
            translate: 'translate-x-[14px]',
            text: 'text-sm',
        },
        md: {
            track: 'w-10 h-5',
            thumb: 'w-4 h-4',
            translate: 'translate-x-5',
            text: 'text-sm',
        },
        lg: {
            track: 'w-12 h-6',
            thumb: 'w-5 h-5',
            translate: 'translate-x-6',
            text: 'text-base',
        },
    };
    return configs[props.size];
});

const colorClasses = computed(() => {
    const colors = {
        blue: 'bg-blue-600',
        green: 'bg-green-600',
        indigo: 'bg-indigo-600',
        purple: 'bg-purple-600',
    };
    return colors[props.activeColor];
});
</script>

<template>
    <label 
        class="inline-flex items-start gap-3 cursor-pointer select-none"
        :class="{ 'opacity-50 cursor-not-allowed': disabled }"
    >
        <!-- Toggle Switch -->
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            @click="toggle"
            :disabled="disabled"
            class="relative inline-flex flex-shrink-0 rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            :class="[
                sizeConfig.track,
                modelValue ? colorClasses : 'bg-gray-300 dark:bg-gray-600'
            ]"
        >
            <span
                class="pointer-events-none inline-block rounded-full bg-white shadow-md transform transition-transform duration-200 ease-in-out"
                :class="[
                    sizeConfig.thumb,
                    modelValue ? sizeConfig.translate : 'translate-x-0.5',
                    'mt-0.5 ml-0.5'
                ]"
            />
        </button>

        <!-- Label & Description -->
        <div v-if="label || description || $slots.default" class="flex-1 min-w-0">
            <span 
                v-if="label" 
                class="font-medium text-gray-900 dark:text-gray-100"
                :class="sizeConfig.text"
            >
                {{ label }}
            </span>
            <slot v-else />
            <p 
                v-if="description" 
                class="text-gray-500 dark:text-gray-400 mt-0.5"
                :class="size === 'sm' ? 'text-xs' : 'text-sm'"
            >
                {{ description }}
            </p>
        </div>
    </label>
</template>
