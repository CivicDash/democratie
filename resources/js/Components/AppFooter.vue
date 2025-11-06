<template>
    <!-- Application Footer with links to objectif2027.fr and civis-consilium.fr -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
        <div class="mx-auto px-4 py-8 sm:px-6 lg:px-8" style="max-width: 100%;">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                <!-- À propos -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                        À propos
                    </h3>
                    <ul class="space-y-3">
                        <li>
                            <a 
                                href="https://objectif2027.fr" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-2"
                            >
                                🌐 Objectif 2027
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a 
                                href="https://civis-consilium.fr" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-2"
                            >
                                🏛️ Association Civis-Consilium
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </li>
                        <li>
                            <Link 
                                :href="route('welcome')"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                            >
                                📖 Présentation
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Légal -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                        Légal
                    </h3>
                    <ul class="space-y-3">
                        <li>
                            <Link 
                                :href="route('privacy')"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                            >
                                🔒 Confidentialité
                            </Link>
                        </li>
                        <li>
                            <Link 
                                :href="route('terms')"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                            >
                                📜 Conditions d'utilisation
                            </Link>
                        </li>
                        <li>
                            <a 
                                href="mailto:contact@objectif2027.fr"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                            >
                                ✉️ Contact
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Communauté -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                        Communauté
                    </h3>
                    <ul class="space-y-3">
                        <li>
                            <a 
                                href="https://github.com/Civis-Consilium" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-2"
                            >
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                </svg>
                                GitHub
                            </a>
                        </li>
                        <li>
                            <Link 
                                :href="route('topics.index')"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                            >
                                💬 Forum Citoyen
                            </Link>
                        </li>
                        <li>
                            <a 
                                href="https://discord.gg/civis-consilium" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-2"
                            >
                                💬 Discord
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Ressources -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                        Ressources
                    </h3>
                    <ul class="space-y-3">
                        <li>
                            <a 
                                href="https://github.com/Civis-Consilium/CivicDash" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-2"
                            >
                                📚 Documentation
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a 
                                href="https://github.com/Civis-Consilium/CivicDash/blob/main/ROADMAP.md" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-2"
                            >
                                🗺️ Roadmap
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a 
                                href="https://github.com/Civis-Consilium/CivicDash/issues" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-2"
                            >
                                🐛 Signaler un bug
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Séparateur -->
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <!-- Copyright -->
                    <div class="text-center md:text-left">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            © {{ new Date().getFullYear() }} 
                            <a 
                                href="https://civis-consilium.fr" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="font-semibold hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                            >
                                Association Civis-Consilium
                            </a>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Association Française Loi 1901 • Démocratie participative
                        </p>
                    </div>

                    <!-- Badges -->
                    <div class="flex items-center gap-4">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            Mode Démo
                        </span>
                        <a 
                            href="https://github.com/Civis-Consilium/CivicDash" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            Open Source
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
