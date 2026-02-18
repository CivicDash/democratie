<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: Boolean,
    title: { type: String, default: '' },
    sections: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const sheetEl = ref(null);
const startY = ref(0);
const currentY = ref(0);
const dragging = ref(false);

function close() {
    emit('update:modelValue', false);
}

function onTouchStart(e) {
    if (!sheetEl.value) return;
    startY.value = e.touches[0].clientY;
    dragging.value = true;
}

function onTouchMove(e) {
    if (!dragging.value) return;
    currentY.value = Math.max(0, e.touches[0].clientY - startY.value);
    if (sheetEl.value) {
        sheetEl.value.style.transform = `translateY(${currentY.value}px)`;
    }
}

function onTouchEnd() {
    dragging.value = false;
    if (currentY.value > 100) {
        close();
    }
    if (sheetEl.value) {
        sheetEl.value.style.transform = '';
    }
    currentY.value = 0;
}

watch(() => props.modelValue, (open) => {
    if (open) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

router.on('navigate', () => close());

onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="motion-safe:duration-300 motion-safe:ease-out motion-reduce:duration-0"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="motion-safe:duration-200 motion-safe:ease-in motion-reduce:duration-0"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modelValue" class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm" @click="close" />
        </Transition>

        <Transition
            enter-active-class="motion-safe:duration-300 motion-safe:ease-out motion-reduce:duration-0"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="motion-safe:duration-200 motion-safe:ease-in motion-reduce:duration-0"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="modelValue"
                ref="sheetEl"
                role="dialog"
                :aria-label="title || 'Menu de navigation'"
                class="fixed inset-x-0 bottom-0 z-[61] max-h-[85vh] flex flex-col bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl will-change-transform"
                style="padding-bottom: env(safe-area-inset-bottom, 0px);"
                @touchstart="onTouchStart"
                @touchmove.passive="onTouchMove"
                @touchend="onTouchEnd"
            >
                <!-- Handle bar -->
                <div class="flex justify-center pt-3 pb-1 flex-shrink-0 cursor-grab">
                    <div class="w-10 h-1 rounded-full bg-gray-300 dark:bg-gray-600" />
                </div>

                <!-- Title -->
                <div v-if="title" class="px-5 pb-3 flex-shrink-0">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ title }}</h2>
                </div>

                <!-- Scrollable content -->
                <div class="flex-1 overflow-y-auto overscroll-contain px-4 pb-6">
                    <div v-for="section in sections" :key="section.label" class="mb-5 last:mb-0">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-1 mb-2">
                            {{ section.label }}
                        </div>
                        <div class="space-y-0.5" role="list">
                            <Link
                                v-for="item in section.items"
                                :key="item.href"
                                :href="item.href"
                                role="listitem"
                                :aria-label="item.title + (item.description ? ' - ' + item.description : '')"
                                class="flex items-center gap-3 px-3 py-3 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/60 active:bg-gray-200 dark:active:bg-gray-700 transition-colors min-h-[44px] focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-indigo-500"
                            >
                                <span class="text-lg flex-shrink-0 w-8 text-center" aria-hidden="true">{{ item.icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium truncate">{{ item.title }}</div>
                                    <div v-if="item.description" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ item.description }}</div>
                                </div>
                                <span
                                    v-if="item.badge"
                                    class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                                    aria-label="badge"
                                >
                                    {{ item.badge }}
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
