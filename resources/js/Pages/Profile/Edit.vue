<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateLocationForm from './Partials/UpdateLocationForm.vue';
import TwoFactorSecurityBanner from './Partials/TwoFactorSecurityBanner.vue';
import TwoFactorForm from './Partials/TwoFactorForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const profile = computed(() => page.props.auth?.user?.profile);
const user = computed(() => page.props.auth?.user);
const bannerDismissed = ref(false);
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                👤 Mon Profil
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-full space-y-6 sm:px-6 lg:px-8">
                <!-- Badge Membre Civis-Consilium -->
                <div
                    v-if="user?.is_association_member"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4 shadow sm:rounded-lg text-white"
                >
                    <div class="flex items-center gap-4">
                        <span class="text-4xl">🎖️</span>
                        <div>
                            <h3 class="text-lg font-bold">Membre Civis-Consilium</h3>
                            <p class="text-blue-100 text-sm">
                                Vous êtes membre de l'association depuis le {{ user.association_member_since || 'récemment' }}
                            </p>
                        </div>
                        <div class="ml-auto text-right">
                            <span v-if="user.association_member_id" class="px-3 py-1 bg-white/20 rounded-full text-sm">
                                ID: {{ user.association_member_id }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bandeau de sécurité 2FA -->
                <TwoFactorSecurityBanner 
                    v-if="!bannerDismissed"
                    @dismiss="bannerDismissed = true" 
                />

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdateLocationForm :profile="profile" class="max-w-xl" />
                </div>

                <!-- Section Sécurité : 2FA -->
                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <TwoFactorForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
