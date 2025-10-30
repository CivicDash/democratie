<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PropositionVote from '@/Components/PropositionVote.vue';
import { ref, computed } from 'vue';

const props = defineProps({
  propositions: Object,
  trending: Array,
  filters: Object,
});

const selectedSource = ref(props.filters?.source || '');
const selectedStatut = ref(props.filters?.statut || '');
const selectedTheme = ref(props.filters?.theme || '');
const searchQuery = ref(props.filters?.search || '');

/**
 * Apply filters
 */
const applyFilters = () => {
  router.get('/legislation', {
    source: selectedSource.value || undefined,
    statut: selectedStatut.value || undefined,
    theme: selectedTheme.value || undefined,
    search: searchQuery.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

/**
 * Clear filters
 */
const clearFilters = () => {
  selectedSource.value = '';
  selectedStatut.value = '';
  selectedTheme.value = '';
  searchQuery.value = '';
  applyFilters();
};

/**
 * Get source badge color
 */
const getSourceBadge = (source) => {
  return source === 'assemblee' 
    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
};

/**
 * Get source icon
 */
const getSourceIcon = (source) => {
  return source === 'assemblee' ? '🏛️' : '🏰';
};

/**
 * Get statut badge color
 */
const getStatutBadge = (statut) => {
  const badges = {
    'depot': 'bg-gray-100 text-gray-800',
    'discussion': 'bg-yellow-100 text-yellow-800',
    'vote': 'bg-orange-100 text-orange-800',
    'adopte': 'bg-green-100 text-green-800',
    'rejete': 'bg-red-100 text-red-800',
    'promulgue': 'bg-purple-100 text-purple-800',
  };
  return badges[statut] || 'bg-gray-100 text-gray-800';
};

/**
 * Get statut label
 */
const getStatutLabel = (statut) => {
  const labels = {
    'depot': 'Déposé',
    'discussion': 'En discussion',
    'vote': 'En vote',
    'adopte': 'Adopté',
    'rejete': 'Rejeté',
    'promulgue': 'Promulgué',
  };
  return labels[statut] || statut;
};

/**
 * Has active filters
 */
const hasActiveFilters = computed(() => {
  return selectedSource.value || selectedStatut.value || selectedTheme.value || searchQuery.value;
});
</script>

<template>
  <Head title="Législation" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            🏛️ Législation
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Suivez les propositions de loi de l'Assemblée Nationale et du Sénat
          </p>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ propositions.total }} proposition(s)
          </p>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
        
        <!-- Trending Propositions -->
        <div v-if="trending && trending.length > 0" class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-2 mb-4">
            <span class="text-2xl">🔥</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Propositions tendances
            </h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Link
              v-for="prop in trending.slice(0, 3)"
              :key="prop.id"
              :href="`/legislation/${prop.id}`"
              class="bg-white dark:bg-gray-800 rounded-xl p-4 hover:shadow-lg transition group"
            >
              <div class="flex items-start justify-between mb-2">
                <span :class="`px-2 py-1 rounded-full text-xs font-semibold ${getSourceBadge(prop.source)}`">
                  {{ getSourceIcon(prop.source) }} {{ prop.source === 'assemblee' ? 'AN' : 'Sénat' }}
                </span>
                <span class="text-xs text-gray-500">N° {{ prop.numero }}</span>
              </div>
              <h4 class="font-semibold text-gray-900 dark:text-gray-100 line-clamp-2 group-hover:text-blue-600 transition">
                {{ prop.titre }}
              </h4>
              <div class="mt-3 flex items-center gap-3 text-sm">
                <span class="text-green-600 dark:text-green-400">👍 {{ prop.upvotes || 0 }}</span>
                <span class="text-red-600 dark:text-red-400">👎 {{ prop.downvotes || 0 }}</span>
                <span class="text-gray-500">Score: {{ prop.score || 0 }}</span>
              </div>
            </Link>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              🔍 Filtres
            </h3>
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium"
            >
              Réinitialiser
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Rechercher
              </label>
              <input
                v-model="searchQuery"
                @keyup.enter="applyFilters"
                type="text"
                placeholder="Mots-clés..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none"
              />
            </div>

            <!-- Source -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Source
              </label>
              <select
                v-model="selectedSource"
                @change="applyFilters"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              >
                <option value="">Toutes</option>
                <option value="assemblee">Assemblée Nationale</option>
                <option value="senat">Sénat</option>
              </select>
            </div>

            <!-- Statut -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Statut
              </label>
              <select
                v-model="selectedStatut"
                @change="applyFilters"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              >
                <option value="">Tous</option>
                <option value="depot">Déposé</option>
                <option value="discussion">En discussion</option>
                <option value="vote">En vote</option>
                <option value="adopte">Adopté</option>
                <option value="rejete">Rejeté</option>
                <option value="promulgue">Promulgué</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Propositions List -->
        <div class="space-y-4">
          <div
            v-for="proposition in propositions.data"
            :key="proposition.id"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition overflow-hidden"
          >
            <div class="p-6">
              <!-- Header -->
              <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                  <span :class="`px-3 py-1 rounded-full text-sm font-semibold ${getSourceBadge(proposition.source)}`">
                    {{ getSourceIcon(proposition.source) }} {{ proposition.source === 'assemblee' ? 'Assemblée' : 'Sénat' }}
                  </span>
                  <span :class="`px-3 py-1 rounded-full text-sm font-semibold ${getStatutBadge(proposition.statut)}`">
                    {{ getStatutLabel(proposition.statut) }}
                  </span>
                  <span class="text-sm text-gray-500 dark:text-gray-400">
                    N° {{ proposition.numero }}
                  </span>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ proposition.date_depot }}
                </span>
              </div>

              <!-- Title -->
              <Link
                :href="`/legislation/${proposition.id}`"
                class="group"
              >
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition mb-3">
                  {{ proposition.titre }}
                </h3>
              </Link>

              <!-- Description -->
              <p v-if="proposition.resume" class="text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                {{ proposition.resume }}
              </p>

              <!-- Footer -->
              <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-6 text-sm">
                  <div v-if="proposition.nb_amendements" class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                    <span>📝</span>
                    <span>{{ proposition.nb_amendements }} amendement(s)</span>
                  </div>
                  <div v-if="proposition.theme" class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                    <span>🏷️</span>
                    <span>{{ proposition.theme }}</span>
                  </div>
                </div>

                <!-- Votes citoyens -->
                <PropositionVote
                  :proposition-id="proposition.id"
                  :show-details="false"
                  :can-vote="true"
                  class="flex-shrink-0"
                />
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="propositions.data.length === 0" class="text-center py-12">
            <div class="text-6xl mb-4">🏛️</div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
              Aucune proposition trouvée
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Essayez de modifier vos filtres
            </p>
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
              Réinitialiser les filtres
            </button>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="propositions.last_page > 1" class="flex items-center justify-center gap-2">
          <Link
            v-for="page in propositions.links"
            :key="page.label"
            :href="page.url"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition',
              page.active
                ? 'bg-blue-600 text-white'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
              !page.url && 'opacity-50 cursor-not-allowed'
            ]"
            v-html="page.label"
          />
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

