<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    document: Object,
    can: { type: Object, default: () => ({}) },
});

const STATUTS = {
    pending: { label: 'En attente de vérification', classe: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' },
    verified: { label: 'Vérifié', classe: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' },
    rejected: { label: 'Rejeté', classe: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' },
};

const statut = computed(() => STATUTS[props.document.status]
    ?? { label: props.document.status, classe: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' });

const taille = computed(() => {
    const o = Number(props.document.size ?? 0);
    if (!o) return '—';
    const unites = ['o', 'ko', 'Mo', 'Go'];
    const i = Math.min(Math.floor(Math.log(o) / Math.log(1024)), unites.length - 1);
    return `${(o / 1024 ** i).toFixed(i === 0 ? 0 : 1)} ${unites[i]}`;
});

const formatDate = (iso) => (iso
    ? new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    : '—');
</script>

<template>
    <Head :title="document.title || document.filename" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
            <nav aria-label="Fil d'Ariane" class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('documents.index')" class="hover:text-blue-600">Documents</Link>
                <span aria-hidden="true">→</span>
                <span class="text-gray-900 dark:text-white truncate">{{ document.title || document.filename }}</span>
            </nav>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ document.title || document.filename }}
                    </h1>
                    <span :class="['px-2.5 py-1 rounded-full text-xs font-medium shrink-0', statut.classe]">
                        {{ statut.label }}
                    </span>
                </div>

                <p v-if="document.description" class="mt-3 text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ document.description }}
                </p>

                <dl class="mt-5 grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fichier</dt>
                        <dd class="text-gray-900 dark:text-white break-all">{{ document.filename }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                        <dd class="text-gray-900 dark:text-white">{{ document.mime_type || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Taille</dt>
                        <dd class="text-gray-900 dark:text-white">{{ taille }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Déposé le</dt>
                        <dd class="text-gray-900 dark:text-white">{{ formatDate(document.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Déposé par</dt>
                        <dd class="text-gray-900 dark:text-white">{{ document.uploader?.name ?? 'Inconnu' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">Empreinte SHA-256</dt>
                        <dd class="text-xs font-mono text-gray-700 dark:text-gray-300 break-all">
                            {{ document.hash || '—' }}
                        </dd>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Elle permet à quiconque de vérifier que le fichier téléchargé est bien
                            celui qui a été déposé, octet pour octet.
                        </p>
                    </div>
                </dl>

                <div class="mt-6">
                    <a :href="route('documents.download', document.id)"
                       class="inline-block px-4 py-2 min-h-[36px] rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm">
                        Télécharger
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Vérifications
                </h2>

                <ul v-if="document.verifications?.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="v in document.verifications" :key="v.id" class="py-3 first:pt-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ v.verifier?.name ?? 'Vérificateur inconnu' }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(v.created_at) }}</span>
                        </div>
                        <p class="text-sm mt-1"
                           :class="v.status === 'verified' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                            {{ v.status === 'verified' ? 'Authentifié' : 'Rejeté' }}
                        </p>
                        <p v-if="v.notes" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ v.notes }}</p>
                    </li>
                </ul>

                <div v-else class="text-center py-6">
                    <p class="text-gray-600 dark:text-gray-400">Ce document n'a pas encore été vérifié.</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                        Un document non vérifié reste consultable, mais ne peut pas être invoqué
                        comme une pièce authentifiée.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
