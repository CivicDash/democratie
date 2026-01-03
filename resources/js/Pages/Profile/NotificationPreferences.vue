<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    preferences: Object, // NotificationPreference model
    categories: Object,
    channels: Object,
});

const breadcrumbs = [
    { label: 'Accueil', url: '/' },
    { label: 'Mon profil', url: '/profile' },
    { label: 'Notifications' },
];

// Form pour la soumission
const form = useForm({
    channel_in_app: props.preferences?.channel_in_app ?? true,
    channel_email: props.preferences?.channel_email ?? true,
    email_frequency: props.preferences?.email_frequency ?? 'instant',
    notify_new_reply: props.preferences?.notify_new_reply ?? true,
    notify_new_vote_on_topic: props.preferences?.notify_new_vote_on_topic ?? true,
    notify_legislative_vote_result: props.preferences?.notify_legislative_vote_result ?? true,
    notify_mention: props.preferences?.notify_mention ?? true,
    notify_vote_on_my_proposal: props.preferences?.notify_vote_on_my_proposal ?? true,
    notify_new_thematique_proposition: props.preferences?.notify_new_thematique_proposition ?? false,
    notify_system_announcement: props.preferences?.notify_system_announcement ?? true,
    notify_followed_topic_update: props.preferences?.notify_followed_topic_update ?? true,
    notify_followed_legislation_update: props.preferences?.notify_followed_legislation_update ?? true,
    group_similar_notifications: props.preferences?.group_similar_notifications ?? false,
    quiet_hours_start: props.preferences?.quiet_hours_start ?? null,
    quiet_hours_end: props.preferences?.quiet_hours_end ?? null,
});

const savePreferences = () => {
    form.post(route('notifications.preferences.update'), {
        preserveScroll: true,
    });
};

const notificationTypes = [
    { key: 'notify_new_reply', label: 'Nouvelles réponses', description: 'Quand quelqu\'un répond à vos sujets ou commentaires', icon: '💬' },
    { key: 'notify_mention', label: 'Mentions', description: 'Quand vous êtes mentionné dans un débat', icon: '@' },
    { key: 'notify_vote_on_my_proposal', label: 'Votes sur mes propositions', description: 'Quand quelqu\'un vote sur vos propositions', icon: '🗳️' },
    { key: 'notify_new_vote_on_topic', label: 'Votes sur les sujets', description: 'Activité de vote sur les sujets que vous suivez', icon: '📊' },
    { key: 'notify_legislative_vote_result', label: 'Résultats législatifs', description: 'Résultats des votes parlementaires sur les lois suivies', icon: '⚖️' },
    { key: 'notify_followed_topic_update', label: 'Mises à jour des sujets suivis', description: 'Nouvelles activités sur les sujets que vous suivez', icon: '🔔' },
    { key: 'notify_followed_legislation_update', label: 'Mises à jour législatives', description: 'Avancées sur les lois que vous suivez', icon: '📜' },
    { key: 'notify_new_thematique_proposition', label: 'Nouvelles propositions', description: 'Nouvelles propositions dans vos thématiques', icon: '💡' },
    { key: 'notify_system_announcement', label: 'Annonces système', description: 'Informations importantes du site', icon: '⚙️' },
];
</script>

<template>
    <Head title="Préférences de notification" />

    <AuthenticatedLayout>
        <!-- Hero Banner -->
        <section class="relative overflow-hidden bg-gradient-to-br from-indigo-700 via-purple-700 to-violet-800">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-4xl">
                        🔔
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">
                            Préférences de notification
                        </h1>
                        <p class="text-indigo-200">
                            Gérez comment et quand vous souhaitez être notifié
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Canaux de notification -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            📡 Canaux de notification
                        </h2>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🖥️</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Notifications sur le site</div>
                                    <div class="text-sm text-gray-500">Affichées dans votre centre de notifications</div>
                                </div>
                            </div>
                            <button
                                @click="form.channel_in_app = !form.channel_in_app"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200',
                                    form.channel_in_app ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'
                                ]"
                            >
                                <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200', form.channel_in_app ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📧</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Notifications par email</div>
                                    <div class="text-sm text-gray-500">Envoyées à votre adresse email</div>
                                </div>
                            </div>
                            <button
                                @click="form.channel_email = !form.channel_email"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200',
                                    form.channel_email ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'
                                ]"
                            >
                                <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200', form.channel_email ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                        </div>
                        
                        <!-- Fréquence email -->
                        <div v-if="form.channel_email" class="ml-12 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fréquence des emails
                            </label>
                            <select
                                v-model="form.email_frequency"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm"
                            >
                                <option value="instant">Instantané (à chaque notification)</option>
                                <option value="daily">Résumé quotidien</option>
                                <option value="weekly">Résumé hebdomadaire</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Types de notifications -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            📋 Types de notifications
                        </h2>
                    </div>
                    
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="type in notificationTypes"
                            :key="type.key"
                            class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ type.icon }}</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ type.label }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ type.description }}</div>
                                </div>
                            </div>
                            <button
                                @click="form[type.key] = !form[type.key]"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200',
                                    form[type.key] ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'
                                ]"
                            >
                                <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200', form[type.key] ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Options avancées -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            ⚙️ Options avancées
                        </h2>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">Regrouper les notifications similaires</div>
                                <div class="text-sm text-gray-500">Combine les notifications du même type</div>
                            </div>
                            <button
                                @click="form.group_similar_notifications = !form.group_similar_notifications"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200',
                                    form.group_similar_notifications ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'
                                ]"
                            >
                                <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200', form.group_similar_notifications ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bouton save -->
                <div class="flex justify-end">
                    <button
                        @click="savePreferences"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 flex items-center gap-2 font-medium"
                    >
                        <span v-if="form.processing">⏳</span>
                        <span v-else>💾</span>
                        Enregistrer les préférences
                    </button>
                </div>

                <!-- Lien vers le centre de notifications -->
                <div class="text-center">
                    <a
                        :href="route('notifications.index')"
                        class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                    >
                        🔔 Voir mes notifications →
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
