<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Badge from '@/Components/Badge.vue';
import { ref } from 'vue';

const props = defineProps({
    liste: Object,
    candidats: Array,
    documents: Array,
    historique: Array,
    nuances_politiques: Array,
});

// Onglet actif
const activeTab = ref('infos');

// Formulaire mise à jour liste
const form = useForm({
    nom_liste: props.liste.nom_liste,
    nuance_politique: props.liste.nuance_politique || '',
    parti_principal: props.liste.parti_principal || '',
    slogan: props.liste.slogan || '',
    description: props.liste.description || '',
    couleur_principale: props.liste.couleur_principale,
    email_contact: props.liste.email_contact || '',
    telephone_contact: props.liste.telephone_contact || '',
    site_web: props.liste.site_web || '',
    facebook_url: props.liste.facebook_url || '',
    twitter_url: props.liste.twitter_url || '',
    instagram_url: props.liste.instagram_url || '',
    youtube_url: props.liste.youtube_url || '',
    tiktok_url: props.liste.tiktok_url || '',
    resume_programme: props.liste.resume_programme || '',
});

const submitInfos = () => {
    form.put(route('elections.municipales.espace-candidat.update-liste', props.liste.uuid));
};

// Formulaire nouveau candidat
const showCandidatModal = ref(false);
const candidatForm = useForm({
    civilite: '',
    nom: '',
    prenom: '',
    date_naissance: '',
    profession: '',
    est_tete_de_liste: false,
    biographie: '',
    email: '',
});

const submitCandidat = () => {
    candidatForm.post(route('elections.municipales.espace-candidat.store-candidat', props.liste.uuid), {
        onSuccess: () => {
            showCandidatModal.value = false;
            candidatForm.reset();
        },
    });
};

// Upload logo
const logoInput = ref(null);
const uploadLogo = () => {
    const file = logoInput.value.files[0];
    if (!file) return;
    
    router.post(route('elections.municipales.espace-candidat.upload-logo', props.liste.uuid), {
        logo: file,
    }, {
        forceFormData: true,
    });
};

// Upload programme
const programmeInput = ref(null);
const uploadProgramme = () => {
    const file = programmeInput.value.files[0];
    if (!file) return;
    
    router.post(route('elections.municipales.espace-candidat.upload-programme', props.liste.uuid), {
        programme: file,
    }, {
        forceFormData: true,
    });
};

// Upload document
const showDocumentModal = ref(false);
const documentForm = useForm({
    type: 'recepisse_prefecture',
    document: null,
    description: '',
    numero_reference: '',
    date_document: '',
});
const documentInput = ref(null);

const submitDocument = () => {
    const file = documentInput.value.files[0];
    if (!file) return;
    
    router.post(route('elections.municipales.espace-candidat.upload-document', props.liste.uuid), {
        type: documentForm.type,
        document: file,
        description: documentForm.description,
        numero_reference: documentForm.numero_reference,
        date_document: documentForm.date_document,
    }, {
        forceFormData: true,
        onSuccess: () => {
            showDocumentModal.value = false;
            documentForm.reset();
        },
    });
};

// Soumettre la liste
const soumissionEnCours = ref(false);
const soumettreListe = () => {
    if (confirm('Êtes-vous sûr de vouloir soumettre cette liste pour validation ? Vous ne pourrez plus la modifier après soumission.')) {
        soumissionEnCours.value = true;
        router.post(route('elections.municipales.espace-candidat.soumettre-liste', props.liste.uuid), {}, {
            preserveScroll: true,
            onFinish: () => {
                soumissionEnCours.value = false;
            },
        });
    }
};

const getStatutBadgeClass = (couleur) => {
    const colors = {
        gray: 'bg-gray-100 text-gray-600 dark:bg-gray-900/50 dark:text-gray-400',
        yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400',
        orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/50 dark:text-orange-400',
        blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400',
        green: 'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400',
        red: 'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400',
    };
    return colors[couleur] || colors.gray;
};
</script>

<template>
    <Head :title="`Modifier ${liste.nom_liste}`" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="bg-gradient-to-r from-indigo-600 to-fuchsia-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <Link
                    :href="route('elections.municipales.espace-candidat.index')"
                    class="text-indigo-200 hover:text-white text-sm mb-2 inline-block"
                >
                    ← Retour à l'espace candidat
                </Link>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-white">
                            {{ liste.nom_liste }}
                        </h1>
                        <p class="text-indigo-100">
                            📍 {{ liste.commune_nom }} ({{ liste.departement_code }})
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge :class="getStatutBadgeClass(liste.statut_couleur)">
                            {{ liste.statut_formate }}
                        </Badge>
                        <button
                            v-if="liste.statut === 'brouillon' || liste.statut === 'documents_requis'"
                            @click="soumettreListe"
                            :disabled="soumissionEnCours"
                            class="px-4 py-2 bg-green-500 text-white font-bold rounded-lg hover:bg-green-400 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="soumissionEnCours">⏳ Soumission en cours...</span>
                            <span v-else>📤 Soumettre pour validation</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerte motif rejet -->
        <div v-if="liste.motif_rejet" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl p-4">
                <h3 class="font-bold text-red-700 dark:text-red-300 mb-2">⚠️ Motif de demande de correction</h3>
                <p class="text-red-600 dark:text-red-400">{{ liste.motif_rejet }}</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                <nav class="flex gap-6 -mb-px overflow-x-auto">
                    <button
                        v-for="tab in [
                            { id: 'infos', label: '📝 Informations', count: null },
                            { id: 'candidats', label: '👥 Candidats', count: candidats.length },
                            { id: 'documents', label: '📁 Documents', count: documents.length },
                            { id: 'historique', label: '📋 Historique', count: null },
                        ]"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition',
                            activeTab === tab.id
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        {{ tab.label }}
                        <span
                            v-if="tab.count !== null"
                            class="ml-2 px-2 py-0.5 rounded-full text-xs"
                            :class="activeTab === tab.id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Tab: Informations -->
            <div v-if="activeTab === 'infos'" class="space-y-6">
                <!-- Visuels -->
                <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🎨 Visuels</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Logo -->
                        <div>
                            <InputLabel value="Logo de la liste" />
                            <div class="mt-2 flex items-center gap-4">
                                <div
                                    v-if="liste.logo_url"
                                    class="w-20 h-20 rounded-xl overflow-hidden"
                                >
                                    <img :src="liste.logo_url" alt="Logo" class="w-full h-full object-cover" />
                                </div>
                                <div
                                    v-else
                                    class="w-20 h-20 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-3xl"
                                >
                                    🏛️
                                </div>
                                <div>
                                    <input
                                        ref="logoInput"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="hidden"
                                        @change="uploadLogo"
                                    />
                                    <button
                                        type="button"
                                        @click="logoInput.click()"
                                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                    >
                                        {{ liste.logo_url ? 'Changer' : 'Ajouter' }}
                                    </button>
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG ou WebP. Max 2 Mo.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Programme PDF -->
                        <div>
                            <InputLabel value="Programme (PDF)" />
                            <div class="mt-2">
                                <div v-if="liste.programme_pdf_url" class="flex items-center gap-3 mb-3">
                                    <span class="text-2xl">📄</span>
                                    <a :href="liste.programme_pdf_url" target="_blank" class="text-indigo-600 hover:underline">
                                        Voir le programme actuel ↗
                                    </a>
                                </div>
                                <input
                                    ref="programmeInput"
                                    type="file"
                                    accept="application/pdf"
                                    class="hidden"
                                    @change="uploadProgramme"
                                />
                                <button
                                    type="button"
                                    @click="programmeInput.click()"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                >
                                    {{ liste.programme_pdf_url ? 'Remplacer' : 'Ajouter' }}
                                </button>
                                <p class="text-xs text-gray-500 mt-1">PDF uniquement. Max 10 Mo.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Formulaire infos -->
                <form @submit.prevent="submitInfos" class="space-y-6">
                    <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🏛️ Informations générales</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <InputLabel for="nom_liste" value="Nom de la liste *" />
                                <TextInput id="nom_liste" v-model="form.nom_liste" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.nom_liste" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="nuance" value="Nuance politique" />
                                <select
                                    id="nuance"
                                    v-model="form.nuance_politique"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Sélectionner...</option>
                                    <option v-for="nuance in nuances_politiques" :key="nuance.code" :value="nuance.code">
                                        {{ nuance.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel for="couleur" value="Couleur" />
                                <div class="flex items-center gap-3 mt-1">
                                    <input v-model="form.couleur_principale" type="color" class="h-10 w-16 rounded border cursor-pointer" />
                                    <TextInput v-model="form.couleur_principale" type="text" class="flex-1" />
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="slogan" value="Slogan" />
                                <TextInput id="slogan" v-model="form.slogan" type="text" class="mt-1 block w-full" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="description" value="Description" />
                                <textarea v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="resume" value="Résumé du programme" />
                                <textarea v-model="form.resume_programme" rows="3" maxlength="500" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                                <p class="text-xs text-gray-500 mt-1">{{ form.resume_programme?.length || 0 }}/500</p>
                            </div>
                        </div>
                    </section>

                    <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📧 Contact & réseaux</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" v-model="form.email_contact" type="email" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="tel" value="Téléphone" />
                                <TextInput id="tel" v-model="form.telephone_contact" type="tel" class="mt-1 block w-full" />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel for="site" value="Site web" />
                                <TextInput id="site" v-model="form.site_web" type="url" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="fb" value="Facebook" />
                                <TextInput id="fb" v-model="form.facebook_url" type="url" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="tw" value="Twitter / X" />
                                <TextInput id="tw" v-model="form.twitter_url" type="url" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="ig" value="Instagram" />
                                <TextInput id="ig" v-model="form.instagram_url" type="url" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="yt" value="YouTube" />
                                <TextInput id="yt" v-model="form.youtube_url" type="url" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 transition disabled:opacity-50"
                        >
                            💾 Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab: Candidats -->
            <div v-if="activeTab === 'candidats'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        👥 Candidats ({{ candidats.length }})
                    </h3>
                    <button
                        @click="showCandidatModal = true"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 transition"
                    >
                        ➕ Ajouter un candidat
                    </button>
                </div>

                <div v-if="candidats.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <span class="text-5xl">👤</span>
                    <p class="text-gray-600 dark:text-gray-400 mt-4">
                        Aucun candidat pour le moment. Ajoutez votre tête de liste !
                    </p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="candidat in candidats"
                        :key="candidat.uuid"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
                        :class="{ 'ring-2 ring-indigo-500': candidat.est_tete_de_liste }"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                v-if="candidat.photo_url"
                                class="w-12 h-12 rounded-full overflow-hidden"
                            >
                                <img :src="candidat.photo_url" class="w-full h-full object-cover" />
                            </div>
                            <div
                                v-else
                                class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold"
                            >
                                {{ candidat.initiales }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ candidat.prenom }} {{ candidat.nom }}
                                    </span>
                                    <Badge v-if="candidat.est_tete_de_liste" class="bg-indigo-100 text-indigo-600 text-xs">
                                        Tête de liste
                                    </Badge>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Position {{ candidat.position }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Documents -->
            <div v-if="activeTab === 'documents'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        📁 Documents justificatifs
                    </h3>
                    <button
                        @click="showDocumentModal = true"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 transition"
                    >
                        ➕ Ajouter un document
                    </button>
                </div>

                <!-- Rappel récépissé -->
                <div
                    v-if="!documents.some(d => d.type === 'recepisse_prefecture')"
                    class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-xl p-4"
                >
                    <p class="text-amber-700 dark:text-amber-300">
                        ⚠️ <strong>Important :</strong> Vous devez ajouter le récépissé de dépôt en préfecture pour pouvoir soumettre votre liste.
                    </p>
                </div>

                <div v-if="documents.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <span class="text-5xl">📄</span>
                    <p class="text-gray-600 dark:text-gray-400 mt-4">
                        Aucun document. Ajoutez votre récépissé de préfecture !
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="doc in documents"
                        :key="doc.uuid"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">{{ doc.est_pdf ? '📄' : '🖼️' }}</span>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        {{ doc.type_formate }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ doc.nom_fichier }} • {{ doc.taille_formatee }}
                                    </p>
                                    <p v-if="doc.numero_reference" class="text-sm text-gray-500">
                                        Réf: {{ doc.numero_reference }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <Badge :class="getStatutBadgeClass(doc.statut_couleur)">
                                    {{ doc.statut_formate }}
                                </Badge>
                                <p v-if="doc.commentaire" class="text-sm text-red-600 mt-1">
                                    {{ doc.commentaire }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Historique -->
            <div v-if="activeTab === 'historique'" class="space-y-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    📋 Historique de la candidature
                </h3>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="space-y-4">
                        <div
                            v-for="(log, index) in historique"
                            :key="index"
                            class="flex gap-4 pb-4 border-b border-gray-100 dark:border-gray-700 last:border-b-0 last:pb-0"
                        >
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-lg"
                                :class="{
                                    'bg-green-100': log.couleur === 'green',
                                    'bg-red-100': log.couleur === 'red',
                                    'bg-blue-100': log.couleur === 'blue',
                                    'bg-yellow-100': log.couleur === 'yellow',
                                    'bg-gray-100': log.couleur === 'gray',
                                }"
                            >
                                {{ log.icone }}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ log.action }}
                                </p>
                                <p v-if="log.commentaire" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ log.commentaire }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ log.date }}
                                    <span v-if="log.moderateur"> • par {{ log.moderateur }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal ajout candidat -->
        <div v-if="showCandidatModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Ajouter un candidat</h3>
                </div>
                <form @submit.prevent="submitCandidat" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="c-civilite" value="Civilité" />
                            <select v-model="candidatForm.civilite" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="">-</option>
                                <option value="M.">M.</option>
                                <option value="Mme">Mme</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="c-naissance" value="Date de naissance" />
                            <TextInput id="c-naissance" v-model="candidatForm.date_naissance" type="date" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="c-prenom" value="Prénom *" />
                            <TextInput id="c-prenom" v-model="candidatForm.prenom" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="c-nom" value="Nom *" />
                            <TextInput id="c-nom" v-model="candidatForm.nom" type="text" class="mt-1 block w-full" required />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="c-profession" value="Profession" />
                        <TextInput id="c-profession" v-model="candidatForm.profession" type="text" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="c-bio" value="Biographie courte" />
                        <textarea id="c-bio" v-model="candidatForm.biographie" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <input id="c-tete" v-model="candidatForm.est_tete_de_liste" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <InputLabel for="c-tete" value="Tête de liste (candidat au poste de maire)" class="!mb-0" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="showCandidatModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                            Annuler
                        </button>
                        <button type="submit" :disabled="candidatForm.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 disabled:opacity-50">
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal ajout document -->
        <div v-if="showDocumentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full mx-4">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Ajouter un document</h3>
                </div>
                <form @submit.prevent="submitDocument" class="p-6 space-y-4">
                    <div>
                        <InputLabel for="d-type" value="Type de document *" />
                        <select v-model="documentForm.type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="recepisse_prefecture">Récépissé de dépôt en préfecture</option>
                            <option value="piece_identite">Pièce d'identité</option>
                            <option value="attestation_eligibilite">Attestation d'éligibilité</option>
                            <option value="declaration_candidature">Déclaration de candidature</option>
                            <option value="photo_officielle">Photo officielle</option>
                            <option value="autre">Autre document</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="d-fichier" value="Fichier *" />
                        <input ref="documentInput" id="d-fichier" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full" required />
                        <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG ou WebP. Max 5 Mo.</p>
                    </div>
                    <div v-if="documentForm.type === 'recepisse_prefecture'">
                        <InputLabel for="d-ref" value="Numéro de récépissé" />
                        <TextInput id="d-ref" v-model="documentForm.numero_reference" type="text" class="mt-1 block w-full" placeholder="Ex: 2026-PRF-00123" />
                    </div>
                    <div>
                        <InputLabel for="d-date" value="Date du document" />
                        <TextInput id="d-date" v-model="documentForm.date_document" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="d-desc" value="Description (optionnel)" />
                        <TextInput id="d-desc" v-model="documentForm.description" type="text" class="mt-1 block w-full" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="showDocumentModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                            Annuler
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500">
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
