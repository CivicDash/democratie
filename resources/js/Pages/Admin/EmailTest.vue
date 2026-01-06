<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    templates: Object,
    mailConfig: Object,
});

const page = usePage();

const form = useForm({
    email: '',
    template: '',
});

// État pour les messages
const sendResult = ref(null); // { type: 'success' | 'error', message: string }

const selectedTemplate = computed(() => {
    if (form.template && props.templates[form.template]) {
        return props.templates[form.template];
    }
    return null;
});

const previewUrl = computed(() => {
    if (form.template) {
        return route('admin.email.preview', { template: form.template });
    }
    return null;
});

const sendTestEmail = () => {
    sendResult.value = null;
    
    form.post(route('admin.email.send'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                sendResult.value = { type: 'success', message: page.props.flash.success };
            }
            if (page.props.flash?.error) {
                sendResult.value = { type: 'error', message: page.props.flash.error };
            }
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).flat().join(', ');
            sendResult.value = { type: 'error', message: errorMessages || 'Erreur lors de l\'envoi' };
        },
    });
};

const openPreview = () => {
    if (previewUrl.value) {
        window.open(previewUrl.value, '_blank');
    }
};

const dismissResult = () => {
    sendResult.value = null;
};
</script>

<template>
    <Head title="Test d'emails" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <span class="text-3xl">📧</span>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Test d'emails
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tester les templates d'emails avant envoi en production
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Configuration actuelle -->
                <div 
                    class="rounded-xl shadow-sm border p-6 mb-6"
                    :class="mailConfig.driver === 'log' || !mailConfig.host
                        ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700'
                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'"
                >
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                        ⚙️ Configuration SMTP
                        <span 
                            v-if="mailConfig.driver === 'log' || !mailConfig.host"
                            class="text-xs px-2 py-1 rounded-full bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200"
                        >
                            ⚠️ Mode développement
                        </span>
                        <span 
                            v-else
                            class="text-xs px-2 py-1 rounded-full bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200"
                        >
                            ✅ Configuré
                        </span>
                    </h3>
                    
                    <div v-if="mailConfig.driver === 'log'" class="mb-4 p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg border border-amber-200 dark:border-amber-700">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <strong>⚠️ Mode log :</strong> Les emails sont écrits dans les logs Laravel au lieu d'être envoyés. 
                            Configurez MAIL_MAILER=smtp dans le fichier .env pour activer l'envoi réel.
                        </p>
                    </div>
                    
                    <div v-else-if="!mailConfig.host" class="mb-4 p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg border border-amber-200 dark:border-amber-700">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <strong>⚠️ Host non configuré :</strong> Vérifiez la variable MAIL_HOST dans le fichier .env.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Driver</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ mailConfig.driver || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Host</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ mailConfig.host || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Port</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ mailConfig.port || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">From</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200 text-sm">{{ mailConfig.from || '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Résultat de l'envoi -->
                <transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 transform -translate-y-2"
                    enter-to-class="opacity-100 transform translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="sendResult" class="mb-6">
                        <div 
                            :class="[
                                'rounded-xl p-4 flex items-start gap-3 shadow-lg',
                                sendResult.type === 'success' 
                                    ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' 
                                    : 'bg-gradient-to-r from-red-500 to-rose-600 text-white'
                            ]"
                        >
                            <span class="text-2xl flex-shrink-0">
                                {{ sendResult.type === 'success' ? '✅' : '❌' }}
                            </span>
                            <div class="flex-1">
                                <p class="font-semibold">
                                    {{ sendResult.type === 'success' ? 'Email envoyé !' : 'Erreur d\'envoi' }}
                                </p>
                                <p class="text-sm opacity-90 mt-1">{{ sendResult.message }}</p>
                            </div>
                            <button 
                                @click="dismissResult" 
                                class="text-white/80 hover:text-white text-xl"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </transition>

                <!-- Formulaire d'envoi -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-6 flex items-center gap-2">
                        ✉️ Envoyer un email de test
                    </h3>

                    <form @submit.prevent="sendTestEmail" class="space-y-6">
                        <!-- Email destinataire -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Adresse email de test
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="votre-email@example.com"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <!-- Sélection du template -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Template à tester
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <button
                                    v-for="(template, key) in templates"
                                    :key="key"
                                    type="button"
                                    @click="form.template = key"
                                    :class="[
                                        'p-4 rounded-lg border-2 text-left transition-all',
                                        form.template === key
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                    ]"
                                >
                                    <span class="text-2xl block mb-1">{{ template.icon }}</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200 text-sm block">{{ template.name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ template.description }}</span>
                                </button>
                            </div>
                            <p v-if="form.errors.template" class="mt-1 text-sm text-red-600">{{ form.errors.template }}</p>
                        </div>

                        <!-- Template sélectionné -->
                        <div v-if="selectedTemplate" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">{{ selectedTemplate.icon }}</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ selectedTemplate.name }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ selectedTemplate.description }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="openPreview"
                                    class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1"
                                >
                                    👁️ Prévisualiser
                                </button>
                            </div>
                        </div>

                        <!-- Bouton d'envoi -->
                        <div class="flex justify-end gap-3">
                            <button
                                type="submit"
                                :disabled="form.processing || !form.email || !form.template"
                                :class="[
                                    'px-6 py-3 rounded-lg font-medium transition-all flex items-center gap-2',
                                    form.processing || !form.email || !form.template
                                        ? 'bg-gray-300 dark:bg-gray-600 text-gray-500 cursor-not-allowed'
                                        : 'bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 shadow-lg hover:shadow-xl'
                                ]"
                            >
                                <span v-if="form.processing" class="animate-spin">⏳</span>
                                <span v-else>📤</span>
                                {{ form.processing ? 'Envoi en cours...' : 'Envoyer l\'email de test' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Liste des templates -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                        📋 Tous les templates disponibles
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-3 font-medium">Template</th>
                                    <th class="pb-3 font-medium">Description</th>
                                    <th class="pb-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(template, key) in templates" :key="key" class="group">
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">{{ template.icon }}</span>
                                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ template.name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ template.description }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <a
                                            :href="route('admin.email.preview', { template: key })"
                                            target="_blank"
                                            class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                        >
                                            Prévisualiser →
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Aide -->
                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <h4 class="font-medium text-amber-800 dark:text-amber-200 flex items-center gap-2">
                        💡 Conseils
                    </h4>
                    <ul class="mt-2 text-sm text-amber-700 dark:text-amber-300 space-y-1">
                        <li>• Utilisez une adresse email que vous pouvez vérifier pour les tests</li>
                        <li>• Les emails de test contiennent des données fictives</li>
                        <li>• Vérifiez les spams si l'email n'arrive pas dans votre boîte principale</li>
                        <li>• La prévisualisation s'ouvre dans un nouvel onglet</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
