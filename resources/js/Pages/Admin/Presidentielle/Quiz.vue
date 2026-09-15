<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import QueueSearch from '@/Components/Admin/QueueSearch.vue';
import Pagination from '@/Components/Pagination.vue';
import { messagePublication } from '@/composables/useModerationAction';

const props = defineProps({
    questions: Object,
    statut: String,
    q: String,
    themes: Array,
    formats: Object,
    controverses: Array,
});

const filtres = ['tous', 'detecte', 'valide', 'publie'];

const nouvelle = reactive({
    theme_id: '', format: 'arbitrage', intitule: '', precision_contexte: '', controverse_id: '',
});

// Une controverse porte déjà un intitulé neutre passé en modération. On le propose en
// amorce — sans le figer : tous ne sont pas interrogatifs (« Hausse du SMIC de plusieurs
// centaines d'euros ») et un intitulé de quiz doit poser une question.
function choisirControverse(id) {
    const c = props.controverses.find((x) => String(x.id) === String(id));
    if (!c) return;
    if (!nouvelle.intitule) nouvelle.intitule = c.titre;
    if (!nouvelle.theme_id && c.theme_id) nouvelle.theme_id = c.theme_id;
}

function creer() {
    router.post(route('admin.presidentielle.quiz.store'), {
        ...nouvelle,
        controverse_id: nouvelle.controverse_id || null,
    }, { preserveScroll: true });
}

function agir(id, action) {
    router.post(route('admin.presidentielle.moderation.action'), {
        type: 'quiz_question', id, action,
    }, { preserveScroll: true });
}

function filtrer(s) {
    router.get(route('admin.presidentielle.quiz'), { statut: s, q: props.q || undefined },
        { preserveState: true, replace: true });
}

const erreurs = () => usePage().props.errors ?? {};

const premiereErreur = computed(() => Object.values(erreurs())[0] ?? null);
</script>

<template>
    <Head title="Quiz — présidentielle 2027" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Questions du quiz</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Une question porte un intitulé neutre ; ses options sont les positions
                réellement défendues, chacune adossée à des mesures publiées. Ce sont les
                mesures qui portent les candidats — une position commune à trois d'entre eux
                les crédite tous les trois.
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex gap-2">
                    <button v-for="f in filtres" :key="f" type="button" @click="filtrer(f)"
                            :class="['px-3 py-1 text-sm rounded',
                                     statut === f ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700']">
                        {{ f }}
                    </button>
                </div>
                <QueueSearch :valeur="q" route-nom="admin.presidentielle.quiz"
                             :params="{ statut }" placeholder="Rechercher un intitulé…"
                             label="Rechercher une question" :total="questions.total" />
            </div>

            <!-- Création -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-medium">➕ Ajouter une question</summary>

                <div class="grid md:grid-cols-2 gap-3 text-sm mt-4">
                    <div>
                        <label for="q-controverse" class="block text-xs text-gray-500 mb-1">
                            Reprendre une controverse (facultatif)
                        </label>
                        <select id="q-controverse" v-model="nouvelle.controverse_id"
                                @change="choisirControverse(nouvelle.controverse_id)"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            <option value="">—</option>
                            <option v-for="c in controverses" :key="c.id" :value="c.id">{{ c.titre }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="q-theme" class="block text-xs text-gray-500 mb-1">Thème</label>
                        <select id="q-theme" v-model="nouvelle.theme_id"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            <option value="">—</option>
                            <option v-for="t in themes" :key="t.id" :value="t.id">{{ t.nom }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="q-format" class="block text-xs text-gray-500 mb-1">Format</label>
                        <select id="q-format" v-model="nouvelle.format"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            <option v-for="(libelle, clef) in formats" :key="clef" :value="clef">{{ libelle }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="q-intitule" class="block text-xs text-gray-500 mb-1">
                            Intitulé — neutre, et de préférence interrogatif
                        </label>
                        <input id="q-intitule" v-model="nouvelle.intitule" type="text" maxlength="500"
                               placeholder="Allègements de cotisations : réduire, maintenir ou rétablir ?"
                               class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                        <p class="text-xs text-gray-400 mt-1">{{ nouvelle.intitule.length }}/500</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="q-precision" class="block text-xs text-gray-500 mb-1">
                            Précision de contexte (facultative)
                        </label>
                        <textarea id="q-precision" v-model="nouvelle.precision_contexte" rows="2" maxlength="2000"
                                  class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-3">
                    <p v-if="premiereErreur" class="text-sm text-red-600">{{ premiereErreur }}</p>
                    <ActionButton verbe="valider" libelle="Créer la question" taille="md" @action="creer" />
                </div>
            </details>

            <!-- Liste -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3 font-medium">Question</th>
                            <th class="p-3 font-medium whitespace-nowrap">Thème</th>
                            <th class="p-3 font-medium text-right whitespace-nowrap">Options</th>
                            <th class="p-3 font-medium text-right whitespace-nowrap">Candidats</th>
                            <th class="p-3 font-medium">Statut</th>
                            <th class="p-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="qu in questions.data" :key="qu.id"
                            class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3">
                                <Link :href="route('admin.presidentielle.quiz.show', qu.id)"
                                      class="font-medium text-blue-700 dark:text-blue-300 hover:underline">
                                    {{ qu.intitule }}
                                </Link>
                                <div class="text-xs text-gray-500">{{ qu.format_libelle }}</div>
                                <ul v-if="qu.raisons_non_publiable.length"
                                    class="text-xs text-amber-700 dark:text-amber-400 mt-1 list-disc list-inside">
                                    <li v-for="r in qu.raisons_non_publiable" :key="r">{{ r }}</li>
                                </ul>
                            </td>
                            <td class="p-3 whitespace-nowrap">{{ qu.theme ?? '—' }}</td>
                            <td class="p-3 text-right">{{ qu.nb_options }}</td>
                            <!-- À un seul candidat, un arbitrage n'oppose personne : il doit se
                                 repérer sans ouvrir la question. -->
                            <td class="p-3 text-right"
                                :class="qu.format === 'arbitrage' && qu.nb_candidats < 2
                                    ? 'text-amber-600 font-medium' : ''">
                                {{ qu.nb_candidats }}
                            </td>
                            <td class="p-3 space-x-1 whitespace-nowrap">
                                <StatusBadge :statut="qu.statut_validation" />
                                <StatusBadge v-if="qu.affiche_publiquement" publie />
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1 flex-wrap">
                                    <ActionButton v-if="qu.statut_validation !== 'valide'"
                                                  verbe="valider" @action="agir(qu.id, 'valider')" />
                                    <ActionButton v-if="qu.statut_validation === 'valide' && !qu.affiche_publiquement"
                                                  verbe="publier"
                                                  :disabled="qu.raisons_non_publiable.length > 0"
                                                  :titre="qu.raisons_non_publiable.join(' · ') || null"
                                                  :confirmation="messagePublication('Cette question', qu.intitule)"
                                                  @action="agir(qu.id, 'publier')" />
                                    <ActionButton v-if="qu.affiche_publiquement"
                                                  verbe="depublier" @action="agir(qu.id, 'depublier')" />
                                </div>
                                <ModerationLog type="quiz_question" :id="qu.id" />
                            </td>
                        </tr>
                        <tr v-if="!questions.data.length">
                            <td colspan="6" class="p-6 text-center text-sm text-gray-500">
                                Aucune question dans ce filtre. Créez-en une ci-dessus — une
                                controverse déjà publiée fait une bonne amorce.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="questions.links" />
        </div>
    </AuthenticatedLayout>
</template>
