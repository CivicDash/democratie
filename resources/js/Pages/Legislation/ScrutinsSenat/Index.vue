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

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard') },
    { label: 'Sénat', href: route('representants.senateurs.index') },
    { label: 'Scrutins', current: true },
];
</script>

<template>
    <Head title="Scrutins du Sénat" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-rose-50 via-white to-slate-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <!-- Header -->
            <div class="bg-gradient-to-r from-rose-600 to-rose-700 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" class="mb-4 text-rose-100" />
                    
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <span class="text-3xl">🗳️</span>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold">Scrutins du Sénat</h1>
                            <p class="text-rose-100 mt-1">Votes publics au Sénat de la République</p>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold">{{ stats.total.toLocaleString() }}</div>
                            <div class="text-sm text-rose-200">Scrutins</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-green-300">{{ stats.adoptes.toLocaleString() }}</div>
                            <div class="text-sm text-rose-200">Adoptés</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-red-300">{{ stats.rejetes.toLocaleString() }}</div>
                            <div class="text-sm text-rose-200">Rejetés</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Recherche -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Rechercher
                            </label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Titre, objet..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-rose-500 focus:border-rose-500"
                            />
                        </div>
                        
                        <!-- Résultat -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Résultat
                            </label>
                            <select
                                v-model="resultat"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-rose-500 focus:border-rose-500"
                            >
                                <option value="">Tous</option>
                                <option value="Adopté">Adoptés</option>
                                <option value="Rejeté">Rejetés</option>
                            </select>
                        </div>
                        
                        <!-- Session -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Session
                            </label>
                            <select
                                v-model="session"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-rose-500 focus:border-rose-500"
                            >
                                <option value="">Toutes</option>
                                <option v-for="s in sessions" :key="s" :value="s">
                                    {{ s }}-{{ s + 1 }}
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <button
                            @click="resetFilters"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            Réinitialiser les filtres
                        </button>
                    </div>
                </div>
            </div>

            <!-- Liste des scrutins -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
                <div class="space-y-4">
                    <div
                        v-for="scrutin in scrutins.data"
                        :key="scrutin.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-100 dark:border-gray-700"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-medium"
                                        :class="scrutin.resultat === 'Adopté' 
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                                            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                    >
                                        {{ scrutin.resultat }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        Scrutin n°{{ scrutin.numero }}
                                    </span>
                                    <span class="text-sm text-gray-400 dark:text-gray-500">
                                        {{ formatDate(scrutin.date_scrutin) }}
                                    </span>
                                </div>
                                
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    {{ scrutin.titre || scrutin.objet || 'Sans titre' }}
                                </h3>
                                
                                <p v-if="scrutin.objet && scrutin.titre" class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">
                                    {{ scrutin.objet }}
                                </p>
                                
                                <!-- Votes -->
                                <div class="flex items-center gap-4 mt-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ scrutin.pour }} pour
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ scrutin.contre }} contre
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ scrutin.suffrages_exprimes - scrutin.pour - scrutin.contre }} abstentions
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <Link
                                :href="route('legislation.scrutins-senat.show', scrutin.id)"
                                class="flex-shrink-0 px-4 py-2 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 rounded-lg hover:bg-rose-200 dark:hover:bg-rose-900/50 transition-colors text-sm font-medium"
                            >
                                Voir détail →
                            </Link>
                        </div>
                        
                        <!-- Barre de votes -->
                        <div class="mt-4 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex">
                            <div
                                class="h-full bg-green-500"
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
                                class="px-3 py-2 rounded-lg text-sm transition-colors"
                                :class="link.active 
                                    ? 'bg-rose-600 text-white' 
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-2 text-sm text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>
                
                <!-- Empty state -->
                <div v-if="!scrutins.data?.length" class="text-center py-12">
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
