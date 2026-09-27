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
 * Relecture d'un repère chiffré : une question neutre, des constats sourcés, leurs limites.
 * Aucun verdict (le format « Ce qu'on entend », qui en rendait, est abandonné).
 *
 * Le travail se fait phrase par phrase : chaque constat est affiché avec ses sources
 * cliquables juste en dessous, pour qu'on vérifie le chiffre dans la publication du
 * producteur avant de cocher « vérifié ». La fiche n'est publiable que quand toutes les
 * phrases le sont — y compris les limites et les lectures croisées, qui sont nos mots.
 */
const props = defineProps({
    fiche: Object,
    raisons: Array,
    constats: Array,
    sources: Array,
    graphiques: Array,
    themes: Array,
    listes: Object,
});

const entete = reactive({
    question: props.fiche.question ?? '',
    resume: props.fiche.resume ?? '',
    theme_id: props.fiche.theme_id,
    themes_secondaires: [...props.fiche.themes_secondaires],
    derniere_verification: props.fiche.derniere_verification ?? '',
});
const titre = computed(() => props.fiche.question || '(question à formuler)');
const adressePublique = computed(() => {
    const theme = props.themes.find((t) => t.id === props.fiche.theme_id);
    return theme ? `/themes/${theme.slug}/chiffres/${props.fiche.slug}/` : '';
});

const opts = { preserveScroll: true };
const r = (nom, param) => route(`admin.presidentielle.${nom}`, param);

function enregistrerEntete() {
    router.post(r('affirmations.update', props.fiche.id), {
        ...entete,
        question: entete.question.trim() || null,
        derniere_verification: entete.derniere_verification || null,
    }, opts);
}

function agir(action) {
    router.post(r('moderation.action'), { type: 'affirmation', id: props.fiche.id, action }, opts);
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

// ── Reste à faire ────────────────────────────────────────────────────────────────
// Le bandeau ne donne que des totaux : ici, chaque point est nommé, avec ce qu'il reste à
// contrôler et un lien qui y mène. Deux familles, comme dans ReglesAffirmation :
//   - ce qui BLOQUE la publication : les réserves à vérifier (une fiche ne paraît jamais
//     sans elles), et les défauts de ce qui paraîtra (phrase vérifiée sans source, source
//     sans URL, graphique sur une série non relue) ;
//   - ce qui sera MASQUÉ en attendant : les autres phrases non vérifiées, que le site
//     annonce comme « en cours de sourçage » sans les montrer.
const SECTIONS_SOURCEES = ['chiffres', 'complement', 'europe'];
const SECTIONS_INTEGRALES = ['limites'];
const verifie = (c) => c.verification === 'verifie';
const extrait = (t) => (t.length > 150 ? `${t.slice(0, 150)}…` : t);
const resteReserves = computed(() => props.constats.filter((c) => SECTIONS_INTEGRALES.includes(c.section) && !verifie(c)));
const resteMasques = computed(() => props.constats.filter((c) => !SECTIONS_INTEGRALES.includes(c.section) && !verifie(c)));
const resteSansSource = computed(() => props.constats.filter((c) => verifie(c) && SECTIONS_SOURCEES.includes(c.section) && !c.sources.length));
const sourcesAffichees = computed(() => new Set(props.constats.filter(verifie).flatMap((c) => c.sources)));
const resteSources = computed(() => props.sources
    .filter((s) => sourcesAffichees.value.has(s.id) && (!s.url_valide || s.exclue))
    .map((s) => ({ ...s, raison: s.exclue ? 'domaine exclu par le cadre éditorial' : 'URL absente ou invalide' })));
const constatsAffiches = computed(() => new Set(props.constats.filter(verifie).map((c) => c.id)));
const resteGraphiques = computed(() => props.graphiques.flatMap((g) => [
    ...(!g.constat_id ? [{ id: g.id, titre: g.titre, raison: 'rattaché à aucune phrase' }] : []),
    // Un graphique ne paraît qu'avec sa phrase : tant qu'elle est masquée, ses séries n'ont
    // pas à être relues.
    ...(constatsAffiches.value.has(g.constat_id) ? g.indicateurs : [])
        .filter((i) => !['a_jour', 'revision'].includes(i.etat))
        .map((i) => ({ id: g.id, titre: g.titre, raison: `série ${i.code} non validée (écran Eurostat)` })),
]));
const resteBloquant = computed(() => resteReserves.value.length + resteSansSource.value.length
    + resteSources.value.length + resteGraphiques.value.length);
const resteTotal = computed(() => resteBloquant.value + resteMasques.value.length);

const seulementReste = ref(false);
const aFaire = (c) => !verifie(c) || (SECTIONS_SOURCEES.includes(c.section) && !c.sources.length);
function aller(ancre) {
    if (ancre.startsWith('constat-')) seulementReste.value = false;
    requestAnimationFrame(() => {
        const el = document.getElementById(ancre);
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Bref surlignage : sur une fiche de quarante phrases, l'œil doit trouver la bonne.
        el.classList.add('ring-2', 'ring-blue-500');
        setTimeout(() => el.classList.remove('ring-2', 'ring-blue-500'), 2500);
    });
}

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
    <Head :title="`Repère — ${titre}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Repère chiffré</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <FormErrors :errors="$page.props.errors" />

            <!-- En-tête -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <Link :href="r('affirmations')" class="text-xs text-blue-600 hover:underline">← Tous les repères</Link>
                <div class="flex items-start justify-between gap-3 flex-wrap mt-2">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-lg" :class="{ italic: !fiche.question }">{{ titre }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Origine (interne, jamais publiée) : « {{ fiche.enonce }} »</p>
                        <p class="text-sm text-gray-500">
                            {{ themes.find((t) => t.id === fiche.theme_id)?.nom }} · {{ adressePublique }}
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
                                  :confirmation="messagePublication('Ce repère', titre, 'Il paraîtra dans la page du thème.')"
                                  @action="agir('publier')" />
                    <ActionButton v-if="fiche.affiche_publiquement" verbe="depublier" @action="agir('depublier')" />
                </div>
                <p v-if="fiche.affiche_publiquement" class="text-xs text-gray-500 mt-3">
                    Repère publié : une modification qui la rendrait impubliable (réserve remise à
                    vérifier, source sans URL…) sera refusée. Dépubliez d'abord. Une autre phrase
                    remise à vérifier disparaît simplement du site au prochain export.
                </p>
            </div>

            <!-- Reste à faire -->
            <section v-if="resteTotal" id="reste-a-faire" class="rounded-xl border p-4"
                     :class="resteBloquant ? 'border-amber-300 dark:border-amber-800' : 'border-gray-200 dark:border-gray-700'">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="font-semibold">Reste à faire ({{ resteTotal }})</h3>
                    <label class="text-sm flex items-center gap-2">
                        <input v-model="seulementReste" type="checkbox" class="rounded" />
                        N'afficher plus bas que les phrases à traiter
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Pour chaque phrase : ouvrir sa source, retrouver le chiffre, corriger le texte si besoin
                    (« ✎ Modifier »), puis « Marquer vérifié ». Cliquer une ligne y mène.
                </p>

                <h4 class="text-sm font-semibold mt-4" :class="resteBloquant ? 'text-amber-800 dark:text-amber-300' : 'text-green-800 dark:text-green-300'">
                    {{ resteBloquant ? `Bloque la publication (${resteBloquant})` : 'Rien ne bloque la publication' }}
                </h4>

                <div v-if="resteReserves.length" class="mt-2">
                    <p class="text-sm font-medium">Réserves à vérifier ({{ resteReserves.length }})</p>
                    <p class="text-xs text-gray-500">Un repère ne paraît jamais sans toutes ses réserves : en masquer une rendrait les chiffres plus tranchés qu'ils ne le sont.</p>
                    <ol class="mt-1 space-y-1.5 text-sm list-decimal list-inside">
                        <li v-for="c in resteReserves" :key="c.id">
                            <a :href="`#constat-${c.id}`" @click.prevent="aller(`constat-${c.id}`)" class="text-blue-700 dark:text-blue-300 hover:underline">{{ extrait(c.texte) }}</a>
                            <span v-if="c.note_verification" class="block pl-5 text-xs text-amber-800 dark:text-amber-300">À contrôler : {{ c.note_verification }}</span>
                        </li>
                    </ol>
                </div>

                <div v-if="resteSansSource.length" class="mt-3">
                    <p class="text-sm font-medium">Phrases vérifiées, chiffrées, sans source citée ({{ resteSansSource.length }})</p>
                    <p class="text-xs text-gray-500">Ajouter la source (bas de page), puis la cocher dans « ✎ Modifier » ; ou supprimer la phrase.</p>
                    <ol class="mt-1 space-y-1 text-sm list-decimal list-inside">
                        <li v-for="c in resteSansSource" :key="c.id">
                            <a :href="`#constat-${c.id}`" @click.prevent="aller(`constat-${c.id}`)" class="text-blue-700 dark:text-blue-300 hover:underline">{{ extrait(c.texte) }}</a>
                        </li>
                    </ol>
                </div>

                <div v-if="resteSources.length" class="mt-3">
                    <p class="text-sm font-medium">Sources affichées à corriger ({{ resteSources.length }})</p>
                    <ul class="mt-1 space-y-1 text-sm">
                        <li v-for="s in resteSources" :key="s.id">
                            <a :href="`#source-${s.id}`" @click.prevent="aller(`source-${s.id}`)" class="text-blue-700 dark:text-blue-300 hover:underline">{{ s.producteur }} — {{ s.titre }}</a>
                            <span class="text-xs text-red-600"> · {{ s.raison }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="resteGraphiques.length" class="mt-3">
                    <p class="text-sm font-medium">Graphiques ({{ resteGraphiques.length }})</p>
                    <ul class="mt-1 space-y-1 text-sm">
                        <li v-for="(g, k) in resteGraphiques" :key="k">
                            <a :href="`#graphique-${g.id}`" @click.prevent="aller(`graphique-${g.id}`)" class="text-blue-700 dark:text-blue-300 hover:underline">{{ g.titre }}</a>
                            <span class="text-xs text-amber-700"> · {{ g.raison }}</span>
                        </li>
                    </ul>
                </div>

                <template v-if="resteMasques.length">
                    <h4 class="text-sm font-semibold mt-5">Masqué sur le site tant que non vérifié ({{ resteMasques.length }})</h4>
                    <p class="text-xs text-gray-500">
                        Ne bloque pas la publication. Ces phrases, leurs sources et leurs graphiques ne
                        paraissent pas ; le repère annonce « {{ resteMasques.length }} élément(s) en cours de
                        sourçage ». Chacune s'affiche au prochain export après « Marquer vérifié ».
                    </p>
                    <ol class="mt-1 space-y-1.5 text-sm list-decimal list-inside">
                        <li v-for="c in resteMasques" :key="c.id">
                            <a :href="`#constat-${c.id}`" @click.prevent="aller(`constat-${c.id}`)" class="text-blue-700 dark:text-blue-300 hover:underline">
                                <span class="text-xs uppercase tracking-wide text-gray-500">{{ listes.sections[c.section] }}<span v-if="c.groupe"> · {{ c.groupe }}</span></span>
                                — {{ extrait(c.texte) }}
                            </a>
                            <span v-if="c.note_verification" class="block pl-5 text-xs text-amber-800 dark:text-amber-300">À contrôler : {{ c.note_verification }}</span>
                        </li>
                    </ol>
                </template>
            </section>
            <p v-else-if="!raisons.length" class="rounded-xl border border-green-300 dark:border-green-800 p-3 text-sm text-green-800 dark:text-green-300">
                Toutes les phrases sont vérifiées et sourcées : le repère peut être validé puis publié.
            </p>

            <!-- Cadrage -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-medium text-sm">✎ Question, résumé, thèmes</summary>
                <div class="grid md:grid-cols-2 gap-3 text-sm mt-4">
                    <div class="md:col-span-2">
                        <label for="f-question" class="block text-xs text-gray-500 mb-1">Question — le titre public, neutre, terminé par « ? » (ex. « Combien d'immigrés vivent en France ? »)</label>
                        <input id="f-question" v-model="entete.question" type="text" maxlength="300" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="f-resume" class="block text-xs text-gray-500 mb-1">Résumé (une ligne dans la page thème, description des moteurs) — descriptif, sans conclusion</label>
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
                    <div class="md:col-span-2 text-right">
                        <ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrerEntete" />
                    </div>
                </div>
            </details>

            <!-- Constats -->
            <section v-for="s in parSection" :key="s.section" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold">{{ s.libelle }}</h3>
                <div v-for="g in s.groupes" :key="g.nom" class="mt-3">
                    <p v-if="g.nom" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ g.nom }}</p>
                    <ul class="space-y-2">
                        <li v-for="c in g.constats" :key="c.id" v-show="!seulementReste || aFaire(c)" :id="`constat-${c.id}`"
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
                <div v-for="g in graphiques" :key="g.id" :id="`graphique-${g.id}`" class="mt-4 rounded border border-gray-200 dark:border-gray-700 p-3 text-sm space-y-2">
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
                <div v-for="s in sources" :key="s.id" :id="`source-${s.id}`" class="mt-3 rounded border p-3 text-sm space-y-2"
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
