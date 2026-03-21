<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    date_of_birth: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
            <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100 mb-1">
                Inscription reservee aux membres
            </h3>
            <p class="text-xs text-emerald-700 dark:text-emerald-300">
                L'inscription est reservee aux membres de l'association Civis-Consilium.
                Votre email doit correspondre a celui enregistre lors de votre adhesion.
                <a href="https://civis-consilium.eu" target="_blank" class="underline font-semibold">
                    Adherer a l'association
                </a>
            </p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Nom complet" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Jean Dupont"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Adresse email (identique a votre adhesion)" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="jean.dupont@email.fr"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="date_of_birth" value="Date de naissance" />

                <TextInput
                    id="date_of_birth"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.date_of_birth"
                    required
                    :max="new Date(new Date().setFullYear(new Date().getFullYear() - 18)).toISOString().split('T')[0]"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Vous devez avoir au moins 18 ans. Sert a verifier votre identite.
                </p>

                <InputError class="mt-2" :message="form.errors.date_of_birth" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Verification en cours...</span>
                    <span v-else>Creer mon compte</span>
                </PrimaryButton>
            </div>

            <div class="mt-4 text-center">
                <Link
                    :href="route('login')"
                    class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    Deja inscrit ? Se connecter
                </Link>
            </div>
        </form>

        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                Protection de vos donnees
            </h3>
            <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                <li>Un email de verification vous sera envoye pour confirmer votre adresse.</li>
                <li>Votre date de naissance est utilisee uniquement pour la verification d'identite.</li>
                <li>Vos donnees sont protegees conformement au RGPD.</li>
            </ul>
        </div>
    </GuestLayout>
</template>
