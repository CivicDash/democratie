<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    scrutins: Object,
    stats: Object,
    sessions: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const resultat = ref(props.filters?.resultat || '');
const session = ref(props.filters?.session || '');

let debounceTimer = null;
const applyFilters = () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('legislation.scrutins-senat.index'), {
            search: search.value || undefined,
            resultat: resultat.value || undefined,
            session: session.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch([search, resultat, session], applyFilters);

const resetFilters = () => {
    search.value = '';
    resultat.value = '';
    session.value = '';
    router.get(route('legislation.scrutins-senat.index'));
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const hasActiveFilters = computed(() => search.value || resultat.value || session.value);

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénat', href: route('representants.senateurs.index'), icon: '🏰' },
    { label: 'Scrutins', current: true, icon: '🗳️' },
];
</script>

<template>
    <Head title="Scrutins du Sénat" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            
            <!-- Hero Section Full Width -->
            <section class="relative overflow-hidden bg-gradient-to-br from-rose-900 via-rose-800 to-pink-900">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>
                
                <div class="relative w-full px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                    <!-- Breadcrumb -->
                    <div class="max-w-full mx-auto">
                        <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                    </div>
                    
                    <div class="max-w-full mx-auto flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Titre -->
                        <div>
                            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                                <span class="text-4xl">🗳️</span>
                                Scrutins du Sénat
                            </h1>
                            <p class="text-rose-200 text-lg">
                                Votes publics au Sénat de la République
                            </p>
                        </div>
                        
                        <!-- Stats rapides -->
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-white">{{ stats?.total?.toLocaleString() || 0 }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Scrutins</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-emerald-400">{{ stats?.adoptes?.toLocaleString() || 0 }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Adoptés</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-red-400">{{ stats?.rejetes?.toLocaleString() || 0 }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Rejetés</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lien vers scrutins AN -->
                    <div class="max-w-full mx-auto mt-6">
                        <Link 
                            :href="route('legislation.scrutins.index')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition border border-white/20 text-sm"
                        >
                            <span>🏛️</span>
                            Voir les scrutins de l'Assemblée Nationale →
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Contenu principal - Full width -->
            <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Filtres -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Recherche -->
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher un scrutin (titre, objet)..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-rose-500 focus:border-rose-500"
                            />
                        </div>
                        
                        <!-- Résultat -->
                        <select
                            v-model="resultat"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-rose-500 focus:border-rose-500"
                        >
                            <option value="">Tous les résultats</option>
                            <option value="Adopté">Adoptés</option>
                            <option value="Rejeté">Rejetés</option>
                        </select>
                        
                        <!-- Session -->
                        <select
                            v-model="session"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-rose-500 focus:border-rose-500"
                        >
                            <option value="">Toutes les sessions</option>
                            <option v-for="s in sessions" :key="s" :value="s">
                                Session {{ s }}-{{ s + 1 }}
                            </option>
                        </select>
                        
                        <!-- Reset -->
                        <button
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition"
                        >
                            Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Liste des scrutins -->
                <div class="space-y-4">
                    <div
                        v-for="scrutin in scrutins.data"
                        :key="scrutin.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-start justify-between gap-6">
                            <div class="flex-1 min-w-0">
                                <!-- Badges -->
                                <div class="flex flex-wrap items-center gap-3 mb-3">
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-medium"
                                        :class="scrutin.resultat === 'Adopté' 
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' 
                                            : scrutin.resultat === 'Rejeté'
                                            ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                                    >
                                        {{ scrutin.resultat === 'Adopté' ? '✅' : scrutin.resultat === 'Rejeté' ? '❌' : '❓' }}
                                        {{ scrutin.resultat || 'Non déterminé' }}
                                    </span>
                                    <span class="px-3 py-1 bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 rounded-full text-sm font-medium">
                                        N° {{ scrutin.numero }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(scrutin.date_scrutin) }}
                                    </span>
                                    <span v-if="scrutin.type_scrutin" class="text-sm text-gray-400 dark:text-gray-500 capitalize">
                                        {{ scrutin.type_scrutin }}
                                    </span>
                                </div>
                                
                                <!-- Titre -->
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    {{ scrutin.intitule || scrutin.intitule_complet || 'Sans titre' }}
                                </h3>
                                
                                <!-- Description -->
                                <p v-if="scrutin.intitule_complet && scrutin.intitule" class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">
                                    {{ scrutin.intitule_complet }}
                                </p>
                            </div>

                            <!-- Résultats de vote -->
                            <div class="flex-shrink-0 flex items-center gap-4">
                                <div v-if="scrutin.pour" class="text-center">
                                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ scrutin.pour }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Pour</div>
                                </div>
                                <div v-if="scrutin.contre" class="text-center">
                                    <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ scrutin.contre }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Contre</div>
                                </div>
                                <div v-if="scrutin.suffrages_exprimes" class="text-center">
                                    <div class="text-xl font-bold text-gray-600 dark:text-gray-400">{{ scrutin.suffrages_exprimes }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Suffrages</div>
                                </div>
                                
                                <Link
                                    :href="route('legislation.scrutins-senat.show', scrutin.id)"
                                    class="px-4 py-2 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 rounded-lg hover:bg-rose-200 dark:hover:bg-rose-900/50 transition-colors text-sm font-medium"
                                >
                                    Détail →
                                </Link>
                            </div>
                        </div>
                        
                        <!-- Barre de votes -->
                        <div v-if="scrutin.pour && scrutin.contre && scrutin.suffrages_exprimes" class="mt-4 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex">
                            <div
                                class="h-full bg-emerald-500"
                                :style="{ width: `${(scrutin.pour / scrutin.suffrages_exprimes) * 100}%` }"
                            ></div>
                            <div
                                class="h-full bg-red-500"
                                :style="{ width: `${(scrutin.contre / scrutin.suffrages_exprimes) * 100}%` }"
                            ></div>
                            <div
                                class="h-full bg-gray-400"
                                :style="{ width: `${((scrutin.suffrages_exprimes - scrutin.pour - scrutin.contre) / scrutin.suffrages_exprimes) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="scrutins.links?.length > 3" class="mt-8 flex justify-center">
                    <nav class="flex items-center gap-1">
                        <template v-for="link in scrutins.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-4 py-2 rounded-lg text-sm transition-colors"
                                :class="link.active 
                                    ? 'bg-rose-600 text-white' 
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-4 py-2 text-sm text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>
                
                <!-- Empty state -->
                <div v-if="!scrutins.data?.length" class="text-center py-16">
                    <div class="text-6xl mb-4">🗳️</div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        Aucun scrutin trouvé
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Essayez de modifier vos filtres de recherche.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
