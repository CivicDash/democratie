<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';
import HemicycleView from '@/Components/HemicycleView.vue';

const props = defineProps({
  deputes: Object,
  groupes: Array,
  filters: Object,
});

const viewMode = ref('list'); // 'list' ou 'hemicycle'
const search = ref(props.filters.search || '');
const selectedGroupe = ref(props.filters.groupe || '');

const applyFilters = () => {
  router.get(route('representants.deputes.index'), {
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
  props.deputes.data.forEach(depute => {
    const sigle = depute.groupe_sigle || 'NI';
    counts[sigle] = (counts[sigle] || 0) + 1;
  });
  return counts;
});
</script>

<template>
  <Head title="Députés - Assemblée Nationale" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-4xl font-bold mb-2">🏛️ Assemblée Nationale</h1>
              <p class="text-blue-100 text-lg">577 Députés - 17ème législature</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="viewMode = 'list'"
                :class="[
                  'px-4 py-2 rounded-lg font-medium transition',
                  viewMode === 'list' 
                    ? 'bg-white text-blue-700' 
                    : 'bg-blue-600 text-white hover:bg-blue-500'
                ]"
              >
                📋 Liste
              </button>
              <button
                @click="viewMode = 'hemicycle'"
                :class="[
                  'px-4 py-2 rounded-lg font-medium transition',
                  viewMode === 'hemicycle' 
                    ? 'bg-white text-blue-700' 
                    : 'bg-blue-600 text-white hover:bg-blue-500'
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
          <HemicycleView
            :deputes="deputes.data"
            :groupes="props.groupes"
            :selectedGroupe="selectedGroupe"
            @select-depute="(depute) => router.visit(route('representants.deputes.show', depute.id))"
            @select-groupe="(sigle) => { selectedGroupe = sigle; applyFilters(); }"
          />
        </Card>

        <!-- Vue Liste -->
        <Card v-else>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Député
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Groupe
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Trigramme
                  </th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="depute in deputes.data"
                  :key="depute.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                        <img
                          v-if="depute.photo_url"
                          :src="depute.photo_url"
                          :alt="depute.nom_complet"
                          class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-xl">
                          👤
                        </div>
                      </div>
                      <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                          {{ depute.nom_complet }}
                          <a
                            v-if="depute.wikipedia_url"
                            :href="depute.wikipedia_url"
                            target="_blank"
                            class="text-blue-500 hover:text-blue-700"
                            title="Voir sur Wikipedia"
                          >
                            📖
                          </a>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ depute.profession || 'Profession non renseignée' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <Badge
                      v-if="depute.groupe"
                      :style="{ 
                        backgroundColor: depute.groupe.couleur || '#6B7280',
                        color: '#fff'
                      }"
                    >
                      {{ depute.groupe.sigle }}
                    </Badge>
                    <span v-else class="text-gray-500 dark:text-gray-400 text-sm">Non inscrit</span>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                    {{ depute.trigramme || '-' }}
                  </td>
                  <td class="px-4 py-4 text-right">
                    <Link
                      :href="route('representants.deputes.show', depute.uid)"
                      class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition"
                    >
                      Voir la fiche
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="deputes.links" class="mt-6 flex justify-center gap-2">
            <Link
              v-for="(link, index) in deputes.links"
              :key="index"
              :href="link.url"
              v-html="link.label"
              :class="[
                'px-3 py-2 rounded text-sm',
                link.active 
                  ? 'bg-blue-600 text-white' 
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

