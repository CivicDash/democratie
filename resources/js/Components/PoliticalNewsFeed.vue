<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    news: { type: Array, default: () => [] },
    compact: { type: Boolean, default: false },
    limit: { type: Number, default: 20 },
});

const categories = [
    { key: 'all', label: 'Tous' },
    { key: 'partis', label: 'Partis' },
    { key: 'institutions', label: 'Institutions' },
    { key: 'thematique', label: 'Économie & Politique' },
];

const activeCategory = ref('all');
const showAll = ref(false);

const filteredNews = computed(() => {
    let items = props.news;
    if (activeCategory.value !== 'all') {
        items = items.filter(n => n.category === activeCategory.value);
    }
    return showAll.value ? items : items.slice(0, props.limit);
});

const hasMore = computed(() => {
    const total = activeCategory.value === 'all'
        ? props.news.length
        : props.news.filter(n => n.category === activeCategory.value).length;
    return total > props.limit && !showAll.value;
});

const sourceColors = {
    'front-national': 'bg-blue-900 text-white',
    'nouveau-front-populaire': 'bg-red-600 text-white',
    'les-republicains': 'bg-blue-600 text-white',
    'parti-communiste-francais': 'bg-red-800 text-white',
    'eelv': 'bg-green-600 text-white',
    'ps': 'bg-pink-600 text-white',
    'elections': 'bg-purple-600 text-white',
    'assemblee-nationale': 'bg-blue-500 text-white',
    'politique': 'bg-gray-700 text-white',
    'plans-sociaux': 'bg-orange-600 text-white',
    'economie': 'bg-emerald-600 text-white',
};

function getSourceColor(feed) {
    return sourceColors[feed] || 'bg-gray-500 text-white';
}

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'à l\'instant';
    if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `il y a ${Math.floor(diff / 3600)}h`;
    if (diff < 604800) return `il y a ${Math.floor(diff / 86400)}j`;
    return date.toLocaleDateString('fr-FR');
}
</script>

<template>
    <div>
        <!-- Filtres -->
        <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
            <button
                v-for="cat in categories"
                :key="cat.key"
                @click="activeCategory = cat.key"
                class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-all"
                :class="activeCategory === cat.key
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
            >
                {{ cat.label }}
            </button>
        </div>

        <!-- Grille d'articles -->
        <div
            class="grid gap-4"
            :class="compact
                ? 'grid-cols-1'
                : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'"
        >
            <a
                v-for="article in filteredNews"
                :key="article.id || article.guid"
                :href="article.url"
                target="_blank"
                rel="noopener noreferrer"
                class="group block rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden hover:shadow-lg transition-all hover:border-indigo-300 dark:hover:border-indigo-600"
            >
                <!-- Image -->
                <div v-if="article.image_url && !compact" class="aspect-video bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <img
                        :src="article.image_url"
                        :alt="article.title"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        loading="lazy"
                    />
                </div>

                <div class="p-3" :class="{ 'p-2': compact }">
                    <!-- Source badge -->
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide" :class="getSourceColor(article.source_feed)">
                            {{ article.source_label || article.source_feed }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ timeAgo(article.published_at) }}</span>
                    </div>

                    <!-- Titre -->
                    <h3
                        class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2"
                        :class="compact ? 'text-sm' : 'text-base'"
                    >
                        {{ article.title }}
                    </h3>

                    <!-- Description -->
                    <p v-if="article.description && !compact" class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                        {{ article.description }}
                    </p>

                    <!-- Lien externe -->
                    <div class="mt-2 flex items-center text-xs text-indigo-500 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity">
                        Lire sur France Info →
                    </div>
                </div>
            </a>
        </div>

        <!-- Voir plus -->
        <div v-if="hasMore" class="mt-4 text-center">
            <button
                @click="showAll = true"
                class="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors"
            >
                Voir plus d'articles
            </button>
        </div>

        <!-- État vide -->
        <div v-if="filteredNews.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p class="text-lg">📰</p>
            <p class="mt-1 text-sm">Aucun article dans cette catégorie</p>
        </div>
    </div>
</template>
