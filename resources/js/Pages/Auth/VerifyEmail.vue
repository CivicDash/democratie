<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verification de l'email" />

        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 mb-4">
                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Verifiez votre adresse email
            </h2>
        </div>

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Merci pour votre inscription ! Avant de commencer, veuillez verifier
            votre adresse email en cliquant sur le lien que nous venons de vous
            envoyer. Si vous n'avez pas recu l'email, nous vous en renverrons
            un avec plaisir.
        </div>

        <div
            class="mb-4 p-3 text-sm font-medium text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
            v-if="verificationLinkSent"
        >
            Un nouveau lien de verification a ete envoye a l'adresse email
            fournie lors de votre inscription.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Renvoyer l'email
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Se deconnecter
                </Link>
            </div>
        </form>

        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
            <p class="text-xs text-amber-700 dark:text-amber-300">
                Pensez a verifier vos spams si vous ne trouvez pas l'email.
                Le lien de verification expire au bout de 60 minutes.
            </p>
        </div>
    </GuestLayout>
</template>
