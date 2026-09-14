<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import QueueSearch from '@/Components/Admin/QueueSearch.vue';
import Pagination from '@/Components/Pagination.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import { useModerationAction, messagePublication } from '@/composables/useModerationAction';

const props = defineProps({
    mesures: Object, // paginator
    statut: String,
    q: { type: String, default: '' },
});

const { agir } = useModerationAction('mesure');

const filtres = [
    ['detecte', 'Détectées'],
    ['en_review', "En cours d'examen"],
    ['a_completer', 'À compléter'],
    ['valide', 'Validées'],
    ['publie', 'Publiées'],
    ['tous', 'Toutes'],
];

function filtrer(s) {
    router.get(route('admin.presidentielle.mesures'),
        { statut: s, q: props.q || undefined },
        { preserveState: true, replace: true });
}

function nomCandidat(m) {
    return m.candidat?.personne_politique
        ? `${m.candidat.personne_politique.prenom} ${m.candidat.personne_politique.nom}`
        : '—';
}

const messageSuppression = 'Sa proposition d’origine reviendra en file de tri, ce qui permet '
    + 'ensuite de supprimer le discours. La mesure est archivée : l’action est réversible.';
</script>

<template>
    <Head title="Mesures — présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Mesures de programme</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="flex gap-2 flex-wrap" role="group" aria-label="Filtrer par statut">
                    <button v-for="[valeur, libelle] in filtres" :key="valeur"
                            type="button"
                            :aria-pressed="statut === valeur"
                            @click="filtrer(valeur)"
                            class="px-3 py-1.5 min-h-[36px] rounded-full text-sm border"
                            :class="statut === valeur
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-300'">
                        {{ libelle }}
                    </button>
                </div>

                <QueueSearch id="recherche-mesures"
                             :valeur="q"
                             route-nom="admin.presidentielle.mesures"
                             :params="{ statut }"
                             label="Rechercher une mesure ou un candidat"
                             placeholder="Titre de mesure, nom de candidat…"
                             :total="mesures.total" />
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th scope="col" class="p-3">Candidat</th>
                            <th scope="col" class="p-3">Thème</th>
                            <th scope="col" class="p-3">Mesure</th>
                            <th scope="col" class="p-3">Faits reliés</th>
                            <th scope="col" class="p-3">Statut</th>
                            <th scope="col" class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in mesures.data" :key="m.id"
                            class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3 whitespace-nowrap">{{ nomCandidat(m) }}</td>
                            <td class="p-3 whitespace-nowrap">{{ m.theme?.nom ?? '—' }}</td>
                            <td class="p-3 max-w-md">
                                {{ m.titre }}
                                <span v-if="m.est_mise_en_avant"
                                      class="ml-1 text-xs px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                    Mesure phare
                                </span>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <Link :href="route('admin.presidentielle.mesures.arguments', m.id)"
                                      class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ m.pour_count }} qui l'étayent / {{ m.contre_count }} qui la contredisent
                                </Link>
                            </td>
                            <td class="p-3 space-x-1">
                                <StatusBadge :statut="m.statut_validation" />
                                <StatusBadge v-if="m.affiche_publiquement" publie />
                            </td>
                            <td class="p-3 text-right whitespace-nowrap space-x-1">
                                <ActionButton v-if="m.statut_validation !== 'valide'"
                                              verbe="valider"
                                              @action="agir(m.id, 'valider')" />

                                <ActionButton v-if="m.statut_validation === 'valide' && !m.affiche_publiquement"
                                              verbe="publier"
                                              titre-confirmation="Publier cette mesure ?"
                                              :confirmation="messagePublication('Cette mesure de ' + nomCandidat(m), m.titre)"
                                              @action="agir(m.id, 'publier')" />

                                <ActionButton v-if="m.affiche_publiquement"
                                              verbe="depublier"
                                              @action="agir(m.id, 'depublier')" />

                                <ActionButton verbe="neutre"
                                              :libelle="m.est_mise_en_avant ? 'Retirer des mesures phares' : 'Mettre en mesure phare'"
                                              titre="Une mesure phare est priorisée dans le comparateur et peut devenir une question du quiz"
                                              @action="agir(m.id, m.est_mise_en_avant ? 'retirer_en_avant' : 'mettre_en_avant')" />

                                <ActionButton v-if="!m.affiche_publiquement"
                                              verbe="supprimer"
                                              titre-confirmation="Supprimer cette mesure ?"
                                              :confirmation="messageSuppression"
                                              @action="agir(m.id, 'supprimer')" />

                                <div class="mt-2">
                                    <ModerationLog type="mesure" :id="m.id" />
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!mesures.data.length">
                            <td colspan="6" class="p-8 text-center">
                                <p class="text-gray-600 dark:text-gray-400">
                                    Aucune mesure dans cette file.
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                    Les mesures naissent des propositions : ouvrez la file d'ingestion
                                    et validez une prise de parole vers une mesure.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="mesures.links" :links="mesures.links" />
        </div>
    </AuthenticatedLayout>
</template>
