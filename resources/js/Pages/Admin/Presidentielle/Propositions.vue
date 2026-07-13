<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    propositions: Object, // paginator
    statut: String,
});

const filtres = ['detecte', 'validee', 'rattachee', 'rejetee', 'tous'];

function filtrer(s) {
    router.get(route('admin.presidentielle.propositions'), { statut: s }, { preserveState: true, replace: true });
}

function agir(proposition, action) {
    if (action === 'rejeter' && !confirm('Rejeter cette proposition ?')) return;
    router.post(route('admin.presidentielle.propositions.action'), { id: proposition.id, action }, { preserveScroll: true });
}

function nomCandidat(p) {
    return p.candidat?.personne_politique
        ? `${p.candidat.personne_politique.prenom} ${p.candidat.personne_politique.nom}`
        : (p.candidat_slug ?? '—');
}
</script>

<template>
    <Head title="File d'ingestion — présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">File d'ingestion — propositions</h2>
                <nav class="text-sm space-x-3">
                    <Link :href="route('admin.presidentielle.moderation')" class="text-blue-600 hover:underline">Tableau de bord</Link>
                    <Link :href="route('admin.presidentielle.mesures')" class="text-blue-600 hover:underline">Mesures</Link>
                </nav>
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-4">
            <div class="flex gap-2 flex-wrap">
                <button v-for="s in filtres" :key="s" @click="filtrer(s)"
                    class="px-3 py-1 rounded-full text-sm border"
                    :class="statut === s ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">
                    {{ s }}
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3">Candidat</th>
                            <th class="p-3">Thème</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Citation verbatim</th>
                            <th class="p-3">Conf.</th>
                            <th class="p-3">Vérif.</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in propositions.data" :key="p.id" class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3 whitespace-nowrap">{{ nomCandidat(p) }}</td>
                            <td class="p-3 whitespace-nowrap">{{ p.theme?.nom ?? p.theme_slug ?? '—' }}</td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs">{{ p.type }}</span></td>
                            <td class="p-3 max-w-md">
                                <p class="italic">« {{ p.citation_verbatim }} »</p>
                                <p class="text-xs text-gray-400 mt-1">{{ p.resume_propose }} <span v-if="p.timestamp_ou_paragraphe">· {{ p.timestamp_ou_paragraphe }}</span></p>
                            </td>
                            <td class="p-3">{{ p.confiance }}</td>
                            <td class="p-3">
                                <span :class="p.verbatim_verifie ? 'text-green-600' : 'text-amber-600'">
                                    {{ p.verbatim_verifie ? '✓' : '⚠' }}
                                </span>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <template v-if="p.statut === 'detecte'">
                                    <button @click="agir(p, 'valider')" class="px-2 py-1 text-xs rounded bg-green-600 text-white">Valider → mesure</button>
                                    <button @click="agir(p, 'rejeter')" class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 ml-1">Rejeter</button>
                                </template>
                                <span v-else class="text-xs text-gray-400">{{ p.statut }}</span>
                            </td>
                        </tr>
                        <tr v-if="!propositions.data.length">
                            <td colspan="7" class="p-6 text-center text-gray-400">Aucune proposition.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="propositions.links" class="flex flex-wrap gap-1">
                <Link v-for="(l, i) in propositions.links" :key="i" :href="l.url ?? ''"
                    v-html="l.label" class="px-3 py-1 text-sm rounded border"
                    :class="[l.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300', !l.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
