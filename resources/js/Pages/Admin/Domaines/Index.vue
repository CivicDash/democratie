<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    fonctions: Object,
    domaines: Array,
    stats: Object,
    filters: Object,
});

// Filtres locaux
const searchQuery = ref(props.filters?.search || '');
const selectedDomaine = ref(props.filters?.domaine_id || '');

// Sélection multiple
const selectedFonctions = ref([]);
const selectAll = ref(false);

// Formulaire d'assignation en masse
const massForm = useForm({
    fonctions: [],
    domaine_id: null,
});

// Watcher pour select all
watch(selectAll, (val) => {
    if (val) {
        selectedFonctions.value = props.fonctions.data.map(f => f.fonction);
    } else {
        selectedFonctions.value = [];
    }
});

// Filtrer
function applyFilters() {
    router.get(route('admin.domaines.index'), {
        search: searchQuery.value,
        domaine_id: selectedDomaine.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

// Assigner un domaine à une fonction
function assignerFonction(fonction, domaineId) {
    router.post(route('admin.domaines.assigner-fonction'), {
        fonction: fonction,
        domaine_id: domaineId,
    }, {
        preserveScroll: true,
    });
}

// Assigner en masse
function assignerMasse() {
    if (selectedFonctions.value.length === 0 || !massForm.domaine_id) {
        alert('Sélectionnez des fonctions et un domaine');
        return;
    }
    
    massForm.fonctions = selectedFonctions.value;
    massForm.post(route('admin.domaines.assigner-masse'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedFonctions.value = [];
            selectAll.value = false;
        },
    });
}

// Couleur du domaine
function getDomaineColor(domaine) {
    if (!domaine) return '#6b7280';
    return domaine.couleur || '#6b7280';
}

// Pourcentage de progression
const progressPct = computed(() => {
    if (!props.stats) return 0;
    return Math.round((props.stats.postes_categorises / props.stats.total_postes) * 100);
});
</script>

<template>
    <Head title="Catégorisation des Ministères" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                🏛️ Catégorisation des Fonctions Ministérielles
                            </h1>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">
                                Associez chaque fonction à un domaine ministériel pour permettre les analyses historiques
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <Link 
                                :href="route('admin.domaines.suggestions')"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg flex items-center gap-2"
                            >
                                🤖 Suggestions auto
                            </Link>
                            <Link 
                                :href="route('admin.domaines.gestion')"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2"
                            >
                                ⚙️ Gérer domaines
                            </Link>
                        </div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_postes }}</div>
                        <div class="text-sm text-gray-500">Postes totaux</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-green-600">{{ stats.postes_categorises }}</div>
                        <div class="text-sm text-gray-500">Catégorisés</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.postes_non_categorises }}</div>
                        <div class="text-sm text-gray-500">Non catégorisés</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.fonctions_uniques }}</div>
                        <div class="text-sm text-gray-500">Fonctions uniques</div>
                    </div>
                </div>
                
                <!-- Progress bar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progression</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ progressPct }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div 
                            class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-500"
                            :style="{ width: progressPct + '%' }"
                        ></div>
                    </div>
                </div>
                
                <!-- Filtres -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-64">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Rechercher une fonction
                            </label>
                            <input 
                                v-model="searchQuery"
                                type="text"
                                placeholder="Ex: Justice, Économie..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div class="w-64">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Filtrer par domaine
                            </label>
                            <select 
                                v-model="selectedDomaine"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">Tous les domaines</option>
                                <option value="null">❌ Non catégorisés</option>
                                <option v-for="d in domaines" :key="d.id" :value="d.id">
                                    {{ d.nom }}
                                </option>
                            </select>
                        </div>
                        <button 
                            @click="applyFilters"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
                        >
                            🔍 Filtrer
                        </button>
                    </div>
                </div>
                
                <!-- Action en masse -->
                <div v-if="selectedFonctions.length > 0" class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-4">
                        <span class="text-blue-800 dark:text-blue-200 font-medium">
                            {{ selectedFonctions.length }} fonction(s) sélectionnée(s)
                        </span>
                        <select 
                            v-model="massForm.domaine_id"
                            class="rounded-lg border-blue-300 dark:border-blue-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option :value="null">Choisir un domaine...</option>
                            <option v-for="d in domaines" :key="d.id" :value="d.id">
                                {{ d.nom }}
                            </option>
                        </select>
                        <button 
                            @click="assignerMasse"
                            :disabled="massForm.processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50"
                        >
                            ✅ Appliquer
                        </button>
                        <button 
                            @click="selectedFonctions = []; selectAll = false"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
                
                <!-- Liste des fonctions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input 
                                        type="checkbox" 
                                        v-model="selectAll"
                                        class="rounded border-gray-300 dark:border-gray-600"
                                    />
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Fonction
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Nb postes
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Domaine actuel
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Assigner à
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr 
                                v-for="item in fonctions.data" 
                                :key="item.fonction"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-4 py-3">
                                    <input 
                                        type="checkbox" 
                                        :value="item.fonction"
                                        v-model="selectedFonctions"
                                        class="rounded border-gray-300 dark:border-gray-600"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900 dark:text-white font-medium">
                                        {{ item.fonction }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                        {{ item.count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span 
                                        v-if="item.domaine"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white"
                                        :style="{ backgroundColor: getDomaineColor(item.domaine) }"
                                    >
                                        {{ item.domaine.nom }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">
                                        Non catégorisé
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <select 
                                        class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        :value="item.domaine_ministeriel_id"
                                        @change="assignerFonction(item.fonction, $event.target.value)"
                                    >
                                        <option :value="null">-- Choisir --</option>
                                        <option v-for="d in domaines" :key="d.id" :value="d.id">
                                            {{ d.nom }}
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Page {{ fonctions.current_page }} sur {{ fonctions.last_page }} 
                                ({{ fonctions.total }} fonctions)
                            </div>
                            <div class="flex gap-2">
                                <Link 
                                    v-if="fonctions.prev_page_url"
                                    :href="fonctions.prev_page_url"
                                    class="px-3 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    ← Précédent
                                </Link>
                                <Link 
                                    v-if="fonctions.next_page_url"
                                    :href="fonctions.next_page_url"
                                    class="px-3 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    Suivant →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
