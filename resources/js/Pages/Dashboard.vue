<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { useNavigation } from '@/composables/useNavigation';
import { Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const { mesElus, institutions, legislatif, agir, comprendre } = useNavigation();

function useAnimatedCounter(target, duration = 1200) {
    const current = ref(0);
    onMounted(() => {
        if (!target || target <= 0) return;
        const start = performance.now();
        const step = (now) => {
            const t = Math.min((now - start) / duration, 1);
            const ease = 1 - Math.pow(1 - t, 3);
            current.value = Math.round(ease * target);
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });
    return current;
}

const props = defineProps({
    trendingTopics: Array,
    propositionsLegislatives: Array,
    votesEnCours: Array,
    globalStats: Object,
    userActivity: Object,
    groupesParlementaires: Array,
    votesLegislatifs: Array,
    mesRepresentants: Object,
    derniersScrutins: { type: Array, default: () => [] },
    topDeputes: { type: Array, default: () => [] },
    topSenateurs: { type: Array, default: () => [] },
    groupesActifs: { type: Array, default: () => [] },
    prochainesReunions: { type: Array, default: () => [] },
    platformStats: { type: Object, default: () => ({}) },
    franceStats: { type: Object, default: () => ({}) },
    isNewUser: { type: Boolean, default: false },
});

const user = computed(() => usePage().props.auth.user);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bonjour';
    if (hour < 18) return 'Bon après-midi';
    return 'Bonsoir';
});

// ── Hero counters (6 stats) ──
const animDeputes = useAnimatedCounter(props.platformStats?.nb_deputes || 577);
const animSenateurs = useAnimatedCounter(props.platformStats?.nb_senateurs || 348);
const animMaires = useAnimatedCounter(props.platformStats?.nb_maires || 0);
const animScrutins = useAnimatedCounter(props.platformStats?.nb_scrutins || 0);
const animAmendements = useAnimatedCounter(props.platformStats?.nb_amendements_an || 0);
const animGouvernements = useAnimatedCounter(props.platformStats?.nb_gouvernements || 0);

// ── Discovery block ──
const showDiscover = ref(false);
onMounted(() => {
    if (props.isNewUser || localStorage.getItem('civicdash_hide_discover') !== '1') {
        showDiscover.value = true;
    }
});
const dismissDiscover = () => {
    showDiscover.value = false;
    localStorage.setItem('civicdash_hide_discover', '1');
};

// ── Chart: use full pre-calculated counts from platformStats ──
const scrutinsAdoptes = computed(() => props.platformStats?.nb_scrutins_adoptes || 0);
const scrutinsRejetes = computed(() => props.platformStats?.nb_scrutins_rejetes || 0);

const scrutinsChartData = computed(() => ({
    labels: [`Adoptés (${scrutinsAdoptes.value})`, `Rejetés (${scrutinsRejetes.value})`],
    datasets: [{
        data: [scrutinsAdoptes.value, scrutinsRejetes.value],
        backgroundColor: ['#10b981', '#ef4444'],
        borderWidth: 0,
        hoverOffset: 6,
    }],
}));
const scrutinsChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
    },
};

const groupesChartData = computed(() => ({
    labels: props.groupesActifs.map(g => g.sigle),
    datasets: [{
        data: props.groupesActifs.map(g => g.nb_membres),
        backgroundColor: props.groupesActifs.map(g => g.couleur || '#6B7280'),
        borderWidth: 0,
        borderRadius: 4,
    }],
}));
const groupesChartOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { display: true } },
        y: { grid: { display: false } },
    },
};

// ── Section cards ──
const sectionCards = computed(() => [
    {
        key: 'mes-elus',
        label: mesElus.value.label,
        emoji: mesElus.value.emoji,
        description: 'Vos représentants locaux',
        href: route('representants.mes-representants'),
        color: 'from-indigo-500 to-violet-600',
        borderHover: 'hover:border-indigo-500',
        teaser: `${props.platformStats?.nb_deputes || 577} députés, ${props.platformStats?.nb_senateurs || 348} sénateurs`,
        items: mesElus.value.items.slice(0, 3),
    },
    {
        key: 'institutions',
        label: institutions.value.label,
        emoji: institutions.value.emoji,
        description: 'AN, Sénat, Gouvernement',
        href: route('representants.deputes.index'),
        color: 'from-sky-500 to-blue-600',
        borderHover: 'hover:border-sky-500',
        teaser: `${props.platformStats?.nb_gouvernements || 51} gouvernements depuis 1958`,
        items: [
            institutions.value.columns[0].items[0],
            institutions.value.columns[1].items[0],
            institutions.value.columns[2].items[1],
        ],
    },
    {
        key: 'legislatif',
        label: legislatif.value.label,
        emoji: legislatif.value.emoji,
        description: 'Lois, scrutins, budget',
        href: route('lois.index'),
        color: 'from-teal-500 to-cyan-600',
        borderHover: 'hover:border-teal-500',
        teaser: `${(props.platformStats?.nb_scrutins || 0).toLocaleString('fr-FR')} scrutins analysés`,
        items: legislatif.value.items.slice(0, 3),
    },
    {
        key: 'agir',
        label: agir.value.label,
        emoji: agir.value.emoji,
        description: 'Participez au débat',
        href: route('participation.ideas.index'),
        color: 'from-emerald-500 to-green-600',
        borderHover: 'hover:border-emerald-500',
        teaser: `${props.globalStats?.total_topics || 0} discussions ouvertes`,
        items: agir.value.items.filter(i => !i.divider).slice(0, 3),
    },
    {
        key: 'comprendre',
        label: comprendre.value.label,
        emoji: comprendre.value.emoji,
        description: 'Démocratie expliquée',
        href: route('democratie.index'),
        color: 'from-amber-500 to-orange-600',
        borderHover: 'hover:border-amber-500',
        teaser: 'Guides, lexique, ressources',
        items: comprendre.value.items.filter(i => !i.divider).slice(0, 3),
    },
]);

// ── Quick links ──
const quickLinks = [
    { label: 'Statistiques France', emoji: '📊', routeName: 'statistics.france' },
    { label: "Budget de l'État", emoji: '💰', routeName: 'budget-etat.index' },
    { label: 'Constitution', emoji: '📜', routeName: 'legislation.constitution' },
    { label: 'Débats Sénat', emoji: '🏛️', routeName: 'debats.senat.index' },
    { label: 'Gouvernements', emoji: '🏢', routeName: 'donnees.gouvernements' },
    { label: 'Questions au Gouv.', emoji: '❓', routeName: 'questions.index' },
];

// ── Helpers ──
const formatNumber = (n) => {
    if (n == null) return '—';
    if (n >= 1e9) return (n / 1e9).toFixed(1).replace('.', ',') + ' Mds';
    if (n >= 1e6) return (n / 1e6).toFixed(1).replace('.', ',') + ' M';
    return Number(n).toLocaleString('fr-FR');
};

const getTopicBadgeColor = (type) => {
    const colors = {
        'question': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'proposal': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'debate': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        'announcement': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
    return colors[type] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
};

const getTopicIcon = (type) => {
    const icons = { 'question': '❓', 'proposal': '💡', 'debate': '💬', 'announcement': '📢' };
    return icons[type] || '📝';
};

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

            <!-- ═══════════ HERO BANNER ═══════════ -->
            <div class="relative bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 dark:from-emerald-800 dark:via-teal-800 dark:to-cyan-800 overflow-hidden">
                <div class="absolute inset-0 opacity-10">
                    <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="dashboard-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1" class="text-white"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#dashboard-grid)" />
                    </svg>
                </div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-cyan-400/20 rounded-full blur-3xl"></div>

                <div class="relative px-4 sm:px-6 lg:px-8 xl:px-12 py-8 lg:py-12">
                    <nav class="flex items-center gap-2 text-sm text-emerald-100 mb-6">
                        <Link :href="route('dashboard')" class="hover:text-white transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </Link>
                        <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-white font-medium">Tableau de bord</span>
                    </nav>

                    <div class="mb-6">
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white flex items-center gap-3">
                            <span class="text-3xl lg:text-4xl">👋</span>
                            {{ greeting }}, {{ user?.name?.split(' ')[0] || 'Citoyen' }}
                        </h1>
                        <p class="text-emerald-100 mt-2 text-lg">
                            Bienvenue sur votre tableau de bord CivicDash
                        </p>
                    </div>

                    <!-- 6 animated counters -->
                    <div data-tour="dashboard-stats" class="grid grid-cols-3 sm:grid-cols-6 gap-2 sm:gap-3">
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-3 text-center border border-white/10">
                            <div class="text-xl sm:text-2xl font-bold text-white">{{ animDeputes.toLocaleString('fr-FR') }}</div>
                            <div class="text-[10px] sm:text-xs text-emerald-100">Députés</div>
                        </div>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-3 text-center border border-white/10">
                            <div class="text-xl sm:text-2xl font-bold text-white">{{ animSenateurs.toLocaleString('fr-FR') }}</div>
                            <div class="text-[10px] sm:text-xs text-emerald-100">Sénateurs</div>
                        </div>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-3 text-center border border-white/10">
                            <div class="text-xl sm:text-2xl font-bold text-white">{{ animMaires.toLocaleString('fr-FR') }}</div>
                            <div class="text-[10px] sm:text-xs text-emerald-100">Maires</div>
                        </div>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-3 text-center border border-white/10">
                            <div class="text-xl sm:text-2xl font-bold text-white">{{ animScrutins.toLocaleString('fr-FR') }}</div>
                            <div class="text-[10px] sm:text-xs text-emerald-100">Scrutins</div>
                        </div>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-3 text-center border border-white/10">
                            <div class="text-xl sm:text-2xl font-bold text-white">{{ animAmendements.toLocaleString('fr-FR') }}</div>
                            <div class="text-[10px] sm:text-xs text-emerald-100">Amendements</div>
                        </div>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-3 text-center border border-white/10">
                            <div class="text-xl sm:text-2xl font-bold text-white">{{ animGouvernements.toLocaleString('fr-FR') }}</div>
                            <div class="text-[10px] sm:text-xs text-emerald-100">Gouvernements</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════ MAIN CONTENT ═══════════ -->
            <div class="px-4 sm:px-6 lg:px-8 xl:px-12 py-8 -mt-4 space-y-8">

                <!-- ── Discovery Block (conditional) ── -->
                <div v-if="showDiscover" class="relative bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/40 dark:to-purple-950/40 border border-indigo-200 dark:border-indigo-800 rounded-2xl p-6">
                    <button @click="dismissDiscover" class="absolute top-3 right-3 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-white/60 dark:hover:bg-slate-700 transition-colors" title="Fermer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Bienvenue sur CivicDash</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Votre plateforme citoyenne pour comprendre et participer à la vie démocratique.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <Link :href="route('representants.mes-representants')" class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-600 hover:shadow-md transition-all">
                            <span class="text-2xl">👥</span>
                            <div>
                                <p class="font-semibold text-sm text-slate-900 dark:text-white">Suivez vos élus</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Députés, sénateurs, maires</p>
                            </div>
                        </Link>
                        <Link :href="route('lois.index')" class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md transition-all">
                            <span class="text-2xl">📜</span>
                            <div>
                                <p class="font-semibold text-sm text-slate-900 dark:text-white">Explorez la législation</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Lois, scrutins, amendements</p>
                            </div>
                        </Link>
                        <Link :href="route('participation.ideas.index')" class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-emerald-400 dark:hover:border-emerald-600 hover:shadow-md transition-all">
                            <span class="text-2xl">💬</span>
                            <div>
                                <p class="font-semibold text-sm text-slate-900 dark:text-white">Participez aux débats</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Proposez, discutez, votez</p>
                            </div>
                        </Link>
                        <Link :href="route('democratie.index')" class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-amber-400 dark:hover:border-amber-600 hover:shadow-md transition-all">
                            <span class="text-2xl">🎓</span>
                            <div>
                                <p class="font-semibold text-sm text-slate-900 dark:text-white">Comprenez la démocratie</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Guides et ressources</p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- ── Municipales 2026 Banner ── -->
                <Link :href="route('elections.municipales.index')" class="block bg-gradient-to-r from-violet-600 to-purple-600 dark:from-violet-800 dark:to-purple-800 rounded-2xl p-5 text-white hover:shadow-lg transition-all group">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🗳️</span>
                            <div>
                                <h2 class="text-lg font-bold">Municipales 2026</h2>
                                <p class="text-sm text-violet-200">15 et 22 mars 2026 — Découvrez les candidats de votre commune</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm font-medium text-violet-100 group-hover:text-white transition-colors">
                            <span>Explorer les candidatures</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 mt-3 text-xs">
                        <span class="bg-white/15 px-3 py-1 rounded-full">📅 1er tour : 15 mars</span>
                        <span class="bg-white/15 px-3 py-1 rounded-full">📅 2nd tour : 22 mars</span>
                        <span class="bg-white/15 px-3 py-1 rounded-full">🔍 Recherche par commune</span>
                        <span class="bg-white/15 px-3 py-1 rounded-full">📋 Comparaison des programmes</span>
                    </div>
                </Link>

                <!-- ── Sujets Tendances (moved up - full width) ── -->
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
                        <Link :href="route('participation.ideas.index')" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                            Voir tout →
                        </Link>
                    </div>
                    <div class="p-4">
                        <div v-if="!trendingTopics || trendingTopics.length === 0" class="text-center py-10 text-slate-500 dark:text-slate-400">
                            <span class="text-4xl mb-3 block">💬</span>
                            <p class="mb-2">Aucune discussion pour le moment</p>
                            <Link :href="route('participation.ideas.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                                Proposer une idée citoyenne →
                            </Link>
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            <Link
                                v-for="topic in trendingTopics"
                                :key="topic.id"
                                :href="route('participation.ideas.show', topic.slug || topic.id)"
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
                                        <h4 class="font-semibold text-slate-900 dark:text-white mb-1 line-clamp-1">{{ topic.titre }}</h4>
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

                <!-- ── Interaction CTA ── -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Link :href="route('representants.mes-representants')" class="flex items-center gap-4 p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-600 hover:shadow-md transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-xl group-hover:scale-110 transition-transform">📍</div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white text-sm">Suivez vos élus</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Configurez votre localisation et recevez des alertes sur l'activité de vos représentants</p>
                        </div>
                    </Link>
                    <Link :href="route('participation.ideas.create')" class="flex items-center gap-4 p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-emerald-400 dark:hover:border-emerald-600 hover:shadow-md transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center text-white text-xl group-hover:scale-110 transition-transform">💡</div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white text-sm">Proposez une idée</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Lancez un débat citoyen, proposez des solutions, votez sur les idées des autres</p>
                        </div>
                    </Link>
                    <Link :href="route('lois.index')" class="flex items-center gap-4 p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 flex items-center justify-center text-white text-xl group-hover:scale-110 transition-transform">🗳️</div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white text-sm">Votez sur les lois</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Donnez votre avis citoyen sur les lois en cours d'examen au Parlement</p>
                        </div>
                    </Link>
                </div>

                <!-- ── France Stats Banner ── -->
                <div v-if="franceStats && franceStats.population" class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-800 dark:to-indigo-800 rounded-2xl p-6 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🇫🇷</span>
                            <h2 class="text-lg font-bold">La France en chiffres</h2>
                            <span v-if="franceStats.annee" class="text-xs bg-white/20 px-2 py-0.5 rounded-full">{{ franceStats.annee }}</span>
                        </div>
                        <Link :href="route('statistics.france')" class="text-sm font-medium text-blue-100 hover:text-white transition-colors mt-2 sm:mt-0">
                            Toutes les statistiques →
                        </Link>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/10 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ formatNumber(franceStats.population) }}</div>
                            <div class="text-xs text-blue-200 mt-1">Habitants</div>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ franceStats.pib ? formatNumber(franceStats.pib * 1e9) : '—' }}</div>
                            <div class="text-xs text-blue-200 mt-1">PIB</div>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ franceStats.chomage != null ? franceStats.chomage + '%' : '—' }}</div>
                            <div class="text-xs text-blue-200 mt-1">Chômage</div>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ franceStats.dette ? formatNumber(franceStats.dette * 1e9) : '—' }}</div>
                            <div class="text-xs text-blue-200 mt-1">Dette publique</div>
                        </div>
                    </div>
                </div>

                <!-- ── Section Cards ── -->
                <div data-tour="dashboard-quick-actions" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="section in sectionCards"
                        :key="section.key"
                        :href="section.href"
                        :data-tour="'dashboard-section-' + section.key"
                        class="group bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all overflow-hidden"
                        :class="section.borderHover"
                    >
                        <div class="p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform bg-gradient-to-br text-white" :class="section.color">
                                    <span class="text-xl">{{ section.emoji }}</span>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ section.label }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ section.description }}</p>
                                </div>
                            </div>
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-2">{{ section.teaser }}</p>
                            <div class="space-y-1.5">
                                <div v-for="item in section.items" :key="item.title" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                    <span class="text-sm" aria-hidden="true">{{ item.icon }}</span>
                                    <span class="truncate">{{ item.title }}</span>
                                    <span v-if="item.badge" class="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">{{ item.badge }}</span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- ── Charts Row ── -->
                <div v-if="(scrutinsAdoptes + scrutinsRejetes) > 0 || groupesActifs.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-if="(scrutinsAdoptes + scrutinsRejetes) > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <span>🗳️</span> Scrutins AN : adoptés vs rejetés
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Sur {{ (scrutinsAdoptes + scrutinsRejetes).toLocaleString('fr-FR') }} scrutins analysés</p>
                        <div class="h-56">
                            <Doughnut :data="scrutinsChartData" :options="scrutinsChartOptions" />
                        </div>
                    </div>
                    <div v-if="groupesActifs.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <span>🏛️</span> Groupes parlementaires AN
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Effectifs par groupe politique</p>
                        <div class="h-56">
                            <Bar :data="groupesChartData" :options="groupesChartOptions" />
                        </div>
                    </div>
                </div>

                <!-- ═══════════ MAIN GRID ═══════════ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <!-- Main column -->
                    <div class="lg:col-span-2 xl:col-span-3 space-y-6">

                        <!-- Propositions de Loi -->
                        <div v-if="propositionsLegislatives && propositionsLegislatives.length > 0" data-tour="dashboard-lois" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                        <span class="text-lg">📜</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Propositions de Loi</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Cliquez pour voter et voir le détail</p>
                                    </div>
                                </div>
                                <Link :href="route('lois.index')" class="text-sm font-medium text-purple-600 hover:text-purple-700 dark:text-purple-400">
                                    Voir tout →
                                </Link>
                            </div>
                            <div class="p-4 space-y-3">
                                <Link
                                    v-for="prop in propositionsLegislatives"
                                    :key="prop.id"
                                    :href="prop.loicod ? route('lois.show', prop.loicod) : '#'"
                                    class="block p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 hover:bg-purple-50 dark:hover:bg-purple-900/20 border border-slate-200 dark:border-slate-600 hover:border-purple-500/30 transition-all cursor-pointer"
                                >
                                    <div class="flex items-start gap-3 mb-3">
                                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            {{ prop.source === 'assemblee' ? 'AN' : 'SN' }}
                                        </span>
                                        <div class="flex-1">
                                            <span class="text-xs text-slate-500 dark:text-slate-400">N° {{ prop.numero }}</span>
                                            <h4 class="font-medium text-slate-900 dark:text-white text-sm line-clamp-2">{{ prop.titre }}</h4>
                                        </div>
                                        <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
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
                                            <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500" :style="{ width: (prop.votes_stats?.pourcentage_pour || 0) + '%' }"></div>
                                        </div>
                                        <span :class="`text-sm font-bold ${getScoreClass(prop.votes_stats?.score || 0)}`">
                                            {{ prop.votes_stats?.score > 0 ? '+' : '' }}{{ prop.votes_stats?.score || 0 }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Derniers Scrutins AN -->
                        <div data-tour="dashboard-scrutins" v-if="derniersScrutins && derniersScrutins.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
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
                                            <h4 class="font-medium text-slate-900 dark:text-white text-sm line-clamp-2">{{ scrutin.titre }}</h4>
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
                            <div v-if="topDeputes && topDeputes.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center"><span class="text-lg">🏆</span></div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 dark:text-white">Top Députés</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Les plus actifs en votes</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 space-y-2">
                                    <Link v-for="(depute, index) in topDeputes" :key="depute.uid" :href="route('representants.deputes.show', depute.uid)" class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-white font-bold text-sm">{{ index + 1 }}</div>
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                            <img v-if="depute.photo" :src="depute.photo" :alt="depute.nom" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ depute.nom }}</p>
                                            <span v-if="depute.groupe" class="px-2 py-0.5 rounded text-xs font-semibold text-white" :style="{ backgroundColor: depute.groupe_couleur }">{{ depute.groupe }}</span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ depute.nb_votes }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">votes</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                            <div v-if="topSenateurs && topSenateurs.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center"><span class="text-lg">🏆</span></div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 dark:text-white">Top Sénateurs</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Les plus actifs en amendements</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 space-y-2">
                                    <Link v-for="(senateur, index) in topSenateurs" :key="senateur.matricule" :href="route('representants.senateurs.show', senateur.matricule)" class="flex items-center gap-3 p-3 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-white font-bold text-sm">{{ index + 1 }}</div>
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                            <img v-if="senateur.photo" :src="senateur.photo" :alt="senateur.nom" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ senateur.nom }}</p>
                                            <span v-if="senateur.groupe" class="px-2 py-0.5 rounded text-xs font-semibold text-white" :style="{ backgroundColor: senateur.groupe_couleur }">{{ senateur.groupe }}</span>
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

                    <!-- ═══════════ SIDEBAR ═══════════ -->
                    <div class="space-y-6">

                        <!-- Votes en Cours -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center"><span class="text-lg">⏳</span></div>
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
                                    <Link v-for="vote in votesEnCours" :key="vote.id" :href="route('participation.ideas.show', vote.topic_slug || vote.topic_id)" class="block p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 border border-transparent hover:border-emerald-500/30 transition-all">
                                        <div class="flex items-start gap-3">
                                            <span v-if="vote.a_vote" class="text-2xl">✅</span>
                                            <span v-else class="text-2xl animate-pulse">⏳</span>
                                            <div class="flex-1">
                                                <h4 class="font-medium text-slate-900 dark:text-white text-sm mb-1 line-clamp-2">{{ vote.question }}</h4>
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
                                    <div class="w-10 h-10 bg-gradient-to-br from-slate-600 to-slate-700 rounded-xl flex items-center justify-center"><span class="text-lg">📊</span></div>
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
                                        <Link v-for="topic in userActivity.derniers_topics" :key="topic.id" :href="route('participation.ideas.show', topic.slug || topic.id)" class="block p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <p class="font-medium text-slate-900 dark:text-white text-sm truncate">{{ topic.titre }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ topic.date }}</p>
                                        </Link>
                                    </div>
                                </div>
                                <div v-if="userActivity?.derniers_votes_loi?.length > 0">
                                    <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Mes votes législatifs</h4>
                                    <div class="space-y-2">
                                        <div v-for="vote in userActivity.derniers_votes_loi" :key="vote.id" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
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
                                    <Link :href="route('participation.ideas.create')" class="text-sm text-emerald-600 hover:underline mt-2 inline-block">
                                        Créer votre première idée →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Mes Représentants -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center"><span class="text-lg">📍</span></div>
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
                                <div v-if="mesRepresentants?.hasLocation">
                                    <div v-if="mesRepresentants.depute" class="mb-4">
                                        <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                            <div class="w-12 h-12 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                                <img v-if="mesRepresentants.depute.photo_url" :src="mesRepresentants.depute.photo_url" :alt="mesRepresentants.depute.nom_complet" class="w-full h-full object-cover" />
                                                <div v-else class="w-full h-full flex items-center justify-center text-xl">👤</div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ mesRepresentants.depute.nom_complet }}</p>
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold text-white" :style="{ backgroundColor: mesRepresentants.depute.groupe_couleur }">{{ mesRepresentants.depute.groupe_sigle }}</span>
                                            </div>
                                            <Link :href="route('representants.deputes.show', mesRepresentants.depute.id)" class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-800/30 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                            </Link>
                                        </div>
                                    </div>
                                    <div v-if="mesRepresentants.senateurs && mesRepresentants.senateurs.length > 0" class="space-y-2">
                                        <div v-for="senateur in mesRepresentants.senateurs.slice(0, 2)" :key="senateur.id" class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex-shrink-0">
                                                <img v-if="senateur.photo_url" :src="senateur.photo_url" :alt="senateur.nom_complet" class="w-full h-full object-cover" />
                                                <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-slate-900 dark:text-white text-sm truncate">{{ senateur.nom_complet }}</p>
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold text-white" :style="{ backgroundColor: senateur.groupe_couleur }">{{ senateur.groupe_sigle }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-6">
                                    <span class="text-4xl mb-3 block">📍</span>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Configurez votre localisation pour découvrir vos élus</p>
                                    <Link :href="route('profile.edit')" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Configurer
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Agenda AN -->
                        <div v-if="prochainesReunions && prochainesReunions.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center"><span class="text-lg">📅</span></div>
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
                                <Link v-for="reunion in prochainesReunions" :key="reunion.uid" :href="route('parlement.calendrier.show', reunion.uid)" class="block p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800">
                                    <div class="flex items-start gap-3">
                                        <div class="text-2xl shrink-0">{{ reunion.emoji }}</div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ reunion.organe || reunion.type }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ reunion.titre }}</p>
                                            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">{{ reunion.date }} · {{ reunion.date_relative }}</p>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ QUICK LINKS ═══════════ -->
                <div class="border-t border-slate-200 dark:border-slate-700 pt-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <span>🧭</span> Explorez aussi
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.routeName"
                            :href="route(link.routeName)"
                            class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-emerald-400 dark:hover:border-emerald-600 hover:shadow-md transition-all text-center group"
                        >
                            <span class="text-2xl group-hover:scale-110 transition-transform">{{ link.emoji }}</span>
                            <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ link.label }}</span>
                        </Link>
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
