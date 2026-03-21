<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    profileSettings: {
        type: Object,
        default: () => ({ display_name: '', is_public_figure: false }),
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    display_name: props.profileSettings.display_name || '',
    is_public_figure: props.profileSettings.is_public_figure || false,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Informations du profil
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Mettez a jour vos informations personnelles et votre adresse email.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Nom complet (prive)" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Votre nom reel n'est jamais affiche publiquement sauf si vous l'autorisez ci-dessous.
                </p>

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Identite publique
                </h3>

                <div>
                    <InputLabel for="display_name" value="Pseudonyme" />
                    <TextInput
                        id="display_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.display_name"
                        maxlength="50"
                        placeholder="Ex: Citoyen1234"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Ce nom sera affiche dans les discussions, le classement et les mentions.
                    </p>
                    <InputError class="mt-2" :message="form.errors.display_name" />
                </div>

                <div class="flex items-start gap-3">
                    <input
                        id="is_public_figure"
                        v-model="form.is_public_figure"
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <div>
                        <InputLabel for="is_public_figure" value="Afficher mon vrai nom publiquement" class="!mb-0" />
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Active pour les personnalites publiques, elus ou journalistes. Votre nom reel remplacera le pseudonyme partout.
                        </p>
                    </div>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg">
                    <strong>Actuellement affiche :</strong> {{ form.is_public_figure ? form.name : (form.display_name || 'Citoyen anonyme') }}
                </div>
            </div>

            <div>
                <InputLabel for="email" value="Adresse email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                    Votre adresse email n'est pas verifiee.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        Cliquez ici pour renvoyer l'email de verification.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    Un nouveau lien de verification a ete envoye a votre adresse email.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Enregistre.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
