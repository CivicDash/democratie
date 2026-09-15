<script setup>
import { reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ModerationLog from '@/Components/Admin/ModerationLog.vue';
import Pagination from '@/Components/Pagination.vue';
import { messagePublication } from '@/composables/useModerationAction';

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

const LIBELLE_CANDIDATURE = {
    pressenti: 'Pressenti', declare: 'Déclaré', investi: 'Investi',
    parrainages_valides: '500 parrainages validés', retire: 'Retiré', elimine_t1: 'Éliminé au 1er tour',
};

// Un état d'édition par ligne, préchargé depuis les données servies : le formulaire est
// replié, mais son contenu doit refléter la ligne dès l'ouverture.
const edition = reactive(Object.fromEntries(props.candidats.data.map((c) => [c.id, {
    statut_candidature: c.statut_candidature,
    parti_soutien: c.parti_soutien ?? '',
    date_declaration: c.date_declaration?.slice(0, 10) ?? '',
}])));

function enregistrer(candidat) {
    router.post(route('admin.presidentielle.candidats.update', candidat.id), {
        ...edition[candidat.id],
        parti_soutien: edition[candidat.id].parti_soutien || null,
        date_declaration: edition[candidat.id].date_declaration || null,
    }, { preserveScroll: true });
}

function agir(candidat, action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'candidat', id: candidat.id, action },
        { preserveScroll: true });
}

function syncParcours(candidat) {
    router.post(route('admin.presidentielle.candidats.sync-parcours', candidat.id), {}, { preserveScroll: true });
}

function nom(c) {
    return c.personne_politique ? `${c.personne_politique.prenom} ${c.personne_politique.nom}` : '—';
}
</script>

<template>
    <Head title="Candidats — présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Candidats 2027</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-4">
            <p class="text-sm text-gray-500">
                Vérifier chaque fiche (identité, parti, date et source de déclaration) avant de valider puis publier.
                Une fois publiée, la fiche apparaît sur objectif2027.fr au prochain rebuild automatique.
            </p>

            <div class="flex gap-2 flex-wrap" role="group" aria-label="Filtrer par statut">
                <button v-for="s in filtres" :key="s" type="button" :aria-pressed="statut === s" @click="filtrer(s)"
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
                            <td class="p-3 space-x-1">
                                <StatusBadge :statut="c.statut_validation" />
                                <StatusBadge v-if="c.affiche_publiquement" publie />
                                <!-- Le statut de CANDIDATURE n'était affiché nulle part, alors
                                     que c'est lui qui dit au public qui est encore en lice. -->
                                <div class="mt-1 text-xs"
                                     :class="['retire', 'elimine_t1'].includes(c.statut_candidature)
                                         ? 'text-amber-700 dark:text-amber-400 font-medium' : 'text-gray-500'">
                                    {{ LIBELLE_CANDIDATURE[c.statut_candidature] ?? c.statut_candidature }}
                                </div>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap space-x-1">
                                <ActionButton verbe="neutre" libelle="Importer le parcours"
                                              titre="Reprend les postes ministériels et mandats déjà présents dans CivicDash"
                                              @action="syncParcours(c)" />
                                <ActionButton v-if="c.statut_validation !== 'valide'"
                                              verbe="valider" @action="agir(c, 'valider')" />
                                <ActionButton v-if="c.statut_validation === 'valide' && !c.affiche_publiquement"
                                              verbe="publier"
                                              titre-confirmation="Publier ce candidat ?"
                                              :confirmation="messagePublication('La fiche de', nom(c), 'Elle nomme une personne : vérifiez le parti, la nuance et la source de la déclaration avant de confirmer.')"
                                              @action="agir(c, 'publier')" />
                                <ActionButton v-if="c.affiche_publiquement"
                                              verbe="depublier" @action="agir(c, 'depublier')" />
                                <div class="mt-2">
                                    <ModerationLog type="candidat" :id="c.id" />
                                </div>
                                <details class="mt-2 text-left">
                                    <summary class="cursor-pointer text-xs text-blue-600">✎ Modifier</summary>
                                    <div class="grid gap-2 mt-2 text-xs" style="min-width: 17rem">
                                        <label :for="`st-${c.id}`" class="sr-only">Statut de candidature</label>
                                        <select :id="`st-${c.id}`" v-model="edition[c.id].statut_candidature"
                                                class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs">
                                            <option v-for="(lib, clef) in LIBELLE_CANDIDATURE" :key="clef" :value="clef">{{ lib }}</option>
                                        </select>
                                        <label :for="`pa-${c.id}`" class="sr-only">Parti de soutien</label>
                                        <input :id="`pa-${c.id}`" v-model="edition[c.id].parti_soutien" type="text" maxlength="150"
                                               placeholder="Parti de soutien"
                                               class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs" />
                                        <label :for="`dt-${c.id}`" class="sr-only">Date de déclaration</label>
                                        <input :id="`dt-${c.id}`" v-model="edition[c.id].date_declaration" type="date"
                                               class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs" />
                                        <ActionButton verbe="valider" libelle="Enregistrer" @action="enregistrer(c)" />
                                    </div>
                                </details>
                            </td>
                        </tr>
                        <tr v-if="!candidats.data.length">
                            <td colspan="7" class="p-8 text-center">
                                <p class="text-gray-600 dark:text-gray-400">Aucun candidat dans cette file.</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                    Ajoutez-en un avec le formulaire ci-dessus : il entrera en
                                    « détecté », non publié.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="candidats.links" :links="candidats.links" />
        </div>
    </AuthenticatedLayout>
</template>
