<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    userStats: Object,
    dataStats: Object,
    recentImports: Array,
    runningImports: Array,
    failedImports: Array,
    moderationStats: Object,
    availableCommands: Array,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Administration', icon: '⚙️' },
];

// Catégories de commandes
const categories = [
    { key: 'global', label: 'Global', icon: '🔄' },
    { key: 'calendrier', label: 'Calendrier', icon: '📅' },
    { key: 'an', label: 'AN', icon: '🔵' },
    { key: 'senat', label: 'Sénat', icon: '🔴' },
    { key: 'enrich', label: 'Enrichir', icon: '✨' },
    { key: 'autres', label: 'Autres', icon: '📦' },
    { key: 'system', label: 'Système', icon: '⚙️' },
];

const activeCategory = ref('global');

const filteredCommands = computed(() => {
    return props.availableCommands.filter(cmd => cmd.category === activeCategory.value);
});

// État pour les commandes en cours
const runningCommand = ref(null);
const autoRefreshInterval = ref(null);

// Auto-refresh quand il y a des imports en cours
const hasRunningImports = computed(() => props.runningImports?.length > 0 || runningCommand.value);

const startAutoRefresh = () => {
    if (autoRefreshInterval.value) return;
    autoRefreshInterval.value = setInterval(() => {
        router.reload({ only: ['recentImports', 'runningImports', 'failedImports'] });
    }, 5000); // Refresh toutes les 5 secondes
};

const stopAutoRefresh = () => {
    if (autoRefreshInterval.value) {
        clearInterval(autoRefreshInterval.value);
        autoRefreshInterval.value = null;
    }
};

watch(hasRunningImports, (val) => {
    if (val) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
}, { immediate: true });

onMounted(() => {
    if (hasRunningImports.value) {
        startAutoRefresh();
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});

// Lancer une commande
const runCommand = (command) => {
    if (runningCommand.value) return;
    
    if (command.dangerous && !confirm(`Êtes-vous sûr de vouloir exécuter "${command.label}" ?`)) {
        return;
    }
    
    runningCommand.value = command.name;
    startAutoRefresh(); // Démarrer l'auto-refresh
    
    router.post(route('admin.run-command'), {
        command: command.name,
        options: {},
    }, {
        onFinish: () => {
            runningCommand.value = null;
        },
    });
};

// Couleurs pour les stats
const getStatusClass = (status) => {
    const classes = {
        running: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        partial: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return classes[status] || classes.running;
};
</script>

<template>
    <Head title="Administration" />
    
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-slate-700 via-slate-800 to-slate-900 rounded-2xl shadow-xl p-8 text-white mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <h1 class="text-4xl font-bold mb-2 flex items-center gap-3">
                                ⚙️ Administration CivicDash
                            </h1>
                            <p class="text-slate-300 text-lg">
                                Gestion des imports, utilisateurs et système
                            </p>
                        </div>
                        
                        <!-- Alertes -->
                        <div class="flex gap-4">
                            <div v-if="runningImports.length > 0" class="bg-blue-500/30 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-2xl font-bold animate-pulse">🔄</p>
                                <p class="text-xs">{{ runningImports.length }} import(s) en cours</p>
                            </div>
                            <div v-if="failedImports.length > 0" class="bg-red-500/30 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-2xl font-bold">⚠️</p>
                                <p class="text-xs">{{ failedImports.length }} échec(s) récent(s)</p>
                            </div>
                            <div v-if="moderationStats.pending > 0" class="bg-yellow-500/30 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-2xl font-bold">🚨</p>
                                <p class="text-xs">{{ moderationStats.pending }} signalement(s)</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
                    <!-- Utilisateurs -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ userStats.total }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Utilisateurs</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">+{{ userStats.week }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Cette semaine</p>
                    </div>
                    
                    <!-- Données -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ dataStats.deputes }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">🔵 Députés</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ dataStats.senateurs }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">🔴 Sénateurs</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ dataStats.scrutins }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Scrutins</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ dataStats.evenements }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">📅 Événements</p>
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Colonne principale -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Derniers imports -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    📥 Derniers imports
                                </h2>
                                <Link :href="route('admin.imports')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Voir tout →
                                </Link>
                            </div>
                            
                            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                                <div
                                    v-for="log in recentImports"
                                    :key="log.id"
                                    class="px-6 py-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/30"
                                >
                                    <!-- Source icon -->
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl"
                                        :style="{ backgroundColor: log.sourceInfo.color + '20' }">
                                        {{ log.sourceInfo.icon }}
                                    </div>
                                    
                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-900 dark:text-white text-sm">
                                            {{ log.command }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ log.startedAt }}
                                            <span v-if="log.user">• par {{ log.user }}</span>
                                        </p>
                                    </div>
                                    
                                    <!-- Stats -->
                                    <div class="text-right text-xs">
                                        <p v-if="log.recordsCreated > 0" class="text-green-600 dark:text-green-400">
                                            +{{ log.recordsCreated }} créés
                                        </p>
                                        <p v-if="log.recordsUpdated > 0" class="text-blue-600 dark:text-blue-400">
                                            {{ log.recordsUpdated }} maj
                                        </p>
                                        <p v-if="log.errorsCount > 0" class="text-red-600 dark:text-red-400">
                                            {{ log.errorsCount }} erreurs
                                        </p>
                                    </div>
                                    
                                    <!-- Status -->
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusClass(log.status)"
                                    >
                                        {{ log.statusLabel }}
                                    </span>
                                    
                                    <!-- Duration -->
                                    <span v-if="log.duration" class="text-xs text-slate-500 dark:text-slate-400 w-16 text-right">
                                        {{ log.duration }}
                                    </span>
                                </div>
                                
                                <div v-if="recentImports.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                    Aucun import récent
                                </div>
                            </div>
                        </div>
                        
                        <!-- Échecs récents -->
                        <div v-if="failedImports.length > 0" class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 overflow-hidden">
                            <div class="px-6 py-4 border-b border-red-200 dark:border-red-800">
                                <h2 class="text-lg font-bold text-red-700 dark:text-red-400 flex items-center gap-2">
                                    ⚠️ Imports échoués récemment
                                </h2>
                            </div>
                            
                            <div class="divide-y divide-red-200 dark:divide-red-800">
                                <div
                                    v-for="log in failedImports"
                                    :key="log.id"
                                    class="px-6 py-4"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-red-700 dark:text-red-400">
                                            {{ log.command }}
                                        </span>
                                        <span class="text-xs text-red-600 dark:text-red-500">
                                            {{ log.startedAt }}
                                        </span>
                                    </div>
                                    <p v-if="log.errorMessage" class="text-sm text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 rounded p-2 font-mono">
                                        {{ log.errorMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="space-y-6">
                        
                        <!-- Actions rapides par catégorie -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    ⚡ Lancer un import
                                </h2>
                            </div>
                            
                            <!-- Onglets catégories -->
                            <div class="flex flex-wrap gap-1 px-4 pt-4 border-b border-slate-200 dark:border-slate-700">
                                <button
                                    v-for="cat in categories"
                                    :key="cat.key"
                                    @click="activeCategory = cat.key"
                                    class="px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors"
                                    :class="activeCategory === cat.key 
                                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' 
                                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                >
                                    {{ cat.icon }} {{ cat.label }}
                                </button>
                            </div>
                            
                            <div class="p-4 space-y-2 max-h-96 overflow-y-auto">
                                <button
                                    v-for="cmd in filteredCommands"
                                    :key="cmd.name"
                                    @click="runCommand(cmd)"
                                    :disabled="runningCommand !== null"
                                    class="w-full flex items-center gap-3 p-3 rounded-lg border transition-all text-left"
                                    :class="{
                                        'border-red-300 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/20': cmd.dangerous,
                                        'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/30': !cmd.dangerous,
                                        'opacity-50 cursor-not-allowed': runningCommand !== null,
                                        'animate-pulse bg-blue-50 dark:bg-blue-900/20': runningCommand === cmd.name,
                                    }"
                                >
                                    <span class="text-xl">{{ cmd.icon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-900 dark:text-white text-sm">
                                            {{ cmd.label }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                            {{ cmd.description }}
                                        </p>
                                    </div>
                                    <span v-if="runningCommand === cmd.name" class="text-blue-500 animate-spin">
                                        🔄
                                    </span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Liens rapides -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    🔗 Accès rapide
                                </h2>
                            </div>
                            
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <Link
                                    :href="route('admin.gouvernement.index')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="text-2xl">🏛️</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Gouvernement</span>
                                </Link>
                                <Link
                                    :href="route('admin.elus.index')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="text-2xl">👥</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Élus</span>
                                </Link>
                                <Link
                                    :href="route('moderation.dashboard')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="text-2xl">🚨</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Modération</span>
                                </Link>
                                <Link
                                    :href="route('admin.imports')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="text-2xl">📥</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Imports</span>
                                </Link>
                                <Link
                                    :href="route('parlement.calendrier.index')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="text-2xl">📅</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Calendrier</span>
                                </Link>
                                <Link
                                    :href="route('dashboard')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="text-2xl">🏠</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Dashboard</span>
                                </Link>
                            </div>
                        </div>
                        
                        <!-- Statistiques données -->
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                            <h3 class="font-bold mb-4 flex items-center gap-2">
                                📊 Données parlementaires
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-indigo-100">🔵 Événements AN</span>
                                    <span class="font-bold">{{ dataStats.evenements_an }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-indigo-100">🔴 Événements Sénat</span>
                                    <span class="font-bold">{{ dataStats.evenements_senat }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-indigo-100">🟡 Événements Élysée</span>
                                    <span class="font-bold">{{ dataStats.evenements_elysee }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-white/20 pt-3 mt-3">
                                    <span class="text-white font-semibold">Total événements</span>
                                    <span class="font-bold text-xl">{{ dataStats.evenements }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>

