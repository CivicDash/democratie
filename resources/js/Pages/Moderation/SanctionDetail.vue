<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps({
    sanction: Object,
    can: { type: Object, default: () => ({}) },
});

const { confirmWarning } = useConfirm();

const TYPES = {
    warning: 'Avertissement',
    mute: 'Mise en sourdine',
    ban: 'Bannissement de publication',
};

const typeLabel = computed(() => TYPES[props.sanction.type] ?? props.sanction.type);

const formatDate = (iso) => (iso
    ? new Date(iso).toLocaleString('fr-FR', { dateStyle: 'long', timeStyle: 'short' })
    : '—');

const lever = async () => {
    const ok = await confirmWarning(
        `La sanction de ${props.sanction.user?.name ?? 'cette personne'} sera levée immédiatement.`,
        'Lever cette sanction ?',
        { confirmLabel: 'Lever' },
    );
    if (ok) {
        router.delete(route('moderation.sanctions.revoke', props.sanction.id));
    }
};
</script>

<template>
    <Head :title="`Sanction #${sanction.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Sanction #{{ sanction.id }}
                </h2>
                <Link :href="route('moderation.sanctions.index')"
                      class="px-3 py-2 min-h-[36px] rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    ← Toutes les sanctions
                </Link>
            </div>
        </template>

        <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ typeLabel }}</h3>
                    <span v-if="sanction.is_active"
                          class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                        En cours
                    </span>
                    <span v-else
                          class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        Échue
                    </span>
                </div>

                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Personne</dt>
                        <dd class="text-gray-900 dark:text-white">{{ sanction.user?.name ?? 'Compte supprimé' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Prononcée par</dt>
                        <dd class="text-gray-900 dark:text-white">{{ sanction.moderator?.name ?? 'Système' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Début</dt>
                        <dd class="text-gray-900 dark:text-white">{{ formatDate(sanction.starts_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Échéance</dt>
                        <dd class="text-gray-900 dark:text-white">
                            {{ sanction.expires_at ? formatDate(sanction.expires_at) : 'aucune' }}
                        </dd>
                    </div>
                    <div v-if="sanction.revoked_at">
                        <dt class="text-gray-500 dark:text-gray-400">Levée le</dt>
                        <dd class="text-gray-900 dark:text-white">{{ formatDate(sanction.revoked_at) }}</dd>
                    </div>
                    <div v-if="sanction.report_id">
                        <dt class="text-gray-500 dark:text-gray-400">Signalement à l'origine</dt>
                        <dd>
                            <Link :href="route('moderation.reports.show', sanction.report_id)"
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                #{{ sanction.report_id }}
                            </Link>
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Motif</p>
                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ sanction.reason }}</p>
                </div>
            </div>

            <div v-if="can.revoke && sanction.is_active"
                 class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <button type="button" @click="lever"
                        class="px-4 py-2 min-h-[36px] rounded-lg bg-green-600 text-white hover:bg-green-700 text-sm">
                    Lever la sanction
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
