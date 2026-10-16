<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';

/**
 * Cadrages budgétaires : une grille commune pour tous les candidats, et la ligne de
 * référence officielle (PLF ou loi de finances, comptes INSEE, avis du HCFP) en regard.
 *
 * Ordre de publication imposé : la référence d'abord. Un cadrage de candidat n'est pas
 * publiable sans elle, et elle ne peut être dépubliée tant qu'un cadrage reste publié.
 * Les candidats sont listés par ordre alphabétique : l'écran ne met personne en tête.
 */
const props = defineProps({
    references: Array,
    candidats: Array,
    natures: Object,
    documents: Array,
    referentiels: Array,
});

const erreurs = computed(() => usePage().props.errors ?? {});
const flash = computed(() => usePage().props.flash ?? {});

const naturesCandidat = computed(() => Object.fromEntries(Object.entries(props.natures).filter(([cle]) => cle !== 'reference_officielle')));

const nouveau = reactive({
    candidat_id: '',
    nature: 'budget_premiere_annee',
    titre: '',
    date_source: '',
    ingestion_document_id: '',
    programme_document_id: '',
});

function creer() {
    const reference = nouveau.candidat_id === '';
    router.post(route('admin.presidentielle.budgets.store'), {
        candidat_id: reference ? null : nouveau.candidat_id,
        nature: reference ? 'reference_officielle' : nouveau.nature,
        titre: nouveau.titre,
        date_source: nouveau.date_source,
        ingestion_document_id: reference ? null : (nouveau.ingestion_document_id || null),
        programme_document_id: reference ? null : (nouveau.programme_document_id || null),
    }, { preserveScroll: true });
}

const referentielsDuCandidat = computed(() => props.referentiels.filter((r) => String(r.candidat_id) === String(nouveau.candidat_id)));
const entourage = (type) => ['parti', 'groupe_parlementaire'].includes(type);
</script>

<template>
    <Head title="Budgets — présidentielle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Cadrages budgétaires</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-6 space-y-6">
            <p v-if="flash.success" class="text-sm text-green-600">{{ flash.success }}</p>
            <p v-if="erreurs.integrite || erreurs.action" class="text-sm text-red-600" role="alert">{{ erreurs.integrite ?? erreurs.action }}</p>

            <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <p>Chaque chiffre est <strong>cité, repéré et attribué</strong> ; seules les lignes vérifiées paraissent. Aucun total, aucune conversion entre cumulé et annuel : la valeur reste celle de la source.</p>
                <p>Une case sans ligne paraît « non précisé » ; une case dont les lignes ne sont pas encore vérifiées paraît « en cours de vérification », sans valeur.</p>
                <p>Le cadrage d'un candidat n'admet que lui et son <strong>équipe de campagne</strong> (nom et rôle affichés) ; la parole d'un parti va dans « Autour du candidat ».</p>
            </section>

            <!-- Référence officielle -->
            <section class="rounded-xl border-2 border-gray-300 dark:border-gray-600 p-4">
                <h3 class="font-semibold">Référence officielle <span class="text-xs font-normal text-gray-500">— n'est pas une proposition de candidat ; à publier en premier</span></h3>
                <p class="text-xs text-gray-500 mt-1">La plus récente publiée est exportée (le PLF, puis la loi de finances votée). L'avis du HCFP porte sur le seul PLF.</p>
                <ul class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                    <li v-for="c in references" :key="c.id" class="py-2 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <Link :href="route('admin.presidentielle.budgets.show', c.id)" class="font-medium text-blue-600 hover:underline">{{ c.titre }}</Link>
                            <p class="text-xs text-gray-500">{{ c.date_source }} · {{ c.nb_verifiees }}/{{ c.nb_lignes }} ligne(s) vérifiée(s)</p>
                            <p v-if="c.raisons.length" class="text-xs text-amber-700 dark:text-amber-300">{{ c.raisons.length }} point(s) bloquent la publication</p>
                        </div>
                        <div class="whitespace-nowrap space-x-1">
                            <StatusBadge :statut="c.statut_validation" />
                            <StatusBadge v-if="c.affiche_publiquement" publie />
                        </div>
                    </li>
                    <li v-if="!references.length" class="py-2 text-sm text-gray-500">Aucune référence saisie : les cadrages des candidats ne peuvent pas encore être publiés.</li>
                </ul>
            </section>

            <!-- Candidats -->
            <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold mb-2">Candidats <span class="text-xs font-normal text-gray-500">(ordre alphabétique)</span></h3>
                <div v-for="c in candidats" :key="c.id" class="py-2 border-t border-gray-100 dark:border-gray-800 first:border-0">
                    <p class="font-medium">
                        {{ c.nom }}
                        <span v-if="!c.publie" class="text-xs font-normal text-gray-400">(candidat non publié)</span>
                        <span class="text-xs font-normal text-gray-500">
                            — {{ c.cadrages.some((x) => x.affiche_publiquement) ? 'publié' : (c.cadrages.length ? 'en cours de traitement' : 'non exprimé') }}
                        </span>
                    </p>
                    <ul v-if="c.cadrages.length" class="mt-1 space-y-1">
                        <li v-for="x in c.cadrages" :key="x.id" class="flex items-start justify-between gap-3 text-sm pl-3">
                            <div class="min-w-0">
                                <Link :href="route('admin.presidentielle.budgets.show', x.id)" class="text-blue-600 hover:underline">{{ x.titre }}</Link>
                                <p class="text-xs text-gray-500">{{ x.nature_libelle }} · {{ x.date_source }} · {{ x.nb_verifiees }}/{{ x.nb_lignes }} ligne(s) vérifiée(s)</p>
                                <p v-if="x.raisons.length" class="text-xs text-amber-700 dark:text-amber-300">{{ x.raisons.length }} point(s) bloquent la publication</p>
                            </div>
                            <div class="whitespace-nowrap space-x-1">
                                <StatusBadge :statut="x.statut_validation" />
                                <StatusBadge v-if="x.affiche_publiquement" publie />
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Création -->
            <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold">Nouveau cadrage</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Le plus souvent, un cadrage arrive avec sa prise de parole (champ « cadrage_budgetaire.json » de la file d'ingestion).
                    Ici : saisir la référence officielle, ou un cadrage dont la source est déjà en base.
                </p>
                <form @submit.prevent="creer" class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                    <label class="block">
                        <span class="text-xs font-medium">Pour</span>
                        <select v-model="nouveau.candidat_id" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900">
                            <option value="">Référence officielle (sans candidat)</option>
                            <option v-for="c in candidats" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                    </label>
                    <label v-if="nouveau.candidat_id !== ''" class="block">
                        <span class="text-xs font-medium">Nature</span>
                        <select v-model="nouveau.nature" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900">
                            <option v-for="(libelle, cle) in naturesCandidat" :key="cle" :value="cle">{{ libelle }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium">Titre</span>
                        <input v-model="nouveau.titre" type="text" required maxlength="300"
                               :placeholder="nouveau.candidat_id === '' ? 'PLF 2027, comptes INSEE, avis du HCFP' : 'Contre-budget 2027'"
                               class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900" />
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium">Date de présentation</span>
                        <input v-model="nouveau.date_source" type="date" required class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900" />
                    </label>
                    <template v-if="nouveau.candidat_id !== ''">
                        <label class="block">
                            <span class="text-xs font-medium">Prise de parole source</span>
                            <select v-model="nouveau.ingestion_document_id" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900">
                                <option value="">—</option>
                                <option v-for="d in documents" :key="d.id" :value="d.id" :disabled="entourage(d.porteur_type)">
                                    #{{ d.id }} · {{ d.date ?? '—' }} · {{ d.titre }}{{ entourage(d.porteur_type) ? ' (parti : exclu)' : '' }}
                                </option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-medium">ou référentiel de programme</span>
                            <select v-model="nouveau.programme_document_id" class="mt-1 w-full rounded border-gray-300 text-sm dark:bg-gray-900">
                                <option value="">—</option>
                                <option v-for="r in referentielsDuCandidat" :key="r.id" :value="r.id">{{ r.titre }}</option>
                            </select>
                        </label>
                    </template>
                    <div class="md:col-span-2">
                        <FormErrors :errors="erreurs" />
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm">Créer (détecté)</button>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
