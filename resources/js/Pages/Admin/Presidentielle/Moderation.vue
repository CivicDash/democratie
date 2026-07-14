<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    files: Object,
    propositions_en_attente: Number,
    referentiels: Array,
    integrite: Object,
});

import { router } from '@inertiajs/vue3';
function agirReferentiel(d, action) {
    router.post(route('admin.presidentielle.moderation.action'), { type: 'programme_document', id: d.id, action }, { preserveScroll: true });
}

const statuts = ['detecte', 'en_review', 'a_completer', 'valide'];

function total(file) {
    return Object.values(file || {}).reduce((a, b) => a + Number(b), 0);
}
</script>

<template>
    <Head title="Modération présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Modération — Présidentielle 2027</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-6">
            <!-- Files par statut -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="(file, nom) in files" :key="nom" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold capitalize mb-3">{{ nom }} <span class="text-gray-400">({{ total(file) }})</span></h3>
                    <ul class="text-sm space-y-1">
                        <li v-for="s in statuts" :key="s" class="flex justify-between">
                            <span class="text-gray-500">{{ s }}</span>
                            <span class="font-mono">{{ file[s] ?? 0 }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- File d'ingestion -->
            <div class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-4">
                <strong>{{ propositions_en_attente }}</strong> proposition(s) d'ingestion en attente de validation.
            </div>

            <!-- Référentiels de programme (plan §11.5) -->
            <div v-if="referentiels?.length" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold mb-3">Référentiels de programme officiel</h3>
                <div v-for="d in referentiels" :key="d.id" class="flex items-center justify-between gap-3 text-sm py-1.5 border-t border-gray-100 dark:border-gray-800 first:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ d.titre }}</p>
                        <p class="text-xs text-gray-500">{{ d.candidat }} · {{ d.nb_items }} entrées · <a :href="d.url" target="_blank" class="text-blue-600 hover:underline">source ↗</a></p>
                    </div>
                    <div class="whitespace-nowrap">
                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs mr-2">{{ d.statut_validation }}{{ d.affiche_publiquement ? ' · publié' : '' }}</span>
                        <button v-if="d.statut_validation !== 'valide'" @click="agirReferentiel(d, 'valider')" class="px-2 py-1 text-xs rounded bg-blue-600 text-white">Valider</button>
                        <button v-if="d.statut_validation === 'valide' && !d.affiche_publiquement" @click="agirReferentiel(d, 'publier')" class="px-2 py-1 text-xs rounded bg-green-600 text-white ml-1">Publier</button>
                        <button v-if="d.affiche_publiquement" @click="agirReferentiel(d, 'depublier')" class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-700 ml-1">Dépublier</button>
                    </div>
                </div>
            </div>

            <!-- Intégrité -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold mb-3">Intégrité éditoriale</h3>
                <p v-if="!integrite.violations.length" class="text-green-600 text-sm">
                    ✓ Aucune violation bloquante — export autorisé.
                </p>
                <ul v-else class="text-sm text-red-600 space-y-1 mb-3">
                    <li v-for="(v, i) in integrite.violations" :key="i">✗ {{ v.message }}</li>
                </ul>
                <ul v-if="integrite.alertes.length" class="text-sm text-amber-600 space-y-1">
                    <li v-for="(a, i) in integrite.alertes" :key="i">⚠ {{ a.message }}</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
