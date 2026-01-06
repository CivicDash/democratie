<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    reason: String,
    banned_at: String,
    appeal_email: String,
});
</script>

<template>
    <GuestLayout>
        <Head title="Compte banni" />

        <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-50 to-rose-100 dark:from-gray-900 dark:to-red-900/20 p-4">
            <div class="max-w-lg w-full">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-red-600 to-rose-600 px-8 py-6 text-center">
                        <span class="text-6xl">🚫</span>
                        <h1 class="text-2xl font-bold text-white mt-4">Compte banni définitivement</h1>
                    </div>

                    <!-- Content -->
                    <div class="p-8 space-y-6">
                        <p class="text-center text-gray-600 dark:text-gray-400">
                            Votre compte a été banni définitivement de la plateforme CivicDash.
                        </p>

                        <!-- Infos bannissement -->
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-6 border border-red-200 dark:border-red-800">
                            <div class="space-y-4">
                                <div v-if="reason">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-300">Motif du bannissement :</p>
                                    <p class="text-gray-700 dark:text-gray-300 mt-1">{{ reason }}</p>
                                </div>

                                <div v-if="banned_at">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-300">Date de la décision :</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ banned_at }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Faire appel -->
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                                ⚖️ Faire appel de la décision
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Si vous souhaitez contester cette décision, vous pouvez plaider votre cause en envoyant un email à :
                            </p>
                            <a 
                                :href="`mailto:${appeal_email}`"
                                class="block w-full text-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium"
                            >
                                📧 {{ appeal_email }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">
                                Merci d'indiquer votre nom d'utilisateur et les raisons de votre contestation.
                            </p>
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
