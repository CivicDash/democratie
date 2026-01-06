<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    villes: Object,
    filters: Object,
    departements: Array,
    regions: Array,
    stats: Object,
    breadcrumbs: Array,
});

const search = ref(props.filters.q || '');
const departement = ref(props.filters.departement || '');
const region = ref(props.filters.region || '');
const minPop = ref(props.filters.min_pop || '');

const doSearch = debounce(() => {
    router.get(route('villes.index'), {
        q: search.value || undefined,
        departement: departement.value || undefined,
        region: region.value || undefined,
        min_pop: minPop.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch([search, departement, region, minPop], doSearch);

const formatPopulation = (pop) => {
    if (!pop) return 'N/A';
    if (pop >= 1000000) return (pop / 1000000).toFixed(1) + 'M';
    if (pop >= 1000) return (pop / 1000).toFixed(0) + 'k';
    return pop.toString();
};
</script>

<template>
    <Head title="Villes de France" />

    <AuthenticatedLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <!-- Hero -->
            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white py-12 px-6">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl font-bold flex items-center gap-4 mb-4">
                        <span class="text-4xl">🏘️</span>
                        Villes de France
                    </h1>
                    <p class="text-blue-100 text-lg max-w-2xl">
                        Explorez les {{ stats.total_villes?.toLocaleString('fr-FR') }} communes françaises,
                        leurs élus, budgets et statistiques.
                    </p>

                    <!-- Stats rapides -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.total_villes?.toLocaleString('fr-FR') }}</div>
                            <div class="text-sm text-blue-200">Communes</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ (stats.total_population / 1000000).toFixed(1) }}M</div>
                            <div class="text-sm text-blue-200">Habitants</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.nb_prefectures }}</div>
                            <div class="text-sm text-blue-200">Préfectures</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.nb_grandes_villes }}</div>
                            <div class="text-sm text-blue-200">+50k habitants</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 border border-slate-200 dark:border-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Recherche -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">
                                🔍 Rechercher une ville
                            </label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Nom ou code postal..."
                                class="w-full px-4 py-3 rounded-xl border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <!-- Région -->
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">
                                🗺️ Région
                            </label>
                            <select
                                v-model="region"
                                class="w-full px-4 py-3 rounded-xl border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option value="">Toutes les régions</option>
                                <option v-for="r in regions" :key="r.code" :value="r.code">
                                    {{ r.nom }}
                                </option>
                            </select>
                        </div>

                        <!-- Département -->
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">
                                📍 Département
                            </label>
                            <select
                                v-model="departement"
                                class="w-full px-4 py-3 rounded-xl border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option value="">Tous les départements</option>
                                <option v-for="d in departements" :key="d.code" :value="d.code">
                                    {{ d.code }} - {{ d.nom }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Ligne 2 : Population + Reset -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                        <!-- Population min -->
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">
                                👥 Population min.
                            </label>
                            <select
                                v-model="minPop"
                                class="w-full px-4 py-3 rounded-xl border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option value="">Toutes</option>
                                <option value="1000">+1 000 hab.</option>
                                <option value="5000">+5 000 hab.</option>
                                <option value="10000">+10 000 hab.</option>
                                <option value="50000">+50 000 hab.</option>
                                <option value="100000">+100 000 hab.</option>
                            </select>
                        </div>

                        <!-- Bouton reset -->
                        <div class="flex items-end">
                            <button
                                v-if="search || departement || region || minPop"
                                @click="search = ''; departement = ''; region = ''; minPop = '';"
                                class="px-4 py-3 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            >
                                ✕ Réinitialiser les filtres
                            </button>
                        </div>

                        <!-- Lien statistiques -->
                        <div class="md:col-span-2 flex items-end justify-end gap-2">
                            <Link 
                                :href="route('statistics.villes')"
                                class="px-4 py-3 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2"
                            >
                                <span>📊</span> Statistiques globales
                            </Link>
                            <Link 
                                :href="route('statistics.regions.index')"
                                class="px-4 py-3 text-sm font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors flex items-center gap-2"
                            >
                                <span>🗺️</span> Par région
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des villes -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="ville in villes.data"
                        :key="ville.id"
                        :href="ville.url"
                        class="group bg-white dark:bg-slate-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600"
                    >
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        {{ ville.nom }}
                                    </h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ ville.departement_nom }}
                                    </p>
                                </div>
                                <div v-if="ville.est_prefecture" class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-medium rounded-full">
                                    Préfecture
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400">Population</span>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ ville.population_formate }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400">Code postal</span>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ ville.code_postal || 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Maire -->
                            <div v-if="ville.maire" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex items-center gap-3">
                                <img
                                    v-if="ville.maire.photo_url"
                                    :src="ville.maire.photo_url"
                                    :alt="ville.maire.nom"
                                    class="w-10 h-10 rounded-full object-cover"
                                />
                                <div v-else class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                                    👤
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ ville.maire.nom }}
                                    </p>
                                    <p class="text-xs text-slate-500">Maire</p>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="villes.links && villes.links.length > 3" class="mt-8 flex justify-center gap-2">
                    <Link
                        v-for="link in villes.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="{
                            'bg-blue-600 text-white': link.active,
                            'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700': !link.active && link.url,
                            'text-slate-400 cursor-not-allowed': !link.url,
                        }"
                    />
                </div>

                <!-- Empty state -->
                <div v-if="villes.data.length === 0" class="text-center py-16">
                    <div class="text-6xl mb-4">🏘️</div>
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Aucune ville trouvée
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400">
                        Essayez de modifier vos critères de recherche.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
