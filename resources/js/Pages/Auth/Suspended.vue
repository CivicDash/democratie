<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    reason: String,
    suspended_until: String,
    remaining: String,
});
</script>

<template>
    <GuestLayout>
        <Head title="Compte suspendu" />

        <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-amber-50 to-orange-100 dark:from-gray-900 dark:to-amber-900/20 p-4">
            <div class="max-w-lg w-full">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-8 py-6 text-center">
                        <span class="text-6xl">⚠️</span>
                        <h1 class="text-2xl font-bold text-white mt-4">Compte suspendu</h1>
                    </div>

                    <!-- Content -->
                    <div class="p-8 space-y-6">
                        <p class="text-center text-gray-600 dark:text-gray-400">
                            Votre compte a été temporairement suspendu par notre équipe de modération.
                        </p>

                        <!-- Infos suspension -->
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-6 border border-amber-200 dark:border-amber-800">
                            <div class="space-y-4">
                                <div v-if="reason">
                                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Motif :</p>
                                    <p class="text-gray-700 dark:text-gray-300 mt-1">{{ reason }}</p>
                                </div>

                                <div v-if="suspended_until" class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Fin de suspension :</p>
                                        <p class="text-gray-700 dark:text-gray-300">{{ suspended_until }}</p>
                                    </div>
                                    <div v-if="remaining" class="text-right">
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Temps restant :</p>
                                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ remaining }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contestation -->
                        <div class="text-center space-y-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Si vous estimez que cette décision est injuste, vous pouvez faire appel :
                            </p>
                            <a 
                                href="mailto:moderation@civis-consilium.eu"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                            >
                                📧 moderation@civis-consilium.eu
                            </a>
                        </div>

                        <!-- Retour -->
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 text-center">
                            <Link 
                                :href="route('login')" 
                                class="text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                ← Retour à la page de connexion
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
