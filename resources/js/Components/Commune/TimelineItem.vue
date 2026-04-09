<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    item: Object,
    codeInsee: String,
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
    ceremonie: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    reunion: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    fete: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    marche: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
};

const itemRoute = (item) => {
    if (item.type === 'article') {
        return route('commune.actualites.show', [props.codeInsee, item.slug]);
    }
    return route('commune.evenements.show', [props.codeInsee, item.slug]);
};
</script>

<template>
    <Link :href="itemRoute(item)" class="group flex gap-4 relative">
        <!-- Timeline line + dot -->
        <div class="flex flex-col items-center flex-shrink-0 w-8">
            <div
                class="w-3 h-3 rounded-full border-2 mt-1.5 flex-shrink-0 z-10"
                :class="item.type === 'article'
                    ? 'bg-blue-500 border-blue-200 dark:border-blue-800'
                    : 'bg-amber-500 border-amber-200 dark:border-amber-800'"
            />
            <div class="w-0.5 flex-1 bg-slate-200 dark:bg-slate-700 -mt-px" />
        </div>

        <!-- Content card -->
        <div class="flex-1 pb-6 min-w-0">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex">
                    <!-- Thumbnail -->
                    <div v-if="item.image_url" class="w-24 sm:w-32 flex-shrink-0 hidden sm:block">
                        <img :src="item.image_url" :alt="item.titre" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>

                    <!-- Text -->
                    <div class="p-4 flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            <!-- Type badge -->
                            <span
                                class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                :class="item.type === 'article'
                                    ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300'
                                    : 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300'"
                            >
                                {{ item.type === 'article' ? '📰 Article' : '📅 Evenement' }}
                            </span>
                            <span
                                v-if="item.categorie"
                                class="text-xs px-2 py-0.5 rounded-full"
                                :class="categorieColors[item.categorie] || 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                            >
                                {{ item.categorie_label || item.categorie }}
                            </span>
                        </div>

                        <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">
                            {{ item.titre }}
                        </h3>

                        <!-- Article: extrait -->
                        <p v-if="item.type === 'article' && item.extrait" class="text-sm text-slate-600 dark:text-slate-400 mt-1 line-clamp-2">
                            {{ item.extrait }}
                        </p>

                        <!-- Evenement: lieu + inscription -->
                        <div v-if="item.type === 'evenement'" class="mt-1 flex flex-wrap gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <span v-if="item.lieu_nom" class="truncate">📍 {{ item.lieu_nom }}</span>
                            <span
                                v-if="item.inscription_requise"
                                class="text-xs px-2 py-0.5 rounded-full"
                                :class="item.est_complet
                                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'"
                            >
                                {{ item.est_complet ? 'Complet' : `${item.places_restantes ?? '?'} places` }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">{{ item.date_formate }}</p>
                    </div>
                </div>
            </div>
        </div>
    </Link>
</template>
