<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    reactions: { type: Object, default: () => ({}) },
    userReaction: { type: String, default: null },
    codeInsee: { type: String, required: true },
    reactableType: { type: String, required: true },
    reactableId: { type: String, required: true },
});

const auth = computed(() => usePage().props.auth?.user);
const showPicker = ref(false);

const reactionEmojis = {
    like: '👍',
    love: '❤️',
    celebrate: '🎉',
    surprise: '😮',
    discuss: '💬',
};

const totalReactions = computed(() =>
    Object.values(props.reactions).reduce((sum, count) => sum + count, 0)
);

const toggleReaction = (type) => {
    if (!auth.value) { window.location.href = route('login'); return; }
    router.post(
        route('commune.reactions.toggle', [props.codeInsee, props.reactableType, props.reactableId]),
        { type },
        { preserveScroll: true }
    );
    showPicker.value = false;
};
</script>

<template>
    <div class="flex items-center gap-2 relative">
        <!-- Existing reaction counts -->
        <button
            v-for="(count, type) in reactions"
            :key="type"
            v-show="count > 0"
            @click="toggleReaction(type)"
            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs transition-colors"
            :class="userReaction === type
                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-1 ring-blue-300 dark:ring-blue-700'
                : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
        >
            <span>{{ reactionEmojis[type] }}</span>
            <span class="font-medium">{{ count }}</span>
        </button>

        <!-- Add reaction button -->
        <div class="relative">
            <button
                @click="showPicker = !showPicker"
                class="p-1.5 rounded-full text-slate-400 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                :title="auth ? 'Reagir' : 'Connectez-vous pour reagir'"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>

            <!-- Picker popup -->
            <div
                v-if="showPicker"
                class="absolute bottom-full mb-2 left-0 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 p-1.5 flex gap-1 z-50"
                @mouseleave="showPicker = false"
            >
                <button
                    v-for="(emoji, type) in reactionEmojis"
                    :key="type"
                    @click="toggleReaction(type)"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-all hover:scale-125"
                    :title="type"
                >
                    {{ emoji }}
                </button>
            </div>
        </div>

        <span v-if="totalReactions > 0" class="text-xs text-slate-400 ml-1">{{ totalReactions }}</span>
    </div>
</template>
