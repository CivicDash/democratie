<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Démo CivicDash - Plateforme de Démocratie Participative" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-blue-50">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">C</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">CivicDash</h1>
                        <p class="text-xs text-indigo-600 font-medium">Mode Démonstration</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm"
                    >
                        Se connecter
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium mb-6">
                    🚀 Environnement de démonstration
                </div>
                
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Bienvenue sur <span class="text-indigo-600">CivicDash</span>
                </h2>
                
                <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-10">
                    Plateforme open-source de démocratie participative. Explorez toutes les fonctionnalités avec nos comptes de démonstration.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        :href="route('login')"
                        class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg hover:shadow-xl"
                    >
                        Accéder à la démo →
                    </Link>
                    
                    <a
                        href="https://objectif2027.fr"
                        target="_blank"
                        class="px-8 py-4 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition-colors border-2 border-gray-200"
                    >
                        En savoir plus
                    </a>
                </div>
            </div>

            <!-- Comptes de test -->
            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 border border-gray-100">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">🔐 Comptes de Test Disponibles</h3>
                    <p class="text-gray-600">Utilisez ces identifiants pour explorer les différents rôles</p>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <!-- Comptes Administratifs -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-6 border border-indigo-100">
                        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <span class="mr-2">👤</span> Comptes Administratifs
                        </h4>
                        <div class="space-y-3 text-sm">
                            <div class="bg-white rounded-lg p-3">
                                <div class="font-semibold text-gray-700">Admin</div>
                                <div class="text-gray-600 font-mono text-xs mt-1">admin@civicdash.fr</div>
                                <div class="text-gray-500 text-xs">Mot de passe : <span class="font-mono font-semibold">password</span></div>
                            </div>
                            <div class="bg-white rounded-lg p-3">
                                <div class="font-semibold text-gray-700">Modérateur</div>
                                <div class="text-gray-600 font-mono text-xs mt-1">moderator@civicdash.fr</div>
                                <div class="text-gray-500 text-xs">Mot de passe : <span class="font-mono font-semibold">password</span></div>
                            </div>
                            <div class="bg-white rounded-lg p-3">
                                <div class="font-semibold text-gray-700">Journaliste</div>
                                <div class="text-gray-600 font-mono text-xs mt-1">journalist@civicdash.fr</div>
                                <div class="text-gray-500 text-xs">Mot de passe : <span class="font-mono font-semibold">password</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Comptes Démo -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100">
                        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <span class="mr-2">🎭</span> Comptes Citoyens & Députés
                        </h4>
                        <div class="space-y-3 text-sm">
                            <div class="bg-white rounded-lg p-3">
                                <div class="font-semibold text-gray-700">Citoyens (50 comptes)</div>
                                <div class="text-gray-600 font-mono text-xs mt-1">citoyen1@demo.civicdash.fr</div>
                                <div class="text-gray-600 font-mono text-xs">à citoyen50@demo.civicdash.fr</div>
                                <div class="text-gray-500 text-xs mt-1">Mot de passe : <span class="font-mono font-semibold">demo2025</span></div>
                            </div>
                            <div class="bg-white rounded-lg p-3">
                                <div class="font-semibold text-gray-700">Députés (50 comptes)</div>
                                <div class="text-gray-600 font-mono text-xs mt-1">depute1@demo.assemblee-nationale.fr</div>
                                <div class="text-gray-600 font-mono text-xs">à depute50@demo.assemblee-nationale.fr</div>
                                <div class="text-gray-500 text-xs mt-1">Mot de passe : <span class="font-mono font-semibold">demo2025</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Données de démo -->
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <h4 class="text-lg font-bold text-gray-900 mb-4 text-center">📊 Données Synthétiques Disponibles</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-indigo-600">30</div>
                            <div class="text-xs text-gray-600">Propositions de loi</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-600">50</div>
                            <div class="text-xs text-gray-600">Députés</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600">25</div>
                            <div class="text-xs text-gray-600">Topics de débat</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-purple-600">740+</div>
                            <div class="text-xs text-gray-600">Posts citoyens</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-600">16</div>
                            <div class="text-xs text-gray-600">Groupes parlementaires</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-red-600">87</div>
                            <div class="text-xs text-gray-600">Amendements</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-teal-600">300</div>
                            <div class="text-xs text-gray-600">Allocations budgétaires</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-pink-600">20</div>
                            <div class="text-xs text-gray-600">Documents vérifiés</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="grid md:grid-cols-3 gap-6 mt-12">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-3">🏛️</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Suivi Législatif</h3>
                    <p class="text-gray-600 text-sm">Propositions de loi, amendements, votes, et références juridiques automatiques (Légifrance).</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-3">💬</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Forum Citoyen</h3>
                    <p class="text-gray-600 text-sm">Débats thématiques, votes anonymes avec bulletins cryptographiques, et système de hashtags.</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-3">💰</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Budget Participatif</h3>
                    <p class="text-gray-600 text-sm">Visualisation des finances publiques et allocation citoyenne des ressources par secteur.</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-8 mt-20">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-600 text-sm">
                <p class="mb-2">
                    <strong>CivicDash</strong> - Plateforme open-source développée par 
                    <a href="https://civis-consilium.eu" target="_blank" class="text-indigo-600 hover:text-indigo-700 font-medium">Civis-Consilium</a>
                </p>
                <p class="text-gray-500">
                    <a href="https://objectif2027.fr" target="_blank" class="hover:text-gray-700">Objectif 2027</a> · 
                    <a href="https://github.com/your-repo" target="_blank" class="hover:text-gray-700 ml-2">GitHub</a>
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Animations personnalisées */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.hover\:scale-105:hover {
    animation: float 2s ease-in-out infinite;
}
</style>

