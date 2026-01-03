<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    secret: String,
    qrCodeSvg: String,
});

const form = useForm({
    code: '',
});

const showSecret = ref(false);

const submit = () => {
    form.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Activer la 2FA" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                🔐 Configurer la Double Authentification
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white shadow sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <!-- Étape 1: Scanner le QR code -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Étape 1 : Scannez le QR code
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Ouvrez votre application d'authentification (Google Authenticator, Authy, etc.) et scannez ce QR code.
                            </p>

                            <div class="flex justify-center p-6 bg-white rounded-lg border dark:bg-gray-100">
                                <div v-html="qrCodeSvg" class="w-48 h-48"></div>
                            </div>
                        </div>

                        <!-- Clé secrète (pour entrée manuelle) -->
                        <div class="mb-8 p-4 bg-gray-50 rounded-lg dark:bg-gray-700/50">
                            <button
                                type="button"
                                class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300"
                                @click="showSecret = !showSecret"
                            >
                                <span>{{ showSecret ? '🔽' : '▶️' }}</span>
                                Impossible de scanner ? Entrez le code manuellement
                            </button>
                            
                            <div v-if="showSecret" class="mt-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    Entrez cette clé dans votre application :
                                </p>
                                <div class="flex items-center gap-2">
                                    <code class="flex-1 p-3 bg-white dark:bg-gray-800 rounded border font-mono text-sm select-all">
                                        {{ secret }}
                                    </code>
                                    <button
                                        type="button"
                                        class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                        @click="navigator.clipboard.writeText(secret)"
                                        title="Copier"
                                    >
                                        📋
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 2: Vérifier le code -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Étape 2 : Vérifiez le code
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Entrez le code à 6 chiffres affiché dans votre application pour confirmer la configuration.
                            </p>

                            <form @submit.prevent="submit">
                                <div class="mb-4">
                                    <InputLabel for="code" value="Code de vérification" />
                                    <TextInput
                                        id="code"
                                        v-model="form.code"
                                        type="text"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="6"
                                        class="mt-1 block w-full text-center text-2xl tracking-widest font-mono"
                                        placeholder="000000"
                                        autofocus
                                    />
                                    <InputError :message="form.errors.code" class="mt-2" />
                                </div>

                                <div class="flex items-center justify-between">
                                    <Link
                                        :href="route('two-factor.show')"
                                        class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                                    >
                                        ← Annuler
                                    </Link>
                                    <PrimaryButton :disabled="form.processing || form.code.length !== 6">
                                        ✅ Activer la 2FA
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
