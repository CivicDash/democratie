<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    ville: Object,
    abonnes_count: Number,
    historique: Array,
});

const sent = ref(false);

const form = useForm({
    sujet: '',
    contenu: '',
    type: 'info',
    cible: 'tous',
});

const types = {
    info: { label: 'Information', icon: 'ℹ️', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' },
    evenement: { label: 'Evenement', icon: '📅', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' },
    alerte: { label: 'Alerte', icon: '⚠️', color: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' },
    urgence: { label: 'Urgence', icon: '🚨', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
};

const previewText = computed(() => {
    return form.contenu.length > 200 ? form.contenu.substring(0, 200) + '...' : form.contenu;
});

const envoyer = () => {
    if (!confirm(`Envoyer cette notification a ${props.abonnes_count} abonne(s) ?`)) return;
    form.post(route('commune.admin.notifications.envoyer', props.ville.code_insee), {
        onSuccess: () => {
            sent.value = true;
            form.reset();
            setTimeout(() => sent.value = false, 5000);
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications - {{ ville.nom }}</h1>
                <Link :href="route('commune.admin.dashboard', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    &larr; Retour au dashboard
                </Link>
            </div>

            <!-- Alerte de succes -->
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="sent" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-300 text-sm font-medium">
                    Notification envoyee avec succes !
                </div>
            </Transition>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Formulaire (2/3) -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="envoyer" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 space-y-4">
                        <h2 class="font-bold text-slate-900 dark:text-white">Nouvelle notification</h2>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="(t, key) in types"
                                    :key="key"
                                    type="button"
                                    @click="form.type = key"
                                    class="px-3 py-2 rounded-xl text-sm font-medium border transition-all"
                                    :class="form.type === key
                                        ? t.color + ' border-transparent ring-2 ring-offset-2 ring-blue-500'
                                        : 'border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'"
                                >
                                    {{ t.icon }} {{ t.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Sujet -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sujet *</label>
                            <input
                                v-model="form.sujet"
                                type="text"
                                required
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Objet de la notification"
                            />
                            <p v-if="form.errors.sujet" class="text-sm text-red-500 mt-1">{{ form.errors.sujet }}</p>
                        </div>

                        <!-- Contenu -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Message *</label>
                            <textarea
                                v-model="form.contenu"
                                rows="6"
                                required
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="Contenu de la notification..."
                            />
                            <p v-if="form.errors.contenu" class="text-sm text-red-500 mt-1">{{ form.errors.contenu }}</p>
                            <p class="text-xs text-slate-400 mt-1 text-right">{{ form.contenu.length }} caracteres</p>
                        </div>

                        <!-- Cible -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Destinataires</label>
                            <select
                                v-model="form.cible"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="tous">Tous les abonnes ({{ abonnes_count }})</option>
                                <option value="email_only">Par email uniquement</option>
                                <option value="app_only">Notification in-app uniquement</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-2">
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ abonnes_count }} abonne{{ abonnes_count > 1 ? 's' : '' }} recevront cette notification
                            </p>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.sujet || !form.contenu"
                                class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                            >
                                {{ form.processing ? 'Envoi...' : 'Envoyer' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sidebar : apercu + historique -->
                <div class="space-y-6">
                    <!-- Apercu -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                        <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm uppercase tracking-wider">Apercu</h3>
                        <div v-if="form.sujet || form.contenu" class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm" :class="types[form.type]?.color + ' px-2 py-0.5 rounded-full text-xs font-medium'">
                                    {{ types[form.type]?.icon }} {{ types[form.type]?.label }}
                                </span>
                            </div>
                            <p class="font-medium text-slate-900 dark:text-white text-sm">{{ form.sujet || 'Sans sujet' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ previewText || 'Aucun contenu' }}</p>
                        </div>
                        <p v-else class="text-sm text-slate-400">Remplissez le formulaire pour voir l'apercu</p>
                    </div>

                    <!-- Historique -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                        <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm uppercase tracking-wider">Historique</h3>
                        <div v-if="historique?.length" class="space-y-3">
                            <div v-for="notif in historique" :key="notif.id" class="border-b border-slate-100 dark:border-slate-700 pb-3 last:border-0 last:pb-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ notif.sujet }}</p>
                                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                    <span>{{ notif.date }}</span>
                                    <span>&middot;</span>
                                    <span>{{ notif.destinataires }} dest.</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-slate-400">Aucune notification envoyee</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
