<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    bans: Object,
    filters: Object,
});

const status = ref(props.filters?.status || 'active');
const scope = ref(props.filters?.scope || '');
const ip = ref(props.filters?.ip || '');

const applyFilters = () => {
    router.get(route('admin.ip-bans.index'), {
        status: status.value || undefined,
        scope: scope.value || undefined,
        ip: ip.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    status.value = 'active';
    scope.value = '';
    ip.value = '';
    router.get(route('admin.ip-bans.index'));
};

const unban = (ban) => {
    const reason = window.prompt(`Motif de déblocage pour ${ban.ip} (optionnel) :`);
    router.post(route('admin.ip-bans.unban', ban.id), { reason: reason || null });
};

const breadcrumbs = [
    { label: 'Admin', url: route('admin.dashboard') },
    { label: 'Bans IP', url: null },
];

const statusLabel = (ban) => {
    if (ban.unbanned_at) return 'Débloqué';
    if (ban.expires_at && new Date(ban.expires_at) <= new Date()) return 'Expiré';
    return 'Actif';
};

const statusClass = (ban) => {
    const label = statusLabel(ban);
    return {
        'Actif': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'Expiré': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'Débloqué': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    }[label];
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Breadcrumb :items="breadcrumbs" class="mb-6" />

                <div class="bg-gradient-to-r from-slate-700 via-gray-700 to-slate-800 text-white rounded-2xl p-6 mb-6">
                    <h1 class="text-2xl font-bold">Bans IP</h1>
                    <p class="text-slate-200">Suivi des IPs bannies et déblocage manuel</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <input
                            v-model="ip"
                            type="text"
                            placeholder="Filtrer par IP..."
                            class="w-full md:w-1/3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            @keyup.enter="applyFilters"
                        />
                        <select
                            v-model="scope"
                            class="w-full md:w-1/3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Tous scopes</option>
                            <option value="api">api</option>
                            <option value="login">login</option>
                        </select>
                        <select
                            v-model="status"
                            class="w-full md:w-1/3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="active">Actifs</option>
                            <option value="expired">Expirés / Débloqués</option>
                            <option value="all">Tous</option>
                        </select>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button
                            @click="applyFilters"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            Filtrer
                        </button>
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                        >
                            Réinitialiser
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div v-if="bans.data.length" class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="ban in bans.data" :key="ban.id" class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ ban.ip }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Scope: {{ ban.scope }} · Motif: {{ ban.reason || 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Expire: {{ ban.expires_at || '—' }} · Créé: {{ ban.created_at }}
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusClass(ban)">
                                    {{ statusLabel(ban) }}
                                </span>
                                <button
                                    v-if="!ban.unbanned_at && (!ban.expires_at || new Date(ban.expires_at) > new Date())"
                                    @click="unban(ban)"
                                    class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"
                                >
                                    Débloquer
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                        Aucun ban IP.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
