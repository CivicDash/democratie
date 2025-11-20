<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  senateurs: Object,
  groupes: Array,
  filters: Object,
});

const viewMode = ref('list'); // 'list' ou 'hemicycle'
const search = ref(props.filters.search || '');
const selectedGroupe = ref(props.filters.groupe || '');

const applyFilters = () => {
  router.get(route('representants.senateurs.index'), {
    search: search.value,
    groupe: selectedGroupe.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Calcul des sièges par groupe pour l'hémicycle
const siegesParGroupe = computed(() => {
  const counts = {};
  props.senateurs.data.forEach(senateur => {
    const sigle = senateur.groupe_sigle || 'NI';
    counts[sigle] = (counts[sigle] || 0) + 1;
  });
  return counts;
});
</script>

<template>
  <Head title="Sénateurs - Sénat" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-700 to-red-800 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-4xl font-bold mb-2">🏛️ Sénat</h1>
              <p class="text-blue-100 text-lg">348 Sénateurs - 17ème législature</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="viewMode = 'list'"
                :class="[
                  'px-4 py-2 rounded-lg font-medium transition',
                  viewMode === 'list' 
                    ? 'bg-white text-red-700' 
                    : 'bg-red-600 text-white hover:bg-blue-500'
                ]"
              >
                📋 Liste
              </button>
              <button
                @click="viewMode = 'hemicycle'"
                :class="[
                  'px-4 py-2 rounded-lg font-medium transition',
                  viewMode === 'hemicycle' 
                    ? 'bg-white text-red-700' 
                    : 'bg-red-600 text-white hover:bg-blue-500'
                ]"
              >
                🏛️ Hémicycle
              </button>
            </div>
          </div>
        </div>

        <!-- Filtres -->
        <Card>
          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🔍 Rechercher
              </label>
              <TextInput
                v-model="search"
                placeholder="Nom, prénom, circonscription..."
                @keyup.enter="applyFilters"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🎨 Groupe parlementaire
              </label>
              <select
                v-model="selectedGroupe"
                @change="applyFilters"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
              >
                <option value="">Tous les groupes</option>
                <option v-for="groupe in props.groupes" :key="groupe.sigle" :value="groupe.sigle">
                  {{ groupe.nom }}
                </option>
              </select>
            </div>
            <div class="flex items-end">
              <button
                @click="search = ''; selectedGroupe = ''; applyFilters()"
                class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
              >
                🔄 Réinitialiser
              </button>
            </div>
          </div>
        </Card>

        <!-- Vue Hémicycle -->
        <Card v-if="viewMode === 'hemicycle'">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            Vue de l'Hémicycle
          </h2>
          
          <!-- Répartition des sièges -->
          <div class="mb-8">
            <div class="flex flex-wrap gap-4 justify-center">
              <div
                v-for="groupe in props.groupes"
                :key="groupe.sigle"
                class="flex items-center gap-2 cursor-pointer hover:opacity-75 transition"
                @click="selectedGroupe = groupe.sigle; applyFilters();"
              >
                <div
                  class="w-6 h-6 rounded-full"
                  :style="{ backgroundColor: groupe.couleur_hex }"
                ></div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ groupe.sigle }} ({{ siegesParGroupe[groupe.sigle] || 0 }})
                </span>
              </div>
            </div>
          </div>

          <!-- Hémicycle SVG simplifié -->
          <div class="relative w-full aspect-[2/1] bg-gradient-to-b from-blue-50 to-white dark:from-gray-800 dark:to-gray-900 rounded-xl overflow-hidden">
            <svg viewBox="0 0 800 400" class="w-full h-full">
              <!-- Rangées de sièges en arc de cercle -->
              <g v-for="(row, rowIndex) in 7" :key="rowIndex">
                <circle
                  v-for="(seat, seatIndex) in Math.floor(80 + rowIndex * 10)"
                  :key="seatIndex"
                  :cx="400 + Math.cos((Math.PI * seatIndex) / (80 + rowIndex * 10) - Math.PI/2) * (150 + rowIndex * 40)"
                  :cy="350 - Math.sin((Math.PI * seatIndex) / (80 + rowIndex * 10) - Math.PI/2) * (150 + rowIndex * 40)"
                  r="3"
                  :fill="groupes[Math.floor(Math.random() * groupes.length)]?.couleur || '#6B7280'"
                  class="hover:r-5 transition-all cursor-pointer"
                />
              </g>
              
              <!-- Tribune centrale -->
              <rect x="350" y="320" width="100" height="60" fill="#8B4513" rx="5" />
              <text x="400" y="355" text-anchor="middle" fill="white" font-size="14" font-weight="bold">
                TRIBUNE
              </text>
            </svg>
          </div>

          <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
            💡 Vue simplifiée de l'hémicycle avec répartition des groupes parlementaires
          </p>
        </Card>

        <!-- Vue Liste -->
        <Card v-else>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Sénateur
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Groupe
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Circonscription
                  </th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="senateur in senateurs.data"
                  :key="senateur.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                        <img
                          v-if="senateur.photo_url"
                          :src="senateur.photo_url"
                          :alt="senateur.nom_complet"
                          class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-xl">
                          👤
                        </div>
                      </div>
                      <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                          {{ senateur.nom_complet }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ senateur.profession || 'Profession non renseignée' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <Badge
                      v-if="senateur.groupe"
                      :style="{ 
                        backgroundColor: senateur.groupe.couleur || '#6B7280',
                        color: '#fff'
                      }"
                    >
                      {{ senateur.groupe.nom }}
                    </Badge>
                    <span v-else class="text-gray-500 dark:text-gray-400 text-sm">Non inscrit</span>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                    {{ senateur.circonscription }}
                  </td>
                  <td class="px-4 py-4 text-right">
                    <Link
                      :href="route('representants.senateurs.show', senateur.matricule)"
                      class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition"
                    >
                      Voir la fiche
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="senateurs.links" class="mt-6 flex justify-center gap-2">
            <Link
              v-for="(link, index) in senateurs.links"
              :key="index"
              :href="link.url"
              v-html="link.label"
              :class="[
                'px-3 py-2 rounded text-sm',
                link.active 
                  ? 'bg-red-600 text-white' 
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600',
                !link.url && 'opacity-50 cursor-not-allowed'
              ]"
            />
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

