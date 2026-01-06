<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    photoStats: Object,
    reportStats: Object,
    bannedWordsCount: Number,
    unverifiedUsers: Number,
    pendingPhotos: Array,
    recentModerations: Array,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Modération', icon: '🛡️' },
];

const getActionBadge = (action) => {
    const badges = {
        'approved': { class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', label: '✅ Approuvé' },
        'rejected': { class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', label: '❌ Refusé' },
    };
    return badges[action] || { class: 'bg-gray-100 text-gray-700', label: action };
};
</script>

<template>
    <Head title="Modération" />
    
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-orange-500 via-red-500 to-pink-600 rounded-2xl shadow-xl p-8 text-white mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <h1 class="text-4xl font-bold mb-2 flex items-center gap-3">
                                🛡️ Centre de Modération
                            </h1>
                            <p class="text-orange-100 text-lg">
                                Gérez les contenus, photos et signalements
                            </p>
                        </div>
                        
                        <!-- Alertes urgentes -->
                        <div class="flex gap-4">
                            <div v-if="photoStats.pending > 0" class="bg-white/20 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-3xl font-bold">{{ photoStats.pending }}</p>
                                <p class="text-xs text-orange-100">📸 Photos en attente</p>
                            </div>
                            <div v-if="reportStats.pending > 0" class="bg-white/20 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-3xl font-bold">{{ reportStats.pending }}</p>
                                <p class="text-xs text-orange-100">🚨 Signalements</p>
                            </div>
                            <div v-if="unverifiedUsers > 0" class="bg-white/20 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-3xl font-bold">{{ unverifiedUsers }}</p>
                                <p class="text-xs text-orange-100">📧 Non vérifiés</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <Link
                        :href="route('admin.moderation.photos.index')"
                        class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-yellow-300 dark:border-yellow-600 p-6 hover:shadow-lg transition group"
                    >
                        <div class="flex items-center gap-4">
                            <span class="text-4xl group-hover:scale-110 transition">📸</span>
                            <div>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ photoStats.pending }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Photos à valider</p>
                            </div>
                        </div>
                    </Link>
                    
                    <Link
                        :href="route('admin.moderation.words')"
                        class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-amber-300 dark:border-amber-600 p-6 hover:shadow-lg transition group"
                    >
                        <div class="flex items-center gap-4">
                            <span class="text-4xl group-hover:scale-110 transition">🚫</span>
                            <div>
                                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ bannedWordsCount }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Mots bannis</p>
                            </div>
                        </div>
                    </Link>
                    
                    <Link
                        :href="route('moderation.reports.index')"
                        class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-red-300 dark:border-red-600 p-6 hover:shadow-lg transition group"
                    >
                        <div class="flex items-center gap-4">
                            <span class="text-4xl group-hover:scale-110 transition">🚨</span>
                            <div>
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ reportStats.pending }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Signalements</p>
                            </div>
                        </div>
                    </Link>
                    
                    <Link
                        :href="route('admin.users.index') + '?verified=0'"
                        class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-blue-300 dark:border-blue-600 p-6 hover:shadow-lg transition group"
                    >
                        <div class="flex items-center gap-4">
                            <span class="text-4xl group-hover:scale-110 transition">📧</span>
                            <div>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ unverifiedUsers }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Email non vérifié</p>
                            </div>
                        </div>
                    </Link>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Photos en attente -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                📸 Photos en attente
                            </h2>
                            <Link 
                                :href="route('admin.moderation.photos.index')" 
                                class="text-sm text-orange-600 dark:text-orange-400 hover:underline font-medium"
                            >
                                Voir tout →
                            </Link>
                        </div>
                        
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <div
                                v-for="photo in pendingPhotos"
                                :key="photo.id"
                                class="px-6 py-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/30"
                            >
                                <img 
                                    :src="photo.photo_url" 
                                    :alt="photo.name"
                                    class="w-12 h-12 rounded-full object-cover border-2 border-yellow-300"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ photo.name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ photo.email }} • {{ photo.submitted_at }}</p>
                                </div>
                                <Link 
                                    :href="route('admin.moderation.photos.index') + '?user=' + photo.id"
                                    class="px-3 py-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50"
                                >
                                    Modérer
                                </Link>
                            </div>
                            
                            <div v-if="pendingPhotos.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                <span class="text-4xl block mb-2">✅</span>
                                Aucune photo en attente
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historique récent -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-700/50 dark:to-slate-600/50">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                🕐 Activité récente
                            </h2>
                        </div>
                        
                        <div class="divide-y divide-slate-100 dark:divide-slate-700 max-h-96 overflow-y-auto">
                            <div
                                v-for="mod in recentModerations"
                                :key="mod.id"
                                class="px-6 py-3 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/30"
                            >
                                <span 
                                    class="px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="getActionBadge(mod.action).class"
                                >
                                    {{ getActionBadge(mod.action).label }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-900 dark:text-white">
                                        <span class="font-medium">{{ mod.moderator_name }}</span>
                                        a modéré la photo de
                                        <span class="font-medium">{{ mod.user_name }}</span>
                                    </p>
                                    <p v-if="mod.reason" class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        « {{ mod.reason }} »
                                    </p>
                                </div>
                                <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap">
                                    {{ mod.created_at }}
                                </span>
                            </div>
                            
                            <div v-if="recentModerations.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                <span class="text-4xl block mb-2">📋</span>
                                Aucune activité récente
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats globales -->
                <div class="mt-8 grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 text-white text-center">
                        <p class="text-3xl font-bold">{{ photoStats.approved }}</p>
                        <p class="text-xs text-green-100">Photos approuvées</p>
                    </div>
                    <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-xl p-4 text-white text-center">
                        <p class="text-3xl font-bold">{{ photoStats.rejected }}</p>
                        <p class="text-xs text-red-100">Photos refusées</p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-4 text-white text-center">
                        <p class="text-3xl font-bold">{{ reportStats.resolved }}</p>
                        <p class="text-xs text-blue-100">Signalements résolus</p>
                    </div>
                    <div class="bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl p-4 text-white text-center">
                        <p class="text-3xl font-bold">{{ reportStats.rejected }}</p>
                        <p class="text-xs text-gray-100">Signalements rejetés</p>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-4 text-white text-center col-span-2">
                        <p class="text-3xl font-bold">{{ bannedWordsCount }}</p>
                        <p class="text-xs text-amber-100">Mots dans la liste noire</p>
                    </div>
                </div>
                
                <!-- Liens vers les outils -->
                <div class="mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        🔧 Outils de modération
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <Link
                            :href="route('admin.moderation.photos.index')"
                            class="flex flex-col items-center gap-2 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition border border-yellow-200 dark:border-yellow-800"
                        >
                            <span class="text-2xl">📸</span>
                            <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Photos</span>
                        </Link>
                        <Link
                            :href="route('admin.moderation.words')"
                            class="flex flex-col items-center gap-2 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition border border-amber-200 dark:border-amber-800"
                        >
                            <span class="text-2xl">🚫</span>
                            <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Mots bannis</span>
                        </Link>
                        <Link
                            :href="route('moderation.reports.index')"
                            class="flex flex-col items-center gap-2 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 transition border border-red-200 dark:border-red-800"
                        >
                            <span class="text-2xl">🚨</span>
                            <span class="text-sm font-medium text-red-700 dark:text-red-300">Signalements</span>
                        </Link>
                        <Link
                            :href="route('admin.users.index')"
                            class="flex flex-col items-center gap-2 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition border border-blue-200 dark:border-blue-800"
                        >
                            <span class="text-2xl">👥</span>
                            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Utilisateurs</span>
                        </Link>
                        <Link
                            :href="route('admin.email.index')"
                            class="flex flex-col items-center gap-2 p-4 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/40 transition border border-cyan-200 dark:border-cyan-800"
                        >
                            <span class="text-2xl">📧</span>
                            <span class="text-sm font-medium text-cyan-700 dark:text-cyan-300">Test Email</span>
                        </Link>
                        <Link
                            :href="route('admin.dashboard')"
                            class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-600/50 transition border border-slate-200 dark:border-slate-600"
                        >
                            <span class="text-2xl">⚙️</span>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Admin</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
