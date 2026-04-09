<script setup>
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    ville: Object,
    evenement: Object,
    inscriptions: Array,
});

const inscrits = computed(() => props.inscriptions.filter(i => i.statut === 'inscrit'));
const enAttente = computed(() => props.inscriptions.filter(i => i.statut === 'liste_attente'));
const annules = computed(() => props.inscriptions.filter(i => i.statut === 'annule'));
const totalPersonnes = computed(() => inscrits.value.reduce((sum, i) => sum + i.nb_personnes, 0));

const annulerInscription = (id) => {
    if (!confirm('Annuler cette inscription ?')) return;
    router.delete(route('commune.admin.evenements.inscriptions.annuler', [props.ville.code_insee, props.evenement.slug, id]), {
        preserveScroll: true,
    });
};

const statutColors = {
    inscrit: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    liste_attente: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    annule: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Inscriptions</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-0.5">{{ evenement.titre }} - {{ evenement.date_debut }}</p>
                    <Link :href="route('commune.admin.evenements', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        &larr; Retour aux evenements
                    </Link>
                </div>
                <a
                    :href="route('commune.admin.evenements.inscriptions.export', [ville.code_insee, evenement.slug])"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exporter CSV
                </a>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalPersonnes }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Personnes inscrites</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ inscrits.length }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Inscriptions confirmees</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl font-bold text-amber-600">{{ enAttente.length }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">En liste d'attente</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        {{ evenement.places_max ? `${totalPersonnes}/${evenement.places_max}` : 'Illimite' }}
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Places</div>
                </div>
            </div>

            <!-- Tableau -->
            <div v-if="inscriptions.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                                <th class="text-left px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Nom</th>
                                <th class="text-left px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Email</th>
                                <th class="text-center px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Pers.</th>
                                <th class="text-center px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Statut</th>
                                <th class="text-left px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Commentaire</th>
                                <th class="text-left px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Date</th>
                                <th class="text-center px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="insc in inscriptions"
                                :key="insc.id"
                                class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ insc.nom }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ insc.email }}</td>
                                <td class="px-4 py-3 text-center text-slate-900 dark:text-white">{{ insc.nb_personnes }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full font-medium" :class="statutColors[insc.statut]">
                                        {{ insc.statut_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 max-w-[200px] truncate">{{ insc.commentaire || '-' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ insc.date }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        v-if="insc.statut !== 'annule'"
                                        @click="annulerInscription(insc.id)"
                                        class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="Annuler l'inscription"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <span v-else class="text-slate-300 dark:text-slate-600 text-xs">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
                <div class="text-4xl mb-3">👥</div>
                <p class="text-slate-500 dark:text-slate-400">Aucune inscription pour cet evenement.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
