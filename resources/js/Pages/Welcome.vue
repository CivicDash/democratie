<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    stats: {
        type: Object,
        default: () => ({
            deputes: 577,
            senateurs: 348,
            lois: 0,
            scrutins: 0,
        })
    }
});

// Animation on scroll
const features = [
    {
        icon: '🏛️',
        title: 'Parlement en temps réel',
        description: 'Suivez les 577 députés et 348 sénateurs. Votes, amendements, questions au gouvernement - tout est transparent.',
        color: 'from-indigo-500 to-blue-600',
        items: ['Profils détaillés des élus', 'Historique des votes', 'Questions au gouvernement', 'Activité législative']
    },
    {
        icon: '⚖️',
        title: 'Suivi des lois',
        description: 'Explorez les projets et propositions de loi. Du dépôt à la promulgation, suivez chaque étape.',
        color: 'from-emerald-500 to-teal-600',
        items: ['Parcours législatif complet', 'Amendements détaillés', 'Résultats des scrutins', 'Textes officiels']
    },
    {
        icon: '💡',
        title: 'Idées citoyennes',
        description: 'Proposez vos idées, débattez avec la communauté, et interpellez vos élus directement.',
        color: 'from-amber-500 to-orange-600',
        items: ['Propositions citoyennes', 'Système de vote', 'Interpellation des élus', 'Débat constructif']
    },
    {
        icon: '💰',
        title: 'Budget participatif',
        description: 'Si vous étiez ministre des Finances, comment répartiriez-vous le budget de l\'État ?',
        color: 'from-violet-500 to-purple-600',
        items: ['Répartition interactive', 'Comparaison citoyenne', 'Données réelles', 'Visualisations']
    },
    {
        icon: '🔍',
        title: 'Recherche intelligente',
        description: 'Trouvez instantanément un élu, une loi, un scrutin grâce à notre moteur de recherche avancé.',
        color: 'from-rose-500 to-pink-600',
        items: ['Recherche multi-critères', 'Suggestions en temps réel', 'Filtres avancés', 'Résultats pertinents']
    },
    {
        icon: '📊',
        title: 'Statistiques & données',
        description: 'Accédez à des analyses détaillées et des visualisations pour mieux comprendre la politique.',
        color: 'from-cyan-500 to-sky-600',
        items: ['Hémicycle interactif', 'Groupes parlementaires', 'Tendances de vote', 'Données ouvertes']
    }
];

const steps = [
    { num: '01', icon: '🔐', title: 'Créez votre compte', desc: 'Inscription gratuite en 2 minutes. Email ou FranceConnect+.' },
    { num: '02', icon: '📍', title: 'Trouvez vos élus', desc: 'Entrez votre code postal pour découvrir votre député et vos sénateurs.' },
    { num: '03', icon: '💬', title: 'Participez', desc: 'Suivez les lois, votez sur les idées, proposez vos solutions.' },
];
</script>

<template>
    <Head title="CivicDash - Plateforme de démocratie participative" />
    
    <div class="min-h-screen bg-slate-950 text-white">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-lg border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xl font-bold">
                            🗳️
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent">
                            CivicDash
                        </span>
                    </div>
                    
                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center gap-6">
                        <a href="#fonctionnalites" class="text-slate-400 hover:text-white transition">Fonctionnalités</a>
                        <a href="#comment-ca-marche" class="text-slate-400 hover:text-white transition">Comment ça marche</a>
                        <a href="https://civis-consilium.eu" target="_blank" class="text-slate-400 hover:text-white transition">Association</a>
                    </div>
                    
                    <!-- Auth Buttons -->
                    <div class="flex items-center gap-3">
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="px-4 py-2 text-slate-300 hover:text-white transition"
                        >
                            Connexion
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-medium rounded-lg transition shadow-lg shadow-indigo-500/25"
                        >
                            Créer un compte
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 lg:pt-40 lg:pb-32 overflow-hidden">
            <!-- Background Effects -->
            <div class="absolute inset-0">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Badges -->
                <div class="flex flex-wrap justify-center gap-3 mb-8">
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 text-sm font-medium rounded-full border border-emerald-500/30">
                        🇫🇷 Made in France
                    </span>
                    <span class="px-3 py-1 bg-blue-500/20 text-blue-400 text-sm font-medium rounded-full border border-blue-500/30">
                        🔓 100% Open Source
                    </span>
                    <span class="px-3 py-1 bg-amber-500/20 text-amber-400 text-sm font-medium rounded-full border border-amber-500/30">
                        ✨ Gratuit
                    </span>
                </div>
                
                <h1 class="text-4xl sm:text-5xl lg:text-7xl font-bold mb-6 leading-tight">
                    <span class="text-white">Votre voix compte.</span><br>
                    <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                        Faites-la entendre.
                    </span>
                </h1>
                
                <p class="max-w-3xl mx-auto text-lg sm:text-xl text-slate-400 mb-12">
                    CivicDash est une plateforme citoyenne qui vous permet de participer activement à la vie démocratique française.
                    <strong class="text-white">Débattez, votez, proposez et suivez vos élus.</strong>
                </p>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-16">
                    <Link
                        :href="route('register')"
                        class="px-8 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl transition shadow-2xl shadow-indigo-500/30 flex items-center justify-center gap-2"
                    >
                        🚀 Essayer gratuitement
                    </Link>
                    <a
                        href="https://discord.gg/civisdash"
                        target="_blank"
                        class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl transition border border-white/20 flex items-center justify-center gap-2"
                    >
                        💬 Rejoindre la communauté
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                        <div class="text-3xl font-bold text-white mb-1">577</div>
                        <div class="text-sm text-slate-400">Députés</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                        <div class="text-3xl font-bold text-white mb-1">348</div>
                        <div class="text-sm text-slate-400">Sénateurs</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                        <div class="text-3xl font-bold text-white mb-1">10k+</div>
                        <div class="text-sm text-slate-400">Scrutins</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                        <div class="text-3xl font-bold text-white mb-1">∞</div>
                        <div class="text-sm text-slate-400">Idées citoyennes</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Demo Warning -->
        <section class="py-6 bg-amber-500/10 border-y border-amber-500/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-center gap-4 text-center">
                    <span class="text-2xl">⚠️</span>
                    <p class="text-amber-200">
                        <strong>Application en développement</strong> — Environnement de démonstration avec données réelles de l'Assemblée Nationale et du Sénat.
                    </p>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="fonctionnalites" class="py-20 lg:py-32">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                        Toutes les fonctionnalités pour participer
                    </h2>
                    <p class="text-lg text-slate-400 max-w-2xl mx-auto">
                        CivicDash vous donne les outils pour vous impliquer dans la vie démocratique, simplement et efficacement.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(feature, index) in features"
                        :key="index"
                        class="group relative bg-white/5 backdrop-blur-sm rounded-2xl p-8 border border-white/10 hover:border-white/20 transition-all duration-300 hover:transform hover:-translate-y-1"
                    >
                        <!-- Icon -->
                        <div 
                            class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-lg"
                            :class="`bg-gradient-to-br ${feature.color}`"
                        >
                            {{ feature.icon }}
                        </div>
                        
                        <h3 class="text-xl font-bold text-white mb-3">
                            {{ feature.title }}
                        </h3>
                        
                        <p class="text-slate-400 mb-6">
                            {{ feature.description }}
                        </p>
                        
                        <ul class="space-y-2">
                            <li v-for="item in feature.items" :key="item" class="flex items-center gap-2 text-sm text-slate-300">
                                <span class="text-emerald-400">✓</span>
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="comment-ca-marche" class="py-20 lg:py-32 bg-gradient-to-b from-slate-950 to-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                        Commencez en 3 étapes simples
                    </h2>
                    <p class="text-lg text-slate-400">
                        Pas besoin d'être expert en politique pour participer !
                    </p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div
                        v-for="step in steps"
                        :key="step.num"
                        class="relative text-center"
                    >
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-3xl mb-6 shadow-xl shadow-indigo-500/30">
                            {{ step.icon }}
                        </div>
                        <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-slate-800 border-2 border-indigo-500 flex items-center justify-center text-xs font-bold text-indigo-400">
                            {{ step.num }}
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">{{ step.title }}</h3>
                        <p class="text-slate-400">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Association Section -->
        <section class="py-20 lg:py-32 bg-gradient-to-br from-indigo-900/50 to-purple-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-4xl mb-4 block">🇪🇺</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                        Une initiative citoyenne portée par l'association Civis-Consilium
                    </h2>
                    <p class="text-lg text-slate-300 max-w-3xl mx-auto">
                        CivicDash est développé et maintenu par <strong>Civis-Consilium</strong>, une association Loi 1901 à but non lucratif dédiée à la promotion de la démocratie participative en Europe.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto mb-12">
                    <div class="text-center">
                        <div class="text-3xl mb-3">🔓</div>
                        <h3 class="font-bold text-white mb-2">Open Source</h3>
                        <p class="text-sm text-slate-400">Tout notre code est public, auditable et améliorable par la communauté</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-3">🤝</div>
                        <h3 class="font-bold text-white mb-2">Indépendance</h3>
                        <p class="text-sm text-slate-400">Aucun parti politique, aucune entreprise : nous sommes citoyens avant tout</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-3">🌍</div>
                        <h3 class="font-bold text-white mb-2">Vision Européenne</h3>
                        <p class="text-sm text-slate-400">Notre vision dépasse les frontières : une démocratie participative pour toute l'Europe</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap justify-center gap-4">
                    <a
                        href="https://civis-consilium.eu"
                        target="_blank"
                        class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl transition border border-white/20 flex items-center gap-2"
                    >
                        🏛️ Découvrir l'association
                    </a>
                    <a
                        href="https://discord.gg/civisdash"
                        target="_blank"
                        class="px-6 py-3 bg-[#5865F2] hover:bg-[#4752C4] text-white font-medium rounded-xl transition flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                        </svg>
                        Rejoindre le Discord
                    </a>
                    <a
                        href="https://github.com/CivicDash"
                        target="_blank"
                        class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white font-medium rounded-xl transition flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                        </svg>
                        Contribuer sur GitHub
                    </a>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="py-20 lg:py-32">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Prêt à faire entendre votre voix ?
                </h2>
                <p class="text-lg text-slate-400 mb-10">
                    Rejoignez des milliers de citoyens qui construisent ensemble une démocratie plus participative et transparente.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <Link
                        :href="route('register')"
                        class="px-10 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl transition shadow-2xl shadow-indigo-500/30"
                    >
                        🚀 Essayer CivicDash
                    </Link>
                    <a
                        href="mailto:contact@civis-consilium.eu"
                        class="px-10 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl transition border border-white/20"
                    >
                        ✉️ Nous contacter
                    </a>
                </div>
                <p class="text-sm text-slate-500 mt-8">
                    💡 <strong>100% gratuit</strong> • <strong>Open Source</strong> • <strong>Made in France</strong>
                </p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8 mb-12">
                    <!-- Logo & Description -->
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xl font-bold">
                                🗳️
                            </div>
                            <span class="text-xl font-bold text-white">CivicDash</span>
                        </div>
                        <p class="text-slate-400 mb-4">
                            La plateforme open-source de démocratie participative française.
                        </p>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-slate-500">Développé par</span>
                            <a href="https://civis-consilium.eu" target="_blank" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium">
                                Civis-Consilium
                            </a>
                        </div>
                    </div>
                    
                    <!-- Links -->
                    <div>
                        <h4 class="font-semibold text-white mb-4">Projet</h4>
                        <ul class="space-y-2 text-sm text-slate-400">
                            <li><a href="#fonctionnalites" class="hover:text-white transition">Fonctionnalités</a></li>
                            <li><a href="#comment-ca-marche" class="hover:text-white transition">Comment ça marche</a></li>
                            <li><a href="https://github.com/CivicDash" target="_blank" class="hover:text-white transition">GitHub</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-white mb-4">Légal</h4>
                        <ul class="space-y-2 text-sm text-slate-400">
                            <li><Link :href="route('privacy')" class="hover:text-white transition">Confidentialité</Link></li>
                            <li><Link :href="route('terms')" class="hover:text-white transition">CGU</Link></li>
                            <li><a href="https://github.com/CivicDash/democratie/blob/main/LICENSE" target="_blank" class="hover:text-white transition">Licence AGPL-3.0</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-sm text-slate-500">
                        © {{ new Date().getFullYear() }} CivicDash • Objectif 2027 • Open Source AGPL-3.0
                    </p>
                    <p class="text-sm text-slate-500">
                        Made with ❤️ in France 🇫🇷
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
