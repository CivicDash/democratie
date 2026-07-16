<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    controverses: Object,        // paginator
    liens_a_resoudre: Array,
    themes: Array,
    statut: String,
});

const filtres = ['tous', 'detecte', 'en_review', 'a_completer', 'valide'];
function filtrer(s) {
    router.get(route('admin.presidentielle.controverses'), { statut: s }, { preserveState: true, replace: true });
}

const nouvelle = reactive({ titre: '', theme_id: '', note_methodologique: '' });
function creer() {
    router.post(route('admin.presidentielle.controverses.store'), nouvelle, {
        preserveScroll: true,
        onSuccess: () => { nouvelle.titre = ''; nouvelle.theme_id = ''; nouvelle.note_methodologique = ''; },
    });
}

const importForm = useForm({ fichier: null });
function importer() {
    importForm.post(route('admin.presidentielle.arguments.import'), { preserveScroll: true, forceFormData: true });
}

function agir(controverse, action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'controverse', id: controverse.id, action }, { preserveScroll: true });
}

const resolutions = reactive({});
function resoudre(lienId) {
    router.post(route('admin.presidentielle.arguments.liens.resolve'),
        { id: lienId, mesure_id: resolutions[lienId] }, { preserveScroll: true });
}

const erreurs = () => usePage().props.errors ?? {};
</script>

<template>
    <Head title="Controverses — présidentielle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Controverses & argumentaire</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-5">
            <!-- Import d'arguments -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold text-sm">📥 Importer un argumentaire (JSON)</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Contrats §4 : <code>arguments</code> (v1.1) ou <code>arguments_controverse</code> (v1.2). Tout entre en
                    <strong>detecte</strong> ; les liaisons sont auto-appariées aux mesures du candidat (résolution ci-dessous si non trouvé).
                </p>
                <form @submit.prevent="importer" class="mt-3 flex items-center gap-3 flex-wrap">
                    <input type="file" accept="application/json,.json" @input="importForm.fichier = $event.target.files[0]"
                        class="text-sm" />
                    <button type="submit" :disabled="!importForm.fichier || importForm.processing"
                        class="px-4 py-2 rounded bg-blue-600 text-white text-sm disabled:opacity-50">Importer</button>
                    <span v-if="erreurs().fichier" class="text-xs text-red-600">{{ erreurs().fichier }}</span>
                </form>
            </div>

            <!-- Liaisons à résoudre -->
            <div v-if="liens_a_resoudre.length" class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-4">
                <h3 class="font-semibold text-sm text-amber-800 dark:text-amber-200">⚠ Liaisons à résoudre ({{ liens_a_resoudre.length }})</h3>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">L'auto-match n'a pas trouvé de mesure. Renseignez l'ID de la mesure cible.</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li v-for="l in liens_a_resoudre" :key="l.id" class="flex items-center gap-2 flex-wrap">
                        <span class="px-1.5 rounded text-[10px]" :class="l.sens === 'pour' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">{{ l.sens }}</span>
                        <span class="font-medium">{{ l.argument_titre }}</span>
                        <span class="text-xs text-gray-500">→ {{ l.candidat_slug_propose }} : « {{ l.mesure_proposee }} »
                            <span v-if="l.detection_confidence != null">({{ Math.round(l.detection_confidence * 100) }}%)</span></span>
                        <input v-model="resolutions[l.id]" type="number" placeholder="ID mesure"
                            class="w-28 rounded border-gray-300 dark:bg-gray-800 text-xs" />
                        <button @click="resoudre(l.id)" :disabled="!resolutions[l.id]"
                            class="px-2 py-1 text-xs rounded bg-blue-600 text-white disabled:opacity-50">Relier</button>
                    </li>
                </ul>
            </div>

            <!-- Créer une controverse -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer font-semibold text-sm">➕ Créer une controverse</summary>
                <form @submit.prevent="creer" class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                    <input v-model="nouvelle.titre" required placeholder="Titre * (ex. Âge de départ à la retraite)" class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2" />
                    <select v-model="nouvelle.theme_id" class="rounded border-gray-300 dark:bg-gray-800">
                        <option value="">— thème (optionnel) —</option>
                        <option v-for="t in themes" :key="t.id" :value="t.id">{{ t.nom }}</option>
                    </select>
                    <textarea v-model="nouvelle.note_methodologique" rows="2" placeholder="Note méthodologique (pourquoi des études sérieuses divergent)"
                        class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2"></textarea>
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm md:col-span-2">Créer (detecte)</button>
                </form>
            </details>

            <!-- Liste -->
            <div class="flex gap-2 flex-wrap">
                <button v-for="s in filtres" :key="s" @click="filtrer(s)" class="px-3 py-1 rounded-full text-sm border"
                    :class="statut === s ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">{{ s }}</button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="p-3">Controverse</th><th class="p-3">Thème</th><th class="p-3">Faits</th>
                            <th class="p-3">Statut</th><th class="p-3">Publié</th><th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in controverses.data" :key="c.id" class="border-t border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-3">
                                <div class="font-medium">{{ c.titre }}</div>
                                <div v-if="c.note_methodologique" class="text-xs text-gray-500 max-w-md">{{ c.note_methodologique }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">{{ c.theme?.nom ?? '—' }}</td>
                            <td class="p-3">{{ c.arguments_count }}</td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs">{{ c.statut_validation }}</span></td>
                            <td class="p-3"><span :class="c.affiche_publiquement ? 'text-green-600' : 'text-gray-400'">{{ c.affiche_publiquement ? '✓' : '—' }}</span></td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <button v-if="c.statut_validation !== 'valide'" @click="agir(c, 'valider')" class="px-2 py-1 text-xs rounded bg-blue-600 text-white">Valider</button>
                                <button v-if="c.statut_validation === 'valide' && !c.affiche_publiquement" @click="agir(c, 'publier')" class="px-2 py-1 text-xs rounded bg-green-600 text-white ml-1">Publier</button>
                                <button v-if="c.affiche_publiquement" @click="agir(c, 'depublier')" class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-700 ml-1">Dépublier</button>
                            </td>
                        </tr>
                        <tr v-if="!controverses.data.length"><td colspan="6" class="p-6 text-center text-gray-400">Aucune controverse.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="controverses.links" class="flex flex-wrap gap-1">
                <Link v-for="(l, i) in controverses.links" :key="i" :href="l.url ?? ''" v-html="l.label"
                    class="px-3 py-1 text-sm rounded border"
                    :class="[l.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300', !l.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
