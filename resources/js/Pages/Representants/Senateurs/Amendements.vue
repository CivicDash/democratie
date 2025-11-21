<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  senateur: Object,
  amendements: Object,
  filters: Object,
  statistiques: Object,
});

const search = ref(props.filters.search || '');
const sort = ref(props.filters.sort || '');

const applyFilters = () => {
  router.get(route('representants.senateurs.amendements', props.senateur.id), {
    search: search.value,
    sort: sort.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getSortBadgeClass = (sortCode) => {
  if (sortCode === 'ADO') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
  if (sortCode === 'REJ') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
  if (sortCode === 'RET') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
  return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
};
</script>

<template>
  <Head :title="`Amendements de ${senateur.nom_complet}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="max-w-7xl mx-auto px-4 sm:px-0 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 transition font-medium">
          <Link :href="route('representants.mes-representants')" class="hover:text-blue-600">
            Mes Représentants
          </Link>
          <span>/</span>
          <Link :href="route('representants.senateurs.index')" class="hover:text-blue-600">
            Sénateurs
          </Link>
          <span>/</span>
          <Link :href="route('representants.senateurs.show', senateur.id)" class="hover:text-blue-600">
            {{ senateur.nom_usuel }}
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100">Amendements</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-green-700 to-teal-700 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-center gap-6">
            <div class="w-24 h-24 rounded-full overflow-hidden bg-white/10 flex-shrink-0">
              <img
                v-if="senateur.photo_wikipedia_url"
                :src="senateur.photo_wikipedia_url"
                :alt="senateur.nom_complet"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-4xl">
                👤
              </div>
            </div>
            <div class="flex-1">
              <h1 class="text-4xl font-bold mb-2">📝 Amendements de {{ senateur.nom_usuel }}</h1>
              <p class="text-green-100 text-lg">{{ senateur.groupe_politique || 'Non inscrit' }}</p>
            </div>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="grid md:grid-cols-4 gap-4">
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-green-600">{{ statistiques.total }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total amendements</div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-blue-600">{{ statistiques.adoptes }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Adoptés</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ statistiques.taux_adoption }}%
              </div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-red-600">{{ statistiques.rejetes }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Rejetés</div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-yellow-600">{{ statistiques.retires }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Retirés</div>
            </div>
          </Card>
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
                placeholder="Numéro, dispositif, exposé..."
                @keyup.enter="applyFilters"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Statut
              </label>
              <select
                v-model="sort"
                @change="applyFilters"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
              >
                <option value="">Tous les amendements</option>
                <option value="ADO">Adoptés</option>
                <option value="REJ">Rejetés</option>
                <option value="RET">Retirés</option>
              </select>
            </div>
            <div class="flex items-end">
              <button
                @click="search = ''; sort = ''; applyFilters()"
                class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
              >
                🔄 Réinitialiser
              </button>
            </div>
          </div>
        </Card>

        <!-- Liste des amendements -->
        <Card>
          <div class="space-y-4">
            <div
              v-for="amendement in amendements.data"
              :key="amendement.id"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-green-400 dark:hover:border-green-600 transition"
            >
              <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                  <span class="text-2xl">📝</span>
                  <div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                      Amendement n°{{ amendement.numero }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">
                      {{ amendement.date_depot }}
                    </div>
                  </div>
                </div>
                <Badge :class="getSortBadgeClass(amendement.sort_code)">
                  {{ amendement.sort_libelle || amendement.sort_code }}
                </Badge>
              </div>
              
              <div v-if="amendement.dispositif" class="text-sm text-gray-700 dark:text-gray-300 mb-3 pl-10">
                <strong>Dispositif :</strong> {{ amendement.dispositif }}
              </div>
              
              <div v-if="amendement.expose" class="text-sm text-gray-600 dark:text-gray-400 mb-3 pl-10">
                <strong>Exposé :</strong> {{ amendement.expose }}
              </div>
              
              <div class="flex gap-4 text-xs text-gray-500 dark:text-gray-500 pl-10">
                <span v-if="amendement.type_amendement">Type : {{ amendement.type_amendement }}</span>
                <span v-if="amendement.texte_nom">Texte : {{ amendement.texte_nom }}</span>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="amendements.links" class="mt-6 flex justify-center gap-2">
            <Link
              v-for="(link, index) in amendements.links"
              :key="index"
              :href="link.url"
              v-html="link.label"
              :class="[
                'px-3 py-2 rounded text-sm',
                link.active 
                  ? 'bg-green-600 text-white' 
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

