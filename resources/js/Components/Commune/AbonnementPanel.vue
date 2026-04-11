<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    codeInsee: String,
    estAbonne: Boolean,
    abonnement: Object,
});

const auth = computed(() => usePage().props.auth?.user);
const loading = ref(false);
const showPrefs = ref(false);

const prefs = ref({
    notif_actus: props.abonnement?.notif_actus ?? true,
    notif_evenements: props.abonnement?.notif_evenements ?? true,
    notif_forum: props.abonnement?.notif_forum ?? false,
    notif_email: props.abonnement?.notif_email ?? false,
});

watch(() => props.abonnement, (v) => {
    if (v) {
        prefs.value = {
            notif_actus: v.notif_actus ?? true,
            notif_evenements: v.notif_evenements ?? true,
            notif_forum: v.notif_forum ?? false,
            notif_email: v.notif_email ?? false,
        };
    }
}, { deep: true });

const subscribe = () => {
    if (!auth.value) { window.location.href = route('login'); return; }
    loading.value = true;
    router.post(route('commune.abonner', props.codeInsee), prefs.value, {
        preserveScroll: true,
        onFinish: () => loading.value = false,
    });
};

const unsubscribe = () => {
    loading.value = true;
    router.delete(route('commune.desabonner', props.codeInsee), {
        preserveScroll: true,
        onFinish: () => { loading.value = false; showPrefs.value = false; },
    });
};

const savePrefs = () => {
    loading.value = true;
    router.put(route('commune.abonnement.preferences', props.codeInsee), prefs.value, {
        preserveScroll: true,
        onFinish: () => loading.value = false,
    });
};

const toggleLabels = [
    { key: 'notif_actus', label: 'Actualites', desc: 'Nouveaux articles publies' },
    { key: 'notif_evenements', label: 'Evenements', desc: 'Nouveaux evenements a venir' },
    { key: 'notif_forum', label: 'Forum', desc: 'Nouveaux sujets du forum communal' },
    { key: 'notif_email', label: 'Digest email', desc: 'Recapitulatif periodique par email' },
];
</script>

<template>
    <div>
        <div v-if="!estAbonne" class="flex items-center gap-2">
            <button
                @click="subscribe"
                :disabled="loading"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 shadow-sm transition-colors"
            >
                <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                Suivre cette commune
            </button>
        </div>

        <div v-else>
            <div class="flex items-center gap-2">
                <button
                    @click="showPrefs = !showPrefs"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/><path d="M10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg>
                    Abonne
                    <svg class="w-3 h-3 transition-transform" :class="showPrefs ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            <div v-if="showPrefs" class="mt-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-lg">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Preferences de notifications</h4>

                <div class="space-y-3">
                    <label
                        v-for="t in toggleLabels"
                        :key="t.key"
                        class="flex items-center justify-between cursor-pointer group"
                    >
                        <div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ t.label }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ t.desc }}</div>
                        </div>
                        <button
                            type="button"
                            @click="prefs[t.key] = !prefs[t.key]; savePrefs()"
                            :class="prefs[t.key] ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-600'"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full transition-colors duration-200 ease-in-out"
                        >
                            <span
                                :class="prefs[t.key] ? 'translate-x-4' : 'translate-x-0'"
                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"
                            />
                        </button>
                    </label>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button
                        @click="unsubscribe"
                        :disabled="loading"
                        class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium"
                    >
                        Se desabonner
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
