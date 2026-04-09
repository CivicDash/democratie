<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    code_insee: String,
    categories: Object,
    article: Object,
});

const isEdit = !!props.article;

const form = useForm({
    titre: props.article?.titre || '',
    contenu: props.article?.contenu || '',
    extrait: props.article?.extrait || '',
    categorie: props.article?.categorie || 'info_generale',
    epingle: props.article?.epingle || false,
    publie: props.article?.publie || false,
    image: null,
});

const imagePreview = ref(props.article?.image_url || null);

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (isEdit) {
        form.post(route('commune.admin.articles.update', [props.code_insee, props.article.slug]), {
            _method: 'put',
            forceFormData: true,
        });
    } else {
        form.post(route('commune.admin.articles.store', props.code_insee), {
            forceFormData: true,
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <div class="mb-6">
                <Link :href="route('commune.admin.articles', code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    &larr; Retour aux articles
                </Link>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">
                    {{ isEdit ? 'Modifier l\'article' : 'Nouvel article' }}
                </h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Titre -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre *</label>
                        <input
                            v-model="form.titre"
                            type="text"
                            required
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-lg font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Titre de l'article"
                        />
                        <p v-if="form.errors.titre" class="text-sm text-red-500 mt-1">{{ form.errors.titre }}</p>
                    </div>

                    <!-- Categorie -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categorie</label>
                            <select
                                v-model="form.categorie"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Image</label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="onImageChange"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                            />
                        </div>
                    </div>

                    <!-- Preview image -->
                    <div v-if="imagePreview" class="rounded-xl overflow-hidden h-40 bg-slate-100 dark:bg-slate-700">
                        <img :src="imagePreview" class="w-full h-full object-cover" />
                    </div>

                    <!-- Extrait -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Extrait (optionnel)</label>
                        <textarea
                            v-model="form.extrait"
                            rows="2"
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            placeholder="Resume court affiche dans les listes (auto-genere si vide)"
                        />
                    </div>

                    <!-- Contenu -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contenu *</label>
                        <textarea
                            v-model="form.contenu"
                            rows="15"
                            required
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono text-sm"
                            placeholder="Contenu de l'article (Markdown supporte)"
                        />
                        <p v-if="form.errors.contenu" class="text-sm text-red-500 mt-1">{{ form.errors.contenu }}</p>
                    </div>
                </div>

                <!-- Options publication -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-4">Publication</h2>
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.epingle" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">Epingler en haut</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.publie" class="rounded border-slate-300 text-green-600 focus:ring-green-500" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">Publier immediatement</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <Link :href="route('commune.admin.articles', code_insee)" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Enregistrement...' : (isEdit ? 'Mettre a jour' : 'Creer l\'article') }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
