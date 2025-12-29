<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    stats: {
        type: Object,
        default: () => ({
            deputes: 0,
            senateurs: 0,
            scrutins: 0,
            amendements: 0,
        }),
    },
    derniersScrutins: {
        type: Array,
        default: () => [],
    },
    derniersAmendements: {
        type: Array,
        default: () => [],
    },
});

// Compteurs animés
const animatedStats = ref({
    deputes: 0,
    senateurs: 0,
    scrutins: 0,
    amendements: 0,
});

// Animation des compteurs
const animateCounters = () => {
    const duration = 2000;
    const steps = 60;
    const interval = duration / steps;
    
    const targets = {
        deputes: props.stats.deputes || 577,
        senateurs: props.stats.senateurs || 348,
        scrutins: props.stats.scrutins || 3876,
        amendements: props.stats.amendements || 63000,
    };
    
    let step = 0;
    const timer = setInterval(() => {
        step++;
        const progress = step / steps;
        const easeOut = 1 - Math.pow(1 - progress, 3);
        
        animatedStats.value = {
            deputes: Math.floor(targets.deputes * easeOut),
            senateurs: Math.floor(targets.senateurs * easeOut),
            scrutins: Math.floor(targets.scrutins * easeOut),
            amendements: Math.floor(targets.amendements * easeOut),
        };
        
        if (step >= steps) {
            clearInterval(timer);
            animatedStats.value = targets;
        }
    }, interval);
};

// État du menu mobile
const mobileMenuOpen = ref(false);

// Dark mode
const isDarkMode = ref(false);

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// Scroll animation observer
const observerElements = ref([]);

onMounted(() => {
    // Init dark mode
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }
    
    // Lancer animation des compteurs
    setTimeout(animateCounters, 500);
    
    // Intersection Observer pour animations au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.scroll-reveal').forEach(el => {
        observer.observe(el);
    });
});

// Format grands nombres
const formatNumber = (num) => {
    if (num >= 1000) {
        return new Intl.NumberFormat('fr-FR').format(num);
    }
    return num;
};
</script>

<template>
    <Head title="CivicDash - Transparence Démocratique" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-slate-900"></div>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">CivicDash</h1>
                            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider">Transparence Démocratique</p>
                        </div>
                    </div>
                    
                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#fonctionnalites" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors">Fonctionnalités</a>
                        <a href="#donnees" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors">Données</a>
                        <a href="#demo" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors">Démo</a>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggleDarkMode"
                            class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                            :title="isDarkMode ? 'Mode clair' : 'Mode sombre'"
                        >
                            <svg v-if="isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                        
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium rounded-lg transition-all shadow-md hover:shadow-lg hover:shadow-emerald-500/25 text-sm"
                        >
                            Se connecter
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 overflow-hidden">
            <!-- Background Effects -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute top-0 -left-1/4 w-1/2 h-1/2 bg-gradient-to-br from-emerald-200/40 to-transparent dark:from-emerald-900/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 -right-1/4 w-1/2 h-1/2 bg-gradient-to-tl from-cyan-200/40 to-transparent dark:from-cyan-900/20 rounded-full blur-3xl"></div>
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiM5QzkyQUMiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-50"></div>
            </div>
            
            <div class="relative px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <div class="text-center">
                    <!-- Badge -->
                    <div class="inline-flex items-center px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-sm font-semibold mb-8 animate-pulse-slow">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                        Données parlementaires en temps réel
                    </div>
                    
                    <!-- Titre -->
                    <h2 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold text-slate-900 dark:text-white mb-6 tracking-tight">
                        <span class="block">Comprendre la</span>
                        <span class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 bg-clip-text text-transparent">Démocratie Française</span>
                    </h2>
                    
                    <!-- Sous-titre -->
                    <p class="max-w-2xl mx-auto text-lg sm:text-xl text-slate-600 dark:text-slate-400 mb-10 leading-relaxed">
                        Suivez l'activité de vos élus, explorez les scrutins, et participez au débat citoyen. 
                        <span class="font-semibold text-slate-800 dark:text-slate-200">Open source</span> et 
                        <span class="font-semibold text-slate-800 dark:text-slate-200">transparent</span>.
                    </p>
                    
                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                        <Link
                            :href="route('login')"
                            class="group px-8 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-xl transition-all shadow-xl hover:shadow-2xl hover:shadow-emerald-500/30 flex items-center justify-center gap-2"
                        >
                            Explorer la plateforme
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                        
                        <a
                            href="https://github.com/civis-consilium/civicdash"
                            target="_blank"
                            class="px-8 py-4 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl transition-all border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            Voir sur GitHub
                        </a>
                    </div>
                    
                    <!-- Statistiques animées -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-6xl mx-auto">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-3xl sm:text-4xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ formatNumber(animatedStats.deputes) }}
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Députés</div>
                        </div>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-3xl sm:text-4xl font-bold text-teal-600 dark:text-teal-400 tabular-nums">
                                {{ formatNumber(animatedStats.senateurs) }}
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Sénateurs</div>
                        </div>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-3xl sm:text-4xl font-bold text-cyan-600 dark:text-cyan-400 tabular-nums">
                                {{ formatNumber(animatedStats.scrutins) }}
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Scrutins</div>
                        </div>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-3xl sm:text-4xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">
                                {{ formatNumber(animatedStats.amendements) }}+
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Amendements</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Activité Parlementaire Récente -->
        <section v-if="derniersScrutins.length > 0 || derniersAmendements.length > 0" class="py-20 bg-white dark:bg-slate-800/50">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <div class="text-center mb-12 scroll-reveal">
                    <h3 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                        Activité Parlementaire
                    </h3>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                        Les derniers scrutins et amendements adoptés à l'Assemblée Nationale
                    </p>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Derniers Scrutins -->
                    <div v-if="derniersScrutins.length > 0" class="scroll-reveal">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <h4 class="text-xl font-bold text-slate-900 dark:text-white">Derniers Scrutins</h4>
                        </div>
                        
                        <div class="space-y-4">
                            <div
                                v-for="scrutin in derniersScrutins"
                                :key="scrutin.uid"
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 hover:border-blue-500/50 transition-all"
                            >
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">N°{{ scrutin.numero }}</span>
                                            <span class="text-xs text-slate-400">{{ scrutin.date }}</span>
                                        </div>
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 line-clamp-2">
                                            {{ scrutin.titre }}
                                        </p>
                                    </div>
                                    <span
                                        class="shrink-0 px-3 py-1 text-xs font-bold rounded-full"
                                        :class="scrutin.adopte ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                    >
                                        {{ scrutin.adopte ? 'Adopté' : 'Rejeté' }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-4 text-xs">
                                    <span class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                        {{ scrutin.pour }} pour
                                    </span>
                                    <span class="flex items-center gap-1 text-red-600 dark:text-red-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        {{ scrutin.contre }} contre
                                    </span>
                                    <span class="text-slate-500 dark:text-slate-400">
                                        {{ scrutin.abstention }} abstentions
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Derniers Amendements Adoptés -->
                    <div v-if="derniersAmendements.length > 0" class="scroll-reveal">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <h4 class="text-xl font-bold text-slate-900 dark:text-white">Amendements Adoptés</h4>
                        </div>
                        
                        <div class="space-y-4">
                            <div
                                v-for="amendement in derniersAmendements"
                                :key="amendement.uid"
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 hover:border-emerald-500/50 transition-all"
                            >
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">N°{{ amendement.numero }}</span>
                                        <span class="text-xs text-slate-400">{{ amendement.date }}</span>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        Adopté
                                    </span>
                                </div>
                                
                                <p v-if="amendement.auteur" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-2">
                                    {{ amendement.auteur }}
                                </p>
                                
                                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                                    {{ amendement.objet }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- CTA pour voir plus -->
                <div class="text-center mt-12 scroll-reveal">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                    >
                        Connectez-vous pour explorer toutes les données
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Fonctionnalités -->
        <section id="fonctionnalites" class="py-20 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-800">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <div class="text-center mb-16 scroll-reveal">
                    <h3 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                        Une plateforme complète
                    </h3>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                        Tout ce dont vous avez besoin pour suivre et comprendre l'activité parlementaire française
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-6">
                    <!-- Feature 1 -->
                    <div class="scroll-reveal group bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-emerald-500/50 transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Suivi des Élus</h4>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            Consultez les profils détaillés de vos députés et sénateurs : votes, amendements, présence, activité parlementaire.
                        </p>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="scroll-reveal group bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-teal-500/50 transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Scrutins & Votes</h4>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            Explorez tous les scrutins de l'Assemblée et du Sénat. Analysez les résultats par groupe politique ou par élu.
                        </p>
                    </div>
                    
                    <!-- Feature 3 -->
                    <div class="scroll-reveal group bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-cyan-500/50 transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Amendements</h4>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            Plus de 60 000 amendements indexés. Suivez leur parcours : adopté, rejeté, retiré, tombé.
                        </p>
                    </div>
                    
                    <!-- Feature 4 -->
                    <div class="scroll-reveal group bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-blue-500/50 transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Carte Interactive</h4>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            Visualisez la représentation politique par région et département. Trouvez vos élus locaux.
                        </p>
                    </div>
                    
                    <!-- Feature 5 -->
                    <div class="scroll-reveal group bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-indigo-500/50 transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Forum Citoyen</h4>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            Participez aux débats, créez des discussions, votez sur les propositions citoyennes.
                        </p>
                    </div>
                    
                    <!-- Feature 6 -->
                    <div class="scroll-reveal group bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-purple-500/50 transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Données HATVP</h4>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            Déclarations de patrimoine et d'intérêts des élus, issues de la Haute Autorité pour la Transparence.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Comment ça marche -->
        <section class="py-20 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-800">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <div class="text-center mb-16 scroll-reveal">
                    <h3 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                        Comment ça marche ?
                    </h3>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                        Des données officielles, actualisées automatiquement, présentées de manière accessible
                    </p>
                </div>
                
                <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    <div class="scroll-reveal text-center">
                        <div class="w-16 h-16 mx-auto mb-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center">
                            <span class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">1</span>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Collecte automatique</h4>
                        <p class="text-slate-600 dark:text-slate-400">
                            Nos scripts récupèrent quotidiennement les données de l'Assemblée nationale, du Sénat et de la HATVP.
                        </p>
                    </div>
                    
                    <div class="scroll-reveal text-center">
                        <div class="w-16 h-16 mx-auto mb-6 bg-teal-100 dark:bg-teal-900/30 rounded-2xl flex items-center justify-center">
                            <span class="text-3xl font-bold text-teal-600 dark:text-teal-400">2</span>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Enrichissement</h4>
                        <p class="text-slate-600 dark:text-slate-400">
                            Les données sont croisées, enrichies avec des photos et des statistiques calculées pour chaque élu.
                        </p>
                    </div>
                    
                    <div class="scroll-reveal text-center">
                        <div class="w-16 h-16 mx-auto mb-6 bg-cyan-100 dark:bg-cyan-900/30 rounded-2xl flex items-center justify-center">
                            <span class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">3</span>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Visualisation</h4>
                        <p class="text-slate-600 dark:text-slate-400">
                            Une interface moderne et responsive pour explorer, filtrer et comprendre l'activité parlementaire.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Démo -->
        <section id="demo" class="py-20 bg-white dark:bg-slate-800/50">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24 max-w-7xl mx-auto">
                <div class="scroll-reveal bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-8 md:p-12 text-white overflow-hidden relative">
                    <!-- Background decoration -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                    
                    <div class="relative">
                        <div class="text-center mb-8">
                            <h3 class="text-3xl sm:text-4xl font-bold mb-4">
                                🚀 Accéder à la démo
                            </h3>
                            <p class="text-lg text-emerald-100 max-w-2xl mx-auto">
                                Testez toutes les fonctionnalités avec nos comptes de démonstration
                            </p>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-6 mb-8">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                                <h4 class="font-bold text-lg mb-4 flex items-center gap-2">
                                    <span>👤</span> Compte Administrateur
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="font-mono bg-white/10 rounded-lg px-3 py-2">admin@civicdash.fr</div>
                                    <div class="text-emerald-100">Mot de passe : <span class="font-mono font-bold text-white">password</span></div>
                                </div>
                            </div>
                            
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                                <h4 class="font-bold text-lg mb-4 flex items-center gap-2">
                                    <span>🎭</span> Comptes Citoyens
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="font-mono bg-white/10 rounded-lg px-3 py-2">citoyen1@demo.civicdash.fr</div>
                                    <div class="text-emerald-100">Mot de passe : <span class="font-mono font-bold text-white">demo2025</span></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-2 px-8 py-4 bg-white text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition-colors shadow-xl"
                            >
                                Se connecter maintenant
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sources de données -->
        <section id="donnees" class="py-20 bg-slate-50 dark:bg-slate-900">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <div class="text-center mb-16 scroll-reveal">
                    <h3 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                        Sources de données officielles
                    </h3>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                        Toutes nos données proviennent des portails Open Data officiels
                    </p>
                </div>
                
                <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    <a href="https://data.assemblee-nationale.fr" target="_blank" class="scroll-reveal group bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-blue-500 hover:shadow-xl transition-all text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-4xl">🏛️</span>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Assemblée Nationale</h4>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Députés, scrutins, amendements, dossiers législatifs
                        </p>
                        <p class="text-blue-600 dark:text-blue-400 text-sm mt-4 font-medium">data.assemblee-nationale.fr →</p>
                    </a>
                    
                    <a href="https://data.senat.fr" target="_blank" class="scroll-reveal group bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-red-500 hover:shadow-xl transition-all text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 dark:bg-red-900/30 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-4xl">🏛️</span>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Sénat</h4>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Sénateurs, votes, amendements, textes législatifs
                        </p>
                        <p class="text-red-600 dark:text-red-400 text-sm mt-4 font-medium">data.senat.fr →</p>
                    </a>
                    
                    <a href="https://www.hatvp.fr/open-data" target="_blank" class="scroll-reveal group bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-purple-500 hover:shadow-xl transition-all text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-4xl">🔍</span>
                        </div>
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-2">HATVP</h4>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Déclarations de patrimoine et d'intérêts
                        </p>
                        <p class="text-purple-600 dark:text-purple-400 text-sm mt-4 font-medium">hatvp.fr/open-data →</p>
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-900 text-slate-400 py-12">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h5 class="font-bold text-white">CivicDash</h5>
                            <p class="text-xs text-slate-500">Open Source - MIT License</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-6 text-sm">
                        <a href="https://objectif2027.fr" target="_blank" class="hover:text-white transition-colors">Objectif 2027</a>
                        <a href="https://civis-consilium.eu" target="_blank" class="hover:text-white transition-colors">Civis-Consilium</a>
                        <a href="https://github.com/civis-consilium/civicdash" target="_blank" class="hover:text-white transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            GitHub
                        </a>
                    </div>
                </div>
                
                <div class="border-t border-slate-800 mt-8 pt-8 text-center text-sm text-slate-500">
                    <p>© 2025 CivicDash. Projet open-source pour la transparence démocratique.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.scroll-reveal {
    opacity: 0;
    transform: translateY(20px);
}

.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.tabular-nums {
    font-variant-numeric: tabular-nums;
}
</style>
