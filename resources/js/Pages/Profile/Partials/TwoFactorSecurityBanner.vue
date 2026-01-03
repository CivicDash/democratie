<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const shouldEnableTwoFactor = computed(() => user.value?.should_enable_two_factor);
const hasFranceConnect = computed(() => user.value?.has_franceconnect);
const twoFactorEnabled = computed(() => user.value?.two_factor_enabled);
const isDemoAccount = computed(() => user.value?.is_demo_account);
</script>

<template>
    <!-- Bandeau de recommandation 2FA pour élus/admins (pas pour les comptes démo) -->
    <div 
        v-if="shouldEnableTwoFactor && !hasFranceConnect && !isDemoAccount"
        class="rounded-lg bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 p-4 dark:from-amber-900/20 dark:to-orange-900/20 dark:border-amber-800 shadow-sm"
    >
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <span class="text-2xl">🔐</span>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-amber-800 dark:text-amber-200 text-lg">
                    Protégez votre compte !
                </h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    En tant qu'élu ou administrateur, nous vous recommandons fortement d'activer la 
                    <strong>double authentification (2FA)</strong> pour sécuriser votre compte contre les accès non autorisés.
                </p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <Link
                        :href="route('two-factor.show')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm rounded-lg transition shadow-sm"
                    >
                        <span>🛡️</span>
                        Activer la 2FA maintenant
                    </Link>
                    <a 
                        href="#" 
                        class="inline-flex items-center gap-1 text-sm text-amber-700 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-200"
                        @click.prevent="$emit('dismiss')"
                    >
                        Me rappeler plus tard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info FranceConnect -->
    <div 
        v-else-if="hasFranceConnect"
        class="rounded-lg bg-blue-50 border border-blue-200 p-4 dark:bg-blue-900/20 dark:border-blue-800"
    >
        <div class="flex items-center gap-3">
            <span class="text-2xl">🇫🇷</span>
            <div>
                <h3 class="font-semibold text-blue-800 dark:text-blue-200">
                    Compte sécurisé via FranceConnect
                </h3>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    Votre compte est protégé par l'authentification FranceConnect de l'État français. 
                    La double authentification n'est pas nécessaire.
                </p>
            </div>
        </div>
    </div>

    <!-- 2FA activée -->
    <div 
        v-else-if="twoFactorEnabled"
        class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 dark:bg-emerald-900/20 dark:border-emerald-800"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">✅</span>
                <div>
                    <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">
                        Double authentification activée
                    </h3>
                    <p class="text-sm text-emerald-700 dark:text-emerald-300">
                        Votre compte est protégé par la 2FA.
                    </p>
                </div>
            </div>
            <Link
                :href="route('two-factor.show')"
                class="text-sm text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-200"
            >
                Gérer →
            </Link>
        </div>
    </div>
</template>
