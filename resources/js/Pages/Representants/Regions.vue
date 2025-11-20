<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import FranceRegionsMap from '@/Components/Representants/FranceRegionsMap.vue';

const props = defineProps({
  regions: Array,
  selectedRegion: Object,
  deputesByRegion: Object,
  senateursByRegion: Object,
  deputes: Array,
  senateurs: Array,
});

const hoveredRegion = ref(null);

const selectRegion = (regionCode) => {
  router.visit(route('representants.regions', { region: regionCode }), {
    preserveScroll: true,
  });
};

// Transformer les données pour le composant carte
const regionsForMap = computed(() => {
  const result = {};
  props.regions.forEach(region => {
    result[region.code] = {
      name: region.name,
      deputesCount: props.deputesByRegion[region.code] || 0,
      senateursCount: props.senateursByRegion[region.code] || 0,
    };
  });
  return result;
});
</script>

<template>
  <Head title="Représentants par Région" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white">
          <h1 class="text-4xl font-bold mb-2">🗺️ Représentants par Région</h1>
          <p class="text-indigo-100 text-lg">Découvrez la répartition des députés et sénateurs dans les 13 régions françaises</p>
        </div>

        <!-- Carte + Liste des régions -->
        <div class="grid lg:grid-cols-3 gap-6">
          
          <!-- Carte France par régions -->
          <Card class="lg:col-span-2">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              Carte de France par régions
            </h2>
            
            <FranceRegionsMap
              :regions="regionsForMap"
              :selectedRegionCode="selectedRegion?.code"
              @select-region="selectRegion"
              @hover-region="(code) => hoveredRegion = code"
            />

            <!-- Légende -->
            <div class="mt-4 flex flex-wrap items-center gap-6 text-sm">
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #1e40af"></div>
                <span class="text-gray-600 dark:text-gray-400">&gt; 40 députés</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #3b82f6"></div>
                <span class="text-gray-600 dark:text-gray-400">30-40</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #60a5fa"></div>
                <span class="text-gray-600 dark:text-gray-400">20-30</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #93c5fd"></div>
                <span class="text-gray-600 dark:text-gray-400">10-20</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #dbeafe"></div>
                <span class="text-gray-600 dark:text-gray-400">&lt; 10</span>
              </div>
            </div>
          </Card>

          <!-- Liste des régions -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              📋 Les 13 régions
            </h2>
            
            <div class="space-y-2 max-h-[600px] overflow-y-auto">
              <button
                v-for="region in regions"
                :key="region.code"
                @click="selectRegion(region.code)"
                @mouseenter="hoveredRegion = region.code"
                @mouseleave="hoveredRegion = null"
                :class="[
                  'w-full text-left px-4 py-3 rounded-lg transition',
                  selectedRegion?.code === region.code
                    ? 'bg-indigo-100 dark:bg-indigo-900 border-2 border-indigo-500'
                    : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border-2 border-transparent'
                ]"
              >
                <div class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                  {{ region.name }}
                </div>
                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                  <span>🏛️ {{ deputesByRegion[region.code] || 0 }} députés</span>
                  <span>🏰 {{ senateursByRegion[region.code] || 0 }} sénateurs</span>
                </div>
              </button>
            </div>
          </Card>
        </div>

        <!-- Représentants de la région sélectionnée -->
        <template v-if="selectedRegion">
          <Card>
            <div class="mb-6">
              <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                {{ selectedRegion.name }}
              </h2>
              <div class="flex items-center gap-6 text-lg text-gray-600 dark:text-gray-400">
                <span>🏛️ <strong>{{ deputes.length }}</strong> députés</span>
                <span>🏰 <strong>{{ senateurs.length }}</strong> sénateurs</span>
                <span>👥 <strong>{{ deputes.length + senateurs.length }}</strong> représentants</span>
              </div>
            </div>

            <!-- Onglets -->
            <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
              <nav class="-mb-px flex gap-6">
                <button
                  @click="$refs.deputesTab.scrollIntoView({ behavior: 'smooth' })"
                  class="py-3 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600 dark:text-blue-400"
                >
                  Députés ({{ deputes.length }})
                </button>
                <button
                  @click="$refs.senateursTab.scrollIntoView({ behavior: 'smooth' })"
                  class="py-3 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
                >
                  Sénateurs ({{ senateurs.length }})
                </button>
              </nav>
            </div>

            <!-- Liste des députés -->
            <div ref="deputesTab" class="mb-8">
              <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                🏛️ Députés
              </h3>
              <div v-if="deputes.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="depute in deputes"
                  :key="depute.id"
                  class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 hover:shadow-lg transition"
                >
                  <div class="flex items-start gap-3">
                    <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                      <img
                        v-if="depute.photo_url"
                        :src="depute.photo_url"
                        :alt="depute.nom_complet"
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center text-2xl">
                        👤
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <h4 class="font-bold text-gray-900 dark:text-gray-100 truncate mb-1">
                        {{ depute.nom_complet }}
                      </h4>
                      <Badge
                        v-if="depute.groupe"
                        :style="{ backgroundColor: depute.groupe.couleur, color: '#fff' }"
                        class="text-xs mb-2"
                      >
                        {{ depute.groupe.sigle }}
                      </Badge>
                      <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                        {{ depute.circonscription }}
                      </p>
                      <Link
                        :href="route('representants.deputes.show', depute.id)"
                        class="text-sm text-blue-600 hover:text-blue-700 mt-2 inline-block"
                      >
                        Voir la fiche →
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                Aucun député dans cette région
              </div>
            </div>

            <!-- Liste des sénateurs -->
            <div ref="senateursTab">
              <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                🏰 Sénateurs
              </h3>
              <div v-if="senateurs.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="senateur in senateurs"
                  :key="senateur.id"
                  class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 hover:shadow-lg transition"
                >
                  <div class="flex items-start gap-3">
                    <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                      <img
                        v-if="senateur.photo_url"
                        :src="senateur.photo_url"
                        :alt="senateur.nom_complet"
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center text-2xl">
                        👤
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <h4 class="font-bold text-gray-900 dark:text-gray-100 truncate mb-1">
                        {{ senateur.nom_complet }}
                      </h4>
                      <Badge
                        v-if="senateur.groupe"
                        :style="{ backgroundColor: senateur.groupe.couleur, color: '#fff' }"
                        class="text-xs mb-2"
                      >
                        {{ senateur.groupe.sigle }}
                      </Badge>
                      <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                        {{ senateur.departement }}
                      </p>
                      <Link
                        :href="route('representants.senateurs.show', senateur.id)"
                        class="text-sm text-red-600 hover:text-red-700 mt-2 inline-block"
                      >
                        Voir la fiche →
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                Aucun sénateur dans cette région
              </div>
            </div>
          </Card>
        </template>

        <!-- Pas de région sélectionnée -->
        <Card v-else class="text-center py-12">
          <div class="text-4xl mb-3">🗺️</div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
            Sélectionnez une région
          </h3>
          <p class="text-gray-600 dark:text-gray-400">
            Cliquez sur une région dans la liste pour voir ses représentants
          </p>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

