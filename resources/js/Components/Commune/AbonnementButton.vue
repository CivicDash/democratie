<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    codeInsee: String,
    estAbonne: Boolean,
});

const loading = ref(false);

const toggleAbonnement = () => {
    if (!usePage().props.auth?.user) {
        window.location.href = route('login');
        return;
    }

    loading.value = true;

    if (props.estAbonne) {
        router.delete(route('commune.desabonner', props.codeInsee), {
            preserveScroll: true,
            onFinish: () => loading.value = false,
        });
    } else {
        router.post(route('commune.abonner', props.codeInsee), {}, {
            preserveScroll: true,
            onFinish: () => loading.value = false,
        });
    }
};
</script>

<template>
    <button
        @click="toggleAbonnement"
        :disabled="loading"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
        :class="estAbonne
            ? 'bg-blue-100 text-blue-700 hover:bg-red-100 hover:text-red-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-red-900/30 dark:hover:text-red-300'
            : 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm'"
    >
        <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <svg v-else-if="estAbonne" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z" />
            <path d="M10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        {{ estAbonne ? 'Abonne' : 'Suivre cette commune' }}
    </button>
</template>
