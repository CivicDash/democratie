<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    documents: Object,
    filters: Object,
    stats: Object,
});

const uploadForm = useForm({
    title: '',
    file: null,
    document_type: 'law',
    source_url: '',
});

const uploadDocument = () => {
    uploadForm.post(route('documents.store'), {
        onSuccess: () => {
            uploadForm.reset();
        },
    });
};

const getTypeBadge = (type) => {
    const badges = {
        law: { variant: 'blue', icon: '📜', label: 'Loi' },
        budget: { variant: 'green', icon: '💰', label: 'Budget' },
        report: { variant: 'yellow', icon: '📊', label: 'Rapport' },
        decree: { variant: 'indigo', icon: '📋', label: 'Décret' },
        other: { variant: 'gray', icon: '📄', label: 'Autre' },
    };
    return badges[type] || badges.other;
};

const getStatusBadge = (status) => {
    const badges = {
        pending: { variant: 'yellow', label: '⏳ En attente' },
        verified: { variant: 'green', label: '✅ Vérifié' },
        rejected: { variant: 'red', label: '❌ Rejeté' },
    };
    return badges[status] || badges.pending;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
};

const formatFileSize = (bytes) => {
    if (!bytes) return 'N/A';
    const mb = bytes / (1024 * 1024);
    return `${mb.toFixed(2)} MB`;
};
</script>

<template>
    <Head title="Documents Publics" />

    <MainLayout title="Documents Publics">
        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            📄 Documents Publics
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Lois, budgets, rapports et documents officiels vérifiés
                        </p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card class="text-center">
                        <div class="text-4xl mb-2">📄</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ stats?.total || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Documents</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">✅</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ stats?.verified || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Vérifiés</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">⏳</div>
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ stats?.pending || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">En attente</div>
                    </Card>
                    <Card class="text-center">
                        <div class="text-4xl mb-2">👥</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ stats?.verifiers || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Vérificateurs</div>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Documents List -->
                    <div class="lg:col-span-2 space-y-4">
                        <Card>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                📚 Bibliothèque de Documents
                            </h2>

                            <!-- Filters -->
                            <div class="mb-4 flex gap-3">
                                <select class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm">
                                    <option value="">Tous les types</option>
                                    <option value="law">📜 Lois</option>
                                    <option value="budget">💰 Budgets</option>
                                    <option value="report">📊 Rapports</option>
                                    <option value="decree">📋 Décrets</option>
                                    <option value="other">📄 Autres</option>
                                </select>
                                <select class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm">
                                    <option value="">Tous les statuts</option>
                                    <option value="verified">✅ Vérifiés</option>
                                    <option value="pending">⏳ En attente</option>
                                </select>
                            </div>

                            <!-- Documents -->
                            <div v-if="documents.data.length > 0" class="space-y-3">
                                <div v-for="doc in documents.data" :key="doc.id"
                                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <Badge :variant="getTypeBadge(doc.document_type).variant">
                                                {{ getTypeBadge(doc.document_type).icon }} {{ getTypeBadge(doc.document_type).label }}
                                            </Badge>
                                            <Badge :variant="getStatusBadge(doc.verification_status).variant" size="sm">
                                                {{ getStatusBadge(doc.verification_status).label }}
                                            </Badge>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(doc.created_at) }}
                                        </span>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                        {{ doc.title }}
                                    </h3>
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <div class="space-x-3">
                                            <span>📂 {{ formatFileSize(doc.file_size) }}</span>
                                            <span>👤 {{ doc.uploader?.name || 'Anonyme' }}</span>
                                            <span v-if="doc.verifications_count">✅ {{ doc.verifications_count }} vérifications</span>
                                        </div>
                                        <div class="space-x-2">
                                            <Link :href="route('documents.show', doc.id)" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                Détails
                                            </Link>
                                            <a :href="route('documents.download', doc.id)" class="text-green-600 dark:text-green-400 hover:underline">
                                                ⬇️ Télécharger
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Aucun document trouvé
                            </div>
                        </Card>
                    </div>

                    <!-- Upload Form -->
                    <div v-if="$page.props.auth.user">
                        <Card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                ⬆️ Téléverser un Document
                            </h3>
                            <form @submit.prevent="uploadDocument" class="space-y-4">
                                <div>
                                    <InputLabel for="title" value="Titre *" />
                                    <input 
                                        id="title"
                                        v-model="uploadForm.title"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    />
                                    <InputError class="mt-2" :message="uploadForm.errors.title" />
                                </div>

                                <div>
                                    <InputLabel for="document_type" value="Type *" />
                                    <select 
                                        id="document_type"
                                        v-model="uploadForm.document_type"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="law">📜 Loi</option>
                                        <option value="budget">💰 Budget</option>
                                        <option value="report">📊 Rapport</option>
                                        <option value="decree">📋 Décret</option>
                                        <option value="other">📄 Autre</option>
                                    </select>
                                    <InputError class="mt-2" :message="uploadForm.errors.document_type" />
                                </div>

                                <div>
                                    <InputLabel for="file" value="Fichier (PDF) *" />
                                    <input 
                                        id="file"
                                        type="file"
                                        accept=".pdf"
                                        @input="uploadForm.file = $event.target.files[0]"
                                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-indigo-50 file:text-indigo-700
                                            hover:file:bg-indigo-100
                                            dark:file:bg-indigo-900/20 dark:file:text-indigo-400"
                                        required
                                    />
                                    <InputError class="mt-2" :message="uploadForm.errors.file" />
                                </div>

                                <div>
                                    <InputLabel for="source_url" value="URL source (optionnel)" />
                                    <input 
                                        id="source_url"
                                        v-model="uploadForm.source_url"
                                        type="url"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="https://..."
                                    />
                                    <InputError class="mt-2" :message="uploadForm.errors.source_url" />
                                </div>

                                <PrimaryButton type="submit" :disabled="uploadForm.processing">
                                    {{ uploadForm.processing ? 'Envoi...' : '⬆️ Téléverser' }}
                                </PrimaryButton>
                            </form>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

