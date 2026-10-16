<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import { messagePublication } from '@/composables/useModerationAction';

/**
 * Relecture d'un cadrage budgétaire, ligne par ligne.
 *
 * Chaque ligne est une valeur telle que la source la donne. Avant de cocher « vérifié »,
 * on écoute (ou on lit) la source au repérage indiqué et on confirme que le CHIFFRE
 * correspond à la citation — son unité, son année, et s'il est annuel ou cumulé. Modifier
 * ce qui a été vérifié remet la ligne « à vérifier ».
 */
const props = defineProps({
    cadrage: Object,
    raisons: Array,
    lignes: Array,
    listes: Object,
    documents: Array,
    referentiels: Array,
    propositions: Array,
});

const erreurs = computed(() => usePage().props.errors ?? {});
const flash = computed(() => usePage().props.flash ?? {});
const opts = { preserveScroll: true };
const r = (nom, param) => route(`admin.presidentielle.${nom}`, param);

// ── En-tête ──────────────────────────────────────────────────────────────────────
const entete = reactive({
    nature: props.cadrage.nature,
    titre: props.cadrage.titre,
    date_source: props.cadrage.date_source ?? '',
    ingestion_document_id: props.cadrage.ingestion_document_id ?? '',
    programme_document_id: props.cadrage.programme_document_id ?? '',
    note_methodologique: props.cadrage.note_methodologique ?? '',
});
function enregistrerEntete() {
    router.post(r('budgets.update', props.cadrage.id), {
        ...entete,
        ingestion_document_id: entete.ingestion_document_id || null,
        programme_document_id: entete.programme_document_id || null,
        note_methodologique: entete.note_methodologique.trim() || null,
    }, opts);
}
function agir(action) {
    router.post(r('moderation.action'), { type: 'cadrage', id: props.cadrage.id, action }, opts);
}
const naturesPossibles = computed(() => Object.fromEntries(Object.entries(props.listes.natures)
    .filter(([cle]) => (props.cadrage.reference ? cle === 'reference_officielle' : cle !== 'reference_officielle'))));

// ── Lignes ───────────────────────────────────────────────────────────────────────
const parRubrique = computed(() => Object.entries(props.listes.rubriques).map(([cle, libelle]) => ({
    cle, libelle, lignes: props.lignes.filter((l) => l.rubrique === cle),
})));
const aVerifier = computed(() => props.lignes.filter((l) => l.verification !== 'verifie').length);

const nombre = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });
function valeur(l) {
    if (l.unite === 'texte' || l.montant === null) return l.valeur_texte ?? '—';
    const q = l.qualificatif ? `${props.listes.qualificatifs[l.qualificatif]} ` : '';
    const m = l.montant_max !== null ? `de ${nombre.format(l.montant)} à ${nombre.format(l.montant_max)}` : nombre.format(l.montant);
    const p = l.portee ? ` (${props.listes.portees[l.portee]})` : '';
    const a = l.annee ? ` en ${l.annee}` : '';
    return `${q}${m} ${props.listes.unites[l.unite]}${p}${a}`;
}
function lienVerification(l) {
    const url = l.source_url || props.cadrage.document?.url;
    if (!url) return null;
    const m = /^(?:(\d{1,2}):)?(\d{1,2}):(\d{2})$/.exec((l.reperage ?? '').trim());
    if (!m || !/youtube\.com|youtu\.be/.test(url)) return url;
    const s = Number(m[1] ?? 0) * 3600 + Number(m[2]) * 60 + Number(m[3]);
    return `${url}${url.includes('?') ? '&' : '?'}t=${s}s`;
}

const unitesDe = (rubrique) => props.listes.unites_par_rubrique[rubrique] ?? Object.keys(props.listes.unites);

const CHAMPS = ['rubrique', 'libelle', 'montant', 'montant_max', 'unite', 'qualificatif', 'portee', 'annee', 'valeur_texte',
    'citation', 'reperage', 'locuteur', 'role_locuteur', 'qualite_locuteur', 'proposition_id', 'ingestion_document_id',
    'source_producteur', 'source_titre', 'source_url', 'source_date', 'note'];

function vierge(rubrique) {
    return { ...Object.fromEntries(CHAMPS.map((c) => [c, ''])), rubrique, unite: unitesDe(rubrique)[0] };
}
const edition = ref(null);           // id de la ligne en cours d'édition, ou 'nouvelle'
const brouillon = reactive(vierge(Object.keys(props.listes.rubriques)[0]));

function editer(l) {
    edition.value = l.id;
    Object.assign(brouillon, Object.fromEntries(CHAMPS.map((c) => [c, l[c] ?? ''])));
}
function nouvelle(rubrique) {
    edition.value = 'nouvelle';
    Object.assign(brouillon, vierge(rubrique));
}
function charge() {
    const out = {};
    for (const c of CHAMPS) {
        const v = brouillon[c];
        out[c] = v === '' || v === undefined ? null : v;
    }
    if (!out.locuteur) { out.role_locuteur = null; out.qualite_locuteur = null; }
    return out;
}
function enregistrer() {
    const cible = edition.value === 'nouvelle' ? r('budgets.lignes.store', props.cadrage.id) : r('budgets.lignes.update', edition.value);
    router.post(cible, charge(), { ...opts, onSuccess: () => { edition.value = null; } });
}
function basculerVerification(l) {
    router.post(r('budgets.lignes.verification', l.id), { verification: l.verification === 'verifie' ? 'a_verifier' : 'verifie' }, opts);
}
function supprimer(l) {
    router.delete(r('budgets.lignes.destroy', l.id), opts);
}
function utiliserCitation(l, a) {
    editer(l);
    brouillon.citation = a.citation;
    brouillon.reperage = a.reperage ?? '';
    brouillon.locuteur = a.locuteur ?? '';
    brouillon.role_locuteur = a.role_locuteur ?? '';
    brouillon.qualite_locuteur = a.locuteur ? 'equipe_campagne' : '';
}
</script>

<template>
    <Head :title="`Budget — ${cadrage.titre}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">
                    <Link :href="route('admin.presidentielle.budgets')" class="text-blue-600 hover:underline">Budgets</Link>
                    › {{ cadrage.reference ? 'Référence officielle' : cadrage.candidat }}
                </h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-6">
            <p v-if="flash.success" class="text-sm text-green-600">{{ flash.success }}</p>
            <p v-if="erreurs.integrite || erreurs.action" class="text-sm text-red-600" role="alert">{{ erreurs.integrite ?? erreurs.action }}</p>

            <!-- Statut et actions -->
            <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-lg">{{ cadrage.titre }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ listes.natures[cadrage.nature] }} · présenté le {{ cadrage.date_source }}
                            · {{ lignes.length - aVerifier }}/{{ lignes.length }} ligne(s) vérifiée(s)
                        </p>
                        <p v-if="cadrage.document" class="text-sm">
                            Source : <a :href="cadrage.document.url" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ cadrage.document.titre }} ↗</a>
                            <span class="text-xs text-gray-500"> · porteur : {{ cadrage.document.porteur }}<span v-if="cadrage.document.porteur_libelle"> — {{ cadrage.document.porteur_libelle }}</span></span>
                        </p>
                    </div>
                    <div class="whitespace-nowrap space-x-1">
                        <StatusBadge :statut="cadrage.statut_validation" />
                        <StatusBadge v-if="cadrage.affiche_publiquement" publie />
                        <ActionButton v-if="cadrage.statut_validation !== 'valide'" verbe="valider" @action="agir('valider')" />
                        <ActionButton v-if="cadrage.statut_validation === 'valide' && !cadrage.affiche_publiquement"
                                      verbe="publier" :disabled="raisons.length > 0"
                                      titre-confirmation="Publier ce cadrage ?"
                                      :confirmation="messagePublication('Le cadrage', cadrage.titre, 'Seules les lignes vérifiées paraissent.')"
                                      @action="agir('publier')" />
                        <ActionButton v-if="cadrage.affiche_publiquement" verbe="depublier" @action="agir('depublier')" />
                        <ActionButton v-if="!cadrage.affiche_publiquement" verbe="supprimer"
                                      titre-confirmation="Supprimer ce cadrage ?"
                                      confirmation="Le cadrage et ses lignes passent à la corbeille."
                                      @action="agir('supprimer')" />
                    </div>
                </div>
                <div v-if="raisons.length" class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-3">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Ce qui bloque la publication</p>
                    <ul class="mt-1 text-xs text-amber-800 dark:text-amber-200 list-disc pl-5 space-y-0.5">
                        <li v-for="x in raisons" :key="x">{{ x }}</li>
                    </ul>
                </div>
                <ModerationLog type="cadrage" :id="cadrage.id" />
            </section>

            <!-- En-tête -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">En-tête du cadrage</summary>
                <form @submit.prevent="enregistrerEntete" class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                    <label class="block">
                        <span class="text-xs font-medium">Nature</span>
                        <select v-model="entete.nature" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900">
                            <option v-for="(libelle, cle) in naturesPossibles" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium">Date de présentation</span>
                        <input v-model="entete.date_source" type="date" required class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900" />
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-xs font-medium">Titre</span>
                        <input v-model="entete.titre" type="text" required maxlength="300" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900" />
                    </label>
                    <template v-if="!cadrage.reference">
                        <label class="block">
                            <span class="text-xs font-medium">Prise de parole source</span>
                            <select v-model="entete.ingestion_document_id" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900">
                                <option value="">—</option>
                                <option v-for="d in documents" :key="d.id" :value="d.id">#{{ d.id }} · {{ d.date ?? '—' }} · {{ d.titre }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-medium">ou référentiel de programme</span>
                            <select v-model="entete.programme_document_id" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900">
                                <option value="">—</option>
                                <option v-for="x in referentiels" :key="x.id" :value="x.id">{{ x.titre }}</option>
                            </select>
                        </label>
                    </template>
                    <label class="block md:col-span-2">
                        <span class="text-xs font-medium">Note méthodologique (publique une fois le cadrage publié)</span>
                        <textarea v-model="entete.note_methodologique" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900"></textarea>
                    </label>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm">Enregistrer l'en-tête</button>
                    </div>
                </form>
            </details>

            <details v-if="cadrage.non_precise.length" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">Relevé « non précisé » de l'ingestion ({{ cadrage.non_precise.length }}) — interne, non publié</summary>
                <ul class="mt-2 text-xs text-gray-600 dark:text-gray-300 list-disc pl-5 space-y-1">
                    <li v-for="n in cadrage.non_precise" :key="n">{{ n }}</li>
                </ul>
            </details>

            <!-- Grille -->
            <section v-for="rub in parRubrique" :key="rub.cle" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold">{{ rub.libelle }}</h3>
                    <button type="button" class="text-xs text-blue-600 hover:underline" @click="nouvelle(rub.cle)">+ ajouter une ligne</button>
                </div>
                <p v-if="!rub.lignes.length" class="text-sm text-gray-400 mt-1">Non précisé.</p>

                <article v-for="l in rub.lignes" :key="l.id" class="mt-3 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 max-w-3xl">
                            <p class="font-medium">
                                <span v-if="l.libelle">{{ l.libelle }} : </span>{{ valeur(l) }}
                            </p>
                            <p v-if="l.valeur_texte && l.unite !== 'texte'" class="text-xs text-gray-500">tel que dit : « {{ l.valeur_texte }} »</p>
                            <p v-if="l.citation" class="text-sm italic mt-1">« {{ l.citation }} »</p>
                            <p class="text-xs text-gray-500 mt-1">
                                <span v-if="l.locuteur">{{ l.locuteur }}<span v-if="l.role_locuteur">, {{ l.role_locuteur }}</span><span v-else class="text-amber-600"> (rôle à renseigner)</span> · </span>
                                <a v-if="lienVerification(l)" :href="lienVerification(l)" target="_blank" rel="noopener" class="text-blue-600 hover:underline">▶ vérifier {{ l.reperage ? 'à ' + l.reperage : 'à la source' }}</a>
                                <span v-else-if="l.reperage">{{ l.reperage }}</span>
                                <span v-if="l.source_producteur"> · {{ l.source_producteur }}<span v-if="l.source_date">, {{ l.source_date }}</span></span>
                                <span v-if="l.proposition_id"> · proposition #{{ l.proposition_id }} ({{ l.proposition_statut }})</span>
                                <span :class="l.verbatim_verifie ? 'text-green-600' : 'text-amber-600'"> · citation {{ l.verbatim_verifie ? 'retrouvée dans la transcription' : 'non retrouvée automatiquement' }}</span>
                            </p>
                            <p v-if="l.note" class="text-xs text-gray-500 mt-1">Note d'ingestion : {{ l.note }}</p>
                            <details v-if="l.autres_citations.length" class="mt-1">
                                <summary class="text-xs text-gray-500 cursor-pointer">{{ l.autres_citations.length }} autre(s) citation(s) relevée(s)</summary>
                                <ul class="mt-1 space-y-1">
                                    <li v-for="(a, i) in l.autres_citations" :key="i" class="text-xs">
                                        « {{ a.citation }} » — {{ a.locuteur ?? 'le candidat' }} {{ a.reperage ? '· ' + a.reperage : '' }}
                                        <button type="button" class="text-blue-600 hover:underline ml-1" @click="utiliserCitation(l, a)">utiliser</button>
                                    </li>
                                </ul>
                            </details>
                            <ul v-if="l.raisons.length" class="mt-1 text-xs text-amber-700 dark:text-amber-300 list-disc pl-5">
                                <li v-for="x in l.raisons" :key="x">{{ x }}</li>
                            </ul>
                        </div>
                        <div class="whitespace-nowrap space-x-1">
                            <StatusBadge :statut="l.verification === 'verifie' ? 'valide' : 'detecte'" />
                            <ActionButton :verbe="l.verification === 'verifie' ? 'neutre' : 'valider'"
                                          :libelle="l.verification === 'verifie' ? 'Remettre à vérifier' : 'Vérifié'"
                                          @action="basculerVerification(l)" />
                            <ActionButton verbe="neutre" libelle="Modifier" @action="edition === l.id ? (edition = null) : editer(l)" />
                            <ActionButton verbe="supprimer" titre-confirmation="Supprimer cette ligne ?" confirmation="La ligne sera définitivement retirée du cadrage." @action="supprimer(l)" />
                        </div>
                    </div>
                </article>

                <!-- Formulaire (ajout dans cette rubrique, ou édition d'une de ses lignes) -->
                <form v-if="(edition === 'nouvelle' && brouillon.rubrique === rub.cle) || rub.lignes.some((l) => l.id === edition)"
                      @submit.prevent="enregistrer"
                      class="mt-3 grid md:grid-cols-4 gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-xs">
                    <label class="block">
                        <span class="font-medium">Rubrique</span>
                        <select v-model="brouillon.rubrique" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                            <option v-for="(libelle, cle) in listes.rubriques" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                    </label>
                    <label class="block md:col-span-3">
                        <span class="font-medium">Libellé (poste, hypothèse, objectif — ou précision)</span>
                        <input v-model="brouillon.libelle" type="text" maxlength="300" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                    </label>
                    <label class="block">
                        <span class="font-medium">Montant</span>
                        <input v-model="brouillon.montant" type="number" step="any" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                    </label>
                    <label class="block">
                        <span class="font-medium">Montant max (fourchette)</span>
                        <input v-model="brouillon.montant_max" type="number" step="any" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                    </label>
                    <label class="block">
                        <span class="font-medium">Unité</span>
                        <select v-model="brouillon.unite" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                            <option v-for="u in unitesDe(brouillon.rubrique)" :key="u" :value="u">{{ listes.unites[u] }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="font-medium">Année</span>
                        <input v-model="brouillon.annee" type="number" min="1990" max="2100" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                    </label>
                    <label class="block">
                        <span class="font-medium">Qualificatif</span>
                        <select v-model="brouillon.qualificatif" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                            <option value="">—</option>
                            <option v-for="(libelle, cle) in listes.qualificatifs" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="font-medium">Portée</span>
                        <select v-model="brouillon.portee" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                            <option value="">non précisée</option>
                            <option v-for="(libelle, cle) in listes.portees" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="font-medium">Valeur telle que dite ou écrite</span>
                        <input v-model="brouillon.valeur_texte" type="text" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                    </label>
                    <label class="block md:col-span-3">
                        <span class="font-medium">Citation (verbatim)</span>
                        <textarea v-model="brouillon.citation" rows="2" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900"></textarea>
                    </label>
                    <label class="block">
                        <span class="font-medium">Repérage (hh:mm:ss ou page)</span>
                        <input v-model="brouillon.reperage" type="text" maxlength="100" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                    </label>
                    <template v-if="!cadrage.reference">
                        <label class="block">
                            <span class="font-medium">Locuteur (vide = le candidat)</span>
                            <input v-model="brouillon.locuteur" type="text" maxlength="150" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                        </label>
                        <label class="block">
                            <span class="font-medium">Rôle du locuteur</span>
                            <input v-model="brouillon.role_locuteur" :disabled="!brouillon.locuteur" type="text" maxlength="200" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                        </label>
                        <label class="block">
                            <span class="font-medium">Qualité</span>
                            <select v-model="brouillon.qualite_locuteur" :disabled="!brouillon.locuteur" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                                <option value="">—</option>
                                <option v-for="(libelle, cle) in listes.qualites" :key="cle" :value="cle">{{ libelle }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="font-medium">Proposition source</span>
                            <select v-model="brouillon.proposition_id" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                                <option value="">—</option>
                                <option v-for="p in propositions" :key="p.id" :value="p.id">{{ p.libelle }}</option>
                            </select>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="font-medium">Autre prise de parole source (si différente de l'en-tête)</span>
                            <select v-model="brouillon.ingestion_document_id" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                                <option value="">— celle de l'en-tête —</option>
                                <option v-for="d in documents" :key="d.id" :value="d.id">#{{ d.id }} · {{ d.titre }}</option>
                            </select>
                        </label>
                    </template>
                    <template v-else>
                        <label class="block">
                            <span class="font-medium">Producteur</span>
                            <input v-model="brouillon.source_producteur" type="text" maxlength="120" placeholder="INSEE, ministère, HCFP" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                        </label>
                        <label class="block">
                            <span class="font-medium">Titre de la publication</span>
                            <input v-model="brouillon.source_titre" type="text" maxlength="300" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                        </label>
                        <label class="block">
                            <span class="font-medium">URL</span>
                            <input v-model="brouillon.source_url" type="url" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                        </label>
                        <label class="block">
                            <span class="font-medium">Date</span>
                            <input v-model="brouillon.source_date" type="date" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                        </label>
                    </template>
                    <label class="block md:col-span-4">
                        <span class="font-medium">Note interne (non publiée)</span>
                        <input v-model="brouillon.note" type="text" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                    </label>
                    <div class="md:col-span-4">
                        <FormErrors :errors="erreurs" />
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-2">
                        <button type="button" class="px-3 py-1.5 rounded border" @click="edition = null">Annuler</button>
                        <button type="submit" class="px-3 py-1.5 rounded bg-blue-600 text-white">{{ edition === 'nouvelle' ? 'Ajouter (à vérifier)' : 'Enregistrer' }}</button>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
