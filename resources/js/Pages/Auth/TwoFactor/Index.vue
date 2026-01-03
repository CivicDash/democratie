<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    enabled: Boolean,
    confirmedAt: String,
    isEluOrAdmin: Boolean,
    hasPassword: Boolean,
});

const showDisableModal = ref(false);

const disableForm = useForm({
    password: '',
});

const confirmDisable = () => {
    disableForm.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            showDisableModal.value = false;
            disableForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Double Authentification" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                🔐 Double Authentification (2FA)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <!-- Bandeau d'avertissement pour élus/admins sans 2FA -->
                <div 
                    v-if="isEluOrAdmin && !enabled"
                    class="mb-6 rounded-lg bg-amber-50 border border-amber-200 p-4 dark:bg-amber-900/20 dark:border-amber-800"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h3 class="font-semibold text-amber-800 dark:text-amber-200">
                                Recommandation de sécurité
                            </h3>
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                En tant qu'élu ou administrateur, nous vous recommandons fortement d'activer la double authentification pour protéger votre compte.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <!-- État actuel -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    État de la double authentification
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Ajoutez une couche de sécurité supplémentaire à votre compte.
                                </p>
                            </div>
                            <div 
                                :class="[
                                    'px-3 py-1 rounded-full text-sm font-medium',
                                    enabled 
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' 
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                ]"
                            >
                                {{ enabled ? '✅ Activée' : '❌ Désactivée' }}
                            </div>
                        </div>

                        <!-- Explication -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg dark:bg-gray-700/50">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Comment ça fonctionne ?
                            </h4>
                            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500">1️⃣</span>
                                    <span>Installez une application d'authentification (Google Authenticator, Authy, 1Password, etc.)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500">2️⃣</span>
                                    <span>Scannez le QR code avec l'application</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500">3️⃣</span>
                                    <span>Lors de chaque connexion, entrez le code à 6 chiffres généré par l'application</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div v-if="!enabled">
                            <div v-if="!hasPassword" class="mb-4 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    ℹ️ Vous êtes connecté via FranceConnect. La double authentification n'est disponible que pour les comptes avec mot de passe.
                                </p>
                            </div>
                            <Link
                                v-else
                                :href="route('two-factor.enable')"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                🔐 Activer la 2FA
                            </Link>
                        </div>

                        <div v-else class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Activée le {{ new Date(confirmedAt).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                            </p>
                            
                            <div class="flex flex-wrap gap-3">
                                <Link
                                    :href="route('two-factor.recovery-codes')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    📋 Voir les codes de récupération
                                </Link>

                                <DangerButton @click="showDisableModal = true">
                                    🔓 Désactiver la 2FA
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info complémentaire -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                        💡 Applications recommandées
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <a href="https://support.google.com/accounts/answer/1066447" target="_blank" class="flex items-center gap-2 text-blue-700 hover:underline dark:text-blue-300">
                            Google Authenticator
                        </a>
                        <a href="https://authy.com/" target="_blank" class="flex items-center gap-2 text-blue-700 hover:underline dark:text-blue-300">
                            Authy
                        </a>
                        <a href="https://1password.com/" target="_blank" class="flex items-center gap-2 text-blue-700 hover:underline dark:text-blue-300">
                            1Password
                        </a>
                        <a href="https://bitwarden.com/" target="_blank" class="flex items-center gap-2 text-blue-700 hover:underline dark:text-blue-300">
                            Bitwarden
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de désactivation -->
        <Modal :show="showDisableModal" @close="showDisableModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Désactiver la double authentification ?
                </h2>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Cette action rendra votre compte moins sécurisé. Confirmez avec votre mot de passe.
                </p>

                <div class="mt-4">
                    <InputLabel for="password" value="Mot de passe" />
                    <TextInput
                        id="password"
                        v-model="disableForm.password"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="Votre mot de passe"
                        @keyup.enter="confirmDisable"
                    />
                    <InputError :message="disableForm.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                        @click="showDisableModal = false"
                    >
                        Annuler
                    </button>
                    <DangerButton
                        :disabled="disableForm.processing"
                        @click="confirmDisable"
                    >
                        Désactiver
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
