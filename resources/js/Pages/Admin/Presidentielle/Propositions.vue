<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    propositions: Object, // paginator
    documents: Array,
    statut: String,
});

function supprimerDocument(d) {
    const avert = d.nb_rattachees > 0
        ? `⚠ ${d.nb_rattachees} proposition(s) déjà rattachée(s) à des mesures — la suppression sera refusée tant que ces mesures existent.\n\n`
        : '';
    if (!confirm(`${avert}Supprimer la prise de parole « ${d.titre} » et ses ${d.nb_propositions} proposition(s) ?`)) return;
    router.delete(route('admin.presidentielle.documents.destroy', d.id), { preserveScroll: true });
}

const fichierJson = ref(null);
const fichierSource = ref(null);
const envoiEnCours = ref(false);

function importer() {
    if (!fichierJson.value) return;
    envoiEnCours.value = true;
    router.post(route('admin.presidentielle.propositions.import'),
        { fichier: fichierJson.value, source: fichierSource.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => { envoiEnCours.value = false; fichierJson.value = null; fichierSource.value = null; },
        });
}

const flash = () => usePage().props.flash ?? {};
const erreurs = () => usePage().props.errors ?? {};

const filtres = ['detecte', 'validee', 'rattachee', 'rejetee', 'tous'];

function filtrer(s) {
    router.get(route('admin.presidentielle.propositions'), { statut: s }, { preserveState: true, replace: true });
}

const enTraitement = ref(new Set());

function agir(proposition, action) {
    if (action === 'rejeter' && !confirm('Rejeter cette proposition ?')) return;
    if (enTraitement.value.has(proposition.id)) return; // anti double-clic
    enTraitement.value.add(proposition.id);
    router.post(route('admin.presidentielle.propositions.action'), { id: proposition.id, action }, {
        preserveScroll: true,
        onFinish: () => enTraitement.value.delete(proposition.id),
    });
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
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">File d'ingestion — propositions</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-4">
            <!-- Chargement d'un discours (contrat JSON §11) sans terminal -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">📥 Charger un discours (JSON de propositions)</summary>
                <p class="text-xs text-gray-500 mt-2">
                    Fichier JSON au contrat d'ingestion v1.0 (généré via Claude à partir de la transcription).
                    Joindre la <strong>transcription source</strong> (txt/srt) pour la vérification automatique des
                    citations verbatim — sans elle, les citations devront être vérifiées à la main.
                    Tout entre en file de modération (<code>detecte</code>).
                </p>
                <form @submit.prevent="importer" class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                    <label class="block">
                        <span class="text-xs font-medium">JSON de propositions *</span>
                        <input type="file" accept=".json,application/json" required
                               @change="fichierJson = $event.target.files[0]"
                               class="mt-1 block w-full text-xs" />
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium">Transcription source (recommandé)</span>
                        <input type="file" accept=".txt,.srt,.vtt,text/plain"
                               @change="fichierSource = $event.target.files[0]"
                               class="mt-1 block w-full text-xs" />
                    </label>
                    <div class="md:col-span-2 flex items-center justify-between gap-3">
                        <p v-if="erreurs().fichier" class="text-xs text-red-600">{{ erreurs().fichier }}</p>
                        <p v-else-if="flash().success" class="text-xs text-green-600">{{ flash().success }}</p>
                        <button type="submit" :disabled="envoiEnCours || !fichierJson"
                                class="ml-auto px-4 py-2 rounded bg-blue-600 text-white text-sm disabled:opacity-50">
                            {{ envoiEnCours ? 'Import…' : 'Importer' }}
                        </button>
                    </div>
                </form>
            </details>

            <!-- Prises de parole importées (suppression d'un import erroné) -->
            <details v-if="documents?.length" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">🗑️ Prises de parole importées ({{ documents.length }})</summary>
                <p class="text-xs text-gray-500 mt-2">Supprimer un document retire toutes ses propositions. Refusé si des propositions ont déjà été rattachées à des mesures (traiter ces mesures d’abord).</p>
                <div v-for="d in documents" :key="d.id" class="flex items-center justify-between gap-3 text-sm py-1.5 border-t border-gray-100 dark:border-gray-800">
                    <div class="min-w-0">
                        <p class="truncate">{{ d.titre }}</p>
                        <p class="text-xs text-gray-500">{{ d.type }} · {{ d.nb_propositions }} proposition(s)<span v-if="d.nb_rattachees"> · {{ d.nb_rattachees }} rattachée(s)</span></p>
                    </div>
                    <button @click="supprimerDocument(d)" class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 whitespace-nowrap">Supprimer</button>
                </div>
                <p v-if="erreurs().document" class="text-xs text-red-600 mt-2">{{ erreurs().document }}</p>
            </details>

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
                                    <button @click="agir(p, 'valider')" :disabled="enTraitement.has(p.id)"
                                        class="px-2 py-1 text-xs rounded bg-green-600 text-white disabled:opacity-50">Valider → mesure</button>
                                    <button @click="agir(p, 'rejeter')" :disabled="enTraitement.has(p.id)"
                                        class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 ml-1 disabled:opacity-50">Rejeter</button>
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
