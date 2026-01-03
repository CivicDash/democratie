<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    stats: { type: Object, required: true },
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénateurs', href: route('representants.senateurs.index'), icon: '🏛️' },
    { label: 'Questions', href: route('questions.senat.index'), icon: '❓' },
    { label: 'Statistiques', current: true, icon: '📊' },
];

function formatNumber(num) {
    return num?.toLocaleString('fr-FR') || '0';
}

// Calculer le taux de réponse
const tauxReponse = props.stats.global?.total > 0 
    ? Math.round((props.stats.global.repondues / props.stats.global.total) * 100) 
    : 0;
</script>

<template>
    <Head title="Statistiques Questions - Sénat" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            
            <!-- Hero Section -->
            <section class="relative overflow-hidden bg-gradient-to-br from-rose-800 via-pink-700 to-fuchsia-800">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>
                
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                    <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                    
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 flex items-center gap-4">
                                <span class="text-4xl">📊</span>
                                Statistiques Questions
                                <span class="text-2xl bg-white/20 px-3 py-1 rounded-full">Sénat</span>
                            </h1>
                            <p class="text-rose-100 text-lg">
                                Analyse des questions au gouvernement posées par les sénateurs
                            </p>
                        </div>
                        
                        <!-- Stats globales -->
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-white">{{ formatNumber(stats.global?.total) }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Questions</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-emerald-400">{{ tauxReponse }}%</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Répondues</div>
                            </div>
                            <div v-if="stats.delai_moyen_jours" class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-amber-400">{{ stats.delai_moyen_jours }}j</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Délai moyen</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Top Thèmes -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            🏷️ Top Thèmes
                        </h2>
                        <div class="space-y-3">
                            <div 
                                v-for="(theme, index) in stats.top_themes"
                                :key="theme.theme"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 flex items-center justify-center bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-full">
                                        {{ index + 1 }}
                                    </span>
                                    <span class="text-gray-900 dark:text-white">{{ theme.theme }}</span>
                                </div>
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm rounded-full">
                                    {{ theme.nb }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Ministères -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            🏛️ Top Ministères
                        </h2>
                        <div class="space-y-3">
                            <div 
                                v-for="(min, index) in stats.top_ministeres"
                                :key="min.ministre_destinataire"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-full">
                                        {{ index + 1 }}
                                    </span>
                                    <span class="text-gray-900 dark:text-white text-sm">{{ min.ministre_destinataire }}</span>
                                </div>
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm rounded-full">
                                    {{ min.nb }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Sénateurs -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 lg:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            👥 Sénateurs les plus actifs
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Link 
                                v-for="(sen, index) in stats.top_senateurs?.slice(0, 12)"
                                :key="sen.matricule"
                                :href="route('questions.senateur', sen.matricule)"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            >
                                <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden shrink-0">
                                    <img 
                                        v-if="sen.photo_url" 
                                        :src="sen.photo_url"
                                        :alt="sen.nom"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ sen.nom }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ sen.nb }} questions
                                    </div>
                                </div>
                                <span class="w-6 h-6 flex items-center justify-center bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-full shrink-0">
                                    {{ index + 1 }}
                                </span>
                            </Link>
                        </div>
                    </div>

                    <!-- Par Type -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            📝 Par type de question
                        </h2>
                        <div class="space-y-3">
                            <div 
                                v-for="type in stats.par_type"
                                :key="type.type"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <span class="text-gray-900 dark:text-white">{{ type.type }}</span>
                                <span class="px-3 py-1 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 font-semibold rounded-full">
                                    {{ formatNumber(type.nb) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            📈 Résumé
                        </h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">Questions ce mois</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ stats.global?.ce_mois || 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">Cette semaine</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ stats.global?.cette_semaine || 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">En attente</span>
                                <span class="font-bold text-amber-600">{{ formatNumber(stats.global?.en_attente) }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">Sénateurs actifs</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ stats.global?.senateurs_actifs || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lien retour -->
                <div class="mt-8 text-center">
                    <Link 
                        :href="route('questions.senat.index')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-medium transition-colors"
                    >
                        ← Retour aux questions
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
