<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    posts: { type: Array, default: () => [] },
    forCount: { type: Number, default: 0 },
    againstCount: { type: Number, default: 0 },
    neutralCount: { type: Number, default: 0 },
});

const activeTab = ref('all');

const tabs = [
    { value: 'all', label: 'Tous', icon: '📋' },
    { value: 'for', label: 'Pour', icon: '👍', color: 'emerald' },
    { value: 'against', label: 'Contre', icon: '👎', color: 'rose' },
    { value: 'neutral', label: 'Neutres', icon: '🤔', color: 'slate' },
];

const filteredPosts = computed(() => {
    if (activeTab.value === 'all') return props.posts;
    return props.posts.filter(p => p.debate_position === activeTab.value);
});

const forPercentage = computed(() => {
    const total = props.forCount + props.againstCount;
    if (total === 0) return 50;
    return Math.round((props.forCount / total) * 100);
});

const againstPercentage = computed(() => {
    return 100 - forPercentage.value;
});

function getPositionStyle(position) {
    switch (position) {
        case 'for': return 'border-l-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/10';
        case 'against': return 'border-l-rose-500 bg-rose-50/50 dark:bg-rose-900/10';
        case 'neutral': return 'border-l-slate-400 bg-slate-50/50 dark:bg-slate-900/10';
        default: return 'border-l-gray-300';
    }
}

function getPositionBadge(position) {
    switch (position) {
        case 'for': return { icon: '👍', label: 'Pour', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' };
        case 'against': return { icon: '👎', label: 'Contre', class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' };
        case 'neutral': return { icon: '🤔', label: 'Neutre', class: 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400' };
        default: return null;
    }
}
</script>

<template>
    <div class="debate-view">
        <!-- Barre de score -->
        <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-xl">👍</span>
                    <span class="font-bold text-emerald-600">{{ forCount }}</span>
                    <span class="text-gray-500 text-sm">Pour</span>
                </div>
                <div class="text-center">
                    <span class="text-2xl">⚔️</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 text-sm">Contre</span>
                    <span class="font-bold text-rose-600">{{ againstCount }}</span>
                    <span class="text-xl">👎</span>
                </div>
            </div>
            
            <!-- Barre de progression -->
            <div class="h-4 rounded-full overflow-hidden flex">
                <div 
                    class="bg-emerald-500 transition-all duration-500 flex items-center justify-end pr-2"
                    :style="{ width: forPercentage + '%' }"
                >
                    <span v-if="forPercentage > 15" class="text-xs text-white font-bold">{{ forPercentage }}%</span>
                </div>
                <div 
                    class="bg-rose-500 transition-all duration-500 flex items-center justify-start pl-2"
                    :style="{ width: againstPercentage + '%' }"
                >
                    <span v-if="againstPercentage > 15" class="text-xs text-white font-bold">{{ againstPercentage }}%</span>
                </div>
            </div>
            
            <div v-if="neutralCount > 0" class="mt-2 text-center text-sm text-gray-500">
                + {{ neutralCount }} argument{{ neutralCount > 1 ? 's' : '' }} neutre{{ neutralCount > 1 ? 's' : '' }}
            </div>
        </div>

        <!-- Onglets de filtre -->
        <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="activeTab = tab.value"
                class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all"
                :class="activeTab === tab.value
                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
            >
                {{ tab.icon }} {{ tab.label }}
                <span class="ml-1 opacity-70">
                    ({{ tab.value === 'all' ? posts.length : 
                       tab.value === 'for' ? forCount : 
                       tab.value === 'against' ? againstCount : neutralCount }})
                </span>
            </button>
        </div>

        <!-- Liste des arguments -->
        <div class="space-y-4">
            <div
                v-for="post in filteredPosts"
                :key="post.id"
                class="p-4 rounded-xl border-l-4 border border-gray-200 dark:border-gray-700"
                :class="getPositionStyle(post.debate_position)"
            >
                <!-- En-tête -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <img 
                            v-if="post.author?.profile_photo_url" 
                            :src="post.author.profile_photo_url" 
                            :alt="post.author.name"
                            class="w-8 h-8 rounded-full object-cover"
                        >
                        <div v-else class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-sm">
                            {{ post.author?.name?.charAt(0) || '?' }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ post.author?.name || 'Anonyme' }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ post.created_at_human }}</span>
                        </div>
                    </div>
                    
                    <!-- Badge position -->
                    <span 
                        v-if="getPositionBadge(post.debate_position)"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                        :class="getPositionBadge(post.debate_position).class"
                    >
                        {{ getPositionBadge(post.debate_position).icon }}
                        {{ getPositionBadge(post.debate_position).label }}
                    </span>
                </div>

                <!-- Contenu -->
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300" v-html="post.content_html || post.content"></div>

                <!-- Actions -->
                <div class="mt-3 flex items-center gap-4 text-sm">
                    <button class="flex items-center gap-1 text-gray-500 hover:text-emerald-600 transition-colors">
                        <span>👍</span>
                        <span>{{ post.upvotes_count || 0 }}</span>
                    </button>
                    <button class="flex items-center gap-1 text-gray-500 hover:text-rose-600 transition-colors">
                        <span>👎</span>
                        <span>{{ post.downvotes_count || 0 }}</span>
                    </button>
                    <button class="text-gray-500 hover:text-blue-600 transition-colors">
                        💬 Répondre
                    </button>
                </div>
            </div>

            <!-- Message vide -->
            <div v-if="filteredPosts.length === 0" class="text-center py-8 text-gray-500">
                <span class="text-4xl block mb-2">🤷</span>
                Aucun argument {{ activeTab !== 'all' ? `"${tabs.find(t => t.value === activeTab)?.label}"` : '' }} pour le moment.
            </div>
        </div>
    </div>
</template>
