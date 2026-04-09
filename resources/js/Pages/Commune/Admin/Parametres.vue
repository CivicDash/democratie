<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ville: Object,
    page: Object,
});

const form = useForm({
    description_courte: props.page.description_courte || '',
    mot_du_maire: props.page.mot_du_maire || '',
    couleur_primaire: props.page.couleur_primaire || '#1e40af',
    couleur_secondaire: props.page.couleur_secondaire || '#3b82f6',
    telephone: props.page.telephone || '',
    email_mairie: props.page.email_mairie || '',
    adresse_mairie: props.page.adresse_mairie || '',
    site_officiel: props.page.site_officiel || '',
    facebook_url: props.page.facebook_url || '',
    twitter_url: props.page.twitter_url || '',
    instagram_url: props.page.instagram_url || '',
    youtube_url: props.page.youtube_url || '',
    linkedin_url: props.page.linkedin_url || '',
    actus_actives: props.page.actus_actives ?? false,
    evenements_actifs: props.page.evenements_actifs ?? false,
    forum_actif: props.page.forum_actif ?? true,
    notifications_actives: props.page.notifications_actives ?? false,
});

const save = () => {
    form.put(route('commune.admin.parametres.update', props.ville.code_insee));
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-8">Parametres - {{ ville.nom }}</h1>

            <form @submit.prevent="save" class="space-y-8">
                <!-- Description -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Presentation</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description courte</label>
                            <textarea v-model="form.description_courte" rows="3" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" placeholder="Presentez votre commune en quelques lignes..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mot du maire</label>
                            <textarea v-model="form.mot_du_maire" rows="5" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" placeholder="Un message a destination de vos administres..." />
                        </div>
                    </div>
                </section>

                <!-- Couleurs -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Personnalisation visuelle</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Couleur primaire</label>
                            <div class="flex items-center gap-2">
                                <input type="color" v-model="form.couleur_primaire" class="w-10 h-10 rounded border-0 cursor-pointer" />
                                <input type="text" v-model="form.couleur_primaire" class="flex-1 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Couleur secondaire</label>
                            <div class="flex items-center gap-2">
                                <input type="color" v-model="form.couleur_secondaire" class="w-10 h-10 rounded border-0 cursor-pointer" />
                                <input type="text" v-model="form.couleur_secondaire" class="flex-1 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono text-sm" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Coordonnees</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Telephone</label>
                            <input type="tel" v-model="form.telephone" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email mairie</label>
                            <input type="email" v-model="form.email_mairie" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse</label>
                            <input type="text" v-model="form.adresse_mairie" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Site officiel</label>
                            <input type="url" v-model="form.site_officiel" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" placeholder="https://" />
                        </div>
                    </div>
                </section>

                <!-- Reseaux sociaux -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Reseaux sociaux</h2>
                    <div class="space-y-3">
                        <div v-for="(label, key) in { facebook_url: 'Facebook', twitter_url: 'X (Twitter)', instagram_url: 'Instagram', youtube_url: 'YouTube', linkedin_url: 'LinkedIn' }" :key="key">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ label }}</label>
                            <input type="url" v-model="form[key]" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" placeholder="https://" />
                        </div>
                    </div>
                </section>

                <!-- Fonctionnalites -->
                <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Fonctionnalites</h2>
                    <div class="space-y-3">
                        <label v-for="(label, key) in { actus_actives: 'Actualites', evenements_actifs: 'Evenements', forum_actif: 'Forum communal', notifications_actives: 'Notifications aux abonnes' }" :key="key" class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" v-model="form[key]" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ label }}</span>
                        </label>
                    </div>
                </section>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
