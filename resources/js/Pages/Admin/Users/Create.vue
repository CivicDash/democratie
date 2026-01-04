<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    roles: Array,
    elu_types: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'citizen',
    elu_type: '',
    elu_ref: '',
    is_verified_elu: false,
    send_welcome_email: true,
});

const showEluFields = computed(() => form.role === 'legislator' || form.elu_type);

const submit = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => form.reset(),
    });
};

const generatePassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$%';
    let password = '';
    for (let i = 0; i < 16; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.password = password;
    form.password_confirmation = password;
};

const breadcrumbs = [
    { label: 'Admin', url: route('admin.dashboard') },
    { label: 'Utilisateurs', url: route('admin.users.index') },
    { label: 'Nouveau', url: null },
];
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Hero Banner -->
            <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" variant="light" class="mb-4" />
                    <h1 class="text-3xl font-bold">Créer un Utilisateur</h1>
                    <p class="mt-2 text-indigo-100">Créez un nouveau compte avec les rôles appropriés</p>
                </div>
            </div>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Informations de base -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Informations de base
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nom complet *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Email *
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Mot de passe *
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="form.password"
                                        type="text"
                                        required
                                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono"
                                        :class="{ 'border-red-500': form.errors.password }"
                                    />
                                    <button
                                        type="button"
                                        @click="generatePassword"
                                        class="px-3 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500"
                                    >
                                        🎲
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Rôle *
                                </label>
                                <select
                                    v-model="form.role"
                                    required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option v-for="role in roles" :key="role.name" :value="role.name">
                                        {{ role.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Informations élu (optionnel) -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Informations Élu (optionnel)
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Si cet utilisateur est un élu, renseignez son type et sa référence pour le lier à ses données parlementaires.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Type d'élu
                                </label>
                                <select
                                    v-model="form.elu_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">Non applicable</option>
                                    <option v-for="type in elu_types" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                            </div>

                            <div v-if="form.elu_type">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Référence élu (UID/Matricule)
                                </label>
                                <input
                                    v-model="form.elu_ref"
                                    type="text"
                                    placeholder="Ex: PA793172, 21071F"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Député: UID AN (PA...) | Sénateur: Matricule | Maire: ID
                                </p>
                            </div>

                            <div v-if="form.elu_type" class="flex items-center">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.is_verified_elu"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        Élu vérifié ✓
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Options
                        </h2>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.send_welcome_email"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Envoyer un email de bienvenue avec les identifiants
                            </span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4">
                        <Link 
                            :href="route('admin.users.index')"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Création...</span>
                            <span v-else>Créer l'utilisateur</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
