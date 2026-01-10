<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import { ref } from 'vue';

const props = defineProps({
    liste: Object,
    createur: Object,
    candidats: Array,
    documents: Array,
    historique: Array,
});

const activeTab = ref('documents');

// Actions de modération
const validerDocument = (uuid) => {
    const commentaire = prompt('Commentaire (optionnel):');
    router.post(route('elections.municipales.moderation.valider-document', uuid), {
        commentaire,
    });
};

const invaliderDocument = (uuid) => {
    const raison = prompt('Raison du rejet (obligatoire):');
    if (!raison) return;
    router.post(route('elections.municipales.moderation.invalider-document', uuid), {
        raison,
    });
};

const validerListe = () => {
    if (confirm('Êtes-vous sûr de vouloir VALIDER cette liste ? Elle sera visible publiquement.')) {
        const commentaire = prompt('Commentaire (optionnel):');
        router.post(route('elections.municipales.moderation.valider-liste', props.liste.uuid), {
            commentaire,
        });
    }
};

const rejeterListe = () => {
    const motif = prompt('Motif du rejet (obligatoire):');
    if (!motif) return;
    if (confirm('Êtes-vous sûr de vouloir REJETER cette liste ?')) {
        router.post(route('elections.municipales.moderation.rejeter-liste', props.liste.uuid), {
            motif,
        });
    }
};

const demanderDocuments = () => {
    const commentaire = prompt('Indiquez les documents manquants ou à corriger:');
    if (!commentaire) return;
    router.post(route('elections.municipales.moderation.demander-documents', props.liste.uuid), {
        commentaire,
    });
};

const getStatutBadgeClass = (couleur) => {
    const colors = {
        gray: 'bg-gray-100 text-gray-600',
        yellow: 'bg-yellow-100 text-yellow-600',
        blue: 'bg-blue-100 text-blue-600',
        green: 'bg-green-100 text-green-600',
        red: 'bg-red-100 text-red-600',
    };
    return colors[couleur] || colors.gray;
};
</script>

<template>
    <Head :title="`Modération - ${liste.nom_liste}`" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-700 to-indigo-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <Link
                    :href="route('elections.municipales.moderation.index')"
                    class="text-purple-200 hover:text-white text-sm mb-2 inline-block"
                >
                    ← Retour à la modération
                </Link>
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-white">
                            {{ liste.nom_liste }}
                        </h1>
                        <p class="text-purple-200">
                            📍 {{ liste.commune_nom }} ({{ liste.departement_code }})
                        </p>
                    </div>
                    <Badge class="bg-white/20 text-white border border-white/30 self-start">
                        {{ liste.statut_formate }}
                    </Badge>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Actions modération -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🛡️ Actions de modération</h2>
                <div class="flex flex-wrap gap-3">
                    <button
                        @click="validerListe"
                        class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-500 transition"
                    >
                        ✅ Valider la liste
                    </button>
                    <button
                        @click="demanderDocuments"
                        class="px-6 py-3 bg-yellow-500 text-white font-bold rounded-lg hover:bg-yellow-400 transition"
                    >
                        📋 Demander des documents
                    </button>
                    <button
                        @click="rejeterListe"
                        class="px-6 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-500 transition"
                    >
                        ❌ Rejeter
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Tabs -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="flex -mb-px">
                                <button
                                    v-for="tab in [
                                        { id: 'documents', label: '📁 Documents', count: documents.length },
                                        { id: 'candidats', label: '👥 Candidats', count: candidats.length },
                                        { id: 'infos', label: '📝 Informations', count: null },
                                    ]"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="[
                                        'flex-1 py-3 px-4 text-center border-b-2 font-medium text-sm transition',
                                        activeTab === tab.id
                                            ? 'border-purple-500 text-purple-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700'
                                    ]"
                                >
                                    {{ tab.label }}
                                    <span v-if="tab.count !== null" class="ml-1 text-xs">({{ tab.count }})</span>
                                </button>
                            </nav>
                        </div>

                        <!-- Tab Documents -->
                        <div v-if="activeTab === 'documents'" class="p-6 space-y-4">
                            <div v-if="documents.length === 0" class="text-center py-8 text-gray-500">
                                Aucun document uploadé
                            </div>
                            <div
                                v-for="doc in documents"
                                :key="doc.uuid"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-2xl">{{ doc.est_pdf ? '📄' : '🖼️' }}</span>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                                    {{ doc.type_formate }}
                                                </h4>
                                                <p class="text-sm text-gray-500">
                                                    {{ doc.nom_fichier }} • {{ doc.taille_formatee }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span v-if="doc.numero_reference">Réf: {{ doc.numero_reference }}</span>
                                            <span v-if="doc.date_document">Date: {{ doc.date_document }}</span>
                                            <span>Uploadé par {{ doc.uploader }} le {{ doc.uploaded_at }}</span>
                                        </div>
                                        <a
                                            :href="doc.url"
                                            target="_blank"
                                            class="inline-flex items-center gap-2 mt-3 text-indigo-600 hover:underline"
                                        >
                                            👁️ Voir le document ↗
                                        </a>
                                    </div>
                                    <div class="text-right">
                                        <Badge :class="getStatutBadgeClass(doc.statut_couleur)">
                                            {{ doc.statut_formate }}
                                        </Badge>
                                        
                                        <div v-if="doc.statut === 'en_attente'" class="mt-3 flex gap-2">
                                            <button
                                                @click="validerDocument(doc.uuid)"
                                                class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 text-sm"
                                            >
                                                ✓ Valider
                                            </button>
                                            <button
                                                @click="invaliderDocument(doc.uuid)"
                                                class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm"
                                            >
                                                ✕ Rejeter
                                            </button>
                                        </div>
                                        <p v-if="doc.commentaire" class="text-sm text-red-600 mt-2 max-w-xs text-left">
                                            {{ doc.commentaire }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Candidats -->
                        <div v-if="activeTab === 'candidats'" class="p-6">
                            <div class="space-y-3">
                                <div
                                    v-for="candidat in candidats"
                                    :key="candidat.uuid"
                                    class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                                    :class="{ 'bg-indigo-50 dark:bg-indigo-900/20': candidat.est_tete_de_liste }"
                                >
                                    <div
                                        v-if="candidat.photo_url"
                                        class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0"
                                    >
                                        <img :src="candidat.photo_url" class="w-full h-full object-cover" />
                                    </div>
                                    <div
                                        v-else
                                        class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-bold text-gray-600 dark:text-gray-300 flex-shrink-0"
                                    >
                                        {{ candidat.initiales }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-900 dark:text-white">
                                                {{ candidat.nom_complet }}
                                            </span>
                                            <Badge v-if="candidat.est_tete_de_liste" class="bg-indigo-100 text-indigo-600 text-xs">
                                                Tête de liste
                                            </Badge>
                                            <Badge v-if="!candidat.est_eligible" class="bg-red-100 text-red-600 text-xs">
                                                ⚠️ Éligibilité à vérifier
                                            </Badge>
                                        </div>
                                        <p class="text-sm text-gray-500">
                                            {{ candidat.fonction_visee }} • Position {{ candidat.position }}
                                        </p>
                                        <p v-if="candidat.date_naissance" class="text-sm text-gray-500">
                                            Né(e) le {{ candidat.date_naissance }} ({{ candidat.age }} ans)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Infos -->
                        <div v-if="activeTab === 'infos'" class="p-6 space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-500">Commune</span>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ liste.commune_nom }} ({{ liste.commune_code_insee }})
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Nuance politique</span>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ liste.nuance_politique || '-' }}
                                    </p>
                                </div>
                                <div v-if="liste.slogan" class="col-span-2">
                                    <span class="text-sm text-gray-500">Slogan</span>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ liste.slogan }}
                                    </p>
                                </div>
                                <div v-if="liste.description" class="col-span-2">
                                    <span class="text-sm text-gray-500">Description</span>
                                    <p class="text-gray-700 dark:text-gray-300">
                                        {{ liste.description }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="liste.email_contact || liste.telephone_contact || liste.site_web">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Contact</h4>
                                <div class="text-sm space-y-1">
                                    <p v-if="liste.email_contact">📧 {{ liste.email_contact }}</p>
                                    <p v-if="liste.telephone_contact">📞 {{ liste.telephone_contact }}</p>
                                    <p v-if="liste.site_web">🌐 <a :href="liste.site_web" target="_blank" class="text-indigo-600 hover:underline">{{ liste.site_web }}</a></p>
                                </div>
                            </div>

                            <div v-if="Object.keys(liste.reseaux_sociaux).length > 0">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Réseaux sociaux</h4>
                                <div class="flex flex-wrap gap-2">
                                    <a
                                        v-for="(url, reseau) in liste.reseaux_sociaux"
                                        :key="reseau"
                                        :href="url"
                                        target="_blank"
                                        class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200"
                                    >
                                        {{ reseau }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Créateur -->
                    <div v-if="createur" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">👤 Créateur</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="text-gray-500">Nom:</span> {{ createur.name }}</p>
                            <p><span class="text-gray-500">Email:</span> {{ createur.email }}</p>
                            <p><span class="text-gray-500">ID:</span> #{{ createur.id }}</p>
                        </div>
                    </div>

                    <!-- Historique -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">📋 Historique</h3>
                        <div class="space-y-3">
                            <div
                                v-for="(log, index) in historique.slice(0, 5)"
                                :key="index"
                                class="flex gap-3 text-sm"
                            >
                                <span>{{ log.icone }}</span>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ log.action }}</p>
                                    <p class="text-gray-500">{{ log.date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
