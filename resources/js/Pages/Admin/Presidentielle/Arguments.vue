<script setup>
import { reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    mesure: Object,
    arguments: Array,
    types_argument: Array,
    types_source: Array,
    raisons_non_publiable: Array,
});

const nouvelArgument = reactive({ mesure_id: props.mesure.id, sens: 'pour', titre: '', contenu: '', type_argument: 'chiffrage' });
const nouvelleSource = reactive({ argument_id: null, type_source: 'rapport_officiel', titre: '', url: '', media: '', fiabilite: 'haute', archive_url: '' });

function ajouterArgument() {
    router.post(route('admin.presidentielle.arguments.store'), nouvelArgument, {
        preserveScroll: true,
        onSuccess: () => { nouvelArgument.titre = ''; nouvelArgument.contenu = ''; },
    });
}

function ajouterSource(argumentId) {
    router.post(route('admin.presidentielle.arguments.sources.store'), { ...nouvelleSource, argument_id: argumentId }, {
        preserveScroll: true,
        onSuccess: () => { nouvelleSource.titre = ''; nouvelleSource.url = ''; nouvelleSource.media = ''; nouvelleSource.archive_url = ''; },
    });
}

function agir(argument, action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'argument', id: argument.id, action },
        { preserveScroll: true });
}

function agirMesure(action) {
    router.post(route('admin.presidentielle.moderation.action'),
        { type: 'mesure', id: props.mesure.id, action },
        { preserveScroll: true });
}

const erreurs = () => usePage().props.errors ?? {};
const pour = () => props.arguments.filter((a) => a.sens === 'pour');
const contre = () => props.arguments.filter((a) => a.sens === 'contre');
</script>

<template>
    <Head :title="`Arguments — ${mesure.titre}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Arguments de la mesure</h2>
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

                <!-- Publiabilité -->
                <div v-if="raisons_non_publiable.length" class="mt-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-300 p-3 text-xs text-amber-800 dark:text-amber-200">
                    <strong>Non publiable :</strong>
                    <ul class="list-disc pl-4 mt-1"><li v-for="(r, i) in raisons_non_publiable" :key="i">{{ r }}</li></ul>
                </div>
                <div v-else class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-300 p-3 text-xs text-green-800 dark:text-green-200">
                    <span>✓ Les conditions de publication sont réunies (source + pour + contre sourcés).</span>
                    <button v-if="!mesure.affiche_publiquement && mesure.statut_validation === 'valide'" @click="agirMesure('publier')"
                        class="px-3 py-1.5 rounded bg-green-600 text-white">Publier la mesure</button>
                    <button v-else-if="!mesure.affiche_publiquement" @click="agirMesure('valider')"
                        class="px-3 py-1.5 rounded bg-blue-600 text-white">Valider la mesure</button>
                </div>
            </div>

            <!-- Colonnes pour / contre -->
            <div class="grid md:grid-cols-2 gap-4">
                <section v-for="(liste, sens) in { pour: pour(), contre: contre() }" :key="sens">
                    <h3 class="font-semibold text-sm mb-2" :class="sens === 'pour' ? 'text-green-700' : 'text-red-700'">
                        {{ sens === 'pour' ? '✔ Arguments pour' : '✘ Arguments contre' }} ({{ liste.length }})
                    </h3>
                    <p v-if="sens === 'contre'" class="text-[11px] text-gray-500 mb-2">Les « contre » exigent une double validation (2 modérateurs différents).</p>

                    <div v-for="a in liste" :key="a.id" class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 mb-3 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <strong>{{ a.titre }}</strong>
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[11px]">{{ a.statut_validation }}{{ a.affiche_publiquement ? ' · publié' : '' }}</span>
                        </div>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ a.contenu }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">{{ a.type_argument }}
                            <span v-if="a.sens === 'contre'"> · validations : {{ a.valide_par ? '1' : '0' }}{{ a.double_valide_par ? ' + double ✓' : '' }}</span>
                        </p>

                        <!-- Sources -->
                        <ul class="mt-2 space-y-1 text-xs">
                            <li v-for="s in a.sources" :key="s.id" class="flex items-center gap-1 flex-wrap">
                                <span class="px-1.5 rounded text-[10px]" :class="s.fiabilite === 'haute' ? 'bg-green-100 text-green-700' : s.fiabilite === 'moyenne' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100'">{{ s.fiabilite }}</span>
                                <a :href="s.url" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ s.media || s.titre || s.url }}</a>
                                <a v-if="s.archive_url" :href="s.archive_url" target="_blank" rel="noopener" class="text-gray-400 hover:underline">(archive)</a>
                            </li>
                            <li v-if="!a.sources.length" class="text-amber-600">⚠ Aucune source — requise avant validation.</li>
                        </ul>

                        <!-- Ajouter une source -->
                        <details class="mt-2">
                            <summary class="cursor-pointer text-[11px] text-blue-600">+ Ajouter une source</summary>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                <select v-model="nouvelleSource.type_source" class="rounded border-gray-300 dark:bg-gray-800 text-xs col-span-1">
                                    <option v-for="t in types_source" :key="t" :value="t">{{ t }}</option>
                                </select>
                                <select v-model="nouvelleSource.fiabilite" class="rounded border-gray-300 dark:bg-gray-800 text-xs">
                                    <option value="haute">haute</option><option value="moyenne">moyenne</option><option value="basse">basse</option>
                                </select>
                                <input v-model="nouvelleSource.url" type="url" placeholder="URL * " class="rounded border-gray-300 dark:bg-gray-800 text-xs col-span-2" />
                                <input v-model="nouvelleSource.media" placeholder="Média / institution" class="rounded border-gray-300 dark:bg-gray-800 text-xs" />
                                <input v-model="nouvelleSource.archive_url" type="url" placeholder="URL archive.org" class="rounded border-gray-300 dark:bg-gray-800 text-xs" />
                                <button type="button" @click="ajouterSource(a.id)" :disabled="!nouvelleSource.url"
                                    class="col-span-2 px-3 py-1.5 rounded bg-blue-600 text-white text-xs disabled:opacity-50">Ajouter la source</button>
                            </div>
                        </details>

                        <!-- Actions -->
                        <div class="mt-2 flex gap-1 flex-wrap">
                            <button v-if="a.statut_validation !== 'valide'" @click="agir(a, 'valider')" class="px-2 py-1 text-[11px] rounded bg-blue-600 text-white">Valider</button>
                            <button v-if="a.sens === 'contre' && a.statut_validation === 'valide' && !a.double_valide_par" @click="agir(a, 'double_valider')"
                                class="px-2 py-1 text-[11px] rounded bg-indigo-600 text-white">Double valider</button>
                            <button v-if="a.statut_validation === 'valide' && !a.affiche_publiquement" @click="agir(a, 'publier')"
                                class="px-2 py-1 text-[11px] rounded bg-green-600 text-white">Publier</button>
                            <button v-if="a.affiche_publiquement" @click="agir(a, 'depublier')" class="px-2 py-1 text-[11px] rounded bg-amber-100 text-amber-700">Dépublier</button>
                        </div>
                    </div>
                    <p v-if="!liste.length" class="text-xs text-gray-400">Aucun argument « {{ sens }} » — la mesure ne pourra pas être publiée sans.</p>
                </section>
            </div>

            <!-- Nouvel argument -->
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4" open>
                <summary class="cursor-pointer font-semibold text-sm">➕ Ajouter un argument</summary>
                <form @submit.prevent="ajouterArgument" class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                    <select v-model="nouvelArgument.sens" class="rounded border-gray-300 dark:bg-gray-800">
                        <option value="pour">Pour</option><option value="contre">Contre</option>
                    </select>
                    <select v-model="nouvelArgument.type_argument" class="rounded border-gray-300 dark:bg-gray-800">
                        <option v-for="t in types_argument" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <input v-model="nouvelArgument.titre" required placeholder="Titre *" class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2" />
                    <textarea v-model="nouvelArgument.contenu" required maxlength="500" rows="3"
                        placeholder="Contenu factuel (≤500 caractères, cite une institution/étude — pas d'opinion) *"
                        class="rounded border-gray-300 dark:bg-gray-800 md:col-span-2"></textarea>
                    <div class="md:col-span-2 flex items-center justify-between">
                        <p v-if="Object.keys(erreurs()).length" class="text-xs text-red-600">{{ Object.values(erreurs())[0] }}</p>
                        <span class="text-[11px] text-gray-400">{{ nouvelArgument.contenu.length }}/500</span>
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm">Ajouter (detecte)</button>
                    </div>
                </form>
            </details>
        </div>
    </AuthenticatedLayout>
</template>
