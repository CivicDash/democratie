<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';

/**
 * Séries Eurostat des comparaisons européennes.
 *
 * Une extraction (mensuelle, ou au bouton) ne change jamais ce que le site montre : elle
 * dépose ce qu'elle trouve à côté de la série publiée. Valider, c'est accepter la
 * différence — et, quand des fiches citent ces chiffres, avoir relu leurs phrases.
 */
const props = defineProps({
    indicateurs: Array,
    derniere_extraction: Object,
});

const LIBELLES = {
    a_jour: ['Validée', 'text-green-700 dark:text-green-400'],
    revision: ['Révision à relire', 'text-amber-700 dark:text-amber-400'],
    nouveau: ['Nouvelle, jamais validée', 'text-blue-700 dark:text-blue-300'],
    jamais_extrait: ['Jamais extraite', 'text-gray-500'],
};
const PAYS = { FR: 'France', DE: 'Allemagne', IT: 'Italie', ES: 'Espagne', EU27_2020: 'UE-27' };

const relus = reactive({});
const nouveaux = computed(() => props.indicateurs.filter((i) => i.etat === 'nouveau').length);
const nf = (v) => (typeof v === 'number' ? v.toLocaleString('fr-FR') : v);
const opts = { preserveScroll: true };

function valider(i) {
    router.post(route('admin.presidentielle.eurostat.valider', i.id), { textes_relus: relus[i.code] ? 1 : 0 }, opts);
}
</script>

<template>
    <Head title="Eurostat — présidentielle 2027" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Séries Eurostat</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Les comparaisons européennes de « Ce qu'on entend » : une seule source harmonisée
                pour les cinq membres du panel (France, Allemagne, Italie, Espagne, UE-27). Le site
                ne montre que des séries relues ici.
            </p>

            <FormErrors :errors="$page.props.errors" />

            <div class="flex items-center gap-3 flex-wrap rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm flex-1 min-w-[16rem]">
                    <p v-if="derniere_extraction">
                        Dernière extraction : {{ derniere_extraction.date }} — {{ derniere_extraction.statut }}
                        <span v-if="derniere_extraction.message" class="text-red-600"> · {{ derniere_extraction.message }}</span>
                    </p>
                    <p v-else>Aucune extraction encore.</p>
                    <p class="text-xs text-gray-500">Automatique le 2 de chaque mois. À relancer avant de publier une fiche.</p>
                </div>
                <ActionButton verbe="neutre" libelle="Extraire maintenant"
                              @action="router.post(route('admin.presidentielle.eurostat.extraire'), {}, opts)" />
                <ActionButton v-if="nouveaux" verbe="valider" :libelle="`Valider les ${nouveaux} séries nouvelles`"
                              :confirmation="`${nouveaux} série(s) jamais publiée(s) deviendront celles que le site montre. Aucune fiche publiée ne les utilise encore.`"
                              @action="router.post(route('admin.presidentielle.eurostat.valider-nouveaux'), {}, opts)" />
            </div>

            <div class="space-y-3">
                <article v-for="i in indicateurs" :key="i.code" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-sm">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="font-medium">{{ i.titre }} <span class="text-gray-500 font-normal">({{ i.unite }})</span></p>
                            <p class="text-xs text-gray-500"><code>{{ i.code }}</code> · jeu <code>{{ i.jeu }}</code>
                                <span v-if="i.extraction_publiee"> · publiée : extraction du {{ i.extraction_publiee }}</span>
                                <span v-if="i.extraction_detectee"> · détectée le {{ i.extraction_detectee }}</span>
                            </p>
                        </div>
                        <span :class="['text-xs font-medium', LIBELLES[i.etat]?.[1]]">{{ LIBELLES[i.etat]?.[0] ?? i.etat }}</span>
                    </div>

                    <div v-if="i.diff && i.etat === 'revision'" class="mt-3 grid md:grid-cols-2 gap-3 text-xs">
                        <div v-if="i.diff.revisions.length">
                            <p class="font-medium">Valeurs révisées ({{ i.diff.revisions.length }})</p>
                            <ul class="mt-1 space-y-0.5">
                                <li v-for="(d, k) in i.diff.revisions.slice(0, 12)" :key="k">
                                    {{ PAYS[d.pays] ?? d.pays }} {{ d.annee }} : {{ nf(d.avant) }} → <strong>{{ nf(d.apres) }}</strong>
                                </li>
                            </ul>
                        </div>
                        <div v-if="i.diff.nouvelles.length">
                            <p class="font-medium">Années nouvelles ({{ i.diff.nouvelles.length }})</p>
                            <ul class="mt-1 space-y-0.5">
                                <li v-for="(d, k) in i.diff.nouvelles.slice(0, 12)" :key="k">
                                    {{ PAYS[d.pays] ?? d.pays }} {{ d.annee }} : {{ nf(d.valeur) }}<span v-if="d.statut"> ({{ d.statut }})</span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="i.diff.statuts.length">
                            <p class="font-medium">Statuts changés ({{ i.diff.statuts.length }})</p>
                            <ul class="mt-1 space-y-0.5">
                                <li v-for="(d, k) in i.diff.statuts.slice(0, 12)" :key="k">
                                    {{ PAYS[d.pays] ?? d.pays }} {{ d.annee }} : « {{ d.avant || '—' }} » → « {{ d.apres || '—' }} »
                                </li>
                            </ul>
                        </div>
                        <div v-if="i.diff.disparues.length">
                            <p class="font-medium text-red-700">Années disparues ({{ i.diff.disparues.length }})</p>
                        </div>
                    </div>

                    <div v-if="i.fiches.length" class="mt-3">
                        <p class="text-xs font-medium">Citée par :</p>
                        <ul class="text-xs mt-1 space-y-2">
                            <li v-for="f in i.fiches" :key="f.id">
                                <Link :href="route('admin.presidentielle.affirmations.show', f.id)" class="text-blue-600 hover:underline">« {{ f.enonce }} »</Link>
                                <span v-if="f.publiee" class="text-green-700"> · publiée</span>
                                <ul v-if="i.etat === 'revision'" class="mt-1 pl-3 border-l-2 border-amber-300 space-y-1 text-gray-600 dark:text-gray-400">
                                    <li v-for="(t, k) in f.textes" :key="k">{{ t }}</li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                    <div v-if="i.etat === 'revision' || i.etat === 'nouveau'" class="mt-3 flex items-center justify-end gap-3 flex-wrap">
                        <label v-if="i.etat === 'revision' && i.fiches.length" class="text-xs flex items-center gap-1.5">
                            <input v-model="relus[i.code]" type="checkbox" class="rounded" />
                            Textes relus : les phrases ci-dessus restent justes avec les nouvelles valeurs
                        </label>
                        <ActionButton verbe="valider" libelle="Valider cette série"
                                      :disabled="i.etat === 'revision' && i.fiches.length > 0 && !relus[i.code]"
                                      @action="valider(i)" />
                    </div>
                </article>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
