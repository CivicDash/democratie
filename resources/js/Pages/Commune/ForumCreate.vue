<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';

const props = defineProps({
    ville: Object,
    categories: Object,
});

const form = useForm({
    title: '',
    description: '',
    forum_categorie: 'vie_locale',
});

const submit = () => {
    form.post(route('commune.forum.store', props.ville.code_insee));
};
</script>

<template>
    <CommuneLayout :ville="ville" :titre="`Nouveau sujet - Forum ${ville.nom}`">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
                <Link :href="route('commune.index', ville.code_insee)" class="hover:text-blue-600">{{ ville.nom }}</Link>
                <span>/</span>
                <Link :href="route('commune.forum', ville.code_insee)" class="hover:text-blue-600">Forum</Link>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Nouveau sujet</span>
            </nav>

            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Nouveau sujet de discussion</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre du sujet</label>
                    <input v-model="form.title" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="De quoi souhaitez-vous discuter ?" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categorie</label>
                    <select v-model="form.forum_categorie" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contenu</label>
                    <textarea v-model="form.description" rows="8" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Decrivez votre sujet en detail..." />
                    <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Publication...' : 'Publier' }}
                    </button>
                    <Link :href="route('commune.forum', ville.code_insee)" class="text-sm text-slate-500 hover:text-slate-700">Annuler</Link>
                </div>
            </form>
        </div>
    </CommuneLayout>
</template>
