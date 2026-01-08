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

// Remplir avec le compte démo citoyen
const fillDemoAccount = () => {
    form.email = 'citoyen1@civicdash.fr';
    form.password = 'demo2025';
    form.remember = true;
};

// Remplir avec le compte démo élu
const fillDemoEluAccount = () => {
    form.email = 'demo-elu@civicdash.fr';
    form.password = 'DemoElu2026!';
    form.remember = true;
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

        <!-- Boutons comptes démo -->
        <div class="mb-6 space-y-3">
            <button
                type="button"
                @click="fillDemoAccount"
                class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
            >
                <span class="text-xl">🎭</span>
                <span>Compte démo citoyen</span>
            </button>
            
            <button
                type="button"
                @click="fillDemoEluAccount"
                class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
            >
                <span class="text-xl">🏛️</span>
                <span>Compte démo élu</span>
            </button>
            
            <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                Testez la plateforme avec un compte citoyen ou un espace élu dédié
            </p>
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

            <div class="mt-5 block">
                <label class="inline-flex items-center gap-3 cursor-pointer select-none group">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-800 dark:group-hover:text-gray-200 transition-colors">
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
