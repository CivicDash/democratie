<script setup>
import { Link } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';

const props = defineProps({
    ville: Object,
    page: Object,
    topics: Object,
});

const typeIcons = {
    debate: '💬',
    idea: '💡',
    question: '❓',
    petition: '✍️',
    bill: '📜',
    referendum: '🗳️',
};
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Forum - ${ville.nom}`">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Forum communal</h1>
                <Link
                    href="/participation/new"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouveau sujet
                </Link>
            </div>

            <div v-if="topics.data?.length" class="space-y-2">
                <Link
                    v-for="topic in topics.data"
                    :key="topic.id"
                    :href="route('topics.show', topic.slug)"
                    class="block bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all group"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-xl flex-shrink-0">{{ typeIcons[topic.type] || '💬' }}</span>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ topic.title }}
                            </h3>
                            <div class="flex items-center gap-3 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                <span>{{ topic.author }}</span>
                                <span>{{ topic.created_at }}</span>
                                <span>{{ topic.posts_count }} reponse(s)</span>
                                <span class="text-slate-400">{{ topic.updated_at }}</span>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else class="text-center py-16">
                <div class="text-4xl mb-3">🏛️</div>
                <p class="text-slate-500 dark:text-slate-400 mb-4">Aucun sujet dans le forum communal.</p>
                <p class="text-sm text-slate-400">Soyez le premier a lancer une discussion !</p>
            </div>

            <!-- Pagination -->
            <div v-if="topics.links?.length > 3" class="flex justify-center gap-1 mt-8">
                <Link
                    v-for="link in topics.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-2 rounded-lg text-sm"
                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                    v-html="link.label"
                />
            </div>
        </div>
    </CommuneLayout>
</template>
