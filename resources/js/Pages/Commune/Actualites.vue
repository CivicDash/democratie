<script setup>
import { Link } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import ArticleCard from '@/Components/Commune/ArticleCard.vue';

const props = defineProps({
    ville: Object,
    page: Object,
    articles: Object,
    epingles: Array,
    categories: Object,
    categorie_active: String,
});
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Actualites - ${ville.nom}`">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Actualites</h1>
            </div>

            <!-- Epingles -->
            <div v-if="epingles?.length && !categorie_active" class="mb-8">
                <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">A la une</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <ArticleCard v-for="article in epingles" :key="article.id" :article="article" :code-insee="ville.code_insee" />
                </div>
            </div>

            <!-- Filtres categories -->
            <div class="flex flex-wrap gap-2 mb-6">
                <Link
                    :href="route('commune.actualites', ville.code_insee)"
                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                    :class="!categorie_active ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                >
                    Toutes
                </Link>
                <Link
                    v-for="(label, key) in categories"
                    :key="key"
                    :href="route('commune.actualites', { codeInsee: ville.code_insee, categorie: key })"
                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                    :class="categorie_active === key ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                >
                    {{ label }}
                </Link>
            </div>

            <!-- Liste articles -->
            <div v-if="articles.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <ArticleCard v-for="article in articles.data" :key="article.id" :article="article" :code-insee="ville.code_insee" />
            </div>

            <div v-else class="text-center py-16">
                <div class="text-4xl mb-3">📰</div>
                <p class="text-slate-500 dark:text-slate-400">Aucune actualite pour le moment.</p>
            </div>

            <!-- Pagination -->
            <div v-if="articles.links?.length > 3" class="flex justify-center gap-1 mt-8">
                <Link
                    v-for="link in articles.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-2 rounded-lg text-sm"
                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'"
                    v-html="link.label"
                />
            </div>
        </div>
    </CommuneLayout>
</template>
