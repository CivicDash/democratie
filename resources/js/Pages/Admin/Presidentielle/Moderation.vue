<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    files: Object,
    propositions_en_attente: Number,
    integrite: Object,
});

const statuts = ['detecte', 'en_review', 'a_completer', 'valide'];

function total(file) {
    return Object.values(file || {}).reduce((a, b) => a + Number(b), 0);
}
</script>

<template>
    <Head title="Modération présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Modération — Présidentielle 2027</h2>
                <nav class="text-sm space-x-3">
                    <Link :href="route('admin.presidentielle.propositions')" class="text-blue-600 hover:underline">File d'ingestion</Link>
                    <Link :href="route('admin.presidentielle.mesures')" class="text-blue-600 hover:underline">Mesures</Link>
                </nav>
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
