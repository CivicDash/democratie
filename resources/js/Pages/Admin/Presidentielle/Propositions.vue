<script setup>
import { reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import Pagination from '@/Components/Pagination.vue';
import { useConfirm } from '@/composables/useConfirm';
import { messagePublication } from '@/composables/useModerationAction';

/**
 * File d'ingestion. Qui parle ? Trois cas, trois circuits :
 *  - le candidat lui-même, et son équipe de campagne (nom et rôle affichés partout) :
 *    « Valider → mesure » ou « Valider sans mesure » (une déclaration chiffrée, un constat) ;
 *  - l'entourage — la parole d'un parti, ou tout document de parti ou de groupe : jamais
 *    une mesure, seulement « Autour du candidat », après validation puis publication.
 * Une proposition ne paraît sur le site qu'une fois validée ou rattachée.
 */
const props = defineProps({
    propositions: Object, // paginator
    documents: Array,
    statut: String,
    qui: String,
    document: Number, // filtre : propositions d'une seule prise de parole
    porteurs: Object,
    qualites: Object,
});

const { confirmDanger } = useConfirm();
const flash = () => usePage().props.flash ?? {};
const erreurs = () => usePage().props.errors ?? {};
const opts = { preserveScroll: true };

// ── Import ───────────────────────────────────────────────────────────────────────
const fichierJson = ref(null);
const fichierSource = ref(null);
const fichierCadrage = ref(null);
const envoiEnCours = ref(false);

function importer() {
    if (!fichierJson.value) return;
    envoiEnCours.value = true;
    router.post(route('admin.presidentielle.propositions.import'),
        { fichier: fichierJson.value, source: fichierSource.value, cadrage: fichierCadrage.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                envoiEnCours.value = false;
                fichierJson.value = null; fichierSource.value = null; fichierCadrage.value = null;
            },
        });
}

// ── Prises de parole : suppression et porteur ────────────────────────────────────
async function supprimerDocument(d) {
    const avertissement = d.nb_rattachees > 0
        ? `${d.nb_rattachees} proposition(s) sont déjà rattachées à des mesures : la suppression sera refusée tant que ces mesures existent. `
        : '';
    const ok = await confirmDanger(
        `${avertissement}Les ${d.nb_propositions} proposition(s) issues de cette prise de parole seront retirées avec elle.`,
        `Supprimer « ${d.titre} » ?`,
        { confirmLabel: 'Supprimer' },
    );
    if (ok) {
        router.delete(route('admin.presidentielle.documents.destroy', d.id), opts);
    }
}

const porteurEdite = ref(null);
const porteurForm = reactive({ porteur_type: 'candidat', porteur_libelle: '', endossement_url: '', endossement_date: '', endossement_note: '' });

function editerPorteur(d) {
    porteurEdite.value = d.id;
    Object.assign(porteurForm, {
        porteur_type: d.porteur_type ?? 'candidat',
        porteur_libelle: d.porteur_libelle ?? '',
        endossement_url: d.endossement_url ?? '',
        endossement_date: d.endossement_date ?? '',
        endossement_note: d.endossement_note ?? '',
    });
}
function enregistrerPorteur(d) {
    const vide = (v) => (v && String(v).trim() !== '' ? v : null);
    router.post(route('admin.presidentielle.documents.porteur', d.id), {
        porteur_type: porteurForm.porteur_type,
        porteur_libelle: vide(porteurForm.porteur_libelle),
        endossement_url: vide(porteurForm.endossement_url),
        endossement_date: vide(porteurForm.endossement_date),
        endossement_note: vide(porteurForm.endossement_note),
    }, { ...opts, onSuccess: () => { porteurEdite.value = null; } });
}
const entourage = (type) => ['parti', 'groupe_parlementaire'].includes(type);

// ── Filtres ──────────────────────────────────────────────────────────────────────
const filtres = ['detecte', 'validee', 'rattachee', 'rejetee', 'tous'];
const filtresQui = { tous: 'Tous', candidat: 'Le candidat', equipe: 'Équipe de campagne', entourage: 'Entourage (parti)' };

function filtrer(params) {
    router.get(route('admin.presidentielle.propositions'), { statut: props.statut, qui: props.qui, document: props.document, ...params }, { preserveState: true, replace: true });
}

const documentFiltre = () => props.documents?.find((d) => d.id === props.document);
const echecsLot = () => usePage().props.flash?.echecs_lot ?? [];

// ── Valider tout un document ─────────────────────────────────────────────────────
// Les propositions en file dont la citation est confirmée mot pour mot passent une par
// une par le circuit habituel ; les autres restent en file, à traiter à la main.
const lotEdite = ref(null);
const lotMode = ref('mesures');
const lotEnCours = ref(false);

function validerTout(d) {
    if (lotEnCours.value) return;
    lotEnCours.value = true;
    router.post(route('admin.presidentielle.documents.valider-tout', d.id), { mode: lotMode.value }, {
        ...opts,
        onSuccess: () => { lotEdite.value = null; },
        onFinish: () => { lotEnCours.value = false; },
    });
}

// ── Actions ──────────────────────────────────────────────────────────────────────
const enTraitement = ref(new Set());

function agir(p, action) {
    if (enTraitement.value.has(p.id)) return; // anti double-clic
    enTraitement.value.add(p.id);
    router.post(route('admin.presidentielle.propositions.action'), { id: p.id, action }, {
        ...opts,
        onFinish: () => enTraitement.value.delete(p.id),
    });
}

// ── Requalification du locuteur ──────────────────────────────────────────────────
const locuteurEdite = ref(null);
const locuteurForm = reactive({ locuteur: '', role_locuteur: '', qualite_locuteur: '' });

function editerLocuteur(p) {
    locuteurEdite.value = p.id;
    Object.assign(locuteurForm, {
        locuteur: p.locuteur ?? '',
        role_locuteur: p.role_locuteur ?? '',
        qualite_locuteur: p.qualite_locuteur ?? '',
    });
}
function enregistrerLocuteur(p) {
    const candidat = locuteurForm.locuteur.trim() === '';
    router.post(route('admin.presidentielle.propositions.locuteur', p.id), {
        locuteur: candidat ? null : locuteurForm.locuteur.trim(),
        role_locuteur: candidat ? null : (locuteurForm.role_locuteur.trim() || null),
        qualite_locuteur: candidat ? null : (locuteurForm.qualite_locuteur || null),
    }, { ...opts, onSuccess: () => { locuteurEdite.value = null; } });
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

        <div class="max-w-7xl mx-auto p-6 space-y-4">
            <!-- Chargement d'un discours (contrat JSON §11) sans terminal -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">📥 Charger un discours (JSON de propositions)</summary>
                <p class="text-xs text-gray-500 mt-2">
                    Fichier JSON au contrat d'ingestion v1 (généré via Claude à partir de la transcription).
                    Chaque proposition peut nommer son <strong>locuteur réel</strong> (<code>locuteur</code>, <code>role_locuteur</code>,
                    <code>qualite_locuteur</code>) et le document son <strong>porteur</strong> (<code>source.porteur</code>).
                    Joindre la <strong>transcription source</strong> (txt/srt) pour la vérification automatique des
                    citations verbatim, et le <strong>cadrage budgétaire</strong> de la même source s'il existe
                    (contrat <code>cadrage_budgetaire.v1</code>). Tout entre en file de modération (<code>detecte</code>).
                </p>
                <form @submit.prevent="importer" class="mt-3 grid md:grid-cols-3 gap-3 text-sm">
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
                    <label class="block">
                        <span class="text-xs font-medium">cadrage_budgetaire.json (facultatif)</span>
                        <input type="file" accept=".json,application/json"
                               @change="fichierCadrage = $event.target.files[0]"
                               class="mt-1 block w-full text-xs" />
                    </label>
                    <div class="md:col-span-3 flex items-center justify-between gap-3">
                        <p v-if="erreurs().fichier" class="text-xs text-red-600">{{ erreurs().fichier }}</p>
                        <button type="submit" :disabled="envoiEnCours || !fichierJson"
                                class="ml-auto px-4 py-2 rounded bg-blue-600 text-white text-sm disabled:opacity-50">
                            {{ envoiEnCours ? 'Import…' : 'Importer' }}
                        </button>
                    </div>
                </form>
            </details>

            <!-- Prises de parole importées : porteur, suppression d'un import erroné -->
            <details v-if="documents?.length" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">🎙️ Prises de parole importées ({{ documents.length }})</summary>
                <p class="text-xs text-gray-500 mt-2">
                    Le <strong>porteur</strong> dit au nom de qui parle le document. Un document de parti ou de groupe parlementaire
                    ne paraît que dans « Autour du candidat » ; tout porteur autre que le candidat doit prouver son endossement
                    (URL, date, note) avant publication. Changer de côté renvoie les propositions validées en file.
                    Supprimer un document retire toutes ses propositions ; refusé si des propositions sont rattachées,
                    publiées « Autour », ou si un cadrage le cite.
                </p>
                <div v-for="d in documents" :key="d.id" class="text-sm py-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate">{{ d.titre }}</p>
                            <p class="text-xs text-gray-500">
                                {{ d.type }} · {{ d.nb_propositions }} proposition(s)
                                <span v-if="d.nb_rattachees"> · {{ d.nb_rattachees }} rattachée(s)</span>
                                <span v-if="d.nb_autour"> · {{ d.nb_autour }} publiée(s) « Autour »</span>
                                <span v-if="d.nb_cadrages"> · {{ d.nb_cadrages }} cadrage(s)</span>
                                · porteur :
                                <span :class="entourage(d.porteur_type) ? 'text-purple-700 dark:text-purple-300 font-medium' : ''">
                                    {{ porteurs[d.porteur_type] ?? d.porteur_type }}<span v-if="d.porteur_libelle"> — {{ d.porteur_libelle }}</span>
                                </span>
                                <span v-if="d.raisons_porteur?.length" class="text-amber-600"> · endossement incomplet</span>
                            </p>
                        </div>
                        <div class="whitespace-nowrap space-x-1">
                            <ActionButton v-if="d.nb_en_file" verbe="neutre" :libelle="`Voir ses ${d.nb_en_file} en file`"
                                          @action="filtrer({ document: d.id, statut: 'detecte', qui: 'tous' })" />
                            <ActionButton v-if="d.nb_en_file_confirmees && !entourage(d.porteur_type)" verbe="valider"
                                          :libelle="`Valider tout (${d.nb_en_file_confirmees})`"
                                          @action="lotEdite = lotEdite === d.id ? null : d.id" />
                            <ActionButton verbe="neutre" libelle="Porteur" @action="porteurEdite === d.id ? (porteurEdite = null) : editerPorteur(d)" />
                            <ActionButton verbe="supprimer" :confirmer="false" @action="supprimerDocument(d)" />
                        </div>
                    </div>
                    <ul v-if="d.raisons_porteur?.length" class="mt-1 text-xs text-amber-700 dark:text-amber-300 list-disc pl-5">
                        <li v-for="r in d.raisons_porteur" :key="r">{{ r }}</li>
                    </ul>
                    <div v-if="lotEdite === d.id" class="mt-2 rounded-lg bg-blue-50 dark:bg-blue-950 p-3 text-xs space-y-2">
                        <p>
                            <strong>{{ d.nb_en_file_confirmees }}</strong> proposition(s) en file ont une citation retrouvée mot pour mot
                            dans la transcription : elles seront validées une par une, comme à la main (règles du locuteur comprises).
                            <span v-if="d.nb_en_file > d.nb_en_file_confirmees">
                                {{ d.nb_en_file - d.nb_en_file_confirmees }} autre(s), dont la citation n'est pas confirmée, resteront en file.
                            </span>
                            Écartez d'abord ce qui vous gêne (« Voir ses propositions en file », puis Rejeter).
                        </p>
                        <fieldset class="space-y-1">
                            <label class="flex items-start gap-2">
                                <input v-model="lotMode" type="radio" value="mesures" class="mt-0.5" />
                                <span>Les propositions de type « mesure » deviennent des mesures (non publiées, à relire dans « Mesures ») ;
                                    les positions et déclarations sont validées sans mesure.</span>
                            </label>
                            <label class="flex items-start gap-2">
                                <input v-model="lotMode" type="radio" value="sans_mesure" class="mt-0.5" />
                                <span>Tout valider sans mesure : les propositions paraissent dans les prises de parole, aucune mesure n'est créée.</span>
                            </label>
                        </fieldset>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="px-3 py-1.5 rounded border text-xs" @click="lotEdite = null">Annuler</button>
                            <ActionButton verbe="valider" :libelle="lotEnCours ? 'Validation…' : `Valider les ${d.nb_en_file_confirmees}`"
                                          :disabled="lotEnCours" confirmer
                                          titre-confirmation="Valider toute la prise de parole ?"
                                          :confirmation="`${d.nb_en_file_confirmees} proposition(s) de « ${d.titre} » seront validées. Chaque décision reste tracée au journal.`"
                                          @action="validerTout(d)" />
                        </div>
                    </div>
                    <form v-if="porteurEdite === d.id" @submit.prevent="enregistrerPorteur(d)"
                          class="mt-2 grid md:grid-cols-2 gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-xs">
                        <label class="block">
                            <span class="font-medium">Porteur</span>
                            <select v-model="porteurForm.porteur_type" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                                <option v-for="(libelle, cle) in porteurs" :key="cle" :value="cle">{{ libelle }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="font-medium">Libellé (qui, précisément ?)</span>
                            <input v-model="porteurForm.porteur_libelle" :disabled="porteurForm.porteur_type === 'candidat'" type="text"
                                   placeholder="Groupe Rassemblement national à l'Assemblée" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                        </label>
                        <template v-if="porteurForm.porteur_type !== 'candidat'">
                            <label class="block">
                                <span class="font-medium">URL de l'endossement</span>
                                <input v-model="porteurForm.endossement_url" type="url" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                            </label>
                            <label class="block">
                                <span class="font-medium">Date de l'endossement</span>
                                <input v-model="porteurForm.endossement_date" type="date" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                            </label>
                            <label class="block md:col-span-2">
                                <span class="font-medium">Note d'endossement</span>
                                <textarea v-model="porteurForm.endossement_note" rows="2" placeholder="Présenté le 06/10/2026 en présence de la candidate…"
                                          class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900"></textarea>
                            </label>
                        </template>
                        <div class="md:col-span-2 flex items-center justify-end gap-2">
                            <p v-if="erreurs().porteur_type || erreurs().porteur_libelle" class="text-red-600 mr-auto">
                                {{ erreurs().porteur_type ?? erreurs().porteur_libelle }}
                            </p>
                            <button type="button" class="px-3 py-1.5 rounded border text-xs" @click="porteurEdite = null">Annuler</button>
                            <button type="submit" class="px-3 py-1.5 rounded bg-blue-600 text-white text-xs">Enregistrer</button>
                        </div>
                    </form>
                    <ModerationLog type="document" :id="d.id" class="mt-1" />
                </div>
                <p v-if="erreurs().document" class="text-xs text-red-600 mt-2">{{ erreurs().document }}</p>
            </details>

            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex gap-2 flex-wrap" role="group" aria-label="Filtrer par statut">
                    <button v-for="s in filtres" :key="s" type="button" :aria-pressed="statut === s" @click="filtrer({ statut: s })"
                        class="px-3 py-1 rounded-full text-sm border"
                        :class="statut === s ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">
                        {{ s }}
                    </button>
                </div>
                <div class="flex gap-2 flex-wrap items-center" role="group" aria-label="Qui parle">
                    <span class="text-xs text-gray-500">Qui parle :</span>
                    <button v-for="(libelle, cle) in filtresQui" :key="cle" type="button" :aria-pressed="qui === cle" @click="filtrer({ qui: cle })"
                        class="px-3 py-1 rounded-full text-sm border"
                        :class="qui === cle ? 'bg-gray-800 text-white border-gray-800 dark:bg-gray-200 dark:text-gray-900' : 'border-gray-300 text-gray-600'">
                        {{ libelle }}
                    </button>
                </div>
            </div>

            <div v-if="document" class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                <span>Prise de parole : <strong>{{ documentFiltre()?.titre ?? ('#' + document) }}</strong></span>
                <button type="button" class="text-blue-600 hover:underline text-xs" @click="filtrer({ document: null })">Tout afficher</button>
            </div>
            <p v-if="flash().success" class="text-sm text-green-700 dark:text-green-300" role="status">{{ flash().success }}</p>
            <div v-if="echecsLot().length" class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950 p-3 text-xs" role="alert">
                <p class="font-medium mb-1">Refusées par les règles, restées en file :</p>
                <ul class="list-disc pl-5">
                    <li v-for="e in echecsLot()" :key="e.id">#{{ e.id }} — {{ e.motif }}</li>
                </ul>
            </div>
            <p v-if="erreurs().action" class="text-sm text-red-600" role="alert">{{ erreurs().action }}</p>
            <p v-if="erreurs().locuteur || erreurs().role_locuteur || erreurs().qualite_locuteur" class="text-sm text-red-600" role="alert">
                {{ erreurs().locuteur ?? erreurs().role_locuteur ?? erreurs().qualite_locuteur }}
            </p>
            <p v-if="erreurs().integrite" class="text-sm text-red-600" role="alert">{{ erreurs().integrite }}</p>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3">Candidat</th>
                            <th class="p-3">Qui parle</th>
                            <th class="p-3">Thème</th>
                            <th class="p-3">Citation verbatim</th>
                            <th class="p-3">Vérif.</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in propositions.data" :key="p.id" class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3 whitespace-nowrap">{{ p.candidat }}</td>
                            <td class="p-3 min-w-[12rem]">
                                <template v-if="p.locuteur">
                                    <p class="font-medium">{{ p.locuteur }}</p>
                                    <p v-if="p.role_locuteur" class="text-xs text-gray-500">{{ p.role_locuteur }}</p>
                                    <p v-else class="text-xs text-amber-600">rôle à renseigner</p>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px]"
                                          :class="p.qualite_locuteur === 'parti'
                                              ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'
                                              : 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300'">
                                        {{ qualites[p.qualite_locuteur] ?? p.qualite_locuteur }}
                                    </span>
                                </template>
                                <p v-else class="text-gray-600 dark:text-gray-300">Le candidat</p>
                                <p v-if="p.document && p.document.porteur_type !== 'candidat'" class="text-[11px] mt-1"
                                   :class="entourage(p.document.porteur_type) ? 'text-purple-700 dark:text-purple-300' : 'text-gray-500'">
                                    Document : {{ porteurs[p.document.porteur_type] }}<span v-if="p.document.porteur_libelle"> — {{ p.document.porteur_libelle }}</span>
                                </p>
                                <p v-if="p.entourage" class="text-[11px] mt-1 font-medium text-purple-700 dark:text-purple-300">→ « Autour du candidat »</p>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                {{ p.theme ?? '—' }}
                                <span class="block mt-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs w-fit">{{ p.type }}</span>
                            </td>
                            <td class="p-3 max-w-md">
                                <p class="italic">« {{ p.citation_verbatim }} »</p>
                                <p class="text-xs text-gray-400 mt-1">{{ p.resume_propose }} <span v-if="p.timestamp_ou_paragraphe">· {{ p.timestamp_ou_paragraphe }}</span></p>
                                <p v-if="p.document" class="text-[11px] text-gray-400 mt-1 truncate">{{ p.document.titre }}</p>
                            </td>
                            <td class="p-3">
                                <span :class="p.verbatim_verifie ? 'text-green-600' : 'text-amber-600'" :title="p.verbatim_verifie ? 'citation retrouvée dans la transcription' : 'citation à vérifier'">
                                    {{ p.verbatim_verifie ? '✓' : '⚠' }}
                                </span>
                                <span class="block text-xs text-gray-400">{{ p.confiance }}</span>
                            </td>
                            <td class="p-3 text-right space-y-1">
                                <div class="flex flex-wrap justify-end gap-1">
                                    <!-- Le candidat et son équipe : mesure, ou validation simple. -->
                                    <template v-if="p.statut === 'detecte' && !p.entourage">
                                        <!-- Bleu, pas vert : la mesure créée naît en « détectée » et n'est pas publiée. -->
                                        <ActionButton verbe="valider" libelle="Valider → mesure"
                                                      :disabled="enTraitement.has(p.id) || p.raisons_cote_candidat.length > 0"
                                                      @action="agir(p, 'valider')" />
                                        <ActionButton verbe="neutre" libelle="Valider sans mesure"
                                                      :disabled="enTraitement.has(p.id) || p.raisons_cote_candidat.length > 0"
                                                      confirmer
                                                      titre-confirmation="Valider sans créer de mesure ?"
                                                      confirmation="La proposition paraîtra dans les prises de parole du candidat (citation vérifiable), sans devenir une mesure du comparateur."
                                                      @action="agir(p, 'valider_sans_mesure')" />
                                    </template>
                                    <!-- L'entourage : jamais une mesure. -->
                                    <ActionButton v-else-if="p.statut === 'detecte' && p.entourage"
                                                  verbe="valider" libelle="Valider pour « Autour du candidat »"
                                                  :disabled="enTraitement.has(p.id)"
                                                  @action="agir(p, 'valider_entourage')" />
                                    <template v-else-if="p.statut === 'validee' && p.entourage">
                                        <StatusBadge v-if="p.affiche_publiquement" publie />
                                        <ActionButton v-if="!p.affiche_publiquement" verbe="publier" libelle="Publier « Autour »"
                                                      :disabled="enTraitement.has(p.id) || p.raisons_autour.length > 0"
                                                      titre-confirmation="Publier dans « Autour du candidat » ?"
                                                      :confirmation="messagePublication('Cette citation de ' + (p.locuteur ?? p.document?.porteur_libelle ?? 'l\'entourage'), p.citation_verbatim.slice(0, 80))"
                                                      @action="agir(p, 'publier_entourage')" />
                                        <ActionButton v-else verbe="depublier" :disabled="enTraitement.has(p.id)" @action="agir(p, 'depublier_entourage')" />
                                    </template>
                                    <StatusBadge v-else :statut="p.statut" />

                                    <ActionButton v-if="['detecte', 'validee'].includes(p.statut) && !p.affiche_publiquement"
                                                  verbe="rejeter"
                                                  :disabled="enTraitement.has(p.id)"
                                                  confirmer
                                                  titre-confirmation="Rejeter cette proposition ?"
                                                  confirmation="Elle sortira de la file d'ingestion et ne paraîtra pas sur le site."
                                                  @action="agir(p, 'rejeter')" />
                                </div>
                                <ul v-if="p.raisons_cote_candidat.length || p.raisons_autour.length"
                                    class="text-left text-xs text-amber-700 dark:text-amber-300 list-disc pl-5 max-w-xs ml-auto">
                                    <li v-for="r in [...p.raisons_cote_candidat, ...p.raisons_autour]" :key="r">{{ r }}</li>
                                </ul>
                                <div class="text-left">
                                    <button type="button" class="text-xs text-blue-600 hover:underline"
                                            @click="locuteurEdite === p.id ? (locuteurEdite = null) : editerLocuteur(p)">
                                        Qui parle ? (requalifier)
                                    </button>
                                    <form v-if="locuteurEdite === p.id" @submit.prevent="enregistrerLocuteur(p)"
                                          class="mt-2 space-y-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-xs min-w-[16rem]">
                                        <p class="text-gray-500">Laisser le nom vide : c'est le candidat lui-même.</p>
                                        <input v-model="locuteurForm.locuteur" type="text" placeholder="Nom du locuteur"
                                               class="w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                                        <input v-model="locuteurForm.role_locuteur" type="text" placeholder="Rôle au moment des propos"
                                               :disabled="!locuteurForm.locuteur.trim()"
                                               class="w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                                        <select v-model="locuteurForm.qualite_locuteur" :disabled="!locuteurForm.locuteur.trim()"
                                                class="w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                                            <option value="">— de quel côté ? —</option>
                                            <option v-for="(libelle, cle) in qualites" :key="cle" :value="cle">{{ libelle }}</option>
                                        </select>
                                        <p v-if="p.statut === 'validee'" class="text-amber-600">La proposition reviendra en file.</p>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="px-3 py-1.5 rounded border" @click="locuteurEdite = null">Annuler</button>
                                            <button type="submit" class="px-3 py-1.5 rounded bg-blue-600 text-white">Enregistrer</button>
                                        </div>
                                    </form>
                                    <ModerationLog type="proposition" :id="p.id" class="mt-1" />
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!propositions.data.length">
                            <td colspan="6" class="p-8 text-center">
                                <p class="text-gray-600 dark:text-gray-400">Aucune proposition dans cette file.</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                    Importez une prise de parole ci-dessus : ses propositions arrivent
                                    en « détectées », prêtes à être triées.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="propositions.links" :links="propositions.links" />
        </div>
    </AuthenticatedLayout>
</template>
