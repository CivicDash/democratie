<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const twoFactorEnabled = computed(() => user.value?.two_factor_enabled);
const hasFranceConnect = computed(() => user.value?.has_franceconnect);
const isDemoAccount = computed(() => user.value?.is_demo_account);
const canEnableTwoFactor = computed(() => user.value?.can_enable_two_factor);
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                🔐 Double Authentification (2FA)
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Ajoutez une couche de sécurité supplémentaire à votre compte en utilisant un code à usage unique.
            </p>
        </header>

        <div class="mt-6">
            <!-- Compte démo -->
            <div v-if="isDemoAccount" class="p-4 bg-amber-50 rounded-lg dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <p class="font-medium text-amber-800 dark:text-amber-200">Compte de démonstration</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            La double authentification n'est pas disponible pour les comptes de démonstration.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info FranceConnect -->
            <div v-else-if="hasFranceConnect" class="p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇫🇷</span>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        Vous êtes connecté via <strong>FranceConnect</strong>. Votre authentification est déjà sécurisée 
                        par les services de l'État. La double authentification n'est pas nécessaire.
                    </p>
                </div>
            </div>

            <!-- 2FA activée -->
            <div v-else-if="twoFactorEnabled" class="p-4 bg-emerald-50 rounded-lg dark:bg-emerald-900/20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                            <span class="text-lg">✅</span>
                        </div>
                        <div>
                            <p class="font-medium text-emerald-800 dark:text-emerald-200">
                                Double authentification activée
                            </p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">
                                Votre compte est sécurisé.
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="route('two-factor.show')"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        Gérer
                    </Link>
                </div>
            </div>

            <!-- 2FA non activée -->
            <div v-else class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                            <span class="text-lg">🔒</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">
                                Double authentification non configurée
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Protégez votre compte avec un code d'authentification.
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="route('two-factor.show')"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        Activer
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
