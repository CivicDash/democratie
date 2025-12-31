<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    recentLaws: {
        type: Array,
        default: () => []
    }
});

// Recherche par code postal
const searchQuery = ref('');
const isSearching = ref(false);

const searchRepresentants = () => {
    if (!searchQuery.value || searchQuery.value.length < 2) return;
    isSearching.value = true;
    router.visit(route('representants.mes-representants', { code_postal: searchQuery.value }));
};
</script>

<template>
    <MainLayout title="Accueil">
        <Head title="CivicDash - Suivez la démocratie" />
        
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 tracking-tight">
                        <span class="block">Comprenez la</span>
                        <span class="block bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                            démocratie française
                        </span>
                    </h1>
                    <p class="max-w-2xl mx-auto text-xl text-slate-300 mb-12">
                        Suivez vos représentants, explorez les lois en cours, et comprenez le travail parlementaire.
                    </p>
                </div>
                
                <!-- Deux parcours principaux -->
                <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                    
                    <!-- Parcours 1: Mes Représentants -->
                    <div class="group relative bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/15 transition-all duration-300 hover:scale-[1.02]">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-sky-500/20 to-transparent rounded-2xl"></div>
                        
                        <div class="relative">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-sky-500 to-sky-600 flex items-center justify-center text-2xl shadow-lg shadow-sky-500/30">
                                    👥
                                </div>
                                <h2 class="text-2xl font-bold text-white">Mes Représentants</h2>
                            </div>
                            
                            <p class="text-slate-300 mb-6">
                                Découvrez qui vous représente et suivez leur activité parlementaire.
                            </p>
                            
                            <!-- Recherche par CP -->
                            <div class="mb-4">
                                <div class="flex gap-2">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Entrez votre code postal..."
                                        class="flex-1 px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                                        @keyup.enter="searchRepresentants"
                                    />
                                    <button
                                        @click="searchRepresentants"
                                        :disabled="isSearching"
                                        class="px-6 py-3 bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                                    >
                                        {{ isSearching ? '...' : 'Trouver' }}
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <Link 
                                    :href="route('representants.deputes.index')" 
                                    class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition-colors"
                                >
                                    Tous les députés →
                                </Link>
                                <Link 
                                    :href="route('representants.senateurs.index')" 
                                    class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition-colors"
                                >
                                    Tous les sénateurs →
                                </Link>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Parcours 2: Suivre une Loi -->
                    <div class="group relative bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/15 transition-all duration-300 hover:scale-[1.02]">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-amber-500/20 to-transparent rounded-2xl"></div>
                        
                        <div class="relative">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-2xl shadow-lg shadow-amber-500/30">
                                    📜
                                </div>
                                <h2 class="text-2xl font-bold text-white">Suivre une Loi</h2>
                            </div>
                            
                            <p class="text-slate-300 mb-6">
                                Explorez les textes en cours d'examen et suivez leur parcours législatif.
                            </p>
                            
                            <Link
                                :href="route('legislation.hub')"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-colors mb-4"
                            >
                                Explorer la législation
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            
                            <div class="flex flex-wrap gap-2">
                                <Link 
                                    :href="route('lois.index')" 
                                    class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition-colors"
                                >
                                    Toutes les lois →
                                </Link>
                                <Link 
                                    :href="route('legislation.scrutins.index')" 
                                    class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition-colors"
                                >
                                    Les scrutins →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Stats rapides -->
        <section class="bg-white dark:bg-slate-800 py-12 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ stats.deputes || 577 }}
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">Députés</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ stats.senateurs || 348 }}
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">Sénateurs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ stats.lois_en_cours || '450+' }}
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">Lois en cours</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ stats.maires || '34K+' }}
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">Maires</div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Autres accès -->
        <section class="py-16 bg-slate-50 dark:bg-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-8 text-center">
                    Autres ressources
                </h2>
                
                <div class="grid md:grid-cols-4 gap-6">
                    <!-- Statistiques élus -->
                    <Link 
                        :href="route('statistics.france')"
                        class="group p-6 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 hover:shadow-lg transition-all"
                    >
                        <div class="text-3xl mb-3">📊</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Statistiques France</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Parité, âge, professions des députés, sénateurs et maires.
                        </p>
                    </Link>
                    
                    <!-- Calendrier -->
                    <Link 
                        :href="route('parlement.calendrier.index')"
                        class="group p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all"
                    >
                        <div class="text-3xl mb-3">📅</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Calendrier</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Séances et commissions.
                        </p>
                    </Link>
                    
                    <!-- Groupes -->
                    <Link 
                        :href="route('groupes.index')"
                        class="group p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all"
                    >
                        <div class="text-3xl mb-3">🏛️</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Groupes politiques</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Répartition politique.
                        </p>
                    </Link>
                    
                    <!-- Thématiques -->
                    <Link 
                        :href="route('thematiques.index')"
                        class="group p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all"
                    >
                        <div class="text-3xl mb-3">🏷️</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Thématiques</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Santé, économie, environnement...
                        </p>
                    </Link>
                </div>
            </div>
        </section>
        
        <!-- Fonctionnalités communautaires -->
        <section class="py-16 bg-white dark:bg-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        Participez à la vie démocratique
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400">
                        Inscrivez-vous pour débattre, proposer et suivre vos sujets favoris.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <Link 
                        :href="route('topics.index')"
                        class="p-6 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800 hover:shadow-lg transition-all"
                    >
                        <div class="text-3xl mb-3">💬</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Forum citoyen</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Discutez des sujets d'actualité politique avec la communauté.
                        </p>
                    </Link>
                    
                    <Link 
                        :href="route('budget.index')"
                        class="p-6 bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-xl border border-violet-200 dark:border-violet-800 hover:shadow-lg transition-all"
                    >
                        <div class="text-3xl mb-3">💰</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Budget participatif</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Proposez et votez pour des projets citoyens.
                        </p>
                    </Link>
                    
                    <Link 
                        :href="route('documents.index')"
                        class="p-6 bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl border border-rose-200 dark:border-rose-800 hover:shadow-lg transition-all"
                    >
                        <div class="text-3xl mb-3">📄</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Documents publics</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Accédez aux rapports et documents officiels.
                        </p>
                    </Link>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
