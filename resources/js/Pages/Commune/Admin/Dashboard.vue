<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ville: Object,
    page: Object,
    stats: Object,
    derniers_articles: Array,
    prochains_evenements: Array,
});

const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '0';
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Administration - {{ ville.nom }}</h1>
                    <p class="text-slate-500 dark:text-slate-400">Gerez votre page commune CivicDash</p>
                </div>
                <Link :href="route('commune.index', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    Voir la page publique
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500">Vues totales</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumber(stats.vues_totales) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500">Abonnes</div>
                    <div class="text-2xl font-bold text-blue-600">{{ formatNumber(stats.abonnes) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500">Articles publies</div>
                    <div class="text-2xl font-bold text-green-600">{{ stats.articles_publies }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500">Brouillons</div>
                    <div class="text-2xl font-bold text-amber-600">{{ stats.articles_brouillon }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500">Evenements a venir</div>
                    <div class="text-2xl font-bold text-purple-600">{{ stats.evenements_a_venir }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500">Topics forum</div>
                    <div class="text-2xl font-bold text-indigo-600">{{ stats.topics_forum }}</div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <Link :href="route('commune.admin.articles.create', ville.code_insee)" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all text-center group">
                    <div class="text-2xl mb-2">📝</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-blue-600">Nouvel article</div>
                </Link>
                <Link :href="route('commune.admin.evenements.create', ville.code_insee)" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all text-center group">
                    <div class="text-2xl mb-2">📅</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-blue-600">Nouvel evenement</div>
                </Link>
                <Link :href="route('commune.admin.galerie', ville.code_insee)" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all text-center group">
                    <div class="text-2xl mb-2">🖼️</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-blue-600">Galerie photos</div>
                </Link>
                <Link :href="route('commune.admin.parametres', ville.code_insee)" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all text-center group">
                    <div class="text-2xl mb-2">⚙️</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-blue-600">Parametres</div>
                </Link>
                <Link :href="route('commune.admin.delegues', ville.code_insee)" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all text-center group">
                    <div class="text-2xl mb-2">👥</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-blue-600">Delegues</div>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Derniers articles -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-bold text-slate-900 dark:text-white">Derniers articles</h2>
                        <Link :href="route('commune.admin.articles', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">Voir tout</Link>
                    </div>
                    <div v-if="derniers_articles?.length" class="space-y-3">
                        <div v-for="a in derniers_articles" :key="a.id" class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700 last:border-0">
                            <div class="min-w-0">
                                <Link :href="route('commune.admin.articles.edit', [ville.code_insee, a.slug])" class="text-sm font-medium text-slate-900 dark:text-white hover:text-blue-600 truncate block">
                                    {{ a.titre }}
                                </Link>
                                <span class="text-xs text-slate-500">{{ a.publie_at || 'Brouillon' }} - {{ a.vues_count }} vues</span>
                            </div>
                            <span
                                class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                                :class="a.publie ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'"
                            >
                                {{ a.publie ? 'Publie' : 'Brouillon' }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500 text-center py-4">Aucun article</p>
                </div>

                <!-- Prochains evenements -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-bold text-slate-900 dark:text-white">Prochains evenements</h2>
                        <Link :href="route('commune.admin.evenements', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">Voir tout</Link>
                    </div>
                    <div v-if="prochains_evenements?.length" class="space-y-3">
                        <div v-for="e in prochains_evenements" :key="e.id" class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700 last:border-0">
                            <div class="min-w-0">
                                <Link :href="route('commune.admin.evenements.edit', [ville.code_insee, e.slug])" class="text-sm font-medium text-slate-900 dark:text-white hover:text-blue-600 truncate block">
                                    {{ e.titre }}
                                </Link>
                                <span class="text-xs text-slate-500">{{ e.date_debut }} - {{ e.inscrits_count }}{{ e.places_max ? '/' + e.places_max : '' }} inscrits</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500 text-center py-4">Aucun evenement</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
