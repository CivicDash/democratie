<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  depute: Object,
  votes: Object,
  filters: Object,
  statistiques: Object,
});

const search = ref(props.filters.search || '');
const typeVote = ref(props.filters.type || '');

const applyFilters = () => {
  router.get(route('representants.deputes.votes', props.depute.uid), {
    search: search.value,
    type: typeVote.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getVoteColor = (position) => {
  const colors = {
    'pour': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'contre': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    'abstention': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    'non-votant': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
  };
  return colors[position] || colors['non-votant'];
};

const getVoteIcon = (position) => {
  const icons = {
    'pour': '✅',
    'contre': '❌',
    'abstention': '⚠️',
    'non-votant': '⭕',
  };
  return icons[position] || '❓';
};
</script>

<template>
  <Head :title="`Votes de ${depute.nom_complet}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('representants.mes-representants')" class="hover:text-blue-600">
            Mes Représentants
          </Link>
          <span>/</span>
          <Link :href="route('representants.deputes.index')" class="hover:text-blue-600">
            Députés
          </Link>
          <span>/</span>
          <Link :href="route('representants.deputes.show', depute.uid)" class="hover:text-blue-600">
            {{ depute.nom }}
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100">Votes</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-center gap-6">
            <div class="w-24 h-24 rounded-full overflow-hidden bg-white/10 flex-shrink-0">
              <img
                v-if="depute.photo_url"
                :src="depute.photo_url"
                :alt="depute.nom_complet"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-4xl">
                👤
              </div>
            </div>
            <div class="flex-1">
              <h1 class="text-4xl font-bold mb-2">🗳️ Votes de {{ depute.nom }}</h1>
              <p class="text-blue-100 text-lg">{{ depute.groupe?.nom || 'Non inscrit' }}</p>
            </div>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="grid md:grid-cols-4 gap-4">
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-blue-600">{{ statistiques.total }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total votes</div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-green-600">{{ statistiques.pour }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Pour</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ statistiques.pour_percent }}%
              </div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-red-600">{{ statistiques.contre }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Contre</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ statistiques.contre_percent }}%
              </div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-3xl font-bold text-yellow-600">{{ statistiques.abstention }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Abstentions</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ statistiques.abstention_percent }}%
              </div>
            </div>
          </Card>
        </div>

        <!-- Filtres -->
        <Card>
          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🔍 Rechercher un scrutin
              </label>
              <TextInput
                v-model="search"
                placeholder="Titre du scrutin..."
                @keyup.enter="applyFilters"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Type de vote
              </label>
              <select
                v-model="typeVote"
                @change="applyFilters"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
              >
                <option value="">Tous les votes</option>
                <option value="pour">Pour</option>
                <option value="contre">Contre</option>
                <option value="abstention">Abstention</option>
                <option value="non-votant">Non-votant</option>
              </select>
            </div>
            <div class="flex items-end">
              <button
                @click="search = ''; typeVote = ''; applyFilters()"
                class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
              >
                🔄 Réinitialiser
              </button>
            </div>
          </div>
        </Card>

        <!-- Liste des votes -->
        <Card>
          <div class="space-y-4">
            <div
              v-for="vote in votes.data"
              :key="vote.id"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-blue-400 dark:hover:border-blue-600 transition"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">{{ getVoteIcon(vote.position) }}</span>
                    <Badge :class="getVoteColor(vote.position)">
                      {{ vote.position.toUpperCase() }}
                    </Badge>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ vote.date }}
                    </span>
                  </div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    {{ vote.scrutin.titre }}
                  </h3>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    {{ vote.scrutin.objet }}
                  </div>
                  <div class="flex gap-4 text-xs text-gray-500 dark:text-gray-500">
                    <span>✅ Pour: {{ vote.scrutin.pour }}</span>
                    <span>❌ Contre: {{ vote.scrutin.contre }}</span>
                    <span>⚠️ Abstention: {{ vote.scrutin.abstention }}</span>
                  </div>
                </div>
                <Link
                  :href="route('legislation.scrutins.show', vote.scrutin.uid)"
                  class="flex-shrink-0 px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition"
                >
                  Voir le scrutin
                </Link>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="votes.links" class="mt-6 flex justify-center gap-2">
            <Link
              v-for="(link, index) in votes.links"
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

