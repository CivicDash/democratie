<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import { messagePublication } from '@/composables/useModerationAction';

const props = defineProps({
    files: Object,
    propositions_en_attente: Number,
    signalements_en_attente: Number,
    referentiels: Array,
    porteurs: Object,
    integrite: Object,
});

// Porteur d'un référentiel : un document de parti ou de groupe n'est jamais le
// « programme complet » du candidat, il paraît dans « Autour du candidat ».
const erreurs = () => usePage().props.errors ?? {};
const porteurEdite = ref(null);
const porteurForm = reactive({ porteur_type: 'candidat', porteur_libelle: '', endossement_url: '', endossement_date: '', endossement_note: '' });
function editerPorteur(d) {
    porteurEdite.value = d.id;
    Object.assign(porteurForm, {
        porteur_type: d.porteur_type ?? 'candidat',
        porteur_libelle: d.porteur_libelle ?? '',
        endossement_url: d.endossement_url ?? '',
        endossement_date: d.endossement_date ?? '',
        endossement_note: d.endossement_note ?? '',
    });
}
function enregistrerPorteur(d) {
    const vide = (v) => (v && String(v).trim() !== '' ? v : null);
    router.post(route('admin.presidentielle.referentiels.porteur', d.id), {
        porteur_type: porteurForm.porteur_type,
        porteur_libelle: vide(porteurForm.porteur_libelle),
        endossement_url: vide(porteurForm.endossement_url),
        endossement_date: vide(porteurForm.endossement_date),
        endossement_note: vide(porteurForm.endossement_note),
    }, { preserveScroll: true, onSuccess: () => { porteurEdite.value = null; } });
}

import { router } from '@inertiajs/vue3';
function agirReferentiel(d, action) {
    router.post(route('admin.presidentielle.moderation.action'), { type: 'programme_document', id: d.id, action }, { preserveScroll: true });
}

const statuts = ['detecte', 'en_review', 'a_completer', 'valide'];

function total(file) {
    return Object.values(file || {}).reduce((a, b) => a + Number(b), 0);
}
</script>

<template>
    <Head title="Modération présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Modération — Présidentielle 2027</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-6">
            <!-- Files par statut -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="(file, nom) in files" :key="nom" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold capitalize mb-3">{{ nom }} <span class="text-gray-400">({{ total(file) }})</span></h3>
                    <ul class="text-sm space-y-1">
                        <li v-for="s in statuts" :key="s" class="flex justify-between">
                            <span class="text-gray-500">{{ s }}</span>
                            <span class="font-mono">{{ file[s] ?? 0 }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- File d'ingestion -->
            <div class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-4">
                <strong>{{ propositions_en_attente }}</strong> proposition(s) d'ingestion en attente de validation.
            </div>

            <!-- Signalements citoyens -->
            <Link :href="route('admin.presidentielle.signalements')"
                class="block rounded-xl border p-4 transition"
                :class="signalements_en_attente > 0
                    ? 'border-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100'
                    : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'">
                🚩 <strong>{{ signalements_en_attente }}</strong> signalement(s) citoyen(s) à traiter.
            </Link>

            <!-- Référentiels de programme (plan §11.5) -->
            <div v-if="referentiels?.length" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold mb-3">Référentiels de programme officiel</h3>
                <div v-for="d in referentiels" :key="d.id" class="text-sm py-1.5 border-t border-gray-100 dark:border-gray-800 first:border-0">
                  <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ d.titre }}</p>
                        <p class="text-xs text-gray-500">
                            {{ d.candidat }} · {{ d.nb_items }} entrées · <a :href="d.url" target="_blank" class="text-blue-600 hover:underline">source ↗</a>
                            · porteur : {{ porteurs?.[d.porteur_type] ?? d.porteur_type }}<span v-if="d.porteur_libelle"> — {{ d.porteur_libelle }}</span>
                            <span v-if="['parti', 'groupe_parlementaire'].includes(d.porteur_type)" class="text-purple-700 dark:text-purple-300"> (« Autour du candidat »)</span>
                        </p>
                        <p v-if="d.raisons_porteur?.length" class="text-xs text-amber-600">{{ d.raisons_porteur.join(' ; ') }}</p>
                    </div>
                    <div class="whitespace-nowrap space-x-1">
                        <ActionButton verbe="neutre" libelle="Porteur" @action="porteurEdite === d.id ? (porteurEdite = null) : editerPorteur(d)" />
                        <StatusBadge :statut="d.statut_validation" />
                        <StatusBadge v-if="d.affiche_publiquement" publie />
                        <ActionButton v-if="d.statut_validation !== 'valide'"
                                      verbe="valider" @action="agirReferentiel(d, 'valider')" />
                        <ActionButton v-if="d.statut_validation === 'valide' && !d.affiche_publiquement"
                                      verbe="publier"
                                      titre-confirmation="Publier ce référentiel ?"
                                      :confirmation="messagePublication('Ce référentiel de programme de ' + d.candidat, d.titre)"
                                      @action="agirReferentiel(d, 'publier')" />
                        <ActionButton v-if="d.affiche_publiquement"
                                      verbe="depublier" @action="agirReferentiel(d, 'depublier')" />
                    </div>
                  </div>
                  <form v-if="porteurEdite === d.id" @submit.prevent="enregistrerPorteur(d)"
                        class="mt-2 grid md:grid-cols-2 gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-xs">
                      <label class="block">
                          <span class="font-medium">Porteur</span>
                          <select v-model="porteurForm.porteur_type" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900">
                              <option v-for="(libelle, cle) in porteurs" :key="cle" :value="cle">{{ libelle }}</option>
                          </select>
                      </label>
                      <label class="block">
                          <span class="font-medium">Libellé</span>
                          <input v-model="porteurForm.porteur_libelle" :disabled="porteurForm.porteur_type === 'candidat'" type="text"
                                 class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                      </label>
                      <template v-if="porteurForm.porteur_type !== 'candidat'">
                          <label class="block">
                              <span class="font-medium">URL de l'endossement</span>
                              <input v-model="porteurForm.endossement_url" type="url" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                          </label>
                          <label class="block">
                              <span class="font-medium">Date de l'endossement</span>
                              <input v-model="porteurForm.endossement_date" type="date" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900" />
                          </label>
                          <label class="block md:col-span-2">
                              <span class="font-medium">Note d'endossement</span>
                              <textarea v-model="porteurForm.endossement_note" rows="2" class="mt-1 w-full rounded border-gray-300 text-xs dark:bg-gray-900"></textarea>
                          </label>
                      </template>
                      <p class="md:col-span-2 text-gray-500">Changer de côté (candidat ↔ parti) renvoie le référentiel en modération, dépublié.</p>
                      <div class="md:col-span-2 flex items-center justify-end gap-2">
                          <p v-if="erreurs().porteur_libelle || erreurs().integrite" class="text-red-600 mr-auto">{{ erreurs().porteur_libelle ?? erreurs().integrite }}</p>
                          <button type="button" class="px-3 py-1.5 rounded border" @click="porteurEdite = null">Annuler</button>
                          <button type="submit" class="px-3 py-1.5 rounded bg-blue-600 text-white">Enregistrer</button>
                      </div>
                  </form>
                </div>
            </div>

            <!-- Intégrité -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold mb-3">Intégrité éditoriale</h3>
                <p v-if="!integrite.violations.length" class="text-green-600 text-sm">
                    ✓ Aucune violation bloquante — export autorisé.
                </p>
                <ul v-else class="text-sm text-red-600 space-y-1 mb-3">
                    <li v-for="(v, i) in integrite.violations" :key="i">✗ {{ v.message }}</li>
                </ul>
                <ul v-if="integrite.alertes.length" class="text-sm text-amber-600 space-y-1">
                    <li v-for="(a, i) in integrite.alertes" :key="i">⚠ {{ a.message }}</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
