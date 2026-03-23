<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    affaire: Object,
    types_affaire: Array,
    categories: Array,
    statuts_judiciaires: Array,
    statuts_validation: Array,
    types_source: Array,
});

const isNew = computed(() => !props.affaire);

const form = useForm({
    titre: props.affaire?.titre || '',
    description: props.affaire?.description || '',
    type_affaire: props.affaire?.type_affaire || '',
    categorie: props.affaire?.categorie || '',
    statut_judiciaire: props.affaire?.statut_judiciaire || 'en_cours',
    nom: props.affaire?.nom || '',
    prenom: props.affaire?.prenom || '',
    parti_politique: props.affaire?.parti_politique || '',
    fonction_au_moment: props.affaire?.fonction_au_moment || '',
    date_faits: props.affaire?.date_faits || '',
    date_mise_en_examen: props.affaire?.date_mise_en_examen || '',
    date_jugement_premiere_instance: props.affaire?.date_jugement_premiere_instance || '',
    date_jugement_appel: props.affaire?.date_jugement_appel || '',
    date_condamnation_definitive: props.affaire?.date_condamnation_definitive || '',
    peine_prison_mois: props.affaire?.peine_prison_mois || null,
    peine_prison_avec_sursis: props.affaire?.peine_prison_avec_sursis || false,
    peine_amende_euros: props.affaire?.peine_amende_euros || null,
    peine_ineligibilite_mois: props.affaire?.peine_ineligibilite_mois || null,
    peine_complementaire: props.affaire?.peine_complementaire || '',
    juridiction: props.affaire?.juridiction || '',
    numero_dossier: props.affaire?.numero_dossier || '',
    lien_decision_justice: props.affaire?.lien_decision_justice || '',
    commentaire_validation: '',
    sources: props.affaire?.sources?.length ? props.affaire.sources.map(s => ({
        url: s.url || '',
        media: s.media || '',
        type_source: s.type_source || 'article_presse',
        fiabilite: s.fiabilite || 'moyenne',
        titre: s.titre || '',
        date_publication: s.date_publication || '',
    })) : [{ url: '', media: '', type_source: 'article_presse', fiabilite: 'moyenne', titre: '', date_publication: '' }],
});

const rejectForm = useForm({ motif: '' });
const complementForm = useForm({ commentaire: '' });
const archiveForm = useForm({ motif: '' });
const newSourceForm = useForm({
    url: '',
    media: '',
    type_source: 'article_presse',
    fiabilite: 'moyenne',
    titre: '',
    date_publication: '',
});

function addSource() {
    form.sources.push({ url: '', media: '', type_source: 'article_presse', fiabilite: 'moyenne', titre: '', date_publication: '' });
}

function removeSource(idx) {
    if (form.sources.length > 1) {
        form.sources.splice(idx, 1);
    }
}

function submitForm() {
    if (isNew.value) {
        form.post(route('admin.affaires.store'));
    } else {
        form.put(route('admin.affaires.valider', props.affaire.id));
    }
}

function prendreEnCharge() {
    router.post(route('admin.affaires.prendre', props.affaire.id));
}

function rejeter() {
    rejectForm.put(route('admin.affaires.rejeter', props.affaire.id));
}

function completer() {
    complementForm.put(route('admin.affaires.completer', props.affaire.id));
}

function archiver() {
    archiveForm.put(route('admin.affaires.archiver', props.affaire.id));
}

function ajouterSource() {
    newSourceForm.post(route('admin.affaires.source.add', props.affaire.id), {
        onSuccess: () => newSourceForm.reset(),
    });
}

function supprimerSource(sourceId) {
    if (confirm('Supprimer cette source ?')) {
        router.delete(route('admin.affaires.source.delete', sourceId));
    }
}

const statutBadge = (s) => {
    const map = {
        detecte: 'bg-blue-100 text-blue-800',
        en_review: 'bg-indigo-100 text-indigo-800',
        valide: 'bg-green-100 text-green-800',
        rejete: 'bg-red-100 text-red-800',
        a_completer: 'bg-orange-100 text-orange-800',
        conteste: 'bg-yellow-100 text-yellow-800',
        archive: 'bg-gray-100 text-gray-800',
    };
    return map[s] || 'bg-gray-100 text-gray-800';
};

const actionColor = (a) => {
    const map = {
        validation: 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20',
        rejet: 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20',
        detection: 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20',
        prise_en_charge: 'border-indigo-300 bg-indigo-50 dark:border-indigo-700 dark:bg-indigo-900/20',
        contestation: 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20',
    };
    return map[a] || 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/20';
};

const typeLabels = {
    corruption: 'Corruption', detournement_fonds: 'Détournement de fonds', fraude_fiscale: 'Fraude fiscale',
    abus_biens_sociaux: 'Abus de biens sociaux', prise_illegale_interet: 'Prise illégale d\'intérêts',
    favoritisme: 'Favoritisme', trafic_influence: 'Trafic d\'influence', emploi_fictif: 'Emploi fictif',
    recel: 'Recel', blanchiment: 'Blanchiment', harcelement: 'Harcèlement', violence: 'Violence',
    diffamation: 'Diffamation', injure: 'Injure', financement_illegal_campagne: 'Financement illégal',
    compte_campagne_rejete: 'Compte campagne rejeté', conflit_interets: 'Conflit d\'intérêts',
    manquement_probite: 'Manquement probité', autre: 'Autre',
};

const catLabels = { probite: 'Probité', financement: 'Financement', personne: 'Personne', manquement: 'Manquement', autre: 'Autre' };
const statutLabels = {
    en_cours: 'Procédure en cours', mis_en_examen: 'Mis en examen', condamne_premiere_instance: 'Condamné (1re inst.)',
    condamne_appel: 'Condamné (appel)', condamne_definitif: 'Condamné (définitif)', relaxe: 'Relaxé',
    acquitte: 'Acquitté', prescrit: 'Prescrit', non_lieu: 'Non-lieu', amnistie: 'Amnistié',
};
</script>

<template>
    <Head :title="isNew ? 'Nouvelle affaire' : `Affaire — ${affaire.prenom} ${affaire.nom}`" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <Link :href="route('admin.affaires.index')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                    &larr; Retour à la file
                </Link>
            </div>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ isNew ? 'Nouvelle affaire' : `${affaire.prenom} ${affaire.nom}` }}
                    </h1>
                    <div v-if="!isNew" class="flex items-center gap-2 mt-1">
                        <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', statutBadge(affaire.statut_validation)]">
                            {{ affaire.statut_validation }}
                        </span>
                        <span v-if="affaire.source_detection" class="text-xs text-gray-500 dark:text-gray-400">
                            Source : {{ affaire.source_detection }}
                        </span>
                        <span v-if="affaire.detection_confidence" class="text-xs text-gray-500 dark:text-gray-400">
                            Confiance : {{ (affaire.detection_confidence * 100).toFixed(0) }}%
                        </span>
                    </div>
                </div>
                <div v-if="!isNew && affaire.statut_validation === 'detecte'" class="flex gap-2">
                    <button @click="prendreEnCharge" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        Prendre en charge
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-8">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Personne concernée</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom</label>
                            <input v-model="form.prenom" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                            <input v-model="form.nom" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parti politique</label>
                            <input v-model="form.parti_politique" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fonction au moment des faits</label>
                            <input v-model="form.fonction_au_moment" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">L'affaire</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre *</label>
                            <input v-model="form.titre" type="text" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                            <p v-if="form.errors.titre" class="text-sm text-red-600 mt-1">{{ form.errors.titre }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea v-model="form.description" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"></textarea>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type d'affaire *</label>
                                <select v-model="form.type_affaire" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">Sélectionner...</option>
                                    <option v-for="t in types_affaire" :key="t" :value="t">{{ typeLabels[t] || t }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catégorie *</label>
                                <select v-model="form.categorie" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">Sélectionner...</option>
                                    <option v-for="c in categories" :key="c" :value="c">{{ catLabels[c] || c }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut judiciaire *</label>
                                <select v-model="form.statut_judiciaire" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option v-for="s in statuts_judiciaires" :key="s" :value="s">{{ statutLabels[s] || s }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Chronologie</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date des faits</label>
                            <input v-model="form.date_faits" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mise en examen</label>
                            <input v-model="form.date_mise_en_examen" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jugement 1re instance</label>
                            <input v-model="form.date_jugement_premiere_instance" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jugement appel</label>
                            <input v-model="form.date_jugement_appel" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Condamnation définitive</label>
                            <input v-model="form.date_condamnation_definitive" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Peine</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prison (mois)</label>
                            <input v-model="form.peine_prison_mois" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input v-model="form.peine_prison_avec_sursis" type="checkbox" class="rounded border-gray-300 dark:border-gray-600" />
                                Avec sursis
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amende (euros)</label>
                            <input v-model="form.peine_amende_euros" type="number" min="0" step="0.01" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inéligibilité (mois)</label>
                            <input v-model="form.peine_ineligibilite_mois" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sources *</h2>
                        <button type="button" @click="addSource" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">+ Ajouter une source</button>
                    </div>
                    <p v-if="form.errors.sources" class="text-sm text-red-600 mb-3">{{ form.errors.sources }}</p>
                    <div v-for="(source, idx) in form.sources" :key="idx" class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-3">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Source {{ idx + 1 }}</span>
                            <button v-if="form.sources.length > 1" type="button" @click="removeSource(idx)" class="text-xs text-red-600 hover:text-red-700">Supprimer</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">URL *</label>
                                <input v-model="source.url" type="url" required class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Média *</label>
                                <input v-model="source.media" type="text" required placeholder="Le Monde, Légifrance..." class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Type</label>
                                <select v-model="source.type_source" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option v-for="t in types_source" :key="t" :value="t">{{ t }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Fiabilité *</label>
                                <select v-model="source.fiabilite" required class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="haute">Haute (décision justice, JO)</option>
                                    <option value="moyenne">Moyenne (presse nationale)</option>
                                    <option value="basse">Basse (autre)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Commentaire de validation</h2>
                    <textarea v-model="form.commentaire_validation" rows="3" placeholder="Notes internes sur la vérification..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"></textarea>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50">
                            {{ isNew ? 'Créer l\'affaire' : 'Valider et publier' }}
                        </button>
                    </div>

                    <div v-if="!isNew" class="flex gap-2">
                        <button type="button" @click="$refs.rejectModal?.showModal()" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300">
                            Rejeter
                        </button>
                        <button type="button" @click="$refs.complementModal?.showModal()" class="px-4 py-2 text-sm font-medium text-orange-700 bg-orange-50 rounded-lg hover:bg-orange-100 dark:bg-orange-900/30 dark:text-orange-300">
                            Demander complément
                        </button>
                        <button type="button" @click="$refs.archiveModal?.showModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                            Archiver
                        </button>
                    </div>
                </div>
            </form>

            <div v-if="!isNew && affaire.moderation_logs?.length" class="mt-10">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Historique de modération</h2>
                <div class="space-y-3">
                    <div
                        v-for="log in affaire.moderation_logs"
                        :key="log.id"
                        :class="['border rounded-lg p-3', actionColor(log.action)]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ log.action_formatee || log.action }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ log.created_at }}
                            </span>
                        </div>
                        <p v-if="log.moderator" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Par {{ log.moderator.name }}
                        </p>
                        <p v-if="log.commentaire" class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            {{ log.commentaire }}
                        </p>
                    </div>
                </div>
            </div>

            <dialog ref="rejectModal" class="rounded-lg p-6 max-w-md backdrop:bg-black/50">
                <h3 class="text-lg font-semibold mb-3">Rejeter l'affaire</h3>
                <textarea v-model="rejectForm.motif" rows="3" placeholder="Motif du rejet..." class="w-full rounded-lg border-gray-300 mb-3"></textarea>
                <div class="flex justify-end gap-2">
                    <button @click="$refs.rejectModal?.close()" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg">Annuler</button>
                    <button @click="rejeter(); $refs.rejectModal?.close()" class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg">Rejeter</button>
                </div>
            </dialog>

            <dialog ref="complementModal" class="rounded-lg p-6 max-w-md backdrop:bg-black/50">
                <h3 class="text-lg font-semibold mb-3">Demander un complément</h3>
                <textarea v-model="complementForm.commentaire" rows="3" placeholder="Informations manquantes..." class="w-full rounded-lg border-gray-300 mb-3"></textarea>
                <div class="flex justify-end gap-2">
                    <button @click="$refs.complementModal?.close()" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg">Annuler</button>
                    <button @click="completer(); $refs.complementModal?.close()" class="px-4 py-2 text-sm text-white bg-orange-600 rounded-lg">Envoyer</button>
                </div>
            </dialog>

            <dialog ref="archiveModal" class="rounded-lg p-6 max-w-md backdrop:bg-black/50">
                <h3 class="text-lg font-semibold mb-3">Archiver l'affaire</h3>
                <textarea v-model="archiveForm.motif" rows="3" placeholder="Motif de l'archivage..." class="w-full rounded-lg border-gray-300 mb-3"></textarea>
                <div class="flex justify-end gap-2">
                    <button @click="$refs.archiveModal?.close()" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg">Annuler</button>
                    <button @click="archiver(); $refs.archiveModal?.close()" class="px-4 py-2 text-sm text-white bg-gray-600 rounded-lg">Archiver</button>
                </div>
            </dialog>
        </div>
    </AuthenticatedLayout>
</template>
