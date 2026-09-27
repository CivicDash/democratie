<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';
import { messagePublication } from '@/composables/useModerationAction';

/**
 * Repères chiffrés des pages thèmes (ex-« Ce qu'on entend ») : une question neutre, des
 * constats sourcés, leurs limites — aucun verdict. La liste dit l'état de vérification de
 * chaque repère et la couverture des thèmes.
 *
 * Une fiche peut paraître avant que tous ses chiffres soient sourcés : les phrases non
 * vérifiées restent masquées, et le site les annonce « en cours de sourçage ». Ce qui
 * bloque : une réserve non vérifiée, ou un défaut dans ce qui paraîtra (phrase vérifiée
 * sans source, source sans URL). Le détail, phrase par phrase, est en tête de chaque fiche.
 */
const props = defineProps({
    fiches: Array,
    couverture: Array,
});
const titre = (f) => f.question || '(question à formuler)';

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
    <Head title="Repères chiffrés — présidentielle 2027" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Repères chiffrés</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Des questions neutres posées dans les pages thèmes, auxquelles répondent des chiffres
                sourcés et leurs limites. Aucun verdict : le site décrit, le lecteur juge. Seules les
                phrases vérifiées dans leur source paraissent ; les autres sont annoncées « en cours
                de sourçage ». Un repère ne paraît jamais sans toutes ses réserves (« Ce que les
                chiffres ne disent pas »).
            </p>

            <FormErrors :errors="$page.props.errors" />

            <!-- Import -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-medium">📥 Importer des repères (contrat presidentielle.affirmations.v2)</summary>
                <div class="mt-4 space-y-3 text-sm">
                    <input type="file" accept="application/json,.json"
                           @change="envoi.fichier = $event.target.files[0] ?? null"
                           class="block text-sm" aria-label="Fichier JSON à importer" />
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="envoi.remplacer" type="checkbox" class="rounded" />
                        Remplacer les repères non publiés déjà en base — les corrections faites ici seraient perdues
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
                            <th class="p-3 font-medium">Repère</th>
                            <th class="p-3 font-medium whitespace-nowrap">Thème</th>
                            <th class="p-3 font-medium text-right whitespace-nowrap">À vérifier</th>
                            <th class="p-3 font-medium text-right whitespace-nowrap" title="Parmi les phrases vérifiées, donc affichées">Chiffres sans source</th>
                            <th class="p-3 font-medium">Statut</th>
                            <th class="p-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="f in fiches" :key="f.id" class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3">
                                <Link :href="route('admin.presidentielle.affirmations.show', f.id)"
                                      class="font-medium text-blue-700 dark:text-blue-300 hover:underline"
                                      :class="{ 'italic': !f.question }">
                                    {{ titre(f) }}
                                </Link>
                                <p class="text-xs text-gray-500 mt-1">Origine (interne) : « {{ f.enonce }} »</p>
                                <ul v-if="f.raisons.length" class="text-xs text-amber-700 dark:text-amber-400 mt-1 list-disc list-inside">
                                    <li v-for="r in f.raisons" :key="r">{{ r }}</li>
                                </ul>
                                <Link v-if="f.raisons.length" :href="`${route('admin.presidentielle.affirmations.show', f.id)}#reste-a-faire`"
                                      class="text-xs text-blue-700 dark:text-blue-300 hover:underline">
                                    Voir la liste de ce qui reste à faire →
                                </Link>
                            </td>
                            <td class="p-3 whitespace-nowrap">{{ f.theme ?? '—' }}</td>
                            <td class="p-3 text-right" :class="f.reserves_a_verifier ? 'text-amber-600 font-medium' : (f.a_verifier ? 'text-gray-600 dark:text-gray-400' : 'text-green-700')">
                                <Link :href="`${route('admin.presidentielle.affirmations.show', f.id)}#reste-a-faire`" class="hover:underline"
                                      :title="`${f.a_verifier} phrase(s) sur ${f.constats} restent à vérifier`">
                                    {{ f.a_verifier }} / {{ f.constats }}
                                </Link>
                                <span v-if="f.reserves_a_verifier" class="block text-xs">dont {{ f.reserves_a_verifier }} réserve(s) — bloquant</span>
                                <span v-else-if="f.a_verifier" class="block text-xs">masquées à la publication</span>
                            </td>
                            <td class="p-3 text-right" :class="f.chiffres_sans_source || f.sources_sans_url ? 'text-amber-600 font-medium' : ''">
                                {{ f.chiffres_sans_source }}
                                <span v-if="f.sources_sans_url" class="block text-xs">+ {{ f.sources_sans_url }} source(s) sans URL</span>
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
                                                  :confirmation="messagePublication('Ce repère', titre(f), 'Il paraîtra dans la page du thème.')"
                                                  @action="agir(f.id, 'publier')" />
                                    <ActionButton v-if="f.affiche_publiquement" verbe="depublier" @action="agir(f.id, 'depublier')" />
                                    <ActionButton v-if="!f.affiche_publiquement" verbe="supprimer"
                                                  :confirmation="`Le repère « ${titre(f)} » sera supprimé (récupérable en base).`"
                                                  @action="agir(f.id, 'supprimer')" />
                                </div>
                                <ModerationLog type="affirmation" :id="f.id" />
                            </td>
                        </tr>
                        <tr v-if="!fiches.length">
                            <td colspan="6" class="p-6 text-center text-sm text-gray-500">
                                Aucun repère. Importez un fichier ci-dessus.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Couverture des thèmes -->
            <section class="rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 p-4">
                <h3 class="font-semibold">Couverture des thèmes</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Chaque thème doit avoir ses repères, selon le même critère : les indicateurs de
                    référence de la statistique publique sur l'objet du thème, et tout indicateur
                    invoqué par au moins deux candidats dans leurs mesures publiées. Un thème sans
                    repère se voit ici.
                </p>
                <div class="overflow-x-auto mt-3">
                    <table class="w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-2 font-medium">Thème</th>
                                <th class="p-2 font-medium text-right">Publiés</th>
                                <th class="p-2 font-medium text-right">En préparation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in couverture" :key="c.theme" class="border-t border-gray-200 dark:border-gray-700">
                                <td class="p-2" :class="{ 'text-amber-700 dark:text-amber-400': !c.publies && !c.en_preparation }">{{ c.theme }}</td>
                                <td class="p-2 text-right tabular-nums">{{ c.publies }}</td>
                                <td class="p-2 text-right tabular-nums">{{ c.en_preparation }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
