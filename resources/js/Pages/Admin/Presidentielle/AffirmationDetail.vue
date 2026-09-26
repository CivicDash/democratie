<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';
import { messagePublication } from '@/composables/useModerationAction';

/**
 * Relecture d'une fiche « Ce qu'on entend ».
 *
 * Le travail se fait phrase par phrase : chaque constat est affiché avec ses sources
 * cliquables juste en dessous, pour qu'on vérifie le chiffre dans la publication du
 * producteur avant de cocher « vérifié ». La fiche n'est publiable que quand toutes les
 * phrases le sont — y compris les limites et les lectures croisées, qui sont nos mots.
 */
const props = defineProps({
    fiche: Object,
    raisons: Array,
    verdicts: Array,
    constats: Array,
    sources: Array,
    graphiques: Array,
    themes: Array,
    listes: Object,
});

const entete = reactive({
    enonce: props.fiche.enonce,
    resume: props.fiche.resume ?? '',
    theme_id: props.fiche.theme_id,
    themes_secondaires: [...props.fiche.themes_secondaires],
    part_de_valeur: props.fiche.part_de_valeur,
    derniere_verification: props.fiche.derniere_verification ?? '',
    coloration_percue: props.fiche.coloration_percue ?? '',
});

const opts = { preserveScroll: true };
const r = (nom, param) => route(`admin.presidentielle.${nom}`, param);

function enregistrerEntete() {
    router.post(r('affirmations.update', props.fiche.id), {
        ...entete,
        derniere_verification: entete.derniere_verification || null,
        coloration_percue: entete.coloration_percue || null,
    }, opts);
}

function agir(action) {
    router.post(r('moderation.action'), { type: 'affirmation', id: props.fiche.id, action }, opts);
}

// ── Verdicts ─────────────────────────────────────────────────────────────────────
const nouveauVerdict = reactive({ verdict: 'nuance', portee: '' });
function ajouterVerdict() {
    router.post(r('affirmations.verdicts.store', props.fiche.id), nouveauVerdict,
        { ...opts, onSuccess: () => { nouveauVerdict.portee = ''; } });
}
function enregistrerVerdict(v) {
    router.post(r('affirmations.verdicts.update', v.id), { verdict: v.verdict, portee: v.portee || null }, opts);
}

// ── Constats ─────────────────────────────────────────────────────────────────────
const sourceParId = computed(() => Object.fromEntries(props.sources.map((s) => [s.id, s])));

const parSection = computed(() => Object.keys(props.listes.sections).map((section) => {
    const groupes = [];
    for (const c of props.constats.filter((x) => x.section === section)) {
        const g = groupes.find((x) => x.nom === (c.groupe ?? ''));
        if (g) g.constats.push(c); else groupes.push({ nom: c.groupe ?? '', constats: [c] });
    }
    return { section, libelle: props.listes.sections[section], groupes };
}));

const aVerifier = computed(() => props.constats.filter((c) => c.verification !== 'verifie').length);

const edition = ref(null);
const brouillon = reactive({ texte: '', groupe: '', section: '', note_verification: '', sources: [] });

function editer(c) {
    edition.value = c.id;
    Object.assign(brouillon, {
        texte: c.texte, groupe: c.groupe ?? '', section: c.section,
        note_verification: c.note_verification ?? '', sources: [...c.sources],
    });
}
function enregistrerConstat(c) {
    router.post(r('affirmations.constats.update', c.id), {
        ...brouillon, groupe: brouillon.groupe || null, note_verification: brouillon.note_verification || null,
    }, { ...opts, onSuccess: () => { edition.value = null; } });
}
function basculerVerification(c) {
    router.post(r('affirmations.constats.verification', c.id),
        { verification: c.verification === 'verifie' ? 'a_verifier' : 'verifie' }, opts);
}

const ajoutSection = ref(null);
const nouveauConstat = reactive({ texte: '', groupe: '', sources: [] });
function ajouterConstat(section) {
    router.post(r('affirmations.constats.store', props.fiche.id), {
        section, texte: nouveauConstat.texte, groupe: nouveauConstat.groupe || null, sources: nouveauConstat.sources,
    }, { ...opts, onSuccess: () => { Object.assign(nouveauConstat, { texte: '', groupe: '', sources: [] }); ajoutSection.value = null; } });
}

// ── Sources ──────────────────────────────────────────────────────────────────────
const sourcesEdit = reactive(Object.fromEntries(props.sources.map((s) => [s.id, { ...s }])));
function enregistrerSource(id) {
    const s = sourcesEdit[id];
    router.post(r('affirmations.sources.update', id), {
        producteur: s.producteur, titre: s.titre, url: s.url || null, archive_url: s.archive_url || null,
        categorie: s.categorie, date_publication: s.date_publication || null, date_consultation: s.date_consultation || null,
    }, opts);
}
const nouvelleSource = reactive({ cle: '', producteur: '', titre: '', url: '', archive_url: '', categorie: 'producteur_public', date_publication: '', date_consultation: '' });
function ajouterSource() {
    const payload = Object.fromEntries(Object.entries(nouvelleSource).map(([k, v]) => [k, v === '' ? null : v]));
    router.post(r('affirmations.sources.store', props.fiche.id), payload, opts);
}

// ── Graphiques ───────────────────────────────────────────────────────────────────
const graphiquesEdit = reactive(Object.fromEntries(props.graphiques.map((g) => [g.id, {
    titre: g.titre, sous_titre: g.sous_titre ?? '', note: g.note ?? '', constat_id: g.constat_id,
}])));
function enregistrerGraphique(id) {
    const g = graphiquesEdit[id];
    router.post(r('affirmations.graphiques.update', id), { ...g, sous_titre: g.sous_titre || null, note: g.note || null }, opts);
}
const constatsEurope = computed(() => props.constats.filter((c) => ['europe', 'complement', 'chiffres'].includes(c.section)));
const libelleEtat = { publie: 'validée', a_jour: 'validée', revision: 'révision à relire', nouveau: 'jamais validée', jamais_extrait: 'jamais extraite' };
const nf = (v) => (typeof v === 'number' ? v.toLocaleString('fr-FR') : v);
</script>

<template>
    <Head :title="`Ce qu'on entend — ${fiche.enonce}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Fiche « Ce qu'on entend »</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <FormErrors :errors="$page.props.errors" />

            <!-- En-tête -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <Link :href="r('affirmations')" class="text-xs text-blue-600 hover:underline">← Toutes les fiches</Link>
                <div class="flex items-start justify-between gap-3 flex-wrap mt-2">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-lg">« {{ fiche.enonce }} »</h3>
                        <p class="text-sm text-gray-500">
                            {{ themes.find((t) => t.id === fiche.theme_id)?.nom }} · /ce-qu-on-entend/{{ fiche.slug }}/
                            · {{ constats.length - aVerifier }} / {{ constats.length }} constats vérifiés
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <StatusBadge :statut="fiche.statut_validation" />
                        <StatusBadge :publie="fiche.affiche_publiquement" />
                    </div>
                </div>

                <div v-if="raisons.length" class="mt-3 rounded border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Pas encore publiable</p>
                    <ul class="text-sm mt-1 list-disc list-inside text-amber-700 dark:text-amber-300">
                        <li v-for="x in raisons" :key="x">{{ x }}</li>
                    </ul>
                </div>

                <div class="flex items-center gap-2 mt-3 flex-wrap">
                    <ActionButton v-if="fiche.statut_validation !== 'valide'" verbe="valider" @action="agir('valider')" />
                    <ActionButton v-if="fiche.statut_validation === 'valide' && !fiche.affiche_publiquement" verbe="publier"
                                  :disabled="raisons.length > 0" :titre="raisons.join(' · ') || null"
                                  :confirmation="messagePublication('Cette fiche', fiche.enonce, 'Son verdict engage la plateforme : il sera lu comme notre conclusion.')"
                                  @action="agir('publier')" />
                    <ActionButton v-if="fiche.affiche_publiquement" verbe="depublier" @action="agir('depublier')" />
                </div>
                <p v-if="fiche.affiche_publiquement" class="text-xs text-gray-500 mt-3">
                    Fiche publiée : une modification qui la rendrait impubliable (constat remis à
                    vérifier, source sans URL…) sera refusée. Dépubliez d'abord.
                </p>
            </div>

            <!-- Cadrage -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-medium text-sm">✎ Énoncé, résumé, thèmes</summary>
                <div class="grid md:grid-cols-2 gap-3 text-sm mt-4">
                    <div class="md:col-span-2">
                        <label for="f-enonce" class="block text-xs text-gray-500 mb-1">Énoncé — l'affirmation telle qu'entendue</label>
                        <input id="f-enonce" v-model="entete.enonce" type="text" maxlength="300" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="f-resume" class="block text-xs text-gray-500 mb-1">Résumé (carte de la liste, description des moteurs)</label>
                        <textarea id="f-resume" v-model="entete.resume" rows="2" maxlength="2000" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"></textarea>
                    </div>
                    <div>
                        <label for="f-theme" class="block text-xs text-gray-500 mb-1">Thème principal</label>
                        <select id="f-theme" v-model="entete.theme_id" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            <option v-for="t in themes" :key="t.id" :value="t.id">{{ t.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="f-verif" class="block text-xs text-gray-500 mb-1">Dernière vérification des sources</label>
                        <input id="f-verif" v-model="entete.derniere_verification" type="date" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                    </div>
                    <fieldset class="md:col-span-2">
                        <legend class="block text-xs text-gray-500 mb-1">Thèmes secondaires</legend>
                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                            <label v-for="t in themes.filter((x) => x.id !== entete.theme_id)" :key="t.id" class="flex items-center gap-1.5">
                                <input v-model="entete.themes_secondaires" type="checkbox" :value="t.id" class="rounded" /> {{ t.nom }}
                            </label>
                        </div>
                    </fieldset>
                    <label class="md:col-span-2 flex items-center gap-2">
                        <input v-model="entete.part_de_valeur" type="checkbox" class="rounded" />
                        Le cœur de l'affirmation est un jugement (« trop », « explose ») que les chiffres ne tranchent pas
                    </label>
                    <div class="md:col-span-2 rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60 p-3">
                        <label for="f-coloration" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                            Coloration perçue — interne, ne sort jamais du back-office
                        </label>
                        <select id="f-coloration" v-model="entete.coloration_percue" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            <option value="">Non renseignée</option>
                            <option v-for="(libelle, cle) in listes.colorations" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Sert uniquement au contrôle de symétrie de la liste des fiches.</p>
                    </div>
                    <div class="md:col-span-2 text-right">
                        <ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrerEntete" />
                    </div>
                </div>
            </details>

            <!-- Verdicts -->
            <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold">Verdict(s) — sur la partie mesurable uniquement</h3>
                <p class="text-xs text-gray-500 mt-1">Aucun verdict n'est « principal » : ils sont tous affichés, chacun avec sa portée.</p>
                <ul class="mt-3 space-y-2">
                    <li v-for="v in verdicts" :key="v.id" class="flex items-center gap-2 flex-wrap">
                        <select v-model="v.verdict" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" aria-label="Verdict">
                            <option v-for="(libelle, cle) in listes.verdicts" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                        <input v-model="v.portee" type="text" maxlength="300" placeholder="Portée (obligatoire s'il y a plusieurs verdicts)"
                               class="flex-1 min-w-[16rem] rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" aria-label="Portée" />
                        <ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrerVerdict(v)" />
                        <ActionButton verbe="supprimer" libelle="Retirer" :confirmer="false"
                                      @action="router.delete(r('affirmations.verdicts.destroy', v.id), opts)" />
                    </li>
                </ul>
                <div class="flex items-center gap-2 flex-wrap mt-3 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                    <select v-model="nouveauVerdict.verdict" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" aria-label="Nouveau verdict">
                        <option v-for="(libelle, cle) in listes.verdicts" :key="cle" :value="cle">{{ libelle }}</option>
                    </select>
                    <input v-model="nouveauVerdict.portee" type="text" maxlength="300" placeholder="Portée"
                           class="flex-1 min-w-[16rem] rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" aria-label="Portée du nouveau verdict" />
                    <ActionButton verbe="neutre" libelle="＋ Ajouter un verdict" @action="ajouterVerdict" />
                </div>
            </section>

            <!-- Constats -->
            <section v-for="s in parSection" :key="s.section" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold">{{ s.libelle }}</h3>
                <div v-for="g in s.groupes" :key="g.nom" class="mt-3">
                    <p v-if="g.nom" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ g.nom }}</p>
                    <ul class="space-y-2">
                        <li v-for="c in g.constats" :key="c.id"
                            :class="['rounded border p-3', c.verification === 'verifie'
                                ? 'border-gray-200 dark:border-gray-700'
                                : 'border-amber-300 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10']">
                            <template v-if="edition !== c.id">
                                <p class="text-sm whitespace-pre-line">{{ c.texte }}</p>
                                <ul v-if="c.sources.length" class="mt-2 space-y-0.5">
                                    <li v-for="id in c.sources" :key="id" class="text-xs">
                                        <a v-if="sourceParId[id]?.url_valide" :href="sourceParId[id].url" target="_blank" rel="noopener"
                                           class="text-blue-600 hover:underline">{{ sourceParId[id].producteur }} — {{ sourceParId[id].titre }} ↗</a>
                                        <span v-else class="text-red-600">{{ sourceParId[id]?.producteur }} — {{ sourceParId[id]?.titre }} (sans URL)</span>
                                    </li>
                                </ul>
                                <p v-else-if="['chiffres', 'complement', 'europe'].includes(c.section)" class="text-xs text-red-600 mt-1">
                                    Aucune source : un chiffre doit citer la sienne.
                                </p>
                                <p v-if="c.note_verification && c.verification !== 'verifie'" class="text-xs text-amber-800 dark:text-amber-300 mt-2">
                                    À contrôler : {{ c.note_verification }}
                                </p>
                                <p v-if="c.alerte_bornes" class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                                    Évolution sans ses deux bornes ? Le cadre demande « valeur A (année) → valeur B (année) ».
                                </p>
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    <ActionButton :verbe="c.verification === 'verifie' ? 'neutre' : 'valider'"
                                                  :libelle="c.verification === 'verifie' ? '✓ Vérifié — remettre à vérifier' : 'Marquer vérifié'"
                                                  @action="basculerVerification(c)" />
                                    <ActionButton verbe="neutre" libelle="✎ Modifier" @action="editer(c)" />
                                    <ActionButton verbe="supprimer" libelle="Supprimer"
                                                  :confirmation="'Ce constat sera supprimé.'"
                                                  @action="router.delete(r('affirmations.constats.destroy', c.id), opts)" />
                                </div>
                            </template>
                            <div v-else class="space-y-2 text-sm">
                                <textarea v-model="brouillon.texte" rows="4" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Texte du constat"></textarea>
                                <div class="grid md:grid-cols-2 gap-2">
                                    <select v-model="brouillon.section" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Section">
                                        <option v-for="(libelle, cle) in listes.sections" :key="cle" :value="cle">{{ libelle }}</option>
                                    </select>
                                    <input v-model="brouillon.groupe" type="text" maxlength="200" placeholder="Sous-titre (facultatif)"
                                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Sous-titre" />
                                </div>
                                <input v-model="brouillon.note_verification" type="text" maxlength="2000" placeholder="Ce qui reste à contrôler (interne)"
                                       class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Note de vérification" />
                                <fieldset>
                                    <legend class="text-xs text-gray-500 mb-1">Sources citées</legend>
                                    <label v-for="src in sources" :key="src.id" class="flex items-start gap-1.5 text-xs">
                                        <input v-model="brouillon.sources" type="checkbox" :value="src.id" class="rounded mt-0.5" />
                                        {{ src.producteur }} — {{ src.titre }}
                                    </label>
                                </fieldset>
                                <div class="flex gap-2 justify-end">
                                    <ActionButton verbe="neutre" libelle="Annuler" @action="edition = null" />
                                    <ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrerConstat(c)" />
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <p v-if="!s.groupes.length" class="text-sm text-gray-500 mt-2">Aucun constat dans cette section.</p>

                <div class="mt-3">
                    <ActionButton verbe="neutre" libelle="＋ Ajouter un constat" @action="ajoutSection = ajoutSection === s.section ? null : s.section" />
                    <div v-if="ajoutSection === s.section" class="mt-2 space-y-2 text-sm">
                        <textarea v-model="nouveauConstat.texte" rows="3" placeholder="Valeur + unité + année + source ; une évolution avec ses deux bornes."
                                  class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Texte du nouveau constat"></textarea>
                        <input v-model="nouveauConstat.groupe" type="text" maxlength="200" placeholder="Sous-titre (facultatif)"
                               class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Sous-titre du nouveau constat" />
                        <fieldset>
                            <legend class="text-xs text-gray-500 mb-1">Sources citées</legend>
                            <label v-for="src in sources" :key="src.id" class="flex items-start gap-1.5 text-xs">
                                <input v-model="nouveauConstat.sources" type="checkbox" :value="src.id" class="rounded mt-0.5" />
                                {{ src.producteur }} — {{ src.titre }}
                            </label>
                        </fieldset>
                        <div class="text-right"><ActionButton verbe="valider" libelle="Ajouter (à vérifier)" @action="ajouterConstat(s.section)" /></div>
                    </div>
                </div>
            </section>

            <!-- Graphiques -->
            <section v-if="graphiques.length" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold">Graphiques de comparaison européenne</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Le rendu se juge sur le site ; ici, la spécification et l'état des séries. Un
                    graphique n'est publiable que sur des séries validées dans l'écran Eurostat, et
                    rattaché à la phrase qui l'accompagne.
                </p>
                <div v-for="g in graphiques" :key="g.id" class="mt-4 rounded border border-gray-200 dark:border-gray-700 p-3 text-sm space-y-2">
                    <p class="text-xs text-gray-500">{{ listes.types[g.type] ?? g.type }}</p>
                    <input v-model="graphiquesEdit[g.id].titre" type="text" maxlength="300" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 font-medium" aria-label="Titre du graphique" />
                    <input v-model="graphiquesEdit[g.id].sous_titre" type="text" maxlength="300" placeholder="Sous-titre : unité et champ" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Sous-titre du graphique" />
                    <textarea v-model="graphiquesEdit[g.id].note" rows="2" placeholder="Note méthodologique (pied du graphique)" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Note du graphique"></textarea>
                    <label class="block text-xs text-gray-500">Phrase qui accompagne le graphique
                        <select v-model="graphiquesEdit[g.id].constat_id" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                            <option :value="null">— aucune (impubliable)</option>
                            <option v-for="c in constatsEurope" :key="c.id" :value="c.id">{{ c.texte.slice(0, 140) }}{{ c.texte.length > 140 ? '…' : '' }}</option>
                        </select>
                    </label>
                    <ul class="text-xs space-y-1">
                        <li v-for="i in g.indicateurs" :key="i.code">
                            <code>{{ i.code }}</code> —
                            <span :class="['publie', 'a_jour'].includes(i.etat) ? 'text-green-700' : 'text-amber-700'">{{ libelleEtat[i.etat] ?? i.etat }}</span>
                            <span v-for="d in i.dernieres" :key="d.pays" class="text-gray-500"> · {{ d.pays === 'EU27_2020' ? 'UE-27' : d.pays }} {{ nf(d.valeur) }} ({{ d.annee }}{{ d.statut ? ', ' + d.statut : '' }})</span>
                        </li>
                    </ul>
                    <div class="text-right"><ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrerGraphique(g.id)" /></div>
                </div>
                <p class="text-xs mt-3"><Link :href="r('eurostat')" class="text-blue-600 hover:underline">Écran Eurostat →</Link></p>
            </section>

            <!-- Sources -->
            <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold">Sources</h3>
                <div v-for="s in sources" :key="s.id" class="mt-3 rounded border p-3 text-sm space-y-2"
                     :class="s.url_valide && !s.exclue ? 'border-gray-200 dark:border-gray-700' : 'border-red-300 dark:border-red-800'">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <code class="text-xs">{{ s.cle }}</code>
                        <span class="text-xs text-gray-500">
                            citée par {{ s.citations }} constat(s)
                            <span v-if="!s.url_valide" class="text-red-600"> · sans URL valide</span>
                            <span v-if="s.exclue" class="text-red-600"> · domaine exclu</span>
                        </span>
                    </div>
                    <div class="grid md:grid-cols-2 gap-2">
                        <input v-model="sourcesEdit[s.id].producteur" type="text" maxlength="200" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Producteur" />
                        <select v-model="sourcesEdit[s.id].categorie" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Catégorie">
                            <option v-for="(libelle, cle) in listes.categories" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                        <input v-model="sourcesEdit[s.id].titre" type="text" maxlength="500" class="md:col-span-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Titre" />
                        <input v-model="sourcesEdit[s.id].url" type="url" maxlength="1000" placeholder="https://…" class="md:col-span-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="URL" />
                        <input v-model="sourcesEdit[s.id].archive_url" type="url" maxlength="1000" placeholder="Archive (web.archive.org…)" class="md:col-span-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="URL d'archive" />
                        <label class="text-xs text-gray-500">Publication <input v-model="sourcesEdit[s.id].date_publication" type="date" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" /></label>
                        <label class="text-xs text-gray-500">Consultation <input v-model="sourcesEdit[s.id].date_consultation" type="date" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" /></label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <a v-if="s.url_valide" :href="s.url" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline self-center">Ouvrir ↗</a>
                        <ActionButton verbe="supprimer" libelle="Supprimer"
                                      :confirmation="`La source « ${s.cle} » sera retirée des constats qui la citent.`"
                                      @action="router.delete(r('affirmations.sources.destroy', s.id), opts)" />
                        <ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrerSource(s.id)" />
                    </div>
                </div>

                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium">＋ Ajouter une source</summary>
                    <div class="grid md:grid-cols-2 gap-2 text-sm mt-3">
                        <input v-model="nouvelleSource.cle" type="text" maxlength="80" placeholder="Clé (ex. insee-ip2117)" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Clé" />
                        <input v-model="nouvelleSource.producteur" type="text" maxlength="200" placeholder="Producteur" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Producteur" />
                        <input v-model="nouvelleSource.titre" type="text" maxlength="500" placeholder="Titre" class="md:col-span-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Titre" />
                        <input v-model="nouvelleSource.url" type="url" maxlength="1000" placeholder="https://…" class="md:col-span-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="URL" />
                        <select v-model="nouvelleSource.categorie" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Catégorie">
                            <option v-for="(libelle, cle) in listes.categories" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                        <input v-model="nouvelleSource.date_consultation" type="date" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" aria-label="Date de consultation" />
                        <div class="md:col-span-2 text-right"><ActionButton verbe="valider" libelle="Ajouter" @action="ajouterSource" /></div>
                    </div>
                </details>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
