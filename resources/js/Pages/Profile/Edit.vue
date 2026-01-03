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
