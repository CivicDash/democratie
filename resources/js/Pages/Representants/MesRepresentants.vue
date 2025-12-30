<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import HemicycleView from '@/Components/Parliament/HemicycleView.vue';
import FranceMapInteractive from '@/Components/Statistics/FranceMapInteractive.vue';
import TextInput from '@/Components/TextInput.vue';

// Vérifier si l'utilisateur est connecté
const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const props = defineProps({
  hasLocation: Boolean,
  depute: Object,
  senateurs: Array,
  maire: Object,
  location: Object,
  deputesByDepartment: Object,
  senateursByDepartment: Object,
  regions: Array,
  deputesByRegion: Object,
  senateursByRegion: Object,
  stats: {
    type: Object,
    default: () => ({
      deputes: 577,
      senateurs: 348,
      maires: 34914,
      discussions: 25,
      votes_citoyens: 150,
    })
  },
});

// Gestion de la carte
const selectedDepartment = ref(null);

const handleDepartmentSelected = (dept) => {
  selectedDepartment.value = dept;
};

// Données régionales pour la carte
const regionalDataForMap = computed(() => {
  if (!props.regions) return [];
  return props.regions.map(region => ({
    region_code: region.code,
    region_name: region.name,
    deputesCount: props.deputesByRegion?.[region.code] || 0,
    senateursCount: props.senateursByRegion?.[region.code] || 0,
  }));
});

// Simulateur de localisation
const showLocationSimulator = ref(!props.hasLocation);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

const searchLocation = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = [];
    return;
  }

  isSearching.value = true;
  
  try {
    const response = await fetch(`/api/representants/search?q=${encodeURIComponent(searchQuery.value)}`);
    const data = await response.json();
    
    if (data.multiple_communes || data.multiple_results) {
      searchResults.value = data.communes || [];
    } else if (data.commune) {
      searchResults.value = [data.commune];
    } else if (data.results) {
      searchResults.value = data.results;
    } else {
      searchResults.value = [];
    }
  } catch (error) {
    console.error('Erreur recherche:', error);
    searchResults.value = [];
  } finally {
    isSearching.value = false;
  }
};

const selectLocation = (location) => {
  router.visit(route('representants.mes-representants'), {
    data: {
      simulate_postal_code: location.code_postal || location.postal_code,
    },
    preserveState: true,
  });
};

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Mes Représentants', current: true, icon: '📍' },
];
</script>

<template>
  <Head title="Mes Représentants" />

  <AuthenticatedLayout>
    <!-- Hero Section Full Width -->
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-purple-900 to-slate-900">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>
      
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <!-- Breadcrumb -->
        <Breadcrumb :items="breadcrumbs" class="mb-6" />
        
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <!-- Titre -->
          <div>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
              <span class="text-4xl">📍</span>
              Mes Représentants
            </h1>
            <p class="text-purple-200 text-lg">
              Découvrez qui vous représente à l'Assemblée Nationale, au Sénat et dans votre commune
            </p>
          </div>
          
          <!-- Stats rapides -->
          <div class="flex flex-wrap gap-4">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
              <div class="text-2xl md:text-3xl font-bold text-white">{{ stats.deputes }}</div>
              <div class="text-purple-200 text-xs uppercase tracking-wide">Députés</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
              <div class="text-2xl md:text-3xl font-bold text-white">{{ stats.senateurs }}</div>
              <div class="text-purple-200 text-xs uppercase tracking-wide">Sénateurs</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
              <div class="text-2xl md:text-3xl font-bold text-white">{{ (stats.maires / 1000).toFixed(0) }}K</div>
              <div class="text-purple-200 text-xs uppercase tracking-wide">Maires</div>
            </div>
          </div>
        </div>

        <!-- Stats secondaires -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">💬</span>
              <div>
                <div class="text-xl font-bold text-white">{{ stats.discussions || 25 }}</div>
                <div class="text-purple-300 text-sm">Discussions actives</div>
              </div>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">🗳️</span>
              <div>
                <div class="text-xl font-bold text-white">{{ stats.votes_citoyens || 150 }}</div>
                <div class="text-purple-300 text-sm">Votes citoyens</div>
              </div>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">💡</span>
              <div>
                <div class="text-xl font-bold text-white">{{ stats.propositions || 12 }}</div>
                <div class="text-purple-300 text-sm">Propositions citoyennes</div>
              </div>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">📊</span>
              <div>
                <div class="text-xl font-bold text-white">101</div>
                <div class="text-purple-300 text-sm">Départements</div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Liens rapides -->
        <div class="flex flex-wrap gap-3 mt-6">
          <Link 
            :href="route('representants.deputes.index')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500/80 hover:bg-blue-500 text-white text-sm rounded-lg transition-colors"
          >
            🏛️ Assemblée Nationale
          </Link>
          <Link 
            :href="route('representants.senateurs.index')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/80 hover:bg-red-500 text-white text-sm rounded-lg transition-colors"
          >
            🏰 Sénat
          </Link>
          <Link 
            :href="route('parlement.comparaison')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg border border-white/20 transition-colors"
          >
            📊 Statistiques des élus
          </Link>
        </div>
      </div>
    </section>

    <!-- Contenu principal -->
    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Hémicycles -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <HemicycleView chamber="assembly" />
          <HemicycleView chamber="senate" />
        </div>

        <!-- Carte de France Interactive -->
        <Card>
          <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              🗺️ Carte de France Interactive
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
              Cliquez sur un département pour découvrir ses représentants
            </p>
          </div>
          
          <FranceMapInteractive 
            :regionalData="regionalDataForMap"
            heatmapMetric="deputesCount"
            @department-selected="handleDepartmentSelected"
          />

          <!-- Détail du département sélectionné -->
          <div v-if="selectedDepartment" class="mt-6 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                {{ selectedDepartment.name }} ({{ selectedDepartment.code }})
              </h3>
              <button 
                @click="selectedDepartment = null"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
              >
                ✕
              </button>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-blue-600">
                  {{ deputesByDepartment?.[selectedDepartment.code] || 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Député(s)</div>
              </div>
              <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-red-600">
                  {{ senateursByDepartment?.[selectedDepartment.code] || 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Sénateur(s)</div>
              </div>
            </div>

            <div class="flex gap-2">
              <Link
                :href="route('representants.deputes.index', { department: selectedDepartment.code })"
                class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
              >
                Voir les députés
              </Link>
              <Link
                :href="route('representants.senateurs.index', { department: selectedDepartment.code })"
                class="flex-1 text-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm"
              >
                Voir les sénateurs
              </Link>
            </div>
          </div>
        </Card>

        <!-- Pas de localisation -->
        <Card v-if="!hasLocation" class="border-2 border-dashed border-indigo-300 dark:border-indigo-600">
          <div class="max-w-md mx-auto text-center py-8">
            <div class="text-6xl mb-4">📍</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              Trouvez vos représentants
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
              Entrez votre code postal ou votre ville pour découvrir votre député, vos sénateurs et votre maire
            </p>
            
            <!-- Simulateur de recherche -->
            <div class="mb-6">
              <div class="relative">
                <TextInput
                  v-model="searchQuery"
                  @input="searchLocation"
                  placeholder="75001 ou Paris..."
                  class="w-full pr-10"
                />
                <div v-if="isSearching" class="absolute right-3 top-3">
                  <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
              </div>

              <!-- Résultats de recherche -->
              <div v-if="searchResults.length > 0" class="mt-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-64 overflow-y-auto text-left">
                <button
                  v-for="result in searchResults"
                  :key="result.insee_code || result.postal_code"
                  @click="selectLocation(result)"
                  class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition"
                >
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ result.nom || result.city_name }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ result.code_postal || result.postal_code }} - {{ result.departement?.nom || result.department_name }}
                  </div>
                </button>
              </div>
              
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                💡 Recherchez par code postal ou nom de ville
              </p>
            </div>

            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
              <Link
                :href="route('profile.edit')"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition"
              >
                ⚙️ Configurer mon profil
              </Link>
            </div>
          </div>
        </Card>

        <!-- Avec localisation -->
        <template v-else>
          <!-- Ma localisation -->
          <Card class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border-indigo-200 dark:border-indigo-700">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1 flex items-center gap-2">
                  📍 Ma localisation
                </h3>
                <p class="text-gray-700 dark:text-gray-300 font-medium">
                  {{ location.city }} ({{ location.postal_code }}) - {{ location.department }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  Circonscription : <span class="font-semibold">{{ location.circonscription }}</span>
                </p>
              </div>
              <Link
                :href="route('profile.edit')"
                class="px-4 py-2 text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 text-sm font-medium bg-white dark:bg-gray-800 rounded-lg shadow-sm"
              >
                Modifier
              </Link>
            </div>
          </Card>

          <!-- Mon Député -->
          <Card v-if="depute && !depute.not_found">
            <div class="border-l-4 border-blue-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🗳️ Mon Député
              </h2>
              <p class="text-gray-600 dark:text-gray-400">{{ depute.circonscription || 'Assemblée Nationale' }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
              <!-- Photo et infos principales -->
              <div class="md:col-span-1">
                <div class="text-center">
                  <div class="w-40 h-40 mx-auto mb-4 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 ring-4 ring-blue-500/30">
                    <img
                      v-if="depute.photo_url"
                      :src="depute.photo_url"
                      :alt="depute.nom_complet"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-6xl">
                      👤
                    </div>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    {{ depute.nom_complet }}
                  </h3>
                  <Badge
                    v-if="depute.groupe"
                    :style="{ backgroundColor: depute.groupe.couleur, color: '#fff' }"
                    class="mb-2"
                  >
                    {{ depute.groupe.sigle }}
                  </Badge>
                  <p v-if="depute.groupe" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ depute.groupe.nom }}
                  </p>
                </div>
              </div>

              <!-- Statistiques -->
              <div class="md:col-span-2">
                <div class="grid grid-cols-2 gap-4 mb-6">
                  <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="text-3xl font-bold text-blue-600">{{ depute.nb_votes || 0 }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Votes (L17)</div>
                  </div>
                  <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-3xl font-bold text-green-600">{{ depute.nb_amendements || 0 }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Amendements</div>
                  </div>
                </div>

                <Link
                  :href="depute.url_profil"
                  class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                >
                  👤 Voir la fiche complète →
                </Link>
              </div>
            </div>
          </Card>

          <!-- Député non trouvé -->
          <Card v-else-if="depute && depute.not_found" class="border-l-4 border-amber-500">
            <div class="flex items-start gap-4">
              <span class="text-3xl">⚠️</span>
              <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                  Mon Député
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-3">{{ depute.message }}</p>
                <Link :href="route('representants.deputes.index')" class="text-blue-600 hover:underline font-medium">
                  Voir tous les députés →
                </Link>
              </div>
            </div>
          </Card>

          <!-- Mes Sénateurs -->
          <Card v-if="senateurs && senateurs.length > 0">
            <div class="border-l-4 border-red-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🏛️ Mes Sénateurs
              </h2>
              <p class="text-gray-600 dark:text-gray-400">Sénat ({{ location.department }})</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div
                v-for="senateur in senateurs"
                :key="senateur.id"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition"
              >
                <div class="flex items-start gap-4">
                  <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0 ring-2 ring-red-500/30">
                    <img
                      v-if="senateur.photo_url"
                      :src="senateur.photo_url"
                      :alt="senateur.nom_complet"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-3xl">
                      👤
                    </div>
                  </div>
                  
                  <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                      {{ senateur.nom_complet }}
                    </h3>
                    <Badge
                      :style="{ backgroundColor: senateur.groupe?.couleur || '#6B7280', color: '#fff' }"
                      class="mb-2 text-xs"
                    >
                      {{ senateur.groupe?.sigle || 'N/A' }}
                    </Badge>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                      {{ senateur.profession }}
                    </p>
                    
                    <Link
                      :href="senateur.url_profil"
                      class="block text-center px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition"
                    >
                      Voir la fiche
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Mon Maire -->
          <Card v-if="maire">
            <div class="border-l-4 border-emerald-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🏘️ Mon Maire
              </h2>
              <p class="text-gray-600 dark:text-gray-400">{{ maire.commune }} ({{ maire.code_departement }})</p>
            </div>

            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-800">
              <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-200 dark:bg-emerald-800 flex items-center justify-center text-3xl flex-shrink-0">
                  {{ maire.civilite === 'Mme' ? '👩' : '👨' }}
                </div>
                
                <div class="flex-1">
                  <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                    {{ maire.nom_complet }}
                  </h3>
                  
                  <span 
                    v-if="maire.nuance_politique"
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium mb-2"
                    :style="{ backgroundColor: maire.nuance_couleur || '#6B7280', color: '#fff' }"
                  >
                    {{ maire.nuance_libelle || maire.nuance_politique }}
                  </span>
                  
                  <p v-if="maire.profession" class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    {{ maire.profession }}
                  </p>
                  
                  <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                    <div>
                      <span class="text-gray-500">Population :</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100 ml-1">
                        {{ maire.population_commune?.toLocaleString('fr-FR') || 'N/A' }} hab.
                      </span>
                    </div>
                    <div v-if="maire.debut_mandat">
                      <span class="text-gray-500">En fonction depuis :</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100 ml-1">{{ maire.debut_mandat }}</span>
                    </div>
                  </div>
                  
                  <!-- Contacts -->
                  <div class="flex flex-wrap gap-2">
                    <a 
                      v-if="maire.email"
                      :href="`mailto:${maire.email}`"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-sm rounded hover:bg-emerald-700 transition"
                    >
                      ✉️ Email
                    </a>
                    <a 
                      v-if="maire.telephone"
                      :href="`tel:${maire.telephone}`"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 text-sm rounded hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition"
                    >
                      📞 {{ maire.telephone }}
                    </a>
                    <a 
                      v-if="maire.site_web"
                      :href="maire.site_web"
                      target="_blank"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 text-sm rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                    >
                      🌐 Site web
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Liens rapides -->
          <div class="grid md:grid-cols-2 gap-6">
            <Link
              :href="route('representants.deputes.index')"
              class="block bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white hover:shadow-xl transition group"
            >
              <div class="text-4xl mb-3">🏛️</div>
              <h3 class="text-xl font-bold mb-2 group-hover:translate-x-1 transition">
                Tous les Députés
              </h3>
              <p class="text-blue-100">
                Découvrez les 577 députés de l'Assemblée Nationale
              </p>
            </Link>

            <Link
              :href="route('representants.senateurs.index')"
              class="block bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white hover:shadow-xl transition group"
            >
              <div class="text-4xl mb-3">🏰</div>
              <h3 class="text-xl font-bold mb-2 group-hover:translate-x-1 transition">
                Tous les Sénateurs
              </h3>
              <p class="text-red-100">
                Découvrez les 348 sénateurs du Sénat
              </p>
            </Link>
          </div>
        </template>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
