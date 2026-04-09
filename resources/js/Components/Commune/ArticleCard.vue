<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    article: Object,
    codeInsee: String,
    compact: { type: Boolean, default: false },
});

const categorieColors = {
    info_generale: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    travaux: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    culture: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    sport: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    association: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300',
    urbanisme: 'bg-slate-100 text-slate-700 dark:bg-slate-700/30 dark:text-slate-300',
    securite: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    environnement: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    education: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    social: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    officiel: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
};
</script>

<template>
    <Link
        :href="route('commune.actualites.show', [codeInsee, article.slug])"
        class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5"
    >
        <!-- Image -->
        <div v-if="article.image_url && !compact" class="h-40 bg-slate-100 dark:bg-slate-700 overflow-hidden">
            <img :src="article.image_url" :alt="article.titre" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
        </div>

        <div class="p-4">
            <!-- Categorie + date -->
            <div class="flex items-center gap-2 mb-2">
                <span
                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="categorieColors[article.categorie] || 'bg-slate-100 text-slate-600'"
                >
                    {{ article.categorie_label }}
                </span>
                <span class="text-xs text-slate-400">{{ article.publie_at }}</span>
            </div>

            <!-- Titre -->
            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" :class="compact ? 'text-sm' : 'text-base'">
                {{ article.titre }}
            </h3>

            <!-- Extrait -->
            <p v-if="!compact" class="text-sm text-slate-600 dark:text-slate-400 mt-1 line-clamp-2">
                {{ article.extrait }}
            </p>
        </div>
    </Link>
</template>
