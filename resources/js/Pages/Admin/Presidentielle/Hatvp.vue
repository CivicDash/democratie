<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    candidats: Array,
    statuts: Array,
});

const q = ref('');
const resultats = ref([]);
const chercheEnCours = ref(false);
const preview = ref(null);
const previewUuid = ref(null);
const cibleCandidat = reactive({}); // { [uuid]: candidat_id }

let debounce = null;
function rechercher() {
    clearTimeout(debounce);
    debounce = setTimeout(async () => {
        if (q.value.trim().length < 2) { resultats.value = []; return; }
        chercheEnCours.value = true;
        try {
            const res = await fetch(route('admin.presidentielle.hatvp.search') + '?q=' + encodeURIComponent(q.value), {
                headers: { Accept: 'application/json' },
            });
            resultats.value = (await res.json()).resultats ?? [];
        } finally { chercheEnCours.value = false; }
    }, 300);
}

async function apercu(uuid) {
    previewUuid.value = uuid;
    preview.value = null;
    const res = await fetch(route('admin.presidentielle.hatvp.preview', uuid), { headers: { Accept: 'application/json' } });
    preview.value = (await res.json()).summary;
}

function rattacher(uuid) {
    const candidatId = cibleCandidat[uuid];
    if (!candidatId) return;
    router.post(route('admin.presidentielle.hatvp.rattacher'), { candidat_id: candidatId, declaration_uuid: uuid }, { preserveScroll: true });
}
function detacher(candidatId) {
    if (!confirm('Détacher la/les déclaration(s) HATVP de ce candidat ?')) return;
    router.post(route('admin.presidentielle.hatvp.detacher'), { candidat_id: candidatId }, { preserveScroll: true });
}
function changerStatut(candidatId, statut) {
    router.post(route('admin.presidentielle.hatvp.statut'), { candidat_id: candidatId, statut }, { preserveScroll: true });
}

function fmtMontant(v) {
    if (!v) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v);
}
// Graphe simple des revenus par année (barres normalisées).
const revenusBars = computed(() => {
    const rpa = preview.value?.revenus_par_annee ?? {};
    const rows = Object.entries(rpa).map(([annee, v]) => ({ annee, total: Number(v?.total ?? v ?? 0) }))
        .sort((a, b) => a.annee.localeCompare(b.annee));
    const max = Math.max(1, ...rows.map((r) => r.total));
    return rows.map((r) => ({ ...r, pct: Math.round((r.total / max) * 100) }));
});
</script>

<template>
    <Head title="HATVP — présidentielle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Déclarations HATVP</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-6">
            <p class="text-xs text-gray-500">
                Recherchez une déclaration HATVP par nom, prévisualisez-la, puis rattachez-la à un candidat (lien validé).
                Intérêts (DIA) uniquement — le patrimoine n'est pas repris. Rien n'est publié ici.
            </p>

            <!-- Recherche + résultats -->
            <div class="grid md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <label class="block text-sm font-medium mb-1">Rechercher une déclaration (nom)</label>
                    <input v-model="q" @input="rechercher" type="search" placeholder="ex. Bertrand, Le Pen…"
                        class="w-full rounded border-gray-300 dark:bg-gray-800 text-sm" />
                    <p v-if="chercheEnCours" class="text-xs text-gray-400 mt-1">Recherche…</p>

                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-gray-800 max-h-96 overflow-y-auto">
                        <li v-for="r in resultats" :key="r.uuid" class="py-2 text-sm">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <span><strong>{{ r.prenom }} {{ r.nom }}</strong>
                                    <span class="text-xs text-gray-400">· {{ r.type }} · {{ r.date_depot }} · {{ r.type_mandat }}</span>
                                    <span v-if="r.deja_liee_a" class="ml-1 text-[10px] px-1 rounded bg-green-100 text-green-700">déjà rattachée</span>
                                </span>
                                <button @click="apercu(r.uuid)" class="px-2 py-1 text-xs rounded border border-gray-300">Aperçu</button>
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <select v-model="cibleCandidat[r.uuid]" class="rounded border-gray-300 dark:bg-gray-800 text-xs flex-1">
                                    <option :value="undefined">— rattacher à… —</option>
                                    <option v-for="c in candidats" :key="c.id" :value="c.id">{{ c.nom }}</option>
                                </select>
                                <button @click="rattacher(r.uuid)" :disabled="!cibleCandidat[r.uuid]"
                                    class="px-2 py-1 text-xs rounded bg-blue-600 text-white disabled:opacity-50">Rattacher</button>
                            </div>
                        </li>
                        <li v-if="q.length >= 2 && !resultats.length && !chercheEnCours" class="py-2 text-xs text-gray-400">Aucune déclaration.</li>
                    </ul>
                </div>

                <!-- Aperçu façon CivicDash -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold text-sm mb-2">Aperçu (intérêts)</h3>
                    <p v-if="!previewUuid" class="text-xs text-gray-400">Cliquez « Aperçu » sur une déclaration.</p>
                    <p v-else-if="!preview" class="text-xs text-gray-400">Chargement…</p>
                    <div v-else class="text-sm space-y-3">
                        <p class="text-xs text-gray-500">{{ preview.declaration_type }} · déposée le {{ preview.declaration_date }}</p>
                        <div class="flex gap-3 text-xs">
                            <span>{{ preview.nombre_mandats }} mandat(s)</span>
                            <span>{{ preview.nombre_emplois }} activité(s)</span>
                            <span>{{ preview.nombre_collaborateurs }} collaborateur(s)</span>
                        </div>

                        <div v-if="revenusBars.length">
                            <p class="text-xs font-medium mb-1">Revenus déclarés par année</p>
                            <div class="space-y-1">
                                <div v-for="b in revenusBars" :key="b.annee" class="flex items-center gap-2">
                                    <span class="w-12 text-xs text-gray-500">{{ b.annee }}</span>
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded h-4">
                                        <div class="h-4 rounded bg-blue-500" :style="{ width: b.pct + '%' }"></div>
                                    </div>
                                    <span class="w-24 text-right text-xs">{{ fmtMontant(b.total) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="preview.participations_dirigeantes?.length">
                            <p class="text-xs font-medium">Participations dirigeantes</p>
                            <ul class="text-xs list-disc pl-4">
                                <li v-for="(p, i) in preview.participations_dirigeantes" :key="i">
                                    {{ p.societe }}<span v-if="p.activite"> — {{ p.activite }}</span>
                                    <span v-if="p.total_remunerations" class="text-gray-400"> ({{ fmtMontant(p.total_remunerations) }})</span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="preview.activites_professionnelles?.length">
                            <p class="text-xs font-medium">Activités professionnelles</p>
                            <ul class="text-xs list-disc pl-4">
                                <li v-for="(a, i) in preview.activites_professionnelles" :key="i">
                                    {{ a.description }}<span v-if="a.employeur"> — {{ a.employeur }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="preview.fonctions_benevoles?.length">
                            <p class="text-xs font-medium">Fonctions bénévoles</p>
                            <ul class="text-xs list-disc pl-4">
                                <li v-for="(f, i) in preview.fonctions_benevoles" :key="i">{{ f.description }}<span v-if="f.organisme"> — {{ f.organisme }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des candidats + rattachement / statut -->
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3">Candidat</th><th class="p-3">Déclaration rattachée</th>
                            <th class="p-3">Statut d'affichage</th><th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in candidats" :key="c.id" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="p-3 whitespace-nowrap">{{ c.nom }}</td>
                            <td class="p-3">
                                <span v-if="c.declaration_liee" class="text-green-600">✓ {{ c.declaration_liee.type }} · {{ c.declaration_liee.date_depot }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="p-3">
                                <select :value="c.hatvp_statut" @change="changerStatut(c.id, $event.target.value)"
                                    class="rounded border-gray-300 dark:bg-gray-800 text-xs">
                                    <option v-for="s in statuts" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </td>
                            <td class="p-3 text-right">
                                <button v-if="c.declaration_liee" @click="detacher(c.id)" class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-700">Détacher</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
