<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    par_departement: Object,
    top_communes: Array,
    stats: Object,
});

// Département sélectionné
const selectedDept = ref(null);
const deptData = ref(null);
const loadingDept = ref(false);

// Liste des départements français
const departements = {
    '01': 'Ain', '02': 'Aisne', '03': 'Allier', '04': 'Alpes-de-Haute-Provence', '05': 'Hautes-Alpes',
    '06': 'Alpes-Maritimes', '07': 'Ardèche', '08': 'Ardennes', '09': 'Ariège', '10': 'Aube',
    '11': 'Aude', '12': 'Aveyron', '13': 'Bouches-du-Rhône', '14': 'Calvados', '15': 'Cantal',
    '16': 'Charente', '17': 'Charente-Maritime', '18': 'Cher', '19': 'Corrèze', '21': 'Côte-d\'Or',
    '22': 'Côtes-d\'Armor', '23': 'Creuse', '24': 'Dordogne', '25': 'Doubs', '26': 'Drôme',
    '27': 'Eure', '28': 'Eure-et-Loir', '29': 'Finistère', '2A': 'Corse-du-Sud', '2B': 'Haute-Corse',
    '30': 'Gard', '31': 'Haute-Garonne', '32': 'Gers', '33': 'Gironde', '34': 'Hérault',
    '35': 'Ille-et-Vilaine', '36': 'Indre', '37': 'Indre-et-Loire', '38': 'Isère', '39': 'Jura',
    '40': 'Landes', '41': 'Loir-et-Cher', '42': 'Loire', '43': 'Haute-Loire', '44': 'Loire-Atlantique',
    '45': 'Loiret', '46': 'Lot', '47': 'Lot-et-Garonne', '48': 'Lozère', '49': 'Maine-et-Loire',
    '50': 'Manche', '51': 'Marne', '52': 'Haute-Marne', '53': 'Mayenne', '54': 'Meurthe-et-Moselle',
    '55': 'Meuse', '56': 'Morbihan', '57': 'Moselle', '58': 'Nièvre', '59': 'Nord',
    '60': 'Oise', '61': 'Orne', '62': 'Pas-de-Calais', '63': 'Puy-de-Dôme', '64': 'Pyrénées-Atlantiques',
    '65': 'Hautes-Pyrénées', '66': 'Pyrénées-Orientales', '67': 'Bas-Rhin', '68': 'Haut-Rhin', '69': 'Rhône',
    '70': 'Haute-Saône', '71': 'Saône-et-Loire', '72': 'Sarthe', '73': 'Savoie', '74': 'Haute-Savoie',
    '75': 'Paris', '76': 'Seine-Maritime', '77': 'Seine-et-Marne', '78': 'Yvelines', '79': 'Deux-Sèvres',
    '80': 'Somme', '81': 'Tarn', '82': 'Tarn-et-Garonne', '83': 'Var', '84': 'Vaucluse',
    '85': 'Vendée', '86': 'Vienne', '87': 'Haute-Vienne', '88': 'Vosges', '89': 'Yonne',
    '90': 'Territoire de Belfort', '91': 'Essonne', '92': 'Hauts-de-Seine', '93': 'Seine-Saint-Denis',
    '94': 'Val-de-Marne', '95': 'Val-d\'Oise', '971': 'Guadeloupe', '972': 'Martinique',
    '973': 'Guyane', '974': 'La Réunion', '976': 'Mayotte'
};

// Couleur selon le nombre de listes
const getColor = (code) => {
    const data = props.par_departement[code];
    if (!data) return '#E5E7EB'; // Gris clair si pas de données
    
    const nb = data.nb_listes;
    if (nb >= 10) return '#7C3AED'; // Violet foncé
    if (nb >= 5) return '#A78BFA';  // Violet moyen
    if (nb >= 2) return '#C4B5FD';  // Violet clair
    return '#DDD6FE';               // Violet très clair
};

// Charger les données d'un département
const loadDepartement = async (code) => {
    selectedDept.value = code;
    loadingDept.value = true;
    deptData.value = null;

    try {
        const response = await fetch(route('elections.municipales.api.departement', code));
        deptData.value = await response.json();
    } catch (error) {
        console.error('Erreur chargement département:', error);
    } finally {
        loadingDept.value = false;
    }
};

// Départements triés par nombre de listes
const departementsTries = computed(() => {
    return Object.entries(departements)
        .map(([code, nom]) => ({
            code,
            nom,
            data: props.par_departement[code] || { nb_listes: 0, nb_communes: 0 },
        }))
        .filter(d => d.data.nb_listes > 0)
        .sort((a, b) => b.data.nb_listes - a.data.nb_listes);
});
</script>

<template>
    <Head title="Carte des candidatures - Élections Municipales 2026" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-indigo-900 via-purple-800 to-fuchsia-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Link
                    :href="route('elections.municipales.index')"
                    class="text-indigo-200 hover:text-white text-sm mb-4 inline-block"
                >
                    ← Retour aux municipales
                </Link>
                
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-4 flex items-center gap-3">
                    🗺️ Carte des candidatures
                </h1>
                <p class="text-xl text-indigo-200">
                    Visualisez les listes inscrites dans chaque département
                </p>

                <!-- Stats globales -->
                <div class="grid grid-cols-3 gap-4 mt-8 max-w-xl">
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-white">{{ stats.total_listes }}</div>
                        <div class="text-sm text-indigo-200">Listes</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-white">{{ stats.total_communes }}</div>
                        <div class="text-sm text-indigo-200">Communes</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-white">{{ stats.total_departements }}</div>
                        <div class="text-sm text-indigo-200">Départements</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Liste des départements -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 sticky top-24">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            📍 Départements avec listes
                        </h2>

                        <div v-if="departementsTries.length === 0" class="text-center py-8 text-gray-500">
                            Aucune liste validée pour le moment
                        </div>

                        <div v-else class="space-y-2 max-h-[60vh] overflow-y-auto">
                            <button
                                v-for="dept in departementsTries"
                                :key="dept.code"
                                @click="loadDepartement(dept.code)"
                                :class="[
                                    'w-full text-left px-4 py-3 rounded-xl transition flex items-center justify-between',
                                    selectedDept === dept.code
                                        ? 'bg-indigo-100 dark:bg-indigo-900/50 ring-2 ring-indigo-500'
                                        : 'bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-900'
                                ]"
                            >
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ dept.code }} - {{ dept.nom }}
                                    </span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ dept.data.nb_communes }} commune(s)
                                    </p>
                                </div>
                                <Badge class="bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">
                                    {{ dept.data.nb_listes }}
                                </Badge>
                            </button>
                        </div>

                        <!-- Légende -->
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Légende</h4>
                            <div class="flex items-center gap-2 text-sm">
                                <div class="w-4 h-4 rounded" style="background: #DDD6FE"></div>
                                <span class="text-gray-600 dark:text-gray-400">1 liste</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <div class="w-4 h-4 rounded" style="background: #C4B5FD"></div>
                                <span class="text-gray-600 dark:text-gray-400">2-4 listes</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <div class="w-4 h-4 rounded" style="background: #A78BFA"></div>
                                <span class="text-gray-600 dark:text-gray-400">5-9 listes</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <div class="w-4 h-4 rounded" style="background: #7C3AED"></div>
                                <span class="text-gray-600 dark:text-gray-400">10+ listes</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Détails du département -->
                <div class="lg:col-span-2">
                    <div v-if="!selectedDept" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                        <span class="text-6xl">🗺️</span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-4 mb-2">
                            Sélectionnez un département
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Cliquez sur un département dans la liste pour voir les listes inscrites
                        </p>
                    </div>

                    <div v-else-if="loadingDept" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                        <div class="animate-spin w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="text-gray-600 dark:text-gray-400 mt-4">Chargement...</p>
                    </div>

                    <div v-else-if="deptData" class="space-y-6">
                        <!-- Header département -->
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 rounded-2xl p-6 text-white">
                            <h2 class="text-2xl font-bold">
                                {{ selectedDept }} - {{ departements[selectedDept] }}
                            </h2>
                            <p class="text-indigo-100 mt-1">
                                {{ deptData.nb_listes }} liste(s) dans {{ deptData.nb_communes }} commune(s)
                            </p>
                        </div>

                        <!-- Communes avec listes -->
                        <div
                            v-for="(communeData, codeInsee) in deptData.communes"
                            :key="codeInsee"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                        >
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <h3 class="font-bold text-gray-900 dark:text-white">
                                    📍 {{ communeData.commune_nom }}
                                </h3>
                                <Badge class="bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">
                                    {{ communeData.nb_listes }} liste(s)
                                </Badge>
                            </div>

                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <Link
                                    v-for="liste in communeData.listes"
                                    :key="liste.uuid"
                                    :href="route('elections.municipales.liste', liste.uuid)"
                                    class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition"
                                >
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-4 h-4 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: liste.couleur }"
                                        ></div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                                                {{ liste.nom_liste }}
                                            </h4>
                                            <p v-if="liste.tete_de_liste" class="text-sm text-gray-500 dark:text-gray-400">
                                                👤 {{ liste.tete_de_liste }}
                                            </p>
                                        </div>
                                        <Badge
                                            v-if="liste.nuance_politique"
                                            class="text-xs"
                                            :style="{ backgroundColor: liste.couleur + '30', color: liste.couleur }"
                                        >
                                            {{ liste.nuance_politique }}
                                        </Badge>
                                        <span class="text-gray-400">→</span>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top communes -->
            <section v-if="top_communes.length > 0" class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    🏆 Communes les plus actives
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <Link
                        v-for="commune in top_communes.slice(0, 10)"
                        :key="commune.commune_code_insee"
                        :href="route('elections.municipales.recherche', { q: commune.commune_nom })"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-purple-300 transition group text-center"
                    >
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 group-hover:scale-110 transition">
                            {{ commune.nb_listes }}
                        </div>
                        <div class="font-semibold text-gray-900 dark:text-white mt-1 truncate">
                            {{ commune.commune_nom }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            ({{ commune.departement_code }})
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
