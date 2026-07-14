<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    evenements: Object, // paginator
    statut: String,
});

const filtres = ['detecte', 'en_review', 'a_completer', 'valide', 'tous'];

function filtrer(s) {
    router.get(route('admin.presidentielle.parcours'), { statut: s }, { preserveState: true, replace: true });
}

function agir(evt, action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'parcours', id: evt.id, action },
        { preserveScroll: true });
}

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
                            <th class="p-3">Statut</th>
                            <th class="p-3">Publié</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in evenements.data" :key="e.id" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="p-3 whitespace-nowrap font-medium">{{ nom(e) }}</td>
                            <td class="p-3 text-xs">{{ e.type }}</td>
                            <td class="p-3">{{ e.titre }}<span v-if="e.organisation" class="text-gray-400"> · {{ e.organisation }}</span></td>
                            <td class="p-3 whitespace-nowrap text-xs">{{ e.date_debut?.slice(0,10) ?? '?' }} → {{ e.date_fin?.slice(0,10) ?? 'en cours' }}</td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs">{{ e.statut_validation }}</span></td>
                            <td class="p-3"><span :class="e.affiche_publiquement ? 'text-green-600' : 'text-gray-400'">{{ e.affiche_publiquement ? '✓' : '—' }}</span></td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <button v-if="e.statut_validation !== 'valide'" @click="agir(e, 'valider')" class="px-2 py-1 text-xs rounded bg-blue-600 text-white">Valider</button>
                                <button v-if="e.statut_validation === 'valide' && !e.affiche_publiquement" @click="agir(e, 'publier')" class="px-2 py-1 text-xs rounded bg-green-600 text-white ml-1">Publier</button>
                                <button v-if="e.affiche_publiquement" @click="agir(e, 'depublier')" class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-700 ml-1">Dépublier</button>
                            </td>
                        </tr>
                        <tr v-if="!evenements.data.length">
                            <td colspan="7" class="p-6 text-center text-gray-400">Aucun événement — utiliser « Sync parcours » depuis la page Candidats.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="evenements.links" class="flex flex-wrap gap-1">
                <Link v-for="(l, i) in evenements.links" :key="i" :href="l.url ?? ''"
                    v-html="l.label" class="px-3 py-1 text-sm rounded border"
                    :class="[l.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300', !l.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
