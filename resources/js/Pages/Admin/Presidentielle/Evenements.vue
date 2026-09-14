<script setup>
import { reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import { messagePublication } from '@/composables/useModerationAction';

const props = defineProps({ evenements: Array, types: Array });

// Édition ligne par ligne : le volume est faible et chaque date est un engagement public.
const edition = reactive({});
function editer(e) {
    edition[e.id] = { ...e };
}
function enregistrer(id) {
    router.post(route('admin.presidentielle.evenements.update'), edition[id], {
        preserveScroll: true,
        onSuccess: () => delete edition[id],
    });
}
function agir(id, action) {
    router.post(route('admin.presidentielle.evenements.action'), { id, action }, { preserveScroll: true });
}
const erreurs = () => usePage().props.errors ?? {};
</script>

<template>
    <Head title="Calendrier de campagne" />
    <AuthenticatedLayout>
        <PresidentielleNav />

        <div class="max-w-6xl mx-auto p-4 space-y-4">
            <div>
                <h1 class="text-xl font-bold">Calendrier de campagne</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Un événement n'est publiable que s'il est <strong>daté</strong> et
                    <strong>vérifiable à une source</strong> : un calendrier sans lien de
                    vérification contredirait la promesse du site.
                </p>
            </div>

            <p v-if="erreurs().action" class="rounded bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm p-3">
                {{ erreurs().action }}
            </p>

            <div v-if="!evenements.length" class="rounded border p-5 dark:border-gray-700">
                <p class="font-medium">Aucun événement.</p>
                <p class="text-sm text-gray-500 mt-1">
                    Ils sont créés à partir des sources déjà dépouillées, puis datés et validés ici.
                </p>
            </div>

            <div v-for="e in evenements" :key="e.id" class="rounded border p-4 dark:border-gray-700">
                <!-- Lecture -->
                <template v-if="!edition[e.id]">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div>
                            <p class="font-medium">{{ e.titre }}</p>
                            <p class="text-sm text-gray-500">
                                {{ e.date_debut ?? '— date manquante —' }}
                                <span v-if="e.precision_date !== 'jour'"> ({{ e.precision_date }})</span>
                                · {{ e.type }}
                                <span v-if="e.lieu"> · {{ e.lieu }}</span>
                                <span v-if="e.candidats.length"> · {{ e.candidats.join(', ') }}</span>
                            </p>
                            <p v-if="e.note_methodologique" class="text-xs italic text-amber-700 dark:text-amber-400 mt-1">
                                {{ e.note_methodologique }}
                            </p>
                        </div>
                        <div class="flex gap-1 flex-wrap">
                            <StatusBadge :statut="e.statut_validation" />
                            <span :class="e.affiche_publiquement ? 'text-green-600' : 'text-gray-400'" class="px-2 py-0.5 text-xs">
                                {{ e.affiche_publiquement ? 'public' : 'non publié' }}
                            </span>
                        </div>
                    </div>

                    <p v-if="e.raisons_non_publiable.length" class="text-xs text-amber-700 dark:text-amber-400 mt-2">
                        Non publiable : {{ e.raisons_non_publiable.join(' ; ') }}
                    </p>

                    <div class="mt-3 flex gap-1 flex-wrap items-center">
                        <ActionButton verbe="neutre" libelle="Modifier" @action="editer(e)" />
                        <ActionButton v-if="e.statut_validation !== 'valide'"
                                      verbe="valider" @action="agir(e.id, 'valider')" />
                        <ActionButton v-if="e.statut_validation === 'valide' && !e.affiche_publiquement"
                                      verbe="publier"
                                      :disabled="e.raisons_non_publiable.length > 0"
                                      :titre="e.raisons_non_publiable.length ? e.raisons_non_publiable.join(' ; ') : null"
                                      titre-confirmation="Publier cet événement ?"
                                      :confirmation="messagePublication('Cet événement', e.titre, 'Il apparaîtra dans le calendrier de campagne public.')"
                                      @action="agir(e.id, 'publier')" />
                        <ActionButton v-if="e.affiche_publiquement"
                                      verbe="depublier" @action="agir(e.id, 'depublier')" />
                        <a v-if="e.url_video || e.url_source" :href="e.url_video || e.url_source" target="_blank" rel="noopener"
                           class="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700">Voir la source ↗</a>
                    </div>
                </template>

                <!-- Édition -->
                <template v-else>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label class="text-sm md:col-span-2">Titre
                            <input v-model="edition[e.id].titre" class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm" />
                        </label>
                        <label class="text-sm">Date
                            <input v-model="edition[e.id].date_debut" type="date" class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm" />
                        </label>
                        <label class="text-sm">Précision
                            <select v-model="edition[e.id].precision_date" class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm">
                                <option value="jour">jour exact</option>
                                <option value="heure">heure connue</option>
                                <option value="mois">mois seulement</option>
                            </select>
                        </label>
                        <label class="text-sm">Type
                            <select v-model="edition[e.id].type" class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm">
                                <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </label>
                        <label class="text-sm">Statut
                            <select v-model="edition[e.id].statut" class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm">
                                <option value="confirme">confirmé</option>
                                <option value="annonce">annoncé</option>
                                <option value="reporte">reporté</option>
                                <option value="annule">annulé</option>
                            </select>
                        </label>
                        <label class="text-sm">Lieu
                            <input v-model="edition[e.id].lieu" class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm" />
                        </label>
                        <label class="text-sm">Ville
                            <input v-model="edition[e.id].ville" class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm" />
                        </label>
                        <label class="text-sm md:col-span-2">Note méthodologique (affichée publiquement)
                            <textarea v-model="edition[e.id].note_methodologique" rows="2"
                                      class="w-full mt-0.5 rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm"></textarea>
                        </label>
                    </div>
                    <div class="mt-2 flex gap-1">
                        <button @click="enregistrer(e.id)" class="px-3 py-1 text-sm rounded bg-blue-600 text-white">Enregistrer</button>
                        <button @click="delete edition[e.id]" class="px-3 py-1 text-sm rounded bg-gray-100 dark:bg-gray-700">Annuler</button>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
