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

// Simulateur de localisation - Mode dynamique sans refresh
const showLocationSimulator = ref(!props.hasLocation);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const isLoadingRepresentants = ref(false);

// Données chargées dynamiquement
const dynamicLocation = ref(null);
const dynamicDepute = ref(null);
const dynamicSenateurs = ref([]);
const dynamicMaire = ref(null);
const hasDynamicResults = ref(false);

// Computed pour utiliser soit les données dynamiques soit les props
const currentLocation = computed(() => dynamicLocation.value || props.location);
const currentDepute = computed(() => dynamicDepute.value || props.depute);
const currentSenateurs = computed(() => dynamicSenateurs.value.length > 0 ? dynamicSenateurs.value : props.senateurs);
const currentMaire = computed(() => dynamicMaire.value || props.maire);
const currentHasLocation = computed(() => hasDynamicResults.value || props.hasLocation);

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
      // Résultat unique - charger directement les représentants
      await loadRepresentants(data);
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

const selectLocation = async (location) => {
  isLoadingRepresentants.value = true;
  searchResults.value = [];
  
  try {
    // Utiliser le code INSEE si disponible, sinon le code postal
    const param = location.insee_code 
      ? `insee_code=${location.insee_code}` 
      : `postal_code=${location.code_postal || location.postal_code}`;
    
    const response = await fetch(`/api/representants/search?${param}`);
    const data = await response.json();
    
    if (data.commune && data.representants) {
      await loadRepresentants(data);
    }
  } catch (error) {
    console.error('Erreur chargement représentants:', error);
  } finally {
    isLoadingRepresentants.value = false;
  }
};

const loadRepresentants = async (data) => {
  // Mettre à jour les données dynamiques
  dynamicLocation.value = {
    city: data.commune.nom,
    postal_code: data.commune.code_postal,
    department: data.commune.departement?.nom,
    circonscription: data.commune.circonscription,
  };
  
  // Député
  if (data.representants.depute) {
    const d = data.representants.depute;
    dynamicDepute.value = {
      uid: d.uid,
      nom_complet: d.nom_complet,
      photo_url: d.photo_url,
      groupe: d.groupe_sigle ? { sigle: d.groupe_sigle, nom: d.groupe, couleur: d.groupe_couleur } : null,
      circonscription: d.circonscription,
      url_profil: d.url,
      nb_votes: 0,
      nb_amendements: 0,
    };
  } else {
    dynamicDepute.value = { not_found: true, message: 'Député non trouvé pour cette circonscription' };
  }
  
  // Sénateurs
  dynamicSenateurs.value = (data.representants.senateurs || []).map(s => ({
    id: s.matricule || s.id,
    nom_complet: s.nom_complet,
    photo_url: s.photo_url,
    groupe: s.groupe ? { sigle: s.groupe, couleur: '#6B7280' } : null,
    profession: '',
    url_profil: s.url,
  }));
  
  // Maire
  if (data.representants.maire) {
    const m = data.representants.maire;
    dynamicMaire.value = {
      nom_complet: `${m.prenom} ${m.nom}`,
      commune: m.commune,
      email: m.email,
      civilite: m.prenom?.endsWith('e') ? 'Mme' : 'M.',
    };
  } else {
    dynamicMaire.value = null;
  }
  
  hasDynamicResults.value = true;
  searchQuery.value = '';
  searchResults.value = [];
  
  // Animation de scroll vers les résultats
  setTimeout(() => {
    const resultsSection = document.getElementById('representants-results');
    if (resultsSection) {
      resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 100);
};

const resetSearch = () => {
  hasDynamicResults.value = false;
  dynamicLocation.value = null;
  dynamicDepute.value = null;
  dynamicSenateurs.value = [];
  dynamicMaire.value = null;
  searchQuery.value = '';
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

        <!-- Bloc "Trouvez vos représentants" - En premier si pas de localisation -->
        <Card v-if="!currentHasLocation || isLoadingRepresentants" class="border-2 border-dashed border-indigo-300 dark:border-indigo-600">
          <div class="max-w-md mx-auto text-center py-8">
            <!-- Indicateur de chargement -->
            <div v-if="isLoadingRepresentants" class="py-12">
              <div class="animate-spin w-16 h-16 mx-auto mb-4 border-4 border-indigo-200 border-t-indigo-600 rounded-full"></div>
              <p class="text-lg font-medium text-gray-700 dark:text-gray-300">Chargement de vos représentants...</p>
            </div>
            
            <template v-else>
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
            </template>
          </div>
        </Card>

        <!-- Avec localisation - Résultats en premier -->
        <template v-if="currentHasLocation && !isLoadingRepresentants">
          <!-- Ma localisation -->
          <Card id="representants-results" class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border-indigo-200 dark:border-indigo-700">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1 flex items-center gap-2">
                  📍 {{ hasDynamicResults ? 'Localisation sélectionnée' : 'Ma localisation' }}
                </h3>
                <p class="text-gray-700 dark:text-gray-300 font-medium">
                  {{ currentLocation.city }} ({{ currentLocation.postal_code }}) - {{ currentLocation.department }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  Circonscription : <span class="font-semibold">{{ currentLocation.circonscription }}</span>
                </p>
              </div>
              <div class="flex gap-2">
                <button
                  v-if="hasDynamicResults"
                  @click="resetSearch"
                  class="px-4 py-2 text-gray-600 hover:text-gray-700 dark:text-gray-400 text-sm font-medium bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                >
                  🔄 Autre recherche
                </button>
                <Link
                  v-if="!hasDynamicResults"
                  :href="route('profile.edit')"
                  class="px-4 py-2 text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 text-sm font-medium bg-white dark:bg-gray-800 rounded-lg shadow-sm"
                >
                  Modifier
                </Link>
              </div>
            </div>
          </Card>

          <!-- Mon Député -->
          <Card v-if="currentDepute && !currentDepute.not_found">
            <div class="border-l-4 border-blue-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🗳️ Mon Député
              </h2>
              <p class="text-gray-600 dark:text-gray-400">{{ currentDepute.circonscription || 'Assemblée Nationale' }}</p>
            </div>

            <div class="flex flex-col md:flex-row items-center gap-6">
              <!-- Photo -->
              <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 ring-4 ring-blue-500/30 flex-shrink-0">
                <img
                  v-if="currentDepute.photo_url"
                  :src="currentDepute.photo_url"
                  :alt="currentDepute.nom_complet"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-5xl">
                  👤
                </div>
              </div>
              
              <!-- Infos -->
              <div class="flex-1 text-center md:text-left">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                  {{ currentDepute.nom_complet }}
                </h3>
                <Badge
                  v-if="currentDepute.groupe"
                  :style="{ backgroundColor: currentDepute.groupe.couleur, color: '#fff' }"
                  class="mb-2"
                >
                  {{ currentDepute.groupe.sigle }}
                </Badge>
                <p v-if="currentDepute.groupe" class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  {{ currentDepute.groupe.nom }}
                </p>
                
                <Link
                  :href="currentDepute.url_profil"
                  class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                >
                  👤 Voir la fiche complète →
                </Link>
              </div>
            </div>
          </Card>

          <!-- Député non trouvé -->
          <Card v-else-if="currentDepute && currentDepute.not_found" class="border-l-4 border-amber-500">
            <div class="flex items-start gap-4">
              <span class="text-3xl">⚠️</span>
              <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                  Mon Député
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-3">{{ currentDepute.message }}</p>
                <Link :href="route('representants.deputes.index')" class="text-blue-600 hover:underline font-medium">
                  Voir tous les députés →
                </Link>
              </div>
            </div>
          </Card>

          <!-- Mes Sénateurs -->
          <Card v-if="currentSenateurs && currentSenateurs.length > 0">
            <div class="border-l-4 border-red-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🏛️ Mes Sénateurs
              </h2>
              <p class="text-gray-600 dark:text-gray-400">Sénat ({{ currentLocation.department }})</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div
                v-for="senateur in currentSenateurs"
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
          <Card v-if="currentMaire">
            <div class="border-l-4 border-emerald-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🏘️ Mon Maire
              </h2>
              <p class="text-gray-600 dark:text-gray-400">{{ currentMaire.commune }} ({{ currentMaire.code_departement || currentLocation?.department }})</p>
            </div>

            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-800">
              <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-200 dark:bg-emerald-800 flex items-center justify-center text-3xl flex-shrink-0">
                  {{ currentMaire.civilite === 'Mme' ? '👩' : '👨' }}
                </div>
                
                <div class="flex-1">
                  <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                    {{ currentMaire.nom_complet }}
                  </h3>
                  
                  <span 
                    v-if="currentMaire.nuance_politique"
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium mb-2"
                    :style="{ backgroundColor: currentMaire.nuance_couleur || '#6B7280', color: '#fff' }"
                  >
                    {{ currentMaire.nuance_libelle || currentMaire.nuance_politique }}
                  </span>
                  
                  <p v-if="currentMaire.profession" class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    {{ currentMaire.profession }}
                  </p>
                  
                  <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                    <div>
                      <span class="text-gray-500">Population :</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100 ml-1">
                        {{ currentMaire.population_commune?.toLocaleString('fr-FR') || 'N/A' }} hab.
                      </span>
                    </div>
                    <div v-if="currentMaire.debut_mandat">
                      <span class="text-gray-500">En fonction depuis :</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100 ml-1">{{ currentMaire.debut_mandat }}</span>
                    </div>
                  </div>
                  
                  <!-- Contacts -->
                  <div class="flex flex-wrap gap-2">
                    <a 
                      v-if="currentMaire.email"
                      :href="`mailto:${currentMaire.email}`"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-sm rounded hover:bg-emerald-700 transition"
                    >
                      ✉️ Email
                    </a>
                    <a 
                      v-if="currentMaire.telephone"
                      :href="`tel:${currentMaire.telephone}`"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 text-sm rounded hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition"
                    >
                      📞 {{ currentMaire.telephone }}
                    </a>
                    <a 
                      v-if="currentMaire.site_web"
                      :href="currentMaire.site_web"
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

        </template>

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

      </div>
    </div>
  </AuthenticatedLayout>
</template>
