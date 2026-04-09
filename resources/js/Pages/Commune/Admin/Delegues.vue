<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    ville: Object,
    admins: Array,
});

const showForm = ref(false);

const form = useForm({
    email: '',
    role: 'editeur',
    permissions: [],
});

const permissionsList = [
    { key: 'articles', label: 'Gerer les articles' },
    { key: 'evenements', label: 'Gerer les evenements' },
    { key: 'parametres', label: 'Modifier les parametres' },
    { key: 'delegues', label: 'Gerer les delegues' },
    { key: 'notifications', label: 'Envoyer des notifications' },
];

const inviter = () => {
    form.post(route('commune.admin.delegues.ajouter', props.ville.code_insee), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const retirer = (adminId) => {
    if (confirm('Retirer cet administrateur ?')) {
        router.delete(route('commune.admin.delegues.supprimer', [props.ville.code_insee, adminId]));
    }
};

const roleLabels = {
    administrateur: 'Administrateur',
    editeur: 'Editeur',
    moderateur: 'Moderateur',
};

const roleColors = {
    administrateur: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    editeur: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    moderateur: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Equipe - {{ ville.nom }}</h1>
                    <Link :href="route('commune.admin.dashboard', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        &larr; Retour au dashboard
                    </Link>
                </div>
                <button
                    @click="showForm = !showForm"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Inviter
                </button>
            </div>

            <!-- Formulaire d'invitation -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-3"
            >
                <form v-if="showForm" @submit.prevent="inviter" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 mb-6 space-y-4">
                    <h2 class="font-bold text-slate-900 dark:text-white">Inviter un delegue</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="collegue@mairie.fr"
                            />
                            <p v-if="form.errors.email" class="text-sm text-red-500 mt-1">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Role</label>
                            <select
                                v-model="form.role"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="administrateur">Administrateur (acces total)</option>
                                <option value="editeur">Editeur (articles & evenements)</option>
                                <option value="moderateur">Moderateur (forum)</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="form.role === 'editeur'">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Permissions specifiques</label>
                        <div class="flex flex-wrap gap-3">
                            <label v-for="perm in permissionsList" :key="perm.key" class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.permissions" :value="perm.key" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">{{ perm.label }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Envoi...' : 'Envoyer l\'invitation' }}
                        </button>
                        <button type="button" @click="showForm = false" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">Annuler</button>
                    </div>
                </form>
            </Transition>

            <!-- Liste des delegues -->
            <div v-if="admins?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div
                    v-for="admin in admins"
                    :key="admin.id"
                    class="flex items-center gap-4 px-5 py-4 border-b border-slate-100 dark:border-slate-700 last:border-0"
                >
                    <!-- Avatar -->
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-sm">
                            {{ (admin.user?.name || '?').charAt(0).toUpperCase() }}
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-slate-900 dark:text-white truncate">
                            {{ admin.user?.name }}
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            {{ admin.user?.email }}
                        </div>
                    </div>

                    <!-- Role badge -->
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium" :class="roleColors[admin.role] || 'bg-slate-100 text-slate-600'">
                        {{ admin.role_label || roleLabels[admin.role] || admin.role }}
                    </span>

                    <!-- Expire -->
                    <span v-if="admin.est_expire" class="text-xs text-red-500 font-medium">Expire</span>
                    <span v-else-if="admin.expire_le" class="text-xs text-slate-400">exp. {{ admin.expire_le }}</span>

                    <!-- Actions -->
                    <button
                        v-if="admin.role !== 'maire'"
                        @click="retirer(admin.id)"
                        class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        title="Retirer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div v-else class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
                <div class="text-4xl mb-3">👥</div>
                <p class="text-slate-500 dark:text-slate-400 mb-4">Aucun delegue pour le moment.</p>
                <button
                    @click="showForm = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Inviter votre premier delegue
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
