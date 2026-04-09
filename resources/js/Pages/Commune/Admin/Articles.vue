<script setup>
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ville: Object,
    articles: Object,
});

const publier = (slug) => {
    router.post(route('commune.admin.articles.publier', [props.ville.code_insee, slug]), {}, {
        preserveScroll: true,
    });
};

const supprimer = (slug) => {
    if (confirm('Supprimer cet article ?')) {
        router.delete(route('commune.admin.articles.destroy', [props.ville.code_insee, slug]));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Articles - {{ ville.nom }}</h1>
                    <Link :href="route('commune.admin.dashboard', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        &larr; Retour au dashboard
                    </Link>
                </div>
                <Link
                    :href="route('commune.admin.articles.create', ville.code_insee)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouvel article
                </Link>
            </div>

            <!-- Liste -->
            <div v-if="articles.data?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-700/50 text-left">
                            <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Titre</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 hidden sm:table-cell">Categorie</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 text-center">Statut</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 text-right hidden sm:table-cell">Vues</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="article in articles.data" :key="article.id" class="border-t border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900 dark:text-white">{{ article.titre }}</div>
                                <div v-if="article.publie_at" class="text-xs text-slate-500">{{ article.publie_at }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400 hidden sm:table-cell">{{ article.categorie }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="article.epingle" class="text-xs mr-1" title="Epingle">📌</span>
                                <span
                                    class="inline-block text-xs px-2 py-0.5 rounded-full font-medium"
                                    :class="article.publie
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'"
                                >
                                    {{ article.publie ? 'Publie' : 'Brouillon' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-500 hidden sm:table-cell">{{ article.vues_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        v-if="!article.publie"
                                        @click="publier(article.slug)"
                                        class="p-1.5 rounded-lg text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                                        title="Publier"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <Link
                                        :href="route('commune.admin.articles.edit', [ville.code_insee, article.slug])"
                                        class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                        title="Modifier"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="supprimer(article.slug)"
                                        class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="Supprimer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
                <div class="text-4xl mb-3">📝</div>
                <p class="text-slate-500 dark:text-slate-400 mb-4">Aucun article pour le moment.</p>
                <Link
                    :href="route('commune.admin.articles.create', ville.code_insee)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Creer votre premier article
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="articles.links?.length > 3" class="flex justify-center gap-1 mt-6">
                <Link
                    v-for="link in articles.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-2 rounded-lg text-sm"
                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                    v-html="link.label"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
