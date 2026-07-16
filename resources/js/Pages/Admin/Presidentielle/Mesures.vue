<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    mesures: Object, // paginator
    statut: String,
});

const filtres = ['detecte', 'en_review', 'a_completer', 'valide', 'publie', 'tous'];

function filtrer(s) {
    router.get(route('admin.presidentielle.mesures'), { statut: s }, { preserveState: true, replace: true });
}

function agir(mesure, action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'mesure', id: mesure.id, action },
        { preserveScroll: true });
}

function supprimer(mesure) {
    if (!confirm('Supprimer cette mesure ?\n\nSa proposition d’origine reviendra en file de tri, ce qui permet ensuite de supprimer le discours. La mesure est archivée (soft-delete, réversible).')) return;
    agir(mesure, 'supprimer');
}

function nomCandidat(m) {
    return m.candidat?.personne_politique
        ? `${m.candidat.personne_politique.prenom} ${m.candidat.personne_politique.nom}`
        : '—';
}
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
                            <th class="p-3">Candidat</th>
                            <th class="p-3">Thème</th>
                            <th class="p-3">Mesure</th>
                            <th class="p-3">Pour / Contre</th>
                            <th class="p-3">Statut</th>
                            <th class="p-3">Publié</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in mesures.data" :key="m.id" class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3 whitespace-nowrap">{{ nomCandidat(m) }}</td>
                            <td class="p-3 whitespace-nowrap">{{ m.theme?.nom ?? '—' }}</td>
                            <td class="p-3 max-w-md">
                                {{ m.titre }}
                                <span v-if="m.est_mise_en_avant" title="Mesure phare (comparateur + quiz)" class="ml-1">⭐</span>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <Link :href="route('admin.presidentielle.mesures.arguments', m.id)" class="text-blue-600 hover:underline">
                                    <span :class="m.pour_count ? 'text-green-600' : 'text-gray-400'">{{ m.pour_count }} pour</span> /
                                    <span :class="m.contre_count ? 'text-green-600' : 'text-red-500'">{{ m.contre_count }} contre</span> ✎
                                </Link>
                            </td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs">{{ m.statut_validation }}</span></td>
                            <td class="p-3">
                                <span :class="m.affiche_publiquement ? 'text-green-600' : 'text-gray-400'">
                                    {{ m.affiche_publiquement ? '✓' : '—' }}
                                </span>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <button v-if="m.statut_validation !== 'valide'" @click="agir(m, 'valider')"
                                    class="px-2 py-1 text-xs rounded bg-blue-600 text-white">Valider</button>
                                <button v-if="m.statut_validation === 'valide' && !m.affiche_publiquement" @click="agir(m, 'publier')"
                                    class="px-2 py-1 text-xs rounded bg-green-600 text-white ml-1">Publier</button>
                                <button v-if="m.affiche_publiquement" @click="agir(m, 'depublier')"
                                    class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-700 ml-1">Dépublier</button>
                                <button @click="agir(m, m.est_mise_en_avant ? 'retirer_en_avant' : 'mettre_en_avant')"
                                    :title="'Mesure phare : priorité au comparateur + question du quiz'"
                                    class="px-2 py-1 text-xs rounded ml-1"
                                    :class="m.est_mise_en_avant ? 'bg-yellow-400 text-yellow-900' : 'border border-gray-300 text-gray-500'">
                                    {{ m.est_mise_en_avant ? '★ Phare' : '☆ Phare' }}
                                </button>
                                <button v-if="!m.affiche_publiquement" @click="supprimer(m)"
                                    title="Supprimer la mesure (sa proposition revient en file ; réversible)"
                                    class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 ml-1">Supprimer</button>
                            </td>
                        </tr>
                        <tr v-if="!mesures.data.length">
                            <td colspan="7" class="p-6 text-center text-gray-400">Aucune mesure.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="mesures.links" class="flex flex-wrap gap-1">
                <Link v-for="(l, i) in mesures.links" :key="i" :href="l.url ?? ''"
                    v-html="l.label" class="px-3 py-1 text-sm rounded border"
                    :class="[l.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300', !l.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
