<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    trendingTopics: Array,
    propositionsLegislatives: Array,
    votesEnCours: Array,
    globalStats: Object,
    userActivity: Object,
    groupesParlementaires: Array,
    votesLegislatifs: Array,
    mesRepresentants: Object,
    derniersScrutins: {
        type: Array,
        default: () => [],
    },
    topDeputes: {
        type: Array,
        default: () => [],
    },
    topSenateurs: {
        type: Array,
        default: () => [],
    },
    groupesActifs: {
        type: Array,
        default: () => [],
    },
    prochainesReunions: {
        type: Array,
        default: () => [],
    },
});

const user = computed(() => usePage().props.auth.user);

// Greeting basé sur l'heure
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bonjour';
    if (hour < 18) return 'Bon après-midi';
    return 'Bonsoir';
});

/**
 * Obtient la couleur du badge selon le type de topic
 */
const getTopicBadgeColor = (type) => {
    const colors = {
        'question': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'proposal': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'debate': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        'announcement': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
    return colors[type] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
};

/**
 * Obtient l'icône du type de topic
 */
const getTopicIcon = (type) => {
    const icons = {
        'question': '❓',
        'proposal': '💡',
        'debate': '💬',
        'announcement': '📢',
    };
    return icons[type] || '📝';
};

/**
 * Score avec couleur
 */
const getScoreClass = (score) => {
    if (score > 50) return 'text-emerald-600 dark:text-emerald-400';
    if (score > 0) return 'text-emerald-500';
    if (score < -50) return 'text-red-600 dark:text-red-400';
    if (score < 0) return 'text-red-500';
    return 'text-slate-500';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
            <!-- Header avec bienvenue -->
            <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 dark:from-emerald-800 dark:via-teal-800 dark:to-cyan-800">
                <div class="px-4 sm:px-6 lg:px-8 xl:px-12 py-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-white">
                                {{ greeting }}, {{ user?.name?.split(' ')[0] || 'Citoyen' }} 👋
                            </h1>
                            <p class="text-emerald-100 mt-1">
                                Bienvenue sur votre tableau de bord CivicDash
                            </p>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 text-center min-w-[100px]">
                                <div class="text-2xl font-bold text-white">{{ globalStats?.total_topics || 0 }}</div>
                                <div class="text-xs text-emerald-100">Discussions</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 text-center min-w-[100px]">
                                <div class="text-2xl font-bold text-white">{{ globalStats?.total_votes || 0 }}</div>
                                <div class="text-xs text-emerald-100">Votes citoyens</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 text-center min-w-[100px]">
                                <div class="text-2xl font-bold text-white">{{ globalStats?.total_propositions || 0 }}</div>
                                <div class="text-xs text-emerald-100">Propositions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="px-4 sm:px-6 lg:px-8 xl:px-12 py-8 -mt-4">
                <!-- Quick Actions -->
                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
                    <Link
                        :href="route('representants.deputes.index')"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 hover:border-emerald-500 hover:shadow-lg transition-all flex items-center gap-3"
                    >
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-lg">🏛️</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm">Députés</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">577 élus</p>
                        </div>
                    </Link>
                    
                    <Link
                        :href="route('representants.senateurs.index')"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 hover:border-red-500 hover:shadow-lg transition-all flex items-center gap-3"
                    >
                        <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-lg">🏛️</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm">Sénateurs</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">348 élus</p>
                        </div>
                    </Link>
                    
                    <Link
                        :href="route('legislation.scrutins.index')"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 hover:border-teal-500 hover:shadow-lg transition-all flex items-center gap-3"
                    >
                        <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-lg">🗳️</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm">Scrutins</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Tous les votes</p>
                        </div>
                    </Link>
                    
                    <Link
                        href="/topics"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 hover:border-purple-500 hover:shadow-lg transition-all flex items-center gap-3"
                    >
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-lg">💬</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm">Forum</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Discussions</p>
                        </div>
                    </Link>
                    
                    <Link
                        :href="route('representants.mes-representants')"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 hover:border-indigo-500 hover:shadow-lg transition-all flex items-center gap-3"
                    >
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-lg">📍</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm">Mes Élus</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Par localisation</p>
                        </div>
                    </Link>
                    
                    <Link
                        :href="route('statistics.france')"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 hover:border-amber-500 hover:shadow-lg transition-all flex items-center gap-3"
                    >
                        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-lg">📊</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm">Statistiques</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">France</p>
                        </div>
                    </Link>
                </div>

                <!-- Grille principale -->
                <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <!-- Colonne principale (2/3 sur lg, 3/4 sur xl) -->
                    <div class="lg:col-span-2 xl:col-span-3 space-y-6">
                        
                        <!-- Sujets Tendances -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">🔥</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Sujets Tendances</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Les discussions les plus populaires</p>
                                    </div>
                                </div>
                                <Link href="/topics" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                    Voir tout →
                                </Link>
                            </div>
                            
                            <div class="p-4">
                                <div v-if="!trendingTopics || trendingTopics.length === 0" class="text-center py-12 text-slate-500 dark:text-slate-400">
                                    <span class="text-4xl mb-3 block">💬</span>
                                    <p>Aucune discussion pour le moment</p>
                                    <Link href="/topics/create" class="text-sm text-emerald-600 hover:underline mt-2 inline-block">
                                        Créer le premier sujet →
                                    </Link>
                                </div>
                                
                                <div v-else class="space-y-3">
                                    <Link
                                        v-for="topic in trendingTopics"
                                        :key="topic.id"
                                        :href="`/topics/${topic.id}`"
                                        class="block p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 border border-transparent hover:border-emerald-500/30 transition-all"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="text-2xl">{{ getTopicIcon(topic.type) }}</span>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span :class="`px-2 py-0.5 rounded-full text-xs font-medium ${getTopicBadgeColor(topic.type)}`">
                                                        {{ topic.type }}
                                                    </span>
                                                    <span class="text-xs text-slate-400">{{ topic.created_at }}</span>
                                                </div>
                                                <h4 class="font-semibold text-slate-900 dark:text-white mb-1 line-clamp-1">
                                                    {{ topic.titre }}
                                                </h4>
                                                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                                                    <span>👤 {{ topic.auteur }}</span>
                                                    <span>💬 {{ topic.nb_posts }}</span>
                                                    <span>👁️ {{ topic.nb_vues }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Propositions de Loi -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">📜</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Propositions de Loi</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Votez pour exprimer votre avis</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <div v-if="!propositionsLegislatives || propositionsLegislatives.length === 0" class="text-center py-12 text-slate-500 dark:text-slate-400">
                                    <span class="text-4xl mb-3 block">📜</span>
                                    <p>Aucune proposition à afficher</p>
                                </div>
                                
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="prop in propositionsLegislatives"
                                        :key="prop.id"
                                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600"
                                    >
                                        <div class="flex items-start gap-3 mb-3">
                                            <span class="px-2 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                {{ prop.source === 'assemblee' ? 'AN' : 'SN' }}
                                            </span>
                                            <div class="flex-1">
                                                <span class="text-xs text-slate-500 dark:text-slate-400">N° {{ prop.numero }}</span>
                                                <h4 class="font-medium text-slate-900 dark:text-white text-sm line-clamp-2">
                                                    {{ prop.titre }}
                                                </h4>
                                            </div>
                                        </div>
                                        
                                        <!-- Vote stats -->
                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center gap-1 text-sm">
                                                <span class="text-green-500">👍</span>
                                                <span class="font-semibold text-green-600 dark:text-green-400">{{ prop.votes_stats?.upvotes || 0 }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 text-sm">
                                                <span class="text-red-500">👎</span>
                                                <span class="font-semibold text-red-600 dark:text-red-400">{{ prop.votes_stats?.downvotes || 0 }}</span>
                                            </div>
                                            <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full bg-gradient-to-r from-emerald-500 to-teal-500"
                                                    :style="{ width: (prop.votes_stats?.pourcentage_pour || 0) + '%' }"
                                                ></div>
                                            </div>
                                            <span :class="`text-sm font-bold ${getScoreClass(prop.votes_stats?.score || 0)}`">
                                                {{ prop.votes_stats?.score > 0 ? '+' : '' }}{{ prop.votes_stats?.score || 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Derniers Scrutins AN (cliquables) -->
                        <div v-if="derniersScrutins && derniersScrutins.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">🗳️</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Derniers Scrutins</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Cliquez pour voir le détail</p>
                                    </div>
                                </div>
                                <Link :href="route('legislation.scrutins.index')" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                    Voir tout →
                                </Link>
                            </div>
                            
                            <div class="p-4 space-y-3">
                                <Link
                                    v-for="scrutin in derniersScrutins"
                                    :key="scrutin.uid"
                                    :href="route('legislation.scrutins.show', scrutin.uid)"
                                    class="block p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 hover:bg-teal-50 dark:hover:bg-teal-900/20 border border-transparent hover:border-teal-500/30 transition-all cursor-pointer"
                                >
                                    <div class="flex items-start justify-between gap-3 mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">N°{{ scrutin.numero }}</span>
                                                <span class="text-xs text-slate-400">{{ scrutin.date }}</span>
                                            </div>
                                            <h4 class="font-medium text-slate-900 dark:text-white text-sm line-clamp-2">
                                                {{ scrutin.titre }}
                                            </h4>
                                        </div>
                                        <span 
                                            class="shrink-0 px-3 py-1 text-xs font-bold rounded-full"
                                            :class="scrutin.adopte ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                        >
                                            {{ scrutin.adopte ? '✓ Adopté' : '✗ Rejeté' }}
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg py-2">
                                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ scrutin.pour }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Pour</p>
                                        </div>
                                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg py-2">
                                            <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ scrutin.contre }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Contre</p>
                                        </div>
                                        <div class="bg-slate-100 dark:bg-slate-600/30 rounded-lg py-2">
                                            <p class="text-lg font-bold text-slate-600 dark:text-slate-300">{{ scrutin.abstention }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Abst.</p>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Top Députés & Sénateurs -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- 🏆 Top 5 Députés -->
                            <div v-if="topDeputes && topDeputes.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                            <span class="text-lg">🏆</span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 dark:text-white">Top Députés</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Les plus actifs en votes</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="p-4 space-y-2">
                                    <Link
                                        v-for="(depute, index) in topDeputes"
                                        :key="depute.uid"
                                        :href="route('representants.deputes.show', depute.uid)"
                                        class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                    >
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-white font-bold text-sm">
                                            {{ index + 1 }}
                                        </div>
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                            <img v-if="depute.photo" :src="depute.photo" :alt="depute.nom" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ depute.nom }}</p>
                                            <span
                                                v-if="depute.groupe"
                                                class="px-2 py-0.5 rounded text-xs font-semibold text-white"
                                                :style="{ backgroundColor: depute.groupe_couleur }"
                                            >
                                                {{ depute.groupe }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ depute.nb_votes }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">votes</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <!-- 🏆 Top 5 Sénateurs -->
                            <div v-if="topSenateurs && topSenateurs.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center">
                                            <span class="text-lg">🏆</span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 dark:text-white">Top Sénateurs</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Les plus actifs en amendements</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="p-4 space-y-2">
                                    <Link
                                        v-for="(senateur, index) in topSenateurs"
                                        :key="senateur.matricule"
                                        :href="route('representants.senateurs.show', senateur.matricule)"
                                        class="flex items-center gap-3 p-3 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                    >
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-white font-bold text-sm">
                                            {{ index + 1 }}
                                        </div>
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                            <img v-if="senateur.photo" :src="senateur.photo" :alt="senateur.nom" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ senateur.nom }}</p>
                                            <span
                                                v-if="senateur.groupe"
                                                class="px-2 py-0.5 rounded text-xs font-semibold text-white"
                                                :style="{ backgroundColor: senateur.groupe_couleur }"
                                            >
                                                {{ senateur.groupe }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ senateur.nb_amendements }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">amend.</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Colonne latérale (1/3) -->
                    <div class="space-y-6">
                        
                        <!-- Votes en Cours -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">⏳</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Votes en Cours</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Participez maintenant</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <div v-if="!votesEnCours || votesEnCours.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                                    <span class="text-3xl mb-2 block">✅</span>
                                    <p class="text-sm">Aucun vote en cours</p>
                                </div>
                                
                                <div v-else class="space-y-3">
                                    <Link
                                        v-for="vote in votesEnCours"
                                        :key="vote.id"
                                        :href="`/topics/${vote.topic_id}`"
                                        class="block p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 border border-transparent hover:border-emerald-500/30 transition-all"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span v-if="vote.a_vote" class="text-2xl">✅</span>
                                            <span v-else class="text-2xl animate-pulse">⏳</span>
                                            <div class="flex-1">
                                                <h4 class="font-medium text-slate-900 dark:text-white text-sm mb-1 line-clamp-2">
                                                    {{ vote.question }}
                                                </h4>
                                                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                                    <span>Fin {{ vote.fin }}</span>
                                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ vote.total_votes }} votes</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Mon Activité -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-slate-600 to-slate-700 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">📊</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Mon Activité</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Votre historique récent</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <div v-if="userActivity?.derniers_topics?.length > 0" class="mb-4">
                                    <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Mes sujets</h4>
                                    <div class="space-y-2">
                                        <Link
                                            v-for="topic in userActivity.derniers_topics"
                                            :key="topic.id"
                                            :href="`/topics/${topic.id}`"
                                            class="block p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        >
                                            <p class="font-medium text-slate-900 dark:text-white text-sm truncate">{{ topic.titre }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ topic.date }}</p>
                                        </Link>
                                    </div>
                                </div>
                                
                                <div v-if="userActivity?.derniers_votes_loi?.length > 0">
                                    <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Mes votes législatifs</h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="vote in userActivity.derniers_votes_loi"
                                            :key="vote.id"
                                            class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50"
                                        >
                                            <div class="flex items-center gap-2 mb-1">
                                                <span>{{ vote.type_vote === 'upvote' ? '👍' : '👎' }}</span>
                                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">N° {{ vote.numero }}</span>
                                            </div>
                                            <p class="text-xs text-slate-900 dark:text-white truncate">{{ vote.titre }}</p>
                                            <p class="text-xs text-slate-500 mt-1">{{ vote.date }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="(!userActivity?.derniers_topics || userActivity.derniers_topics.length === 0) && (!userActivity?.derniers_votes_loi || userActivity.derniers_votes_loi.length === 0)" class="text-center py-8 text-slate-500 dark:text-slate-400">
                                    <span class="text-3xl mb-2 block">📝</span>
                                    <p class="text-sm">Aucune activité récente</p>
                                    <Link href="/topics/create" class="text-sm text-emerald-600 hover:underline mt-2 inline-block">
                                        Créer votre premier sujet →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Mes Représentants -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">📍</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Mes Élus</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Vos représentants</p>
                                    </div>
                                </div>
                                <Link :href="route('representants.mes-representants')" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                    Voir tout →
                                </Link>
                            </div>
                            
                            <div class="p-4">
                                <!-- Avec localisation -->
                                <div v-if="mesRepresentants?.hasLocation">
                                    <!-- Député -->
                                    <div v-if="mesRepresentants.depute" class="mb-4">
                                        <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                            <div class="w-12 h-12 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                                <img
                                                    v-if="mesRepresentants.depute.photo_url"
                                                    :src="mesRepresentants.depute.photo_url"
                                                    :alt="mesRepresentants.depute.nom_complet"
                                                    class="w-full h-full object-cover"
                                                />
                                                <div v-else class="w-full h-full flex items-center justify-center text-xl">👤</div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">
                                                    {{ mesRepresentants.depute.nom_complet }}
                                                </p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span
                                                        class="px-2 py-0.5 rounded text-xs font-semibold text-white"
                                                        :style="{ backgroundColor: mesRepresentants.depute.groupe_couleur }"
                                                    >
                                                        {{ mesRepresentants.depute.groupe_sigle }}
                                                    </span>
                                                </div>
                                            </div>
                                            <Link
                                                :href="route('representants.deputes.show', mesRepresentants.depute.id)"
                                                class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-800/30 rounded-lg transition-colors"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </div>

                                    <!-- Sénateurs -->
                                    <div v-if="mesRepresentants.senateurs && mesRepresentants.senateurs.length > 0">
                                        <div class="space-y-2">
                                            <div
                                                v-for="senateur in mesRepresentants.senateurs.slice(0, 2)"
                                                :key="senateur.id"
                                                class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl"
                                            >
                                                <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                                    <img
                                                        v-if="senateur.photo_url"
                                                        :src="senateur.photo_url"
                                                        :alt="senateur.nom_complet"
                                                        class="w-full h-full object-cover"
                                                    />
                                                    <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium text-slate-900 dark:text-white text-sm truncate">
                                                        {{ senateur.nom_complet }}
                                                    </p>
                                                    <span
                                                        class="px-2 py-0.5 rounded text-xs font-semibold text-white"
                                                        :style="{ backgroundColor: senateur.groupe_couleur }"
                                                    >
                                                        {{ senateur.groupe_sigle }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sans localisation -->
                                <div v-else class="text-center py-6">
                                    <span class="text-4xl mb-3 block">📍</span>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                        Configurez votre localisation pour découvrir vos élus
                                    </p>
                                    <Link
                                        :href="route('profile.edit')"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Configurer
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- 📅 Prochaines Réunions -->
                        <div v-if="prochainesReunions && prochainesReunions.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                            <span class="text-lg">📅</span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 dark:text-white">Agenda AN</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Prochaines réunions</p>
                                        </div>
                                    </div>
                                    <Link :href="route('parlement.calendrier.index')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Voir tout →
                                    </Link>
                                </div>
                            </div>
                            
                            <div class="p-4 space-y-2">
                                <Link
                                    v-for="reunion in prochainesReunions"
                                    :key="reunion.uid"
                                    :href="route('parlement.calendrier.show', reunion.uid)"
                                    class="block p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="text-2xl shrink-0">{{ reunion.emoji }}</div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">
                                                {{ reunion.organe || reunion.type }}
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                {{ reunion.titre }}
                                            </p>
                                            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                                📆 {{ reunion.date }} · {{ reunion.date_relative }}
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Groupes Parlementaires -->
                        <div v-if="groupesActifs && groupesActifs.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">🏛️</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Groupes AN</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Par effectif</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 space-y-2">
                                <div
                                    v-for="(groupe, index) in groupesActifs"
                                    :key="groupe.uid"
                                    class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                                >
                                    <div class="flex items-center justify-center w-7 h-7 rounded-full text-white font-bold text-xs" :style="{ backgroundColor: groupe.couleur }">
                                        {{ index + 1 }}
                                    </div>
                                    <div 
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0" 
                                        :style="{ backgroundColor: groupe.couleur }"
                                    >
                                        {{ groupe.nb_membres }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-900 dark:text-white text-sm">{{ groupe.sigle }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ groupe.nom }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
