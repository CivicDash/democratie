<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import HemicycleView from '@/Components/Parliament/HemicycleView.vue';
import FranceMapInteractive from '@/Components/Statistics/FranceMapInteractive.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  hasLocation: Boolean,
  depute: Object,
  senateurs: Array,
  location: Object,
  deputesByDepartment: Object,
  senateursByDepartment: Object,
  regions: Array,
  deputesByRegion: Object,
  senateursByRegion: Object,
});

// Gestion de la carte
const selectedDepartment = ref(null);

const handleDepartmentSelected = (dept) => {
  selectedDepartment.value = dept;
  // Optionnel: naviguer vers les représentants du département
};

// Données régionales pour la carte (format attendu par FranceMapInteractive)
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
    
    if (data.results) {
      searchResults.value = data.results;
    } else if (data.commune) {
      searchResults.value = [data.commune];
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
  // Rediriger vers la page avec les paramètres de simulation
  router.visit(route('representants.mes-representants'), {
    data: {
      simulate_postal_code: location.code_postal || location.postal_code,
    },
    preserveState: true,
  });
};
</script>

<template>
  <Head title="Mes Représentants" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 rounded-xl shadow-lg p-8 text-white">
          <h1 class="text-4xl font-bold mb-2">🏛️ Mes Représentants</h1>
          <p class="text-blue-100 text-lg">Découvrez vos élus à l'Assemblée Nationale et au Sénat</p>
        </div>

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
          <div v-if="selectedDepartment" class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
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
        <Card v-if="!hasLocation" class="border-2 border-dashed border-gray-300 dark:border-gray-600">
          <div class="max-w-md mx-auto text-center py-8">
            <div class="text-6xl mb-4">📍</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              Découvrez vos représentants
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
              Entrez votre code postal ou votre ville pour découvrir vos député, sénateurs et maire
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
                💡 Mode démo : vous pouvez rechercher n'importe quelle localisation
              </p>
            </div>

            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
              <Link
                :href="route('profile.edit')"
                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
              >
                ⚙️ Configurer mon profil
              </Link>
            </div>
          </div>
        </Card>

        <!-- Avec localisation -->
        <template v-else>
          <!-- Ma localisation -->
          <Card>
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                  📍 Ma localisation
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                  {{ location.city }} ({{ location.postal_code }}) - {{ location.department }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                  Circonscription : <span class="font-semibold">{{ location.circonscription }}</span>
                </p>
              </div>
              <Link
                :href="route('profile.edit')"
                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
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
                  <div class="w-40 h-40 mx-auto mb-4 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
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
                  👤 Voir la fiche complète
                </Link>
              </div>
            </div>
          </Card>

          <!-- Député non trouvé mais circonscription connue -->
          <Card v-else-if="depute && depute.not_found">
            <div class="border-l-4 border-amber-500 pl-4 mb-4">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🗳️ Mon Député
              </h2>
              <p class="text-gray-600 dark:text-gray-400">{{ depute.message }}</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
              <p class="text-amber-800 dark:text-amber-200">
                ⚠️ Les données de liaison député-circonscription sont en cours de mise à jour.
                <br/>
                <Link :href="route('representants.deputes.index')" class="underline hover:no-underline">
                  Voir tous les députés →
                </Link>
              </p>
            </div>
          </Card>

          <!-- Pas de député trouvé du tout -->
          <Card v-else>
            <div class="border-l-4 border-gray-300 pl-4 mb-4">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🗳️ Mon Député
              </h2>
              <p class="text-gray-600 dark:text-gray-400">Assemblée Nationale</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
              <p class="text-gray-600 dark:text-gray-400">
                Données de circonscription non disponibles pour cette localisation.
                <br/>
                <Link :href="route('representants.deputes.index')" class="text-blue-600 hover:underline">
                  Voir tous les députés →
                </Link>
              </p>
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
                  <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
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
                      :style="{ backgroundColor: senateur.groupe.couleur, color: '#fff' }"
                      class="mb-2 text-xs"
                    >
                      {{ senateur.groupe.sigle }}
                    </Badge>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                      {{ senateur.profession }}
                    </p>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                      <div class="text-center">
                        <div class="font-bold text-blue-600">{{ senateur.nb_votes || 0 }}</div>
                        <div class="text-gray-500">Votes</div>
                      </div>
                      <div class="text-center">
                        <div class="font-bold text-green-600">{{ senateur.nb_amendements || 0 }}</div>
                        <div class="text-gray-500">Amend.</div>
                      </div>
                    </div>

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

          <!-- Pas de sénateurs -->
          <Card v-else>
            <div class="text-center py-8">
              <div class="text-4xl mb-3">🔍</div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Aucun sénateur trouvé
              </h3>
              <p class="text-gray-600 dark:text-gray-400">
                Nous n'avons pas trouvé de sénateur pour votre département.
              </p>
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
              <div class="text-4xl mb-3">🏛️</div>
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

