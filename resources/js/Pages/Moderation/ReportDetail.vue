<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';
import { useConfirm } from '@/composables/useConfirm';

/**
 * Détail d'un signalement.
 *
 * Cet écran manquait : on pouvait lister les signalements sans jamais en ouvrir un,
 * ce qui rendait inatteignables les trois actions du contrôleur — prendre en charge,
 * résoudre, rejeter.
 */
const props = defineProps({
    report: Object,
    can: { type: Object, default: () => ({}) },
});

const { confirmWarning } = useConfirm();

const RAISONS = {
    spam: 'Spam ou publicité',
    harassment: 'Harcèlement',
    hate_speech: 'Discours haineux',
    violence: 'Incitation à la violence',
    misinformation: 'Désinformation',
    inappropriate: 'Contenu inapproprié',
    off_topic: 'Hors sujet',
    impersonation: "Usurpation d'identité",
    copyright: "Violation de droits d'auteur",
    personal_data: 'Données personnelles exposées',
    other: 'Autre',
};

const STATUTS = {
    pending: { label: 'En attente', classe: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' },
    reviewing: { label: 'En cours de traitement', classe: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' },
    resolved: { label: 'Résolu', classe: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' },
    dismissed: { label: 'Rejeté', classe: 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
};

const statut = computed(() => STATUTS[props.report.status] ?? { label: props.report.status, classe: 'bg-gray-100 text-gray-700' });
const raison = computed(() => RAISONS[props.report.reason] ?? props.report.reason);
const estClos = computed(() => ['resolved', 'dismissed'].includes(props.report.status));

const resolution = useForm({ notes: '' });
const rejet = useForm({ reason: '' });

const prendreEnCharge = () => {
    useForm({}).post(route('moderation.reports.assign', props.report.id), { preserveScroll: true });
};

const resoudre = async () => {
    const ok = await confirmWarning(
        'Le signalement sera clos et la personne qui l\'a émis en sera informée.',
        'Résoudre ce signalement ?',
        { confirmLabel: 'Résoudre' },
    );
    if (ok) {
        resolution.post(route('moderation.reports.resolve', props.report.id), { preserveScroll: true });
    }
};

const rejeter = async () => {
    const ok = await confirmWarning(
        'Le signalement sera classé sans suite. Motivez la décision : elle sera lisible dans l\'historique.',
        'Rejeter ce signalement ?',
        { confirmLabel: 'Rejeter' },
    );
    if (ok) {
        rejet.reason = resolution.notes;   // même zone de saisie, deux destinataires côté serveur
        rejet.post(route('moderation.reports.reject', props.report.id), { preserveScroll: true });
    }
};

const formatDate = (iso) => (iso
    ? new Date(iso).toLocaleString('fr-FR', { dateStyle: 'long', timeStyle: 'short' })
    : '—');
</script>

<template>
    <Head :title="`Signalement #${report.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Signalement #{{ report.id }}
                </h2>
                <Link :href="route('moderation.reports.index')"
                      class="px-3 py-2 min-h-[36px] rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    ← Tous les signalements
                </Link>
            </div>
        </template>

        <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ raison }}</h3>
                    <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', statut.classe]">
                        {{ statut.label }}
                    </span>
                </div>

                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Signalé par</dt>
                        <dd class="text-gray-900 dark:text-white">{{ report.reporter?.name ?? 'Compte supprimé' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Reçu le</dt>
                        <dd class="text-gray-900 dark:text-white">{{ formatDate(report.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Contenu visé</dt>
                        <dd class="text-gray-900 dark:text-white">
                            {{ report.reportable_type?.split('\\').pop() ?? 'Inconnu' }} #{{ report.reportable_id }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pris en charge par</dt>
                        <dd class="text-gray-900 dark:text-white">{{ report.moderator?.name ?? 'personne' }}</dd>
                    </div>
                </dl>

                <div v-if="report.description" class="mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Ce que dit le signalement</p>
                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ report.description }}</p>
                </div>

                <div v-if="report.moderator_notes" class="mt-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                    <p class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-1">Décision</p>
                    <p class="text-blue-900 dark:text-blue-100 whitespace-pre-line">{{ report.moderator_notes }}</p>
                    <p v-if="report.resolved_at" class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                        {{ formatDate(report.resolved_at) }}
                    </p>
                </div>
            </div>

            <div v-if="!estClos" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Traiter</h3>

                <button v-if="can.assign && !report.moderator_id"
                        type="button"
                        @click="prendreEnCharge"
                        class="px-4 py-2 min-h-[36px] rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Prendre en charge
                </button>

                <template v-if="can.resolve">
                    <div>
                        <label for="notes-resolution" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Note de décision
                        </label>
                        <textarea id="notes-resolution" v-model="resolution.notes" rows="3"
                                  class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Elle reste attachée au signalement : c'est ce qu'on relira si la décision est contestée.
                        </p>
                    </div>

                    <FormErrors :errors="resolution.errors" />

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="resoudre" :disabled="resolution.processing"
                                class="px-4 py-2 min-h-[36px] rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 text-sm">
                            Résoudre
                        </button>
                        <button type="button" @click="rejeter" :disabled="rejet.processing"
                                class="px-4 py-2 min-h-[36px] rounded-lg border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20 text-sm">
                            Rejeter
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
