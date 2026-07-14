<script setup>
import { reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    candidats: Object, // paginator
    statut: String,
});

const filtres = ['tous', 'detecte', 'en_review', 'a_completer', 'valide'];
const statutsCandidature = ['declare', 'pressenti', 'investi', 'parrainages_valides', 'retire', 'elimine_t1'];
const nuances = ['EXG', 'GAU', 'ECO', 'DVG', 'CEN', 'DVD', 'DR', 'EXD', 'DIV', 'REG'];

const nouveau = reactive({
    prenom: '', nom: '', parti: '', nuance: '', statut_candidature: 'declare',
    date_declaration: '', source_url: '', site_campagne_url: '', slogan: '', couleur_hex: '',
});

function ajouter() {
    router.post(route('admin.presidentielle.candidats.store'), { ...nouveau, couleur_hex: nouveau.couleur_hex || null }, {
        preserveScroll: true,
        onSuccess: () => Object.keys(nouveau).forEach((k) => (nouveau[k] = k === 'statut_candidature' ? 'declare' : '')),
    });
}

const erreurs = () => usePage().props.errors ?? {};

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

            <!-- Ajout manuel (ex. nouvelle déclaration de candidature) -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">➕ Ajouter un candidat manuellement</summary>
                <p class="text-xs text-gray-500 mt-2">
                    Entre en file de modération (statut <code>detecte</code>, non publié). Fournir la source de la
                    déclaration : elle sera exigée à la validation.
                </p>
                <form @submit.prevent="ajouter" class="mt-3 grid md:grid-cols-3 gap-3 text-sm">
                    <input v-model="nouveau.prenom" required placeholder="Prénom *" class="rounded border-gray-300 dark:bg-gray-800" />
                    <input v-model="nouveau.nom" required placeholder="Nom *" class="rounded border-gray-300 dark:bg-gray-800" />
                    <input v-model="nouveau.parti" placeholder="Parti / soutien" class="rounded border-gray-300 dark:bg-gray-800" />
                    <select v-model="nouveau.nuance" class="rounded border-gray-300 dark:bg-gray-800">
                        <option value="">Nuance…</option>
                        <option v-for="n in nuances" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <select v-model="nouveau.statut_candidature" class="rounded border-gray-300 dark:bg-gray-800">
                        <option v-for="s in statutsCandidature" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <input v-model="nouveau.date_declaration" type="date" class="rounded border-gray-300 dark:bg-gray-800" />
                    <input v-model="nouveau.source_url" type="url" placeholder="Source de la déclaration (URL)" class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2" />
                    <input v-model="nouveau.site_campagne_url" type="url" placeholder="Site de campagne (URL)" class="rounded border-gray-300 dark:bg-gray-800" />
                    <input v-model="nouveau.slogan" placeholder="Slogan (officiel)" class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2" />
                    <input v-model="nouveau.couleur_hex" placeholder="#2563eb" pattern="#[0-9a-fA-F]{6}" class="rounded border-gray-300 dark:bg-gray-800" />
                    <div class="md:col-span-3 flex items-center justify-between">
                        <p v-if="Object.keys(erreurs()).length" class="text-xs text-red-600">{{ Object.values(erreurs())[0] }}</p>
                        <button type="submit" class="ml-auto px-4 py-2 rounded bg-blue-600 text-white text-sm">Ajouter (detecte)</button>
                    </div>
                </form>
            </details>

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
