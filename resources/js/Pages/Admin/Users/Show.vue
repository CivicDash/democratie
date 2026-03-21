<script setup>
import { ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    user: Object,
    roles: Array,
    member_types: Object,
});

const editMode = ref(false);

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    role: props.user.roles[0] || 'citizen',
    elu_type: props.user.elu_type || '',
    elu_ref: props.user.elu_ref || '',
    is_verified_elu: props.user.is_verified_elu || false,
    // Membre association
    is_association_member: props.user.is_association_member || false,
    member_type: props.user.member_type || 'adherent',
    member_since: props.user.member_since || '',
    member_until: props.user.member_until || '',
    member_number: props.user.member_number || '',
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id), {
        onSuccess: () => {
            editMode.value = false;
        },
    });
};

const verifyEmail = () => {
    if (confirm('Valider manuellement l\'adresse email de cet utilisateur ?')) {
        router.post(route('admin.users.verify-email', props.user.id));
    }
};

const verifyElu = () => {
    if (confirm('Vérifier cet élu ?')) {
        router.post(route('admin.users.verify-elu', props.user.id));
    }
};

const revokeElu = () => {
    if (confirm('Révoquer la vérification de cet élu ?')) {
        router.post(route('admin.users.revoke-elu', props.user.id));
    }
};

const deleteUser = () => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${props.user.name} ?`)) {
        router.delete(route('admin.users.destroy', props.user.id));
    }
};

const getRoleBadgeClass = (role) => {
    return {
        'admin': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'moderator': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        'elu': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'citizen': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    }[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const breadcrumbs = [
    { label: 'Admin', url: route('admin.dashboard') },
    { label: 'Utilisateurs', url: route('admin.users.index') },
    { label: props.user.name, url: null },
];
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Hero Banner -->
            <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" variant="light" class="mb-4" />
                    
                    <div class="flex items-center gap-6">
                        <div class="h-20 w-20 rounded-full bg-white/20 flex items-center justify-center text-4xl font-bold">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold">{{ user.name }}</h1>
                            <p class="text-indigo-100">{{ user.email }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span :class="[
                                    'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                                    getRoleBadgeClass(user.primary_role)
                                ]">
                                    {{ user.primary_role_label }}
                                </span>
                                <span v-if="user.is_demo" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-amber-100 text-amber-800">
                                    🔒 Démo
                                </span>
                                <span v-if="user.is_verified_elu" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                    ✓ Élu vérifié
                                </span>
                                <span v-if="user.is_association_member" :class="[
                                    'inline-flex items-center px-3 py-1 rounded-full text-sm',
                                    user.is_active_member ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800'
                                ]">
                                    {{ user.is_active_member ? '🏅' : '⏸' }} Membre {{ user.member_type_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Colonne principale -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Informations -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Informations
                                </h2>
                                <button
                                    v-if="!editMode"
                                    @click="editMode = true"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                >
                                    Modifier
                                </button>
                            </div>

                            <div v-if="!editMode" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Nom</div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Email</div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ user.email }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Rôles</div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ user.roles.join(', ') || 'Aucun' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Inscrit le</div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ user.created_at }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Email vérifié</div>
                                        <div v-if="user.email_verified_at" class="font-medium text-green-600 dark:text-green-400">
                                            ✓ {{ user.email_verified_at }}
                                        </div>
                                        <div v-else class="font-medium text-red-500 dark:text-red-400">
                                            ✗ Non vérifié
                                        </div>
                                    </div>
                                </div>

                                <div v-if="user.elu_type" class="pt-4 border-t dark:border-gray-700">
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Informations Élu</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Type</div>
                                            <div class="font-medium text-gray-900 dark:text-white capitalize">{{ user.elu_type }}</div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Référence</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ user.elu_ref }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section Membre Association -->
                                <div v-if="user.is_association_member" class="pt-4 border-t dark:border-gray-700">
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                        🏅 Membre de l'Association
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Type de membre</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ user.member_type_label }}</div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">N° adhérent</div>
                                            <div class="font-medium text-gray-900 dark:text-white font-mono">{{ user.member_number }}</div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Membre depuis</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ user.member_since }}</div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Valide jusqu'au</div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ user.member_until || 'À vie' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!user.is_active_member" class="mt-3 p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                        <p class="text-sm text-amber-800 dark:text-amber-200">
                                            ⚠️ Cotisation expirée
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Mode édition -->
                            <form v-else @submit.prevent="submit" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                        <input v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                        <input v-model="form.email" type="email" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nouveau mot de passe</label>
                                        <input v-model="form.password" type="password" placeholder="Laisser vide pour garder l'actuel" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rôle</label>
                                        <select v-model="form.role" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <option v-for="role in roles" :key="role.name" :value="role.name">{{ role.label }}</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Section Membre Association -->
                                <div class="pt-4 border-t dark:border-gray-700">
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                        🏅 Membre de l'Association
                                    </h3>
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-3">
                                            <input 
                                                type="checkbox" 
                                                v-model="form.is_association_member" 
                                                id="is_association_member"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label for="is_association_member" class="text-sm text-gray-700 dark:text-gray-300">
                                                Est membre de l'association
                                            </label>
                                        </div>

                                        <div v-if="form.is_association_member" class="grid grid-cols-2 gap-4 pl-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de membre</label>
                                                <select v-model="form.member_type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <option v-for="(label, value) in member_types" :key="value" :value="value">{{ label }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">N° adhérent</label>
                                                <input v-model="form.member_number" type="text" placeholder="Auto-généré si vide" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Membre depuis</label>
                                                <input v-model="form.member_since" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Valide jusqu'au
                                                    <span class="text-gray-500 text-xs">(vide = à vie)</span>
                                                </label>
                                                <input v-model="form.member_until" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 pt-4">
                                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                        Enregistrer
                                    </button>
                                    <button type="button" @click="editMode = false" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Permissions -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Permissions
                            </h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="flex items-center gap-2">
                                    <span :class="user.can_post ? 'text-green-500' : 'text-red-500'">
                                        {{ user.can_post ? '✓' : '✗' }}
                                    </span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Peut poster</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="user.can_vote ? 'text-green-500' : 'text-red-500'">
                                        {{ user.can_vote ? '✓' : '✗' }}
                                    </span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Peut voter</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="user.can_moderate ? 'text-green-500' : 'text-red-500'">
                                        {{ user.can_moderate ? '✓' : '✗' }}
                                    </span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Peut modérer</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="!user.is_muted ? 'text-green-500' : 'text-red-500'">
                                        {{ !user.is_muted ? '✓' : '✗' }}
                                    </span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Non mute</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="!user.is_banned ? 'text-green-500' : 'text-red-500'">
                                        {{ !user.is_banned ? '✓' : '✗' }}
                                    </span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Non banni</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="user.two_factor_enabled ? 'text-green-500' : 'text-amber-500'">
                                        {{ user.two_factor_enabled ? '✓' : '⚠' }}
                                    </span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">2FA</span>
                                </div>
                            </div>

                            <div v-if="user.is_read_only" class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    🔒 Ce compte est en <strong>lecture seule</strong> (compte de démonstration).
                                    Il ne peut pas créer de contenu ni voter.
                                </p>
                            </div>
                        </div>

                        <!-- Sanctions -->
                        <div v-if="user.sanctions && user.sanctions.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Sanctions
                            </h2>
                            <div class="space-y-3">
                                <div v-for="sanction in user.sanctions" :key="sanction.id" 
                                     :class="['p-3 rounded-lg', sanction.is_active ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-gray-700']">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium capitalize" :class="sanction.is_active ? 'text-red-800 dark:text-red-200' : 'text-gray-700 dark:text-gray-300'">
                                            {{ sanction.type }}
                                        </span>
                                        <span v-if="sanction.is_active" class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded">
                                            Actif
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ sanction.reason }}</p>
                                    <p v-if="sanction.expires_at" class="text-xs text-gray-500 mt-1">
                                        Expire le {{ sanction.expires_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Statistiques -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Activité
                            </h2>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Sujets créés</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ user.topics_count }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Posts</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ user.posts_count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions rapides -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Actions
                            </h2>
                            <div class="space-y-2">
                                <button
                                    v-if="!user.email_verified_at"
                                    @click="verifyEmail"
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                                >
                                    ✉ Valider l'adresse email
                                </button>
                                <button
                                    v-if="user.elu_type && !user.is_verified_elu"
                                    @click="verifyElu"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                >
                                    ✓ Vérifier comme élu
                                </button>
                                <button
                                    v-if="user.is_verified_elu"
                                    @click="revokeElu"
                                    class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm"
                                >
                                    ✗ Révoquer vérification
                                </button>
                                <button
                                    v-if="!user.is_demo"
                                    @click="deleteUser"
                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"
                                >
                                    🗑 Supprimer le compte
                                </button>
                                <p v-if="user.is_demo" class="text-xs text-gray-500 text-center">
                                    Les comptes démo ne peuvent pas être supprimés.
                                </p>
                            </div>
                        </div>

                        <!-- Retour -->
                        <Link 
                            :href="route('admin.users.index')"
                            class="block text-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
                        >
                            ← Retour à la liste
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
