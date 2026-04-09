<script setup>
import { Link } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import ArticleCard from '@/Components/Commune/ArticleCard.vue';
import { computed } from 'vue';

const props = defineProps({
    ville: Object,
    article: Object,
    articles_recents: Array,
    seo: Object,
});

const articleJsonLd = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'NewsArticle',
    headline: props.article.titre,
    datePublished: props.article.publie_at_iso,
    author: props.article.auteur ? { '@type': 'Person', name: props.article.auteur } : undefined,
    image: props.article.image_url || undefined,
    publisher: { '@type': 'Organization', name: 'CivicDash' },
}));
</script>

<template>
    <CommuneLayout :ville="ville" :titre="article.titre + ' - ' + ville.nom">
        <component :is="'script'" type="application/ld+json" v-text="articleJsonLd" />
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
                <Link :href="route('commune.index', ville.code_insee)" class="hover:text-blue-600">{{ ville.nom }}</Link>
                <span>/</span>
                <Link :href="route('commune.actualites', ville.code_insee)" class="hover:text-blue-600">Actualites</Link>
                <span>/</span>
                <span class="text-slate-900 dark:text-white truncate">{{ article.titre }}</span>
            </nav>

            <!-- Image -->
            <div v-if="article.image_url" class="rounded-2xl overflow-hidden mb-6 -mx-4 sm:mx-0">
                <img :src="article.image_url" :alt="article.titre" class="w-full h-64 sm:h-80 object-cover" />
            </div>

            <!-- Meta -->
            <div class="flex items-center gap-3 mb-4">
                <span class="text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">
                    {{ article.categorie_label }}
                </span>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ article.publie_at }}</span>
                <span v-if="article.auteur" class="text-sm text-slate-500 dark:text-slate-400">par {{ article.auteur }}</span>
                <span class="text-xs text-slate-400">{{ article.vues_count }} vues</span>
            </div>

            <!-- Titre -->
            <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-6 leading-tight">
                {{ article.titre }}
            </h1>

            <!-- Contenu -->
            <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed" v-html="article.contenu" />

            <!-- Articles recents -->
            <div v-if="articles_recents?.length" class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Autres actualites</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <ArticleCard
                        v-for="a in articles_recents"
                        :key="a.id"
                        :article="a"
                        :code-insee="ville.code_insee"
                        :compact="true"
                    />
                </div>
            </div>
        </div>
    </CommuneLayout>
</template>
