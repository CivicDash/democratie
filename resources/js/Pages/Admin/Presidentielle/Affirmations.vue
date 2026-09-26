<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';
import { messagePublication } from '@/composables/useModerationAction';

/**
 * « Ce qu'on entend » : les fiches, leur état de vérification, et le contrôle de symétrie.
 *
 * Deux chiffres disent si une fiche est prête : les constats encore à vérifier, et les
 * sources sans URL. Tant qu'ils ne sont pas à zéro, la fiche reste impubliable.
 */
const props = defineProps({
    fiches: Array,
    symetrie: Object,
    verdicts: Object,
    colorations: Object,
});

const vueSymetrie = ref('toutes');
const familles = [
    ['confirme', 'Confirmé / plutôt confirmé'],
    ['nuance', 'Nuancé'],
    ['infirme', 'Plutôt infirmé / infirmé'],
    ['inverifiable', 'Invérifiable'],
];

const envoi = reactive({ fichier: null, remplacer: false });

function importer(apercu) {
    router.post(route('admin.presidentielle.affirmations.import'), {
        fichier: envoi.fichier, remplacer: envoi.remplacer ? 1 : 0, apercu: apercu ? 1 : 0,
    }, { preserveScroll: true, forceFormData: true });
}

function agir(id, action) {
    router.post(route('admin.presidentielle.moderation.action'), { type: 'affirmation', id, action }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Ce qu'on entend — présidentielle 2027" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Ce qu'on entend</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Des affirmations entendues dans le débat public, confrontées aux données. C'est le
                seul endroit du site où nous rendons un verdict : il ne porte que sur la partie
                mesurable, et une fiche n'est publiable que lorsque chacune de ses phrases a été
                vérifiée dans sa source.
            </p>

            <FormErrors :errors="$page.props.errors" />

            <!-- Import -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-medium">📥 Importer des fiches (contrat presidentielle.affirmations.v1)</summary>
                <div class="mt-4 space-y-3 text-sm">
                    <input type="file" accept="application/json,.json"
                           @change="envoi.fichier = $event.target.files[0] ?? null"
                           class="block text-sm" aria-label="Fichier JSON à importer" />
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="envoi.remplacer" type="checkbox" class="rounded" />
                        Remplacer les fiches non publiées déjà en base — les corrections faites ici seraient perdues
                    </label>
                    <div class="flex gap-2">
                        <ActionButton verbe="neutre" libelle="Contrôler sans importer" :disabled="!envoi.fichier" @action="importer(true)" />
                        <ActionButton verbe="valider" libelle="Importer" :disabled="!envoi.fichier" @action="importer(false)" />
                    </div>
                    <p class="text-xs text-gray-500">
                        Tout arrive en « détecté », non publié. Après l'import, c'est ce back-office qui
                        fait foi, plus le dossier Markdown.
                    </p>
                </div>
            </details>

            <!-- Liste -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3 font-medium">Affirmation</th>
                            <th class="p-3 font-medium whitespace-nowrap">Thème</th>
                            <th class="p-3 font-medium text-right whitespace-nowrap">À vérifier</th>
                            <th class="p-3 font-medium text-right whitespace-nowrap">Sources sans URL</th>
                            <th class="p-3 font-medium">Statut</th>
                            <th class="p-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="f in fiches" :key="f.id" class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3">
                                <Link :href="route('admin.presidentielle.affirmations.show', f.id)"
                                      class="font-medium text-blue-700 dark:text-blue-300 hover:underline">
                                    « {{ f.enonce }} »
                                </Link>
                                <ul class="text-xs text-gray-600 dark:text-gray-400 mt-1 space-y-0.5">
                                    <li v-for="(v, i) in f.verdicts" :key="i">
                                        {{ verdicts[v.verdict] ?? v.verdict }}<span v-if="v.portee"> — {{ v.portee }}</span>
                                    </li>
                                </ul>
                                <ul v-if="f.raisons.length" class="text-xs text-amber-700 dark:text-amber-400 mt-1 list-disc list-inside">
                                    <li v-for="r in f.raisons" :key="r">{{ r }}</li>
                                </ul>
                            </td>
                            <td class="p-3 whitespace-nowrap">{{ f.theme ?? '—' }}</td>
                            <td class="p-3 text-right" :class="f.a_verifier ? 'text-amber-600 font-medium' : 'text-green-700'">
                                {{ f.a_verifier }} / {{ f.constats }}
                            </td>
                            <td class="p-3 text-right" :class="f.sources_sans_url ? 'text-amber-600 font-medium' : ''">
                                {{ f.sources_sans_url }}
                            </td>
                            <td class="p-3 space-x-1 whitespace-nowrap">
                                <StatusBadge :statut="f.statut_validation" />
                                <StatusBadge v-if="f.affiche_publiquement" publie />
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1 flex-wrap">
                                    <ActionButton v-if="f.statut_validation !== 'valide'" verbe="valider" @action="agir(f.id, 'valider')" />
                                    <ActionButton v-if="f.statut_validation === 'valide' && !f.affiche_publiquement"
                                                  verbe="publier"
                                                  :disabled="f.raisons.length > 0"
                                                  :titre="f.raisons.join(' · ') || null"
                                                  :confirmation="messagePublication('Cette fiche', f.enonce, 'Son verdict engage la plateforme : il sera lu comme notre conclusion.')"
                                                  @action="agir(f.id, 'publier')" />
                                    <ActionButton v-if="f.affiche_publiquement" verbe="depublier" @action="agir(f.id, 'depublier')" />
                                    <ActionButton v-if="!f.affiche_publiquement" verbe="supprimer"
                                                  :confirmation="`La fiche « ${f.enonce} » sera supprimée (récupérable en base).`"
                                                  @action="agir(f.id, 'supprimer')" />
                                </div>
                                <ModerationLog type="affirmation" :id="f.id" />
                            </td>
                        </tr>
                        <tr v-if="!fiches.length">
                            <td colspan="6" class="p-6 text-center text-sm text-gray-500">
                                Aucune fiche. Importez le dossier converti ci-dessus.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Contrôle de symétrie (annexe C) -->
            <section class="rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 p-4">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="font-semibold">Contrôle de symétrie</h3>
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Interne — jamais publié</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Pour chaque coloration <em>perçue</em> dans le débat public, les verdicts rendus.
                    Ce tableau sert à voir un déséquilibre et à le dire — pas à le corriger en forçant
                    des verdicts que les données ne donnent pas. Il indique aussi où chercher les
                    prochaines affirmations.
                </p>
                <div class="flex gap-2 mt-3" role="group" aria-label="Périmètre">
                    <button v-for="[cle, libelle] in [['toutes', 'Toutes les fiches'], ['publiees', 'Fiches publiées']]" :key="cle"
                            type="button" :aria-pressed="vueSymetrie === cle" @click="vueSymetrie = cle"
                            :class="['px-3 py-1 text-sm rounded', vueSymetrie === cle ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600']">
                        {{ libelle }}
                    </button>
                </div>
                <div class="overflow-x-auto mt-3">
                    <table class="w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-2 font-medium">Coloration perçue</th>
                                <th class="p-2 font-medium text-right">Fiches</th>
                                <th v-for="[cle, libelle] in familles" :key="cle" class="p-2 font-medium text-right">{{ libelle }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="l in symetrie[vueSymetrie]" :key="l.coloration" class="border-t border-gray-200 dark:border-gray-700 align-top">
                                <td class="p-2">
                                    {{ l.libelle }}
                                    <details v-if="l.detail.length" class="text-xs text-gray-500 mt-1">
                                        <summary class="cursor-pointer">détail</summary>
                                        <ul class="mt-1 space-y-0.5">
                                            <li v-for="d in l.detail" :key="d.enonce">
                                                « {{ d.enonce }} » : {{ d.verdicts.map((v) => verdicts[v] ?? v).join(' ; ') }}
                                            </li>
                                        </ul>
                                    </details>
                                </td>
                                <td class="p-2 text-right">{{ l.fiches }}</td>
                                <td v-for="[cle] in familles" :key="cle" class="p-2 text-right tabular-nums">{{ l.familles[cle] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Chaque verdict compte : une fiche à double verdict apparaît dans deux colonnes.
                </p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
