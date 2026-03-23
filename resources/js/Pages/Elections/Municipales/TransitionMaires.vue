<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransitionMaireCard from '@/Components/Municipales/TransitionMaireCard.vue';

const props = defineProps({
    stats: Object,
    grandes_villes: Array,
});

const sortBy = ref('population');
const filterNuance = ref(null);

const renouvellement = props.stats?.renouvellement;
const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';

const hasData = computed(() => (props.grandes_villes?.length ?? 0) > 0);

const nuances = computed(() => {
    const set = new Set();
    props.grandes_villes?.forEach(m => { if (m.nuance) set.add(m.nuance); });
    return [...set].sort();
});

const sortedVilles = computed(() => {
    let list = [...(props.grandes_villes || [])];

    if (filterNuance.value) {
        list = list.filter(m => m.nuance === filterNuance.value);
    }

    if (sortBy.value === 'population') {
        list.sort((a, b) => (b.population || 0) - (a.population || 0));
    } else if (sortBy.value === 'changement') {
        list.sort((a, b) => (a.reelu === b.reelu ? 0 : a.reelu ? 1 : -1));
    }

    return list;
});
</script>

<template>
    <Head title="Transition des maires — Municipales 2026" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-indigo-900 via-purple-900 to-fuchsia-900 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid-trans" width="8" height="8" patternUnits="userSpaceOnUse">
                            <path d="M 8 0 L 0 0 0 8" fill="none" stroke="currentColor" stroke-width="0.3"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid-trans)" />
                </svg>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
                <nav class="mb-6 text-sm text-purple-300 flex items-center gap-2">
                    <Link :href="route('elections.municipales.resultats.index')" class="hover:text-white transition-colors">Résultats</Link>
                    <span>/</span>
                    <span class="text-white font-medium">Transition maires</span>
                </nav>

                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        Transition des <span class="text-fuchsia-300">maires</span>
                    </h1>
                    <p class="text-lg text-purple-200 max-w-2xl mx-auto mb-10">
                        Mandature 2020-2026 vers 2026-2032 : qui succède à qui dans les grandes villes ?
                    </p>

                    <!-- Compteurs renouvellement -->
                    <div v-if="renouvellement" class="grid grid-cols-2 md:grid-cols-3 gap-4 max-w-2xl mx-auto">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-5 border border-white/20">
                            <div class="text-3xl font-bold text-emerald-300">{{ formatNumber(renouvellement.sortants_reelus) }}</div>
                            <div class="text-sm text-emerald-200">Maires réélus</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-5 border border-white/20">
                            <div class="text-3xl font-bold text-fuchsia-300">{{ formatNumber(renouvellement.nouveaux) }}</div>
                            <div class="text-sm text-fuchsia-200">Nouveaux maires</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-5 border border-white/20 col-span-2 md:col-span-1">
                            <div class="text-3xl font-bold text-white">{{ renouvellement.taux_reelection?.toFixed(1) }}%</div>
                            <div class="text-sm text-purple-200">Taux de réélection</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- État vide -->
            <div v-if="!hasData" class="text-center py-16">
                <div class="text-6xl mb-4">🏛️</div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Transition en cours de calcul</h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    La transition des maires n'a pas encore été calculée. Les résultats des élections doivent d'abord être entièrement importés et traités.
                </p>
                <Link :href="route('elections.municipales.resultats.index')" class="inline-block mt-6 px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition">
                    Retour aux résultats
                </Link>
            </div>

            <!-- Filtres + Grille -->
            <template v-else>
                <div class="flex flex-wrap gap-4 mb-6">
                    <select v-model="sortBy" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm">
                        <option value="population">Tri par population</option>
                        <option value="changement">Changements d'abord</option>
                    </select>
                    <select v-model="filterNuance" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm">
                        <option :value="null">Toutes les nuances</option>
                        <option v-for="n in nuances" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="maire in sortedVilles" :key="maire.code_commune">
                        <TransitionMaireCard
                            :ancien="maire.predecesseur"
                            :nouveau="{
                                nom_complet: maire.nom_complet,
                                nuance_politique: maire.nuance,
                                photo: maire.photo,
                                reelu: maire.reelu,
                                score: maire.score,
                                mandature: '2026-2032',
                            }"
                            :commune-nom="`${maire.commune} (${formatNumber(maire.population)} hab.)`"
                        />
                    </div>
                </div>

                <div v-if="sortedVilles.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    Aucun résultat avec ces filtres.
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
