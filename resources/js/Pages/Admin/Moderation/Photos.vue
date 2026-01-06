<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    photos: Object,
    stats: Object,
    currentStatus: String,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Administration', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Modération photos', icon: '📸' },
];

// Modal de rejet
const showRejectModal = ref(false);
const rejectingUser = ref(null);
const rejectForm = useForm({
    reason: '',
});

const openRejectModal = (user) => {
    rejectingUser.value = user;
    rejectForm.reason = '';
    showRejectModal.value = true;
};

const closeRejectModal = () => {
    showRejectModal.value = false;
    rejectingUser.value = null;
};

const submitReject = () => {
    rejectForm.post(route('admin.moderation.photos.reject', rejectingUser.value.id), {
        onSuccess: () => closeRejectModal(),
    });
};

const approve = (userId) => {
    router.post(route('admin.moderation.photos.approve', userId));
};

// Raisons prédéfinies pour le rejet
const rejectionReasons = [
    'Photo inappropriée ou offensante',
    'Photo ne représentant pas une personne réelle',
    'Qualité insuffisante (floue, trop sombre)',
    'Contenu protégé par le droit d\'auteur',
    'Photo d\'une autre personne',
    'Autre (voir détail)',
];
</script>

<template>
    <Head title="Modération des photos" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="breadcrumbItems" />
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 flex items-center gap-3">
                            📸 Modération des photos de profil
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">
                            Validez ou refusez les photos de profil soumises par les membres
                        </p>
                    </div>
                    <Link
                        :href="route('admin.moderation.photos.history')"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                    >
                        📜 Historique
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <Link
                        :href="route('admin.moderation.photos.index', { status: 'pending' })"
                        :class="[
                            'p-4 rounded-xl text-center transition border-2',
                            currentStatus === 'pending' ? 'bg-yellow-50 border-yellow-400 dark:bg-yellow-900/30' : 'bg-white dark:bg-gray-800 border-transparent'
                        ]"
                    >
                        <span class="text-3xl">⏳</span>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.pending }}</p>
                        <p class="text-sm text-gray-500">En attente</p>
                    </Link>
                    <Link
                        :href="route('admin.moderation.photos.index', { status: 'approved' })"
                        :class="[
                            'p-4 rounded-xl text-center transition border-2',
                            currentStatus === 'approved' ? 'bg-green-50 border-green-400 dark:bg-green-900/30' : 'bg-white dark:bg-gray-800 border-transparent'
                        ]"
                    >
                        <span class="text-3xl">✅</span>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.approved }}</p>
                        <p class="text-sm text-gray-500">Approuvées</p>
                    </Link>
                    <Link
                        :href="route('admin.moderation.photos.index', { status: 'rejected' })"
                        :class="[
                            'p-4 rounded-xl text-center transition border-2',
                            currentStatus === 'rejected' ? 'bg-red-50 border-red-400 dark:bg-red-900/30' : 'bg-white dark:bg-gray-800 border-transparent'
                        ]"
                    >
                        <span class="text-3xl">❌</span>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.rejected }}</p>
                        <p class="text-sm text-gray-500">Refusées</p>
                    </Link>
                </div>

                <!-- Liste vide -->
                <div v-if="photos.data.length === 0" class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl">
                    <span class="text-6xl">{{ currentStatus === 'pending' ? '✨' : '📭' }}</span>
                    <h3 class="mt-4 text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ currentStatus === 'pending' ? 'Aucune photo en attente !' : 'Aucune photo dans cette catégorie' }}
                    </h3>
                </div>

                <!-- Grille de photos -->
                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div
                        v-for="photo in photos.data"
                        :key="photo.id"
                        class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700"
                    >
                        <!-- Photo -->
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative">
                            <img
                                :src="photo.photo_url"
                                :alt="photo.name"
                                class="w-full h-full object-cover"
                            />
                            <!-- Badge membre -->
                            <span
                                v-if="photo.is_association_member"
                                class="absolute top-2 left-2 px-2 py-1 bg-blue-600 text-white text-xs rounded-full"
                            >
                                🎖️ Membre
                            </span>
                            <!-- Badge statut -->
                            <span
                                v-if="photo.status === 'approved'"
                                class="absolute top-2 right-2 px-2 py-1 bg-green-500 text-white text-xs rounded-full"
                            >
                                ✓
                            </span>
                            <span
                                v-else-if="photo.status === 'rejected'"
                                class="absolute top-2 right-2 px-2 py-1 bg-red-500 text-white text-xs rounded-full"
                            >
                                ✕
                            </span>
                        </div>

                        <!-- Infos -->
                        <div class="p-3">
                            <p class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ photo.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">@{{ photo.username || 'sans pseudo' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Soumise le {{ photo.submitted_at || 'N/A' }}
                            </p>
                            
                            <!-- Raison du rejet -->
                            <p v-if="photo.status === 'rejected' && photo.rejection_reason" class="text-xs text-red-600 dark:text-red-400 mt-2 line-clamp-2">
                                ❌ {{ photo.rejection_reason }}
                            </p>
                        </div>

                        <!-- Actions (si pending) -->
                        <div v-if="currentStatus === 'pending'" class="p-3 pt-0 flex gap-2">
                            <button
                                @click="approve(photo.id)"
                                class="flex-1 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm font-medium"
                            >
                                ✓ Approuver
                            </button>
                            <button
                                @click="openRejectModal(photo)"
                                class="flex-1 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm font-medium"
                            >
                                ✕ Refuser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="photos.links.length > 3" class="mt-6 flex justify-center gap-2">
                    <Link
                        v-for="link in photos.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                            'px-3 py-2 rounded-lg text-sm',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : link.url
                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Modal de rejet -->
        <Teleport to="body">
            <div
                v-if="showRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="closeRejectModal"
            >
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                            ❌ Refuser la photo
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ rejectingUser?.name }}
                        </p>
                    </div>

                    <div class="p-4 space-y-4">
                        <!-- Photo -->
                        <div class="flex justify-center">
                            <img
                                :src="rejectingUser?.photo_url"
                                class="w-32 h-32 rounded-full object-cover"
                            />
                        </div>

                        <!-- Raisons prédéfinies -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Raison du refus :</label>
                            <div class="space-y-2">
                                <button
                                    v-for="reason in rejectionReasons"
                                    :key="reason"
                                    type="button"
                                    @click="rejectForm.reason = reason"
                                    :class="[
                                        'w-full text-left px-3 py-2 rounded-lg text-sm border transition',
                                        rejectForm.reason === reason
                                            ? 'border-red-500 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300'
                                            : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    {{ reason }}
                                </button>
                            </div>
                        </div>

                        <!-- Message personnalisé -->
                        <div>
                            <textarea
                                v-model="rejectForm.reason"
                                rows="3"
                                placeholder="Raison du refus (obligatoire)..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                            ></textarea>
                            <p v-if="rejectForm.errors.reason" class="text-sm text-red-600 mt-1">{{ rejectForm.errors.reason }}</p>
                        </div>
                    </div>

                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                        <button
                            @click="closeRejectModal"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            Annuler
                        </button>
                        <button
                            @click="submitReject"
                            :disabled="!rejectForm.reason || rejectForm.processing"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ rejectForm.processing ? 'Envoi...' : 'Refuser' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
