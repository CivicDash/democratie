<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    candidats: Object, // paginator
    statut: String,
});

const filtres = ['tous', 'detecte', 'en_review', 'a_completer', 'valide'];

function filtrer(s) {
    router.get(route('admin.presidentielle.candidats'), { statut: s }, { preserveState: true, replace: true });
}

function agir(candidat, action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'candidat', id: candidat.id, action },
        { preserveScroll: true });
}

function nom(c) {
    return c.personne_politique ? `${c.personne_politique.prenom} ${c.personne_politique.nom}` : '—';
}
</script>

<template>
    <Head title="Candidats — présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Candidats 2027</h2>
                <nav class="text-sm space-x-3">
                    <Link :href="route('admin.presidentielle.moderation')" class="text-blue-600 hover:underline">Tableau de bord</Link>
                    <Link :href="route('admin.presidentielle.medias')" class="text-blue-600 hover:underline">Médias</Link>
                </nav>
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-4">
            <p class="text-sm text-gray-500">
                Vérifier chaque fiche (identité, parti, date et source de déclaration) avant de valider puis publier.
                Une fois publiée, la fiche apparaît sur objectif2027.fr au prochain rebuild automatique.
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
                            <th class="p-3">Candidat</th>
                            <th class="p-3">Parti</th>
                            <th class="p-3">Nuance</th>
                            <th class="p-3">Déclaration</th>
                            <th class="p-3">Statut</th>
                            <th class="p-3">Publié</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in candidats.data" :key="c.id" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="p-3 whitespace-nowrap font-medium">
                                <span class="inline-block w-2.5 h-2.5 rounded-full mr-1.5" :style="{ background: c.couleur_hex || '#94a3b8' }"></span>
                                {{ nom(c) }}
                            </td>
                            <td class="p-3">{{ c.parti_soutien }}</td>
                            <td class="p-3">{{ c.nuance_politique }}</td>
                            <td class="p-3 whitespace-nowrap">{{ c.date_declaration?.slice(0, 10) ?? '—' }}</td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs">{{ c.statut_validation }}</span></td>
                            <td class="p-3">
                                <span :class="c.affiche_publiquement ? 'text-green-600' : 'text-gray-400'">{{ c.affiche_publiquement ? '✓' : '—' }}</span>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <button v-if="c.statut_validation !== 'valide'" @click="agir(c, 'valider')"
                                    class="px-2 py-1 text-xs rounded bg-blue-600 text-white">Valider</button>
                                <button v-if="c.statut_validation === 'valide' && !c.affiche_publiquement" @click="agir(c, 'publier')"
                                    class="px-2 py-1 text-xs rounded bg-green-600 text-white ml-1">Publier</button>
                                <button v-if="c.affiche_publiquement" @click="agir(c, 'depublier')"
                                    class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-700 ml-1">Dépublier</button>
                            </td>
                        </tr>
                        <tr v-if="!candidats.data.length">
                            <td colspan="7" class="p-6 text-center text-gray-400">Aucun candidat.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="candidats.links" class="flex flex-wrap gap-1">
                <Link v-for="(l, i) in candidats.links" :key="i" :href="l.url ?? ''"
                    v-html="l.label" class="px-3 py-1 text-sm rounded border"
                    :class="[l.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300', !l.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
