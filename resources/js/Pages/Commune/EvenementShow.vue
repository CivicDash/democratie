<script setup>
import { Link, router } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    ville: Object,
    evenement: Object,
    est_inscrit: Boolean,
    inscription: Object,
    seo: Object,
});

const eventJsonLd = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Event',
    name: props.evenement.titre,
    startDate: props.evenement.date_debut,
    endDate: props.evenement.date_fin || undefined,
    location: props.evenement.lieu_nom ? {
        '@type': 'Place',
        name: props.evenement.lieu_nom,
        address: props.evenement.lieu_adresse || undefined,
    } : undefined,
    image: props.evenement.image_url || undefined,
    organizer: { '@type': 'Organization', name: `Mairie de ${props.ville.nom}` },
    eventStatus: props.evenement.annule ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
}));

const nbPersonnes = ref(1);
const inscriptionLoading = ref(false);

const inscrire = () => {
    inscriptionLoading.value = true;
    router.post(route('commune.evenements.inscrire', [props.ville.code_insee, props.evenement.slug]), {
        nb_personnes: nbPersonnes.value,
    }, {
        preserveScroll: true,
        onFinish: () => inscriptionLoading.value = false,
    });
};

const desinscrire = () => {
    inscriptionLoading.value = true;
    router.delete(route('commune.evenements.desinscrire', [props.ville.code_insee, props.evenement.slug]), {
        preserveScroll: true,
        onFinish: () => inscriptionLoading.value = false,
    });
};
</script>

<template>
    <CommuneLayout :ville="ville" :titre="evenement.titre + ' - ' + ville.nom">
        <component :is="'script'" type="application/ld+json" v-text="eventJsonLd" />
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
                <Link :href="route('commune.index', ville.code_insee)" class="hover:text-blue-600">{{ ville.nom }}</Link>
                <span>/</span>
                <Link :href="route('commune.evenements', ville.code_insee)" class="hover:text-blue-600">Evenements</Link>
                <span>/</span>
                <span class="text-slate-900 dark:text-white truncate">{{ evenement.titre }}</span>
            </nav>

            <!-- Image -->
            <div v-if="evenement.image_url" class="rounded-2xl overflow-hidden mb-6">
                <img :src="evenement.image_url" :alt="evenement.titre" class="w-full h-64 sm:h-80 object-cover" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Contenu principal -->
                <div class="lg:col-span-2">
                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">
                            {{ evenement.categorie_label }}
                        </span>
                        <span v-if="evenement.annule" class="text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 px-2 py-0.5 rounded-full">
                            Annule
                        </span>
                        <span v-if="evenement.est_passe" class="text-xs bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-full">
                            Termine
                        </span>
                    </div>

                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ evenement.titre }}</h1>

                    <div v-if="evenement.description" class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300" v-html="evenement.description" />
                </div>

                <!-- Sidebar infos -->
                <div class="space-y-4">
                    <!-- Date & heure -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-3">Date et heure</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400">📅</span>
                                <span class="text-slate-700 dark:text-slate-300">{{ evenement.date_debut }}</span>
                            </div>
                            <div v-if="evenement.date_fin" class="flex items-center gap-2">
                                <span class="text-slate-400">🏁</span>
                                <span class="text-slate-700 dark:text-slate-300">Fin : {{ evenement.date_fin }}</span>
                            </div>
                            <div v-if="evenement.journee_entiere" class="text-slate-500">Journee entiere</div>
                        </div>
                    </div>

                    <!-- Lieu -->
                    <div v-if="evenement.lieu_nom || evenement.lieu_adresse" class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-3">Lieu</h3>
                        <div class="text-sm text-slate-700 dark:text-slate-300">
                            <div v-if="evenement.lieu_nom" class="font-medium">{{ evenement.lieu_nom }}</div>
                            <div v-if="evenement.lieu_adresse" class="text-slate-500 mt-1">{{ evenement.lieu_adresse }}</div>
                        </div>
                    </div>

                    <!-- Inscription -->
                    <div v-if="evenement.inscription_requise" class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-3">Inscription</h3>

                        <div v-if="evenement.places_max" class="mb-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-500">Places</span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ evenement.inscrits_count }} / {{ evenement.places_max }}</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="evenement.est_complet ? 'bg-red-500' : 'bg-blue-500'"
                                    :style="{ width: Math.min(100, (evenement.inscrits_count / evenement.places_max) * 100) + '%' }"
                                />
                            </div>
                        </div>

                        <div v-if="est_inscrit" class="space-y-2">
                            <div class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-lg p-3 text-sm font-medium text-center">
                                Vous etes inscrit ({{ inscription?.nb_personnes }} pers.)
                            </div>
                            <button
                                @click="desinscrire"
                                :disabled="inscriptionLoading"
                                class="w-full py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            >
                                Annuler mon inscription
                            </button>
                        </div>

                        <div v-else-if="evenement.inscription_ouverte" class="space-y-3">
                            <div>
                                <label class="block text-sm text-slate-600 dark:text-slate-400 mb-1">Nombre de personnes</label>
                                <select v-model="nbPersonnes" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <button
                                @click="inscrire"
                                :disabled="inscriptionLoading"
                                class="w-full bg-blue-600 text-white py-3 rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                            >
                                {{ inscriptionLoading ? 'Inscription...' : 'S\'inscrire' }}
                            </button>
                        </div>

                        <div v-else class="text-center text-sm text-slate-500 py-2">
                            {{ evenement.est_complet ? 'Complet' : 'Inscriptions fermees' }}
                        </div>

                        <p v-if="evenement.inscription_infos" class="text-xs text-slate-500 mt-3">{{ evenement.inscription_infos }}</p>
                    </div>
                </div>
            </div>
        </div>
    </CommuneLayout>
</template>
