<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    documents: Object,
    filters: Object,
    stats: Object,
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Documents Publics', current: true, icon: '📄' },
];

// Filtres locaux
const selectedType = ref(props.filters?.type || '');
const selectedStatus = ref(props.filters?.status || '');
const searchQuery = ref(props.filters?.search || '');

// Types de documents
const documentTypes = [
    { value: '', label: 'Tous les types', icon: '📁' },
    { value: 'law', label: 'Lois', icon: '📜', color: 'blue' },
    { value: 'budget', label: 'Budgets', icon: '💰', color: 'green' },
    { value: 'report', label: 'Rapports', icon: '📊', color: 'amber' },
    { value: 'decree', label: 'Décrets', icon: '📋', color: 'indigo' },
    { value: 'other', label: 'Autres', icon: '📄', color: 'gray' },
];

// Appliquer les filtres
function applyFilters() {
    router.get(route('documents.index'), {
        type: selectedType.value || undefined,
        status: selectedStatus.value || undefined,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

// Helpers
function getTypeInfo(type) {
    return documentTypes.find(t => t.value === type) || documentTypes[documentTypes.length - 1];
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatFileSize(bytes) {
    if (!bytes) return '-';
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / (1024 * 1024)).toFixed(2) + ' Mo';
}

// Stats calculées
const hasDocuments = computed(() => props.documents?.data?.length > 0);
const totalDocs = computed(() => props.stats?.total || 0);
</script>

<template>
    <Head title="Documents Publics" />

    <AuthenticatedLayout>
        <!-- Hero Banner -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-teal-700 to-cyan-800 py-12">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAyOHYySDI0di0yaDEyek0yNCAyMmgxMnYySDI0di0yek0yNCAzNmgxMnYySDI0di0yeiIvPjwvZz48L2c+PC9zdmc+')] opacity-30"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                            <span class="text-4xl">📄</span>
                            Documents Publics
                        </h1>
                        <p class="text-emerald-100 text-lg max-w-2xl">
                            Lois, rapports parlementaires, budgets et documents officiels de la République
                        </p>
                    </div>
                    
                    <!-- Stats rapides -->
                    <div class="flex gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
                            <div class="text-3xl font-bold text-white">{{ totalDocs }}</div>
                            <div class="text-xs text-emerald-200">Documents</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
                            <div class="text-3xl font-bold text-green-300">{{ stats?.verified || 0 }}</div>
                            <div class="text-xs text-emerald-200">Vérifiés</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Filtres par type (badges) -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        v-for="type in documentTypes"
                        :key="type.value"
                        @click="selectedType = type.value; applyFilters()"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-all"
                        :class="selectedType === type.value 
                            ? 'bg-emerald-600 text-white shadow-lg' 
                            : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 border border-gray-200 dark:border-gray-700'"
                    >
                        <span class="mr-1">{{ type.icon }}</span>
                        {{ type.label }}
                    </button>
                </div>

                <!-- Message si vide -->
                <div v-if="!hasDocuments" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        Aucun document disponible
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                        La bibliothèque de documents publics est en cours de constitution. 
                        Les lois, rapports et budgets seront bientôt disponibles.
                    </p>
                    <div class="flex justify-center gap-4">
                        <Link 
                            :href="route('lois.index')"
                            class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition"
                        >
                            📜 Voir les Lois
                        </Link>
                        <Link 
                            :href="route('dashboard')"
                            class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition"
                        >
                            🏠 Accueil
                        </Link>
                    </div>
                </div>

                <!-- Liste des documents -->
                <div v-else class="space-y-4">
                    <div 
                        v-for="doc in documents.data" 
                        :key="doc.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icône type -->
                            <div 
                                class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"
                                :class="{
                                    'bg-blue-100 dark:bg-blue-900/30': doc.document_type === 'law',
                                    'bg-green-100 dark:bg-green-900/30': doc.document_type === 'budget',
                                    'bg-amber-100 dark:bg-amber-900/30': doc.document_type === 'report',
                                    'bg-indigo-100 dark:bg-indigo-900/30': doc.document_type === 'decree',
                                    'bg-gray-100 dark:bg-gray-700': !['law', 'budget', 'report', 'decree'].includes(doc.document_type),
                                }"
                            >
                                {{ getTypeInfo(doc.document_type).icon }}
                            </div>
                            
                            <!-- Contenu -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">
                                        {{ doc.title }}
                                    </h3>
                                    <span 
                                        class="px-2 py-1 rounded-full text-xs font-medium flex-shrink-0"
                                        :class="{
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': doc.status === 'verified',
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': doc.status === 'pending',
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': doc.status === 'rejected',
                                        }"
                                    >
                                        {{ doc.status === 'verified' ? '✅ Vérifié' : doc.status === 'pending' ? '⏳ En attente' : '❌ Rejeté' }}
                                    </span>
                                </div>
                                
                                <p v-if="doc.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                    {{ doc.description }}
                                </p>
                                
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        📅 {{ formatDate(doc.created_at) }}
                                    </span>
                                    <span v-if="doc.file_size" class="flex items-center gap-1">
                                        📦 {{ formatFileSize(doc.file_size) }}
                                    </span>
                                    <span v-if="doc.uploader" class="flex items-center gap-1">
                                        👤 {{ doc.uploader.name }}
                                    </span>
                                    <span v-if="doc.verifications_count" class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                        ✅ {{ doc.verifications_count }} vérification(s)
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex flex-col gap-2">
                                <Link 
                                    :href="route('documents.show', doc.id)"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition text-center"
                                >
                                    Voir
                                </Link>
                                <a 
                                    v-if="doc.file_path"
                                    :href="route('documents.download', doc.id)"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition text-center"
                                >
                                    ⬇️ PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="documents.last_page > 1" class="flex justify-center gap-2 mt-8">
                        <Link 
                            v-if="documents.prev_page_url"
                            :href="documents.prev_page_url"
                            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            ← Précédent
                        </Link>
                        <span class="px-4 py-2 text-gray-600 dark:text-gray-400">
                            Page {{ documents.current_page }} / {{ documents.last_page }}
                        </span>
                        <Link 
                            v-if="documents.next_page_url"
                            :href="documents.next_page_url"
                            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Suivant →
                        </Link>
                    </div>
                </div>

                <!-- Note informative -->
                <div class="mt-8 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-3xl">💡</div>
                        <div>
                            <h4 class="font-semibold text-emerald-800 dark:text-emerald-200 mb-1">
                                Sources officielles
                            </h4>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                Les documents présentés ici proviennent des sources officielles : 
                                <a href="https://www.legifrance.gouv.fr" target="_blank" class="underline hover:text-emerald-900 dark:hover:text-emerald-100">Légifrance</a>, 
                                <a href="https://www.assemblee-nationale.fr" target="_blank" class="underline hover:text-emerald-900 dark:hover:text-emerald-100">Assemblée nationale</a>, 
                                <a href="https://www.senat.fr" target="_blank" class="underline hover:text-emerald-900 dark:hover:text-emerald-100">Sénat</a>.
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
