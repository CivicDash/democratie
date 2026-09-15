<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';
import { messagePublication } from '@/composables/useModerationAction';

/**
 * Construction d'une question de quiz.
 *
 * Le libellé court d'une option est le SEUL texte que nous écrivons nous-mêmes dans tout
 * le quiz — ailleurs, tout est verbatim. Aucune règle automatique ne peut vérifier qu'il
 * ne déforme pas la mesure ni ne reprend le cadrage d'un camp. L'écran sert donc le
 * libellé et les verbatims des mesures rattachées l'un sous l'autre, en continu : c'est
 * le seul contrôle possible sur ce point, et il doit être rendu facile.
 */
const props = defineProps({
    question: Object,
    options: Array,
    themes: Array,
    formats: Object,
});

const entete = reactive({
    intitule: props.question.intitule,
    precision_contexte: props.question.precision_contexte ?? '',
    theme_id: props.question.theme_id,
    format: props.question.format,
});

const nouvelleOption = reactive({ libelle: '' });

function enregistrerEntete() {
    router.post(route('admin.presidentielle.quiz.update', props.question.id), entete, { preserveScroll: true });
}

function ajouterOption() {
    router.post(route('admin.presidentielle.quiz.options.store'), {
        question_id: props.question.id,
        libelle: nouvelleOption.libelle,
    }, { preserveScroll: true, onSuccess: () => { nouvelleOption.libelle = ''; } });
}

function renommerOption(option, libelle) {
    if (!libelle || libelle === option.libelle) return;
    router.post(route('admin.presidentielle.quiz.options.update', option.id), { libelle }, { preserveScroll: true });
}

function supprimerOption(id) {
    router.delete(route('admin.presidentielle.quiz.options.destroy', id), { preserveScroll: true });
}

function detacher(optionId, mesureId) {
    router.delete(route('admin.presidentielle.quiz.options.mesures.detach', optionId), {
        data: { mesure_id: mesureId }, preserveScroll: true,
    });
}

function rattacher(optionId, mesureId) {
    router.post(route('admin.presidentielle.quiz.options.mesures.attach', optionId), {
        mesure_id: mesureId,
    }, { preserveScroll: true });
}

// ── Sélecteur de mesures ────────────────────────────────────────────────────────────
// Recherche serveur et non liste préchargée : le gisement est l'ensemble des mesures
// publiées — plus de cinq cents à ce jour, soit une septantaine de kilo-octets de seuls
// titres. Les inliner répéterait le défaut de la page quiz publique.
const optionActive = ref(null);
const terme = ref('');
const limiterAuTheme = ref(true);
const resultats = ref([]);
const cherche = ref(false);
let minuteur = null;

function ouvrirSelecteur(optionId) {
    optionActive.value = optionActive.value === optionId ? null : optionId;
    terme.value = '';
    resultats.value = [];
    if (optionActive.value) chercher();
}

function chercher() {
    clearTimeout(minuteur);
    minuteur = setTimeout(async () => {
        const params = new URLSearchParams();
        if (terme.value.trim()) params.set('q', terme.value.trim());
        if (limiterAuTheme.value && props.question.theme_id) params.set('theme_id', props.question.theme_id);
        if (![...params.keys()].length) { resultats.value = []; return; }

        cherche.value = true;
        try {
            const r = await fetch(`${route('admin.presidentielle.quiz.mesures.search')}?${params}`, {
                headers: { Accept: 'application/json' },
            });
            resultats.value = r.ok ? (await r.json()).mesures ?? [] : [];
        } catch (_) {
            resultats.value = [];
        } finally {
            cherche.value = false;
        }
    }, 300);
}

watch([terme, limiterAuTheme], chercher);

// Une mesure déjà rattachée ailleurs dans CETTE question reste proposable : deux options
// ne doivent pas la partager, mais c'est au relecteur d'en juger, pas à l'écran.
const dejaRattachees = computed(() => {
    const o = props.options.find((x) => x.id === optionActive.value);
    return new Set((o?.mesures ?? []).map((m) => m.id));
});

const erreurs = () => usePage().props.errors ?? {};
</script>

<template>
    <Head :title="`Quiz — ${question.intitule}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Question du quiz</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <FormErrors :errors="$page.props.errors" />

            <!-- En-tête -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <Link :href="route('admin.presidentielle.quiz')" class="text-xs text-blue-600 hover:underline">
                    ← Toutes les questions
                </Link>

                <div class="flex items-start justify-between gap-3 flex-wrap mt-2">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-lg">{{ question.intitule }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ question.theme }} · {{ question.format_libelle }}
                            <span v-if="question.controverse"> · d'après « {{ question.controverse }} »</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <StatusBadge :statut="question.statut_validation" />
                        <StatusBadge :publie="question.affiche_publiquement" />
                    </div>
                </div>

                <div v-if="question.raisons_non_publiable.length"
                     class="mt-3 rounded border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Pas encore publiable</p>
                    <ul class="text-sm mt-1 list-disc list-inside text-amber-700 dark:text-amber-300">
                        <li v-for="r in question.raisons_non_publiable" :key="r">{{ r }}</li>
                    </ul>
                </div>

                <div class="flex items-center gap-2 mt-3 flex-wrap">
                    <ActionButton v-if="question.statut_validation !== 'valide'"
                                  verbe="valider"
                                  @action="router.post(route('admin.presidentielle.moderation.action'),
                                           { type: 'quiz_question', id: question.id, action: 'valider' },
                                           { preserveScroll: true })" />
                    <ActionButton v-if="question.statut_validation === 'valide' && !question.affiche_publiquement"
                                  verbe="publier"
                                  :disabled="question.raisons_non_publiable.length > 0"
                                  :titre="question.raisons_non_publiable.join(' · ') || null"
                                  :confirmation="messagePublication('Cette question', question.intitule)"
                                  @action="router.post(route('admin.presidentielle.moderation.action'),
                                           { type: 'quiz_question', id: question.id, action: 'publier' },
                                           { preserveScroll: true })" />
                    <ActionButton v-if="question.affiche_publiquement"
                                  verbe="depublier"
                                  @action="router.post(route('admin.presidentielle.moderation.action'),
                                           { type: 'quiz_question', id: question.id, action: 'depublier' },
                                           { preserveScroll: true })" />
                </div>

                <p class="text-xs text-gray-500 mt-3">
                    Une mesure doit être publiée avant d'adosser une option. Ordre :
                    mesures → question.
                </p>
            </div>

            <!-- Édition de l'intitulé -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-medium text-sm">✎ Modifier l'intitulé et le cadrage</summary>
                <div class="grid md:grid-cols-2 gap-3 text-sm mt-4">
                    <div class="md:col-span-2">
                        <label for="e-intitule" class="block text-xs text-gray-500 mb-1">Intitulé</label>
                        <input id="e-intitule" v-model="entete.intitule" type="text" maxlength="500"
                               class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                    </div>
                    <div>
                        <label for="e-theme" class="block text-xs text-gray-500 mb-1">Thème</label>
                        <select id="e-theme" v-model="entete.theme_id"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            <option v-for="t in themes" :key="t.id" :value="t.id">{{ t.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="e-format" class="block text-xs text-gray-500 mb-1">Format</label>
                        <select id="e-format" v-model="entete.format"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            <option v-for="(libelle, clef) in formats" :key="clef" :value="clef">{{ libelle }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="e-precision" class="block text-xs text-gray-500 mb-1">Précision de contexte</label>
                        <textarea id="e-precision" v-model="entete.precision_contexte" rows="2" maxlength="2000"
                                  class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"></textarea>
                    </div>
                    <div class="md:col-span-2 text-right">
                        <ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrerEntete" />
                    </div>
                </div>
            </details>

            <!-- Options -->
            <div v-for="o in options" :key="o.id"
                 class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="min-w-0 flex-1">
                        <label :for="`opt-${o.id}`" class="block text-xs uppercase tracking-wide text-gray-500 mb-1">
                            Libellé proposé au visiteur
                        </label>
                        <input :id="`opt-${o.id}`" :value="o.libelle" type="text" maxlength="300"
                               @change="renommerOption(o, $event.target.value)"
                               class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 font-medium" />
                    </div>
                    <ActionButton verbe="supprimer" libelle="Supprimer l'option"
                                  :confirmation="`L'option « ${o.libelle} » et ses rattachements seront supprimés.`"
                                  @action="supprimerOption(o.id)" />
                </div>

                <!-- L'aide à la relecture : le libellé ci-dessus, les verbatims ci-dessous. -->
                <p class="text-xs uppercase tracking-wide text-gray-500 mt-4 mb-2">
                    Mesures adossées ({{ o.mesures.length }}) — verbatim intégral
                </p>

                <p v-if="!o.mesures.length" class="text-sm text-amber-700 dark:text-amber-400">
                    Aucune mesure. Une option sans mesure publiée derrière serait une position
                    que nous aurions formulée nous-mêmes : la question restera impubliable.
                </p>

                <ul v-else class="space-y-2">
                    <li v-for="m in o.mesures" :key="m.id"
                        class="rounded border border-gray-200 dark:border-gray-700 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm">{{ m.titre }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ m.candidat }} · {{ m.theme }}
                                    <span v-if="!m.affiche_publiquement" class="text-amber-600">
                                        · non publiée
                                    </span>
                                    ·
                                    <Link :href="route('admin.presidentielle.mesures.arguments', m.id)"
                                          class="text-blue-600 hover:underline">argumentaire →</Link>
                                </p>
                            </div>
                            <ActionButton verbe="depublier" libelle="Détacher"
                                          @action="detacher(o.id, m.id)" />
                        </div>
                    </li>
                </ul>

                <!-- Sélecteur -->
                <div class="mt-3">
                    <ActionButton verbe="neutre" libelle="＋ Rattacher une mesure"
                                  @action="ouvrirSelecteur(o.id)" />

                    <div v-if="optionActive === o.id" class="mt-3 rounded border border-gray-200 dark:border-gray-700 p-3">
                        <div class="flex items-center gap-3 flex-wrap">
                            <input v-model="terme" type="search"
                                   :placeholder="`Titre de mesure ou nom de candidat…`"
                                   class="flex-1 min-w-[16rem] rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
                            <label class="text-xs flex items-center gap-1.5">
                                <input v-model="limiterAuTheme" type="checkbox" class="rounded" />
                                Limiter au thème « {{ question.theme }} »
                            </label>
                        </div>

                        <p v-if="cherche" class="text-xs text-gray-500 mt-2">Recherche…</p>
                        <p v-else-if="!resultats.length" class="text-xs text-gray-500 mt-2">
                            Saisissez au moins deux caractères, ou laissez le filtre par thème
                            actif pour parcourir les mesures publiées du thème.
                        </p>

                        <ul v-else class="mt-2 divide-y divide-gray-100 dark:divide-gray-800 max-h-80 overflow-y-auto">
                            <li v-for="m in resultats" :key="m.id"
                                class="py-2 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm">{{ m.titre }}</p>
                                    <p class="text-xs text-gray-500">{{ m.candidat }} · {{ m.theme }}</p>
                                </div>
                                <ActionButton verbe="valider" libelle="Rattacher"
                                              :disabled="dejaRattachees.has(m.id)"
                                              :titre="dejaRattachees.has(m.id) ? 'Déjà rattachée à cette option' : null"
                                              @action="rattacher(o.id, m.id)" />
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Ajout d'option -->
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-4">
                <label for="nouvelle-option" class="block text-xs uppercase tracking-wide text-gray-500 mb-1">
                    Ajouter une option
                </label>
                <div class="flex items-center gap-3 flex-wrap">
                    <input id="nouvelle-option" v-model="nouvelleOption.libelle" type="text" maxlength="300"
                           placeholder="Réduire le dispositif"
                           class="flex-1 min-w-[16rem] rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
                    <ActionButton verbe="valider" libelle="Ajouter" @action="ajouterOption" />
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Court et neutre : c'est ce que le visiteur lit. Le verbatim de la mesure
                    reste affiché dessous pour que l'écart se voie.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
