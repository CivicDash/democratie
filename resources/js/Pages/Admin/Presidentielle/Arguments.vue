<script setup>
import { reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    mesure: Object,
    liens: Array,          // liaisons argument↔mesure (chaque lien porte le sens + la note + le fait)
    types_argument: Array,
    types_source: Array,
    raisons_non_publiable: Array,
});

const nouvelArgument = reactive({
    mesure_id: props.mesure.id, sens: 'pour', titre: '', contenu: '',
    type_argument: 'chiffrage', note_contextuelle: '',
});
const nouvelleSource = reactive({ argument_id: null, type_source: 'rapport_officiel', titre: '', url: '', media: '', fiabilite: 'haute', archive_url: '' });

function ajouterArgument() {
    router.post(route('admin.presidentielle.arguments.store'), nouvelArgument, {
        preserveScroll: true,
        onSuccess: () => { nouvelArgument.titre = ''; nouvelArgument.contenu = ''; nouvelArgument.note_contextuelle = ''; },
    });
}

function ajouterSource(argumentId) {
    router.post(route('admin.presidentielle.arguments.sources.store'), { ...nouvelleSource, argument_id: argumentId }, {
        preserveScroll: true,
        onSuccess: () => { nouvelleSource.titre = ''; nouvelleSource.url = ''; nouvelleSource.media = ''; nouvelleSource.archive_url = ''; },
    });
}

// Le fait (argument) et la liaison ont chacun leur cycle de validation.
function agirFait(argumentId, action) {
    router.post(route('admin.presidentielle.moderation.action'), { type: 'argument', id: argumentId, action }, { preserveScroll: true });
}
function agirLien(lienId, action) {
    router.post(route('admin.presidentielle.moderation.action'), { type: 'argument_lien', id: lienId, action }, { preserveScroll: true });
}
function agirMesure(action) {
    router.post(route('admin.presidentielle.moderation.action'), { type: 'mesure', id: props.mesure.id, action }, { preserveScroll: true });
}

const erreurs = () => usePage().props.errors ?? {};
const parSens = (s) => props.liens.filter((l) => l.sens === s);
</script>

<template>
    <Head :title="`Argumentaire — ${mesure.titre}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Argumentaire de la mesure</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <!-- Mesure -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <Link :href="route('admin.presidentielle.mesures')" class="text-xs text-blue-600 hover:underline">← Toutes les mesures</Link>
                        <h3 class="font-semibold mt-1">{{ mesure.titre }}</h3>
                        <p class="text-sm text-gray-500">{{ mesure.candidat }} · {{ mesure.theme }}</p>
                    </div>
                    <div class="text-right space-y-1">
                        <span class="inline-block px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs">{{ mesure.statut_validation }}</span>
                        <span class="inline-block px-2 py-0.5 rounded text-xs" :class="mesure.affiche_publiquement ? 'bg-green-100 text-green-700' : 'bg-gray-100 dark:bg-gray-700'">
                            {{ mesure.affiche_publiquement ? 'Publiée' : 'Non publiée' }}
                        </span>
                    </div>
                </div>

                <div v-if="raisons_non_publiable.length" class="mt-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-300 p-3 text-xs text-amber-800 dark:text-amber-200">
                    <strong>Mesure non publiable :</strong>
                    <ul class="list-disc pl-4 mt-1"><li v-for="(r, i) in raisons_non_publiable" :key="i">{{ r }}</li></ul>
                </div>
                <div v-else class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-300 p-3 text-xs text-green-800 dark:text-green-200">
                    <span>✓ Conditions de publication réunies (source + ≥1 pour + ≥1 contre publiés & sourcés).</span>
                    <button v-if="!mesure.affiche_publiquement && mesure.statut_validation === 'valide'" @click="agirMesure('publier')"
                        class="px-3 py-1.5 rounded bg-green-600 text-white">Publier la mesure</button>
                    <button v-else-if="!mesure.affiche_publiquement" @click="agirMesure('valider')"
                        class="px-3 py-1.5 rounded bg-blue-600 text-white">Valider la mesure</button>
                </div>
            </div>

            <!-- Colonnes pour / contre (liaisons) -->
            <div class="grid md:grid-cols-2 gap-4">
                <section v-for="sens in ['pour', 'contre']" :key="sens">
                    <h3 class="font-semibold text-sm mb-2" :class="sens === 'pour' ? 'text-green-700' : 'text-red-700'">
                        {{ sens === 'pour' ? '✔ Pour' : '✘ Contre' }} ({{ parSens(sens).length }})
                    </h3>
                    <p v-if="sens === 'contre'" class="text-[11px] text-gray-500 mb-2">Les liaisons « contre » exigent une double validation (2 modérateurs différents).</p>

                    <div v-for="l in parSens(sens)" :key="l.id" class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 mb-3 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <strong>{{ l.argument.titre }}</strong>
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[11px]">
                                fait: {{ l.argument.statut_validation }}{{ l.argument.affiche_publiquement ? '·publié' : '' }}
                            </span>
                        </div>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ l.argument.contenu }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">
                            {{ l.argument.type_argument }}
                            <span v-if="l.argument.controverse"> · controverse : {{ l.argument.controverse }}</span>
                            <span v-if="l.argument.nb_autres_liens" class="text-indigo-500"> · fait partagé ({{ l.argument.nb_autres_liens }} autre(s) mesure(s))</span>
                            <span v-if="l.source_detection === 'suggestion_auto'"> · auto-match {{ l.detection_confidence != null ? Math.round(l.detection_confidence * 100) + '%' : '' }}</span>
                        </p>

                        <!-- Note contextuelle (portée par la liaison, obligatoire à la publication) -->
                        <p class="mt-2 text-xs rounded bg-gray-50 dark:bg-gray-800 p-2">
                            <span class="text-gray-400">Note contextuelle :</span>
                            <span v-if="l.note_contextuelle"> {{ l.note_contextuelle }}</span>
                            <span v-else class="text-amber-600"> — obligatoire avant publication</span>
                        </p>

                        <!-- Sources du fait -->
                        <ul class="mt-2 space-y-1 text-xs">
                            <li v-for="s in l.argument.sources" :key="s.id" class="flex items-center gap-1 flex-wrap">
                                <span class="px-1.5 rounded text-[10px]" :class="s.fiabilite === 'haute' ? 'bg-green-100 text-green-700' : s.fiabilite === 'moyenne' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100'">{{ s.fiabilite }}</span>
                                <a v-if="s.url" :href="s.url" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ s.media || s.titre || s.url }}</a>
                                <span v-else class="text-amber-600">{{ s.titre || 'source sans URL' }} (URL à lier)</span>
                                <a v-if="s.archive_url" :href="s.archive_url" target="_blank" rel="noopener" class="text-gray-400 hover:underline">(archive)</a>
                            </li>
                            <li v-if="!l.argument.sources.length" class="text-amber-600">⚠ Aucune source — requise avant validation du fait.</li>
                        </ul>

                        <details class="mt-2">
                            <summary class="cursor-pointer text-[11px] text-blue-600">+ Ajouter une source au fait</summary>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                <select v-model="nouvelleSource.type_source" class="rounded border-gray-300 dark:bg-gray-800 text-xs">
                                    <option v-for="t in types_source" :key="t" :value="t">{{ t }}</option>
                                </select>
                                <select v-model="nouvelleSource.fiabilite" class="rounded border-gray-300 dark:bg-gray-800 text-xs">
                                    <option value="haute">haute</option><option value="moyenne">moyenne</option><option value="basse">basse</option>
                                </select>
                                <input v-model="nouvelleSource.url" type="url" placeholder="URL *" class="rounded border-gray-300 dark:bg-gray-800 text-xs col-span-2" />
                                <input v-model="nouvelleSource.media" placeholder="Média / institution" class="rounded border-gray-300 dark:bg-gray-800 text-xs" />
                                <input v-model="nouvelleSource.archive_url" type="url" placeholder="URL archive.org" class="rounded border-gray-300 dark:bg-gray-800 text-xs" />
                                <button type="button" @click="ajouterSource(l.argument.id)" :disabled="!nouvelleSource.url"
                                    class="col-span-2 px-3 py-1.5 rounded bg-blue-600 text-white text-xs disabled:opacity-50">Ajouter la source</button>
                            </div>
                        </details>

                        <!-- Publiabilité de la liaison -->
                        <p v-if="l.raisons_non_publiable && l.raisons_non_publiable.length" class="mt-2 text-[11px] text-amber-700">
                            Liaison non publiable : {{ l.raisons_non_publiable.join(' ; ') }}
                        </p>

                        <!-- Actions : fait puis liaison -->
                        <div class="mt-2 flex gap-1 flex-wrap items-center">
                            <span class="text-[10px] text-gray-400">Fait :</span>
                            <button v-if="l.argument.statut_validation !== 'valide'" @click="agirFait(l.argument.id, 'valider')" class="px-2 py-1 text-[11px] rounded bg-blue-600 text-white">Valider</button>
                            <button v-if="l.argument.statut_validation === 'valide' && !l.argument.affiche_publiquement" @click="agirFait(l.argument.id, 'publier')" class="px-2 py-1 text-[11px] rounded bg-green-600 text-white">Publier</button>
                            <button v-if="l.argument.affiche_publiquement" @click="agirFait(l.argument.id, 'depublier')" class="px-2 py-1 text-[11px] rounded bg-amber-100 text-amber-700">Dépublier</button>

                            <span class="text-[10px] text-gray-400 ml-2">Liaison :</span>
                            <button v-if="l.statut_validation !== 'valide'" @click="agirLien(l.id, 'valider')" class="px-2 py-1 text-[11px] rounded bg-blue-600 text-white">Valider</button>
                            <button v-if="sens === 'contre' && l.statut_validation === 'valide' && !l.double_valide_par" @click="agirLien(l.id, 'double_valider')" class="px-2 py-1 text-[11px] rounded bg-indigo-600 text-white">Double valider</button>
                            <button v-if="l.statut_validation === 'valide' && !l.affiche_publiquement" @click="agirLien(l.id, 'publier')" class="px-2 py-1 text-[11px] rounded bg-green-600 text-white">Publier</button>
                            <button v-if="l.affiche_publiquement" @click="agirLien(l.id, 'depublier')" class="px-2 py-1 text-[11px] rounded bg-amber-100 text-amber-700">Dépublier</button>
                        </div>
                    </div>
                    <p v-if="!parSens(sens).length" class="text-xs text-gray-400">Aucun argument « {{ sens }} » — la mesure ne pourra pas être publiée sans.</p>
                </section>
            </div>

            <!-- Nouvel argument (fait + liaison) -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4" open>
                <summary class="cursor-pointer font-semibold text-sm">➕ Ajouter un fait + le relier à cette mesure</summary>
                <form @submit.prevent="ajouterArgument" class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                    <select v-model="nouvelArgument.sens" class="rounded border-gray-300 dark:bg-gray-800">
                        <option value="pour">Pour</option><option value="contre">Contre</option>
                    </select>
                    <select v-model="nouvelArgument.type_argument" class="rounded border-gray-300 dark:bg-gray-800">
                        <option v-for="t in types_argument" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <input v-model="nouvelArgument.titre" required placeholder="Titre du fait *" class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2" />
                    <textarea v-model="nouvelArgument.contenu" required maxlength="500" rows="3"
                        placeholder="Contenu factuel (≤500 caractères, cite une institution/étude — pas d'opinion) *"
                        class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2"></textarea>
                    <textarea v-model="nouvelArgument.note_contextuelle" required rows="2"
                        placeholder="Note contextuelle : pourquoi ce fait joue dans ce sens POUR CETTE mesure *"
                        class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2"></textarea>
                    <div class="md:col-span-2 flex items-center justify-between gap-2">
                        <p v-if="Object.keys(erreurs()).length" class="text-xs text-red-600">{{ Object.values(erreurs())[0] }}</p>
                        <span class="text-[11px] text-gray-400">{{ nouvelArgument.contenu.length }}/500</span>
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm">Ajouter (detecte)</button>
                    </div>
                </form>
            </details>
        </div>
    </AuthenticatedLayout>
</template>
