<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const showRecoveryInput = ref(false);

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('two-factor.verify'), {
        preserveScroll: true,
        onError: () => {
            form.reset('code');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Vérification 2FA" />

        <div class="mb-6 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center dark:bg-indigo-900/30">
                <span class="text-3xl">🔐</span>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                Double Authentification
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ showRecoveryInput 
                    ? 'Entrez un de vos codes de récupération'
                    : 'Entrez le code à 6 chiffres de votre application'
                }}
            </p>
        </div>

        <form @submit.prevent="submit">
            <div class="mb-4">
                <InputLabel 
                    for="code" 
                    :value="showRecoveryInput ? 'Code de récupération' : 'Code d\'authentification'" 
                />
                <TextInput
                    id="code"
                    v-model="form.code"
                    :type="showRecoveryInput ? 'text' : 'text'"
                    :inputmode="showRecoveryInput ? 'text' : 'numeric'"
                    :pattern="showRecoveryInput ? undefined : '[0-9]*'"
                    :maxlength="showRecoveryInput ? 9 : 6"
                    :class="[
                        'mt-1 block w-full text-center font-mono',
                        showRecoveryInput ? 'text-lg tracking-wide' : 'text-2xl tracking-widest'
                    ]"
                    :placeholder="showRecoveryInput ? 'XXXX-XXXX' : '000000'"
                    autofocus
                    autocomplete="one-time-code"
                />
                <InputError :message="form.errors.code" class="mt-2" />
            </div>

            <PrimaryButton 
                class="w-full justify-center" 
                :disabled="form.processing || (!showRecoveryInput && form.code.length !== 6)"
            >
                {{ form.processing ? 'Vérification...' : 'Vérifier' }}
            </PrimaryButton>
        </form>

        <div class="mt-6 text-center">
            <button
                type="button"
                class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                @click="showRecoveryInput = !showRecoveryInput; form.reset()"
            >
                {{ showRecoveryInput 
                    ? '← Utiliser le code de l\'application' 
                    : 'Utiliser un code de récupération →'
                }}
            </button>
        </div>

        <div class="mt-4 text-center">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
                Se déconnecter
            </Link>
        </div>
    </GuestLayout>
</template>
