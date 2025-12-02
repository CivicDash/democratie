<script setup>
import { ref } from 'vue';
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
</script>

<template>
  <Head title="Sénateurs - Sénat" />

  <AuthenticatedLayout>
    <div class="py-4 sm:py-8">
      <div class="px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6 max-w-full mx-auto">
        
        <!-- Header - Responsive -->
        <div class="bg-gradient-to-r from-red-700 to-red-800 rounded-xl shadow-lg p-4 sm:p-8 text-white">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl sm:text-4xl font-bold mb-1 sm:mb-2">🏛️ Sénat</h1>
              <p class="text-red-100 text-sm sm:text-lg">348 Sénateurs</p>
            </div>
            <div class="text-left sm:text-right">
              <div class="text-2xl sm:text-3xl font-bold">{{ senateurs.total || senateurs.data?.length || 0 }}</div>
              <div class="text-red-200 text-xs sm:text-sm">sénateurs affichés</div>
            </div>
          </div>
        </div>

        <!-- Filtres - Responsive -->
        <Card>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🔍 Rechercher
              </label>
              <TextInput
                v-model="search"
                placeholder="Nom, prénom..."
                @keyup.enter="applyFilters"
                class="w-full min-h-[44px]"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🎨 Groupe parlementaire
              </label>
              <select
                v-model="selectedGroupe"
                @change="applyFilters"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 min-h-[44px]"
              >
                <option value="">Tous les groupes</option>
                <option v-for="groupe in props.groupes" :key="groupe.nom" :value="groupe.nom">
                  {{ groupe.nom }}
                </option>
              </select>
            </div>
            <div class="flex items-end sm:col-span-2 lg:col-span-1">
              <button
                @click="search = ''; selectedGroupe = ''; applyFilters()"
                class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition min-h-[44px]"
              >
                🔄 Réinitialiser
              </button>
            </div>
          </div>
        </Card>

        <!-- Liste des sénateurs -->
        <Card>
          <!-- Vue Desktop : Table -->
          <div class="hidden lg:block overflow-x-auto">
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
                          v-if="senateur.wikipedia?.photo"
                          :src="senateur.wikipedia.photo"
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

          <!-- Vue Mobile : Cards -->
          <div class="lg:hidden space-y-4">
            <Link
              v-for="senateur in senateurs.data"
              :key="senateur.id"
              :href="route('representants.senateurs.show', senateur.matricule)"
              class="block p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition active:bg-gray-50 dark:active:bg-gray-700"
            >
              <div class="flex items-start gap-4">
                <!-- Photo -->
                <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                  <img
                    v-if="senateur.wikipedia?.photo"
                    :src="senateur.wikipedia.photo"
                    :alt="senateur.nom_complet"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-2xl">
                    👤
                  </div>
                </div>
                
                <!-- Infos -->
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                    {{ senateur.nom_complet }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ senateur.profession || 'Profession non renseignée' }}
                  </p>
                  
                  <div class="flex flex-wrap items-center gap-2 mt-2">
                    <Badge
                      v-if="senateur.groupe"
                      :style="{ 
                        backgroundColor: senateur.groupe.couleur || '#6B7280',
                        color: '#fff'
                      }"
                      class="text-xs"
                    >
                      {{ senateur.groupe.nom }}
                    </Badge>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      📍 {{ senateur.circonscription }}
                    </span>
                  </div>
                </div>
                
                <!-- Chevron -->
                <div class="flex-shrink-0 text-gray-400">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            </Link>
          </div>

          <!-- Pagination - Responsive -->
          <div v-if="senateurs.links" class="mt-6 flex flex-wrap justify-center gap-1 sm:gap-2">
            <Link
              v-for="(link, index) in senateurs.links"
              :key="index"
              :href="link.url"
              v-html="link.label"
              :class="[
                'px-2 sm:px-3 py-2 rounded text-xs sm:text-sm min-h-[44px] flex items-center justify-center',
                link.active 
                  ? 'bg-red-600 text-white' 
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600',
                !link.url && 'opacity-50 cursor-not-allowed pointer-events-none'
              ]"
            />
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
