<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import NuanceBadge from '@/Components/Municipales/NuanceBadge.vue';

const props = defineProps({
    maires: Object,
    totalMaires: Number,
    filters: Object,
    departements: Array,
    nuances: Array,
});

const search = ref(props.filters.query || '');
const selectedDepartement = ref(props.filters.departement || '');
const selectedNuance = ref(props.filters.nuance || '');

const applyFilters = () => {
    router.get(route('elus.maires.index'), {
        q: search.value || undefined,
        departement: selectedDepartement.value || undefined,
        nuance: selectedNuance.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    selectedDepartement.value = '';
    selectedNuance.value = '';
    router.get(route('elus.maires.index'));
};

const formatPopulation = (pop) => {
    if (!pop) return '—';
    return pop >= 1000 ? (pop / 1000).toFixed(pop >= 10000 ? 0 : 1) + 'k' : pop.toString();
};

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Maires de France', current: true, icon: '🏛️' },
];
</script>

<template>
    <Head title="Maires de France" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden bg-gradient-to-br from-amber-900 via-amber-800 to-orange-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>

            <div class="relative w-full px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight">
                            Maires de France
                        </h1>
                        <p class="text-amber-200 text-lg">
                            Les premiers magistrats des communes francaises
                        </p>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">{{ totalMaires?.toLocaleString('fr-FR') }}</div>
                            <div class="text-amber-300 text-sm">maires en exercice</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filtres -->
            <Card class="mb-6">
                <div class="p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher un maire ou une commune..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-amber-500 focus:border-amber-500"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div class="sm:w-48">
                            <select
                                v-model="selectedDepartement"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-amber-500 focus:border-amber-500"
                                @change="applyFilters"
                            >
                                <option value="">Tous les departements</option>
                                <option v-for="dep in departements" :key="dep.code" :value="dep.code">
                                    {{ dep.code }} - {{ dep.nom }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select
                                v-model="selectedNuance"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-amber-500 focus:border-amber-500"
                                @change="applyFilters"
                            >
                                <option value="">Toutes nuances</option>
                                <option v-for="n in nuances" :key="n.code" :value="n.code">
                                    {{ n.libelle }} ({{ n.total }})
                                </option>
                            </select>
                        </div>
                        <button
                            @click="applyFilters"
                            class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition font-medium"
                        >
                            Rechercher
                        </button>
                        <button
                            v-if="filters.query || filters.departement || filters.nuance"
                            @click="resetFilters"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition"
                        >
                            Reinitialiser
                        </button>
                    </div>
                </div>
            </Card>

            <!-- Resultats -->
            <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                {{ maires.total?.toLocaleString('fr-FR') }} resultat(s)
            </div>

            <!-- Grille de cartes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <Link
                    v-for="maire in maires.data"
                    :key="maire.id"
                    :href="maire.url"
                    class="group"
                >
                    <Card class="h-full transition-all duration-200 group-hover:shadow-lg group-hover:ring-2 group-hover:ring-amber-500/30">
                        <div class="p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                    <img
                                        v-if="maire.photo"
                                        :src="maire.photo"
                                        :alt="maire.nom_complet"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xl">
                                        {{ maire.civilite === 'Mme' ? '👩' : '👨' }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">
                                        {{ maire.nom_complet }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ maire.commune }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
                                        {{ maire.departement }} · {{ formatPopulation(maire.population) }} hab.
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <NuanceBadge v-if="maire.nuance" :nuance="maire.nuance.code" size="sm" />
                                <span v-else class="text-xs text-gray-400">—</span>
                                <span v-if="maire.reelu" class="text-xs text-green-600 dark:text-green-400 font-medium">
                                    Reelu(e)
                                </span>
                                <span v-else-if="maire.reelu === false" class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                    Nouveau
                                </span>
                            </div>
                        </div>
                    </Card>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="maires.links && maires.links.length > 3" class="mt-8 flex justify-center">
                <nav class="flex items-center gap-1">
                    <template v-for="(link, i) in maires.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-2 rounded-lg text-sm transition"
                            :class="link.active
                                ? 'bg-amber-600 text-white font-semibold'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2 text-sm text-gray-400 dark:text-gray-600"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>

            <!-- Etat vide -->
            <div v-if="maires.data?.length === 0" class="text-center py-16">
                <div class="text-6xl mb-4">🏛️</div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucun maire trouve</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Essayez de modifier vos criteres de recherche.</p>
                <button @click="resetFilters" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                    Voir tous les maires
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
