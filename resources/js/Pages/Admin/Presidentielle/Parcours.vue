<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { useModerationAction, messagePublication } from '@/composables/useModerationAction';

const props = defineProps({
    evenements: Object, // paginator
    statut: String,
});

const filtres = ['detecte', 'en_review', 'a_completer', 'valide', 'tous'];

function filtrer(s) {
    router.get(route('admin.presidentielle.parcours'), { statut: s }, { preserveState: true, replace: true });
}

const { agir } = useModerationAction('parcours');

function nom(e) {
    return e.personne_politique ? `${e.personne_politique.prenom} ${e.personne_politique.nom}` : '—';
}
</script>

<template>
    <Head title="Parcours — présidentielle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Événements de parcours</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-4">
            <p class="text-sm text-gray-500">
                Événements importés depuis les données CivicDash (bouton « Sync parcours » sur la page Candidats)
                ou saisis. Vérifier dates et intitulés avant validation + publication.
            </p>

            <div class="flex gap-2 flex-wrap">
                <button v-for="s in filtres" :key="s" @click="filtrer(s)"
                    class="px-3 py-1 rounded-full text-sm border"
                    :class="statut === s ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">
                    {{ s }}
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3">Personne</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Fonction / mandat</th>
                            <th class="p-3">Période</th>
                            <th scope="col" class="p-3">Statut</th>
                            <th scope="col" class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in evenements.data" :key="e.id" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="p-3 whitespace-nowrap font-medium">{{ nom(e) }}</td>
                            <td class="p-3 text-xs">{{ e.type }}</td>
                            <td class="p-3">{{ e.titre }}<span v-if="e.organisation" class="text-gray-400"> · {{ e.organisation }}</span></td>
                            <td class="p-3 whitespace-nowrap text-xs">{{ e.date_debut?.slice(0,10) ?? '?' }} → {{ e.date_fin?.slice(0,10) ?? 'en cours' }}</td>
                            <td class="p-3 space-x-1">
                                <StatusBadge :statut="e.statut_validation" />
                                <StatusBadge v-if="e.affiche_publiquement" publie />
                            </td>
                            <td class="p-3 text-right whitespace-nowrap space-x-1">
                                <ActionButton v-if="e.statut_validation !== 'valide'"
                                              verbe="valider" @action="agir(e.id, 'valider')" />
                                <ActionButton v-if="e.statut_validation === 'valide' && !e.affiche_publiquement"
                                              verbe="publier"
                                              titre-confirmation="Publier cette étape de parcours ?"
                                              :confirmation="messagePublication('Cette étape du parcours de ' + nom(e), e.titre)"
                                              @action="agir(e.id, 'publier')" />
                                <ActionButton v-if="e.affiche_publiquement"
                                              verbe="depublier" @action="agir(e.id, 'depublier')" />
                            </td>
                        </tr>
                        <tr v-if="!evenements.data.length">
                            <td colspan="6" class="p-6 text-center text-gray-400">Aucun événement — utiliser « Sync parcours » depuis la page Candidats.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="evenements.links" :links="evenements.links" />
        </div>
    </AuthenticatedLayout>
</template>
