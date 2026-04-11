<script setup>
import { Link } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';

defineProps({
    ville: Object,
    page: Object,
    consultations: Object,
    seo: Object,
});
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :seo="seo" :titre="`Consultations - ${ville.nom}`">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Consultations citoyennes</h1>
            <p class="text-slate-500 dark:text-slate-400 mb-8">Donnez votre avis sur les sujets qui comptent pour {{ ville.nom }}</p>

            <div class="space-y-4">
                <Link
                    v-for="c in consultations.data"
                    :key="c.id"
                    :href="route('commune.consultations.show', [ville.code_insee, c.slug])"
                    class="block bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all group"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="c.est_ouverte
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                                >
                                    {{ c.est_ouverte ? 'En cours' : 'Terminee' }}
                                </span>
                                <span v-if="c.ferme_at" class="text-xs text-slate-400">Jusqu'au {{ c.ferme_at }}</span>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ c.titre }}</h3>
                            <p v-if="c.description" class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ c.description }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ c.votes_count }}</div>
                            <div class="text-xs text-slate-400">votes</div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-4 text-xs text-slate-400">
                        <span>{{ c.options_count }} options</span>
                        <span>Publiee le {{ c.publie_at }}</span>
                    </div>
                </Link>
            </div>

            <div v-if="!consultations.data?.length" class="text-center py-16">
                <p class="text-slate-400 dark:text-slate-500">Aucune consultation pour le moment.</p>
            </div>
        </div>
    </CommuneLayout>
</template>
