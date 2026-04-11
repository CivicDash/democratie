<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    ville: Object,
    page: Object,
    topics: Object,
    epingles: { type: Array, default: () => [] },
    categories: { type: Object, default: () => ({}) },
    categorie_active: String,
    est_admin: Boolean,
});

const auth = computed(() => usePage().props.auth?.user);

const typeIcons = {
    debate: '💬',
    idea: '💡',
    question: '❓',
    petition: '✍️',
    bill: '📜',
    referendum: '🗳️',
};

const togglePin = (topicId) => {
    router.post(route('commune.admin.forum.epingler', [props.ville.code_insee, topicId]), {}, { preserveScroll: true });
};
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Forum - ${ville.nom}`">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Forum communal</h1>
                <Link
                    v-if="auth"
                    :href="route('commune.forum.create', ville.code_insee)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nouveau sujet
                </Link>
            </div>

            <!-- Categories filter -->
            <div class="flex flex-wrap gap-2 mb-6">
                <Link
                    :href="route('commune.forum', ville.code_insee)"
                    class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                    :class="!categorie_active ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                >
                    Tous
                </Link>
                <Link
                    v-for="(label, key) in categories"
                    :key="key"
                    :href="route('commune.forum', { codeInsee: ville.code_insee, categorie: key })"
                    class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                    :class="categorie_active === key ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                >
                    {{ label }}
                </Link>
            </div>

            <!-- Pinned topics -->
            <div v-if="epingles?.length && !categorie_active" class="mb-6 space-y-2">
                <div
                    v-for="topic in epingles"
                    :key="topic.id"
                    class="bg-amber-50 dark:bg-amber-900/10 rounded-xl p-4 border border-amber-200 dark:border-amber-800"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-amber-500 flex-shrink-0" title="Epingle">📌</span>
                        <div class="flex-1 min-w-0">
                            <Link :href="route('topics.show', topic.slug)" class="font-semibold text-slate-900 dark:text-white hover:text-blue-600 transition-colors">
                                {{ topic.title }}
                            </Link>
                            <div class="flex items-center gap-3 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                <span v-if="topic.forum_categorie_label" class="text-amber-600 dark:text-amber-400 font-medium">{{ topic.forum_categorie_label }}</span>
                                <span>{{ topic.author }}</span>
                                <span>{{ topic.posts_count }} reponse(s)</span>
                                <span>{{ topic.updated_at }}</span>
                            </div>
                        </div>
                        <button v-if="est_admin" @click="togglePin(topic.id)" class="text-xs text-slate-400 hover:text-amber-600" title="Desepingler">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Topics list -->
            <div v-if="topics.data?.length" class="space-y-2">
                <div
                    v-for="topic in topics.data"
                    :key="topic.id"
                    class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all group"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-xl flex-shrink-0">{{ typeIcons[topic.type] || '💬' }}</span>
                        <div class="flex-1 min-w-0">
                            <Link :href="route('topics.show', topic.slug)" class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ topic.title }}
                            </Link>
                            <div class="flex items-center gap-3 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                <span v-if="topic.forum_categorie_label" class="text-blue-600 dark:text-blue-400 font-medium">{{ topic.forum_categorie_label }}</span>
                                <span>{{ topic.author }}</span>
                                <span>{{ topic.created_at }}</span>
                                <span>{{ topic.posts_count }} reponse(s)</span>
                                <span class="text-slate-400">{{ topic.updated_at }}</span>
                            </div>
                        </div>
                        <button v-if="est_admin" @click="togglePin(topic.id)" class="text-xs text-slate-400 hover:text-amber-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Epingler">
                            📌
                        </button>
                    </div>
                </div>
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
