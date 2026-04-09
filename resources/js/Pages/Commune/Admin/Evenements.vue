<script setup>
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ville: Object,
    evenements: Object,
});

const supprimer = (slug) => {
    if (confirm('Supprimer cet evenement ?')) {
        router.delete(route('commune.admin.evenements.destroy', [props.ville.code_insee, slug]));
    }
};

const annulerEvenement = (slug) => {
    if (confirm('Annuler cet evenement ? Les inscrits seront informes.')) {
        router.post(route('commune.admin.evenements.annuler', [props.ville.code_insee, slug]));
    }
};

const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Evenements - {{ ville.nom }}</h1>
                    <Link :href="route('commune.admin.dashboard', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        &larr; Retour au dashboard
                    </Link>
                </div>
                <Link
                    :href="route('commune.admin.evenements.create', ville.code_insee)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouvel evenement
                </Link>
            </div>

            <div v-if="evenements.data?.length" class="space-y-3">
                <div
                    v-for="event in evenements.data"
                    :key="event.id"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4"
                >
                    <!-- Date badge -->
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl flex flex-col items-center justify-center text-center"
                        :class="new Date(event.date_debut) > new Date() ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-500'"
                    >
                        <span class="text-xs font-medium uppercase">{{ new Date(event.date_debut).toLocaleDateString('fr-FR', { month: 'short' }) }}</span>
                        <span class="text-xl font-bold leading-tight">{{ new Date(event.date_debut).getDate() }}</span>
                    </div>

                    <!-- Infos -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium text-slate-900 dark:text-white truncate">{{ event.titre }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                {{ event.categorie }}
                            </span>
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400 flex flex-wrap gap-3">
                            <span>📅 {{ formatDate(event.date_debut) }}</span>
                            <span v-if="event.places_max">
                                👤 {{ event.inscrits_count || 0 }}/{{ event.places_max }}
                            </span>
                        </div>
                    </div>

                    <!-- Statut + Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span
                            class="text-xs px-2 py-1 rounded-full font-medium"
                            :class="{
                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300': event.publie && !event.annule && new Date(event.date_debut) > new Date(),
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': !event.publie && !event.annule,
                                'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400': event.publie && !event.annule && new Date(event.date_debut) <= new Date(),
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300': event.annule,
                            }"
                        >
                            {{ event.annule ? 'Annule' : (!event.publie ? 'Brouillon' : (new Date(event.date_debut) > new Date() ? 'A venir' : 'Passe')) }}
                        </span>
                        <Link
                            v-if="event.inscrits_count > 0 || event.places_max"
                            :href="route('commune.admin.evenements.inscriptions', [ville.code_insee, event.slug])"
                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
                            title="Voir les inscriptions"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </Link>
                        <Link
                            :href="route('commune.admin.evenements.edit', [ville.code_insee, event.slug])"
                            class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                            title="Modifier"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>
                        <button
                            v-if="!event.annule && event.publie"
                            @click="annulerEvenement(event.slug)"
                            class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                            title="Annuler l'evenement"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </button>
                        <button
                            @click="supprimer(event.slug)"
                            class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            title="Supprimer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
                <div class="text-4xl mb-3">📅</div>
                <p class="text-slate-500 dark:text-slate-400 mb-4">Aucun evenement programme.</p>
                <Link
                    :href="route('commune.admin.evenements.create', ville.code_insee)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Creer votre premier evenement
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="evenements.links?.length > 3" class="flex justify-center gap-1 mt-6">
                <Link
                    v-for="link in evenements.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-2 rounded-lg text-sm"
                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                    v-html="link.label"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
