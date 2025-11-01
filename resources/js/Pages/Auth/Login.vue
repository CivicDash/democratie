<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import FranceConnectButton from '@/Components/FranceConnectButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- FranceConnect+ Section (Prioritaire RGPD) -->
        <div class="mb-6">
            <FranceConnectButton />
            
            <p class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                FranceConnect+ est le service d'authentification sécurisé de l'État français.
                <br>
                En vous connectant, vous acceptez notre 
                <Link :href="route('privacy')" class="underline text-indigo-600 hover:text-indigo-800">
                    politique de confidentialité
                </Link>
                conforme au RGPD.
            </p>
        </div>

        <!-- Séparateur -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white dark:bg-gray-900 text-gray-500">
                    Ou connexion classique
                </span>
            </div>
        </div>

        <!-- Formulaire classique -->
        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                        Se souvenir de moi
                    </span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Mot de passe oublié ?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Se connecter
                </PrimaryButton>
            </div>
        </form>

        <!-- Notice RGPD (Art. 13) -->
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                🔒 Protection de vos données
            </h3>
            <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                <li>• <strong>Anonymat</strong> : Votre nom n'apparaît jamais publiquement (pseudonyme aléatoire)</li>
                <li>• <strong>Chiffrement</strong> : Toutes vos données sensibles sont chiffrées</li>
                <li>• <strong>Minimisation</strong> : Nous ne collectons que les données strictement nécessaires</li>
                <li>• <strong>Vos droits</strong> : Accès, rectification, effacement (contact@demoscratos.fr)</li>
            </ul>
            <p class="mt-2 text-xs text-blue-700 dark:text-blue-300">
                En savoir plus : 
                <Link :href="route('privacy')" class="underline font-semibold">Politique de confidentialité</Link>
                • 
                <Link :href="route('terms')" class="underline font-semibold">Conditions d'utilisation</Link>
            </p>
        </div>
    </GuestLayout>
</template>
