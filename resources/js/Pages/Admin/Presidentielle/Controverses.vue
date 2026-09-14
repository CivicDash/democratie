<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import { useConfirm } from '@/composables/useConfirm';
import { messagePublication } from '@/composables/useModerationAction';

const props = defineProps({
    controverses: Object,        // paginator
    liens_a_resoudre: Array,
    themes: Array,
    statut: String,
});

const filtres = ['tous', 'detecte', 'en_review', 'a_completer', 'valide'];
function filtrer(s) {
    router.get(route('admin.presidentielle.controverses'), { statut: s }, { preserveState: true, replace: true });
}

const nouvelle = reactive({ titre: '', theme_id: '', note_methodologique: '' });
function creer() {
    router.post(route('admin.presidentielle.controverses.store'), nouvelle, {
        preserveScroll: true,
        onSuccess: () => { nouvelle.titre = ''; nouvelle.theme_id = ''; nouvelle.note_methodologique = ''; },
    });
}

const importForm = useForm({ fichier: null });
function importer() {
    importForm.post(route('admin.presidentielle.arguments.import'), { preserveScroll: true, forceFormData: true });
}

// Action en lot sur l'argumentaire d'une controverse. Chaque objet reste traité et tracé
// individuellement côté serveur ; les échecs sont rendus sans interrompre le reste du lot.
const { confirm } = useConfirm();

async function agirLot(type, ids, action, libelle) {
    if (!ids?.length) return;

    const message = action === 'publier'
        ? `${ids.length} élément(s) deviendront publiquement lisibles sur objectif2027.fr. `
          + 'Publier une mesure avant ses faits et ses liaisons fait échouer le contrôle '
          + "d'intégrité, et l'export refuse alors de régénérer le site entier."
        : `${ids.length} élément(s) seront traités, chacun individuellement et tracé au journal. `
          + 'Les échecs éventuels seront listés sans interrompre le reste du lot.';

    const ok = await confirm({
        type: action === 'publier' ? 'warning' : 'info',
        title: `${libelle} ?`,
        message,
        confirmLabel: libelle,
    });
    if (!ok) return;

    router.post(route('admin.presidentielle.moderation.action-lot'),
        { type, ids, action }, { preserveScroll: true });
}
const echecsLot = () => usePage().props.flash?.echecs_lot ?? [];

function agir(controverse, action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'controverse', id: controverse.id, action }, { preserveScroll: true });
}

const recherche = reactive({});   // { [lienId]: texte de recherche }

// Mesures du candidat proposé filtrées par le texte saisi (recherche sans ID).
function mesuresFiltrees(lien) {
    const q = (recherche[lien.id] ?? '').trim().toLowerCase();
    const liste = lien.mesures_candidat ?? [];
    if (!q) return liste.slice(0, 8);
    return liste.filter((m) => m.titre.toLowerCase().includes(q)).slice(0, 8);
}

function resoudre(lienId, mesureId) {
    router.post(route('admin.presidentielle.arguments.liens.resolve'),
        { id: lienId, mesure_id: mesureId }, { preserveScroll: true });
}

const erreurs = () => usePage().props.errors ?? {};
</script>

<template>
    <Head title="Controverses — présidentielle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Controverses & argumentaire</h2>
                <PresidentielleNav />

            <div v-if="echecsLot().length" class="mt-4 rounded border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-3">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                    {{ echecsLot().length }} élément(s) refusé(s) lors de la dernière action en lot
                </p>
                <ul class="mt-1 text-xs text-amber-700 dark:text-amber-300 list-disc pl-5">
                    <li v-for="e in echecsLot()" :key="e.id">#{{ e.id }} — {{ e.motif }}</li>
                </ul>
            </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-5">
            <!-- Import d'arguments -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold text-sm">📥 Importer un argumentaire (JSON)</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Contrats §4 : <code>arguments</code> (v1.1) ou <code>arguments_controverse</code> (v1.2). Tout entre en
                    <strong>detecte</strong> ; les liaisons sont auto-appariées aux mesures du candidat (résolution ci-dessous si non trouvé).
                </p>
                <form @submit.prevent="importer" class="mt-3 flex items-center gap-3 flex-wrap">
                    <input type="file" accept="application/json,.json" @input="importForm.fichier = $event.target.files[0]"
                        class="text-sm" />
                    <button type="submit" :disabled="!importForm.fichier || importForm.processing"
                        class="px-4 py-2 rounded bg-blue-600 text-white text-sm disabled:opacity-50">Importer</button>
                    <span v-if="erreurs().fichier" class="text-xs text-red-600">{{ erreurs().fichier }}</span>
                </form>
            </div>

            <!-- Liaisons à résoudre -->
            <div v-if="liens_a_resoudre.length" class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-4">
                <h3 class="font-semibold text-sm text-amber-800 dark:text-amber-200">⚠ Liaisons à résoudre ({{ liens_a_resoudre.length }})</h3>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">L'auto-match n'a pas trouvé de mesure. Renseignez l'ID de la mesure cible.</p>
                <ul class="mt-3 space-y-3 text-sm">
                    <li v-for="l in liens_a_resoudre" :key="l.id" class="rounded-lg border border-amber-200 dark:border-amber-800/40 bg-white/50 dark:bg-black/10 p-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-1.5 py-0.5 rounded text-[11px] bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                {{ l.sens === 'pour' ? 'étaye' : 'contredit' }}
                            </span>
                            <span class="font-medium">{{ l.argument_titre }}</span>
                            <span class="text-xs text-gray-500">proposé : {{ l.candidat_slug_propose }} — « {{ l.mesure_proposee }} »
                                <span v-if="l.detection_confidence != null">({{ Math.round(l.detection_confidence * 100) }}%)</span></span>
                        </div>
                        <div class="mt-2">
                            <input v-model="recherche[l.id]" type="search" placeholder="Rechercher une mesure du candidat…"
                                class="w-full max-w-md rounded border-gray-300 dark:bg-gray-800 text-sm" />
                            <ul v-if="l.mesures_candidat.length" class="mt-1 divide-y divide-gray-100 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden max-w-md">
                                <li v-for="m in mesuresFiltrees(l)" :key="m.id" class="flex items-center justify-between gap-2 px-3 py-1.5 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                    <span class="text-xs">{{ m.titre }}</span>
                                    <button @click="resoudre(l.id, m.id)" class="flex-none px-2 py-1 text-xs rounded bg-blue-600 text-white">Relier</button>
                                </li>
                                <li v-if="!mesuresFiltrees(l).length" class="px-3 py-1.5 text-xs text-gray-400">Aucune mesure ne correspond.</li>
                            </ul>
                            <p v-else class="mt-1 text-xs text-gray-400">Aucune mesure en base pour ce candidat — validez-en une d'abord (file d'ingestion → Mesures).</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Créer une controverse -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">➕ Créer une controverse</summary>
                <form @submit.prevent="creer" class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                    <input v-model="nouvelle.titre" required placeholder="Titre * (ex. Âge de départ à la retraite)" class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2" />
                    <select v-model="nouvelle.theme_id" class="rounded border-gray-300 dark:bg-gray-800">
                        <option value="">— thème (optionnel) —</option>
                        <option v-for="t in themes" :key="t.id" :value="t.id">{{ t.nom }}</option>
                    </select>
                    <textarea v-model="nouvelle.note_methodologique" rows="2" placeholder="Note méthodologique (pourquoi des études sérieuses divergent)"
                        class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2"></textarea>
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm md:col-span-2">Créer (detecte)</button>
                </form>
            </details>

            <!-- Liste -->
            <div class="flex gap-2 flex-wrap" role="group" aria-label="Filtrer par statut">
                <button v-for="s in filtres" :key="s" type="button" :aria-pressed="statut === s" @click="filtrer(s)" class="px-3 py-1 rounded-full text-sm border"
                    :class="statut === s ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">{{ s }}</button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3">Controverse</th><th class="p-3">Thème</th><th class="p-3">Faits</th>
                            <th class="p-3">Statut</th><th class="p-3">Publié</th><th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in controverses.data" :key="c.id" class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3">
                                <div class="font-medium">{{ c.titre }}</div>
                                <div v-if="c.note_methodologique" class="text-xs text-gray-500 max-w-md">{{ c.note_methodologique }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">{{ c.theme?.nom ?? '—' }}</td>
                            <td class="p-3">{{ c.arguments_count }}</td>
                            <td class="p-3 space-x-1">
                                <StatusBadge :statut="c.statut_validation" />
                                <StatusBadge v-if="c.affiche_publiquement" publie />
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <div class="space-x-1">
                                    <ActionButton v-if="c.statut_validation !== 'valide'"
                                                  verbe="valider" @action="agir(c, 'valider')" />
                                    <ActionButton v-if="c.statut_validation === 'valide' && !c.affiche_publiquement"
                                                  verbe="publier"
                                                  titre-confirmation="Publier cette question clé ?"
                                                  :confirmation="messagePublication('Cette question clé', c.titre)"
                                                  @action="agir(c, 'publier')" />
                                    <ActionButton v-if="c.affiche_publiquement"
                                                  verbe="depublier" @action="agir(c, 'depublier')" />
                                </div>
                                <div class="mt-2">
                                    <ModerationLog type="controverse" :id="c.id" />
                                </div>
                                <div v-if="c.lot" class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 space-y-1">
                                    <div class="text-[11px] uppercase tracking-wide text-gray-400">
                                        Argumentaire
                                        <span class="normal-case tracking-normal text-gray-400" title="Publier une mesure avant que ses arguments et liaisons ne soient publiés fait échouer le contrôle d'intégrité, et l'export refuse alors de régénérer le site entier.">— dans l'ordre : faits, puis liaisons, puis mesures</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        <ActionButton v-if="c.lot.arguments_a_valider.length"
                                                      verbe="valider"
                                                      :libelle="`Valider ${c.lot.arguments_a_valider.length} fait(s)`"
                                                      @action="agirLot('argument', c.lot.arguments_a_valider, 'valider', 'Valider les faits')" />
                                        <ActionButton v-if="c.lot.liens_a_valider.length"
                                                      verbe="valider"
                                                      :libelle="`Valider ${c.lot.liens_a_valider.length} liaison(s)`"
                                                      @action="agirLot('argument_lien', c.lot.liens_a_valider, 'valider', 'Valider les liaisons')" />
                                        <ActionButton v-if="c.lot.liens_a_double_valider.length"
                                                      verbe="double_valider"
                                                      :libelle="`2ᵉ validation · ${c.lot.liens_a_double_valider.length} liaison(s)`"
                                                      titre="Exige un modérateur différent du premier validateur"
                                                      @action="agirLot('argument_lien', c.lot.liens_a_double_valider, 'double_valider', 'Seconde validation')" />
                                        <ActionButton v-if="c.lot.arguments_a_publier.length"
                                                      verbe="publier" :confirmer="false"
                                                      :libelle="`Publier ${c.lot.arguments_a_publier.length} fait(s)`"
                                                      @action="agirLot('argument', c.lot.arguments_a_publier, 'publier', 'Publier les faits')" />
                                        <ActionButton v-if="c.lot.liens_a_publier.length"
                                                      verbe="publier" :confirmer="false"
                                                      :libelle="`Publier ${c.lot.liens_a_publier.length} liaison(s)`"
                                                      @action="agirLot('argument_lien', c.lot.liens_a_publier, 'publier', 'Publier les liaisons')" />
                                        <span v-if="!c.lot.arguments_a_valider.length && !c.lot.liens_a_valider.length
                                                    && !c.lot.arguments_a_publier.length && !c.lot.liens_a_publier.length
                                                    && !c.lot.liens_a_double_valider.length"
                                              class="text-xs text-gray-400">Rien à traiter.</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!controverses.data.length">
                            <td colspan="6" class="p-8 text-center">
                                <p class="text-gray-600 dark:text-gray-400">Aucune question clé.</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                    Créez-en une ci-dessus, puis importez son argumentaire : les faits
                                    se relieront aux mesures des candidats.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="controverses.links" class="flex flex-wrap gap-1">
                <Link v-for="(l, i) in controverses.links" :key="i" :href="l.url ?? ''" v-html="l.label"
                    class="px-3 py-1 text-sm rounded border"
                    :class="[l.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300', !l.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
