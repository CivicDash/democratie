<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    personnes: Object,
    partis: Array,
    filters: Object,
});

const breadcrumbs = [
    { label: 'État', href: route('dashboard'), icon: '🇫🇷' },
    { label: 'Gouvernement', href: route('gouvernement.index'), icon: '🏛️' },
    { label: 'Ministres', current: true, icon: '👥' },
];

// Filtres
const search = ref(props.filters?.search || '');
const parti = ref(props.filters?.parti || '');
const actifsOnly = ref(props.filters?.actifs === 'true' || props.filters?.actifs === true);

const applyFilters = () => {
    router.get(route('gouvernement.personnes'), {
        search: search.value || undefined,
        parti: parti.value || undefined,
        actifs: actifsOnly.value ? 'true' : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

let debounceTimer;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 500);
});
watch([parti, actifsOnly], applyFilters);

// Vérifier si une personne a un poste actif
const hasActivePoste = (personne) => {
    return personne.postes?.some(p => p.actif);
};

// Obtenir le poste actuel
const getPosteActuel = (personne) => {
    const posteActif = personne.postes?.find(p => p.actif);
    return posteActif?.fonction || null;
};
</script>

<template>
    <Head title="Ministres - Personnes politiques" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-12">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid-ministres" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid-ministres)"/>
                </svg>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />

                <h1 class="text-3xl lg:text-4xl font-bold text-white mb-2">
                    👥 Personnes politiques
                </h1>
                <p class="text-purple-200 text-lg">
                    {{ personnes.total }} personnes ayant exercé des fonctions ministérielles
                </p>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mt-8 max-w-xl">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-white">{{ personnes.total }}</div>
                        <div class="text-purple-200 text-sm">Total</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-emerald-400">
                            {{ personnes.data.filter(p => hasActivePoste(p)).length }}+
                        </div>
                        <div class="text-purple-200 text-sm">En fonction</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-white">{{ partis.length }}</div>
                        <div class="text-purple-200 text-sm">Partis</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Filtres -->
                <Card class="mb-6">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-64">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher une personne..."
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <select
                            v-model="parti"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">Tous les partis</option>
                            <option v-for="p in partis" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <label class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer">
                            <input v-model="actifsOnly" type="checkbox" class="rounded text-emerald-600" />
                            <span class="text-gray-700 dark:text-gray-300">En fonction</span>
                        </label>
                    </div>
                </Card>

                <!-- Grille de personnes -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <Link
                        v-for="personne in personnes.data"
                        :key="personne.id"
                        :href="route('gouvernement.personne', personne.slug)"
                        class="block"
                    >
                        <Card class="hover:shadow-lg transition hover:-translate-y-1 h-full">
                            <div class="flex items-start gap-4">
                                <img 
                                    v-if="personne.photo_url || personne.photo"
                                    :src="personne.photo_url || personne.photo"
                                    :alt="personne.nom_complet"
                                    class="w-16 h-16 rounded-full object-cover border-2"
                                    :class="hasActivePoste(personne) ? 'border-emerald-500' : 'border-gray-200 dark:border-gray-700'"
                                />
                                <div v-else class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl border-2"
                                    :class="hasActivePoste(personne) ? 'border-emerald-500' : 'border-gray-200 dark:border-gray-700'"
                                >
                                    {{ personne.civilite === 'Mme' ? '👩' : '👨' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-gray-900 dark:text-gray-100 truncate">
                                            {{ personne.prenom }} {{ personne.nom }}
                                        </h3>
                                        <span 
                                            v-if="hasActivePoste(personne)"
                                            class="flex-shrink-0 w-2 h-2 bg-emerald-500 rounded-full"
                                            title="En fonction"
                                        ></span>
                                    </div>
                                    <p 
                                        v-if="getPosteActuel(personne)"
                                        class="text-sm text-blue-600 dark:text-blue-400 truncate"
                                    >
                                        {{ getPosteActuel(personne) }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span 
                                            v-if="personne.parti_politique"
                                            class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded truncate"
                                        >
                                            {{ personne.parti_politique }}
                                        </span>
                                        <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 text-xs rounded">
                                            {{ personne.postes_count }} poste{{ personne.postes_count > 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </Link>
                </div>

                <!-- Message si aucun résultat -->
                <Card v-if="personnes.data.length === 0" class="text-center py-12">
                    <div class="text-6xl mb-4">🔍</div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        Aucun résultat
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Essayez de modifier vos critères de recherche.
                    </p>
                </Card>

                <!-- Pagination -->
                <div v-if="personnes.links && personnes.links.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                    <Link
                        v-for="link in personnes.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-4 py-2 rounded-lg transition',
                            link.active 
                                ? 'bg-purple-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
