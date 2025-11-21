<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  senateur: Object,
  votes: Object,
  filters: Object,
  statistiques: Object,
});

const search = ref(props.filters.search || '');
const typeVote = ref(props.filters.type || '');

const applyFilters = () => {
  router.get(route('representants.senateurs.votes', props.senateur.id), {
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
    'non_votant': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
  };
  return colors[position] || colors['non_votant'];
};

const getVoteIcon = (position) => {
  const icons = {
    'pour': '✅',
    'contre': '❌',
    'abstention': '⚠️',
    'non_votant': '⭕',
  };
  return icons[position] || '❓';
};
</script>

<template>
  <Head :title="`Votes de ${senateur.nom_complet}`" />

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
          <span class="text-gray-900 dark:text-gray-100">Votes</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-red-700 to-pink-700 rounded-xl shadow-lg p-8 text-white">
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
              <h1 class="text-4xl font-bold mb-2">🗳️ Votes de {{ senateur.nom_usuel }}</h1>
              <p class="text-red-100 text-lg">{{ senateur.groupe_politique || 'Non inscrit' }}</p>
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
                placeholder="Intitulé du scrutin..."
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
                <option value="non_votant">Non-votant</option>
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
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-red-400 dark:hover:border-red-600 transition"
            >
              <div class="flex items-start gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">{{ getVoteIcon(vote.position) }}</span>
                    <Badge :class="getVoteColor(vote.position)">
                      {{ vote.position.replace('_', '-').toUpperCase() }}
                    </Badge>
                    <Badge 
                      :class="vote.resultat_scrutin === 'Adopté' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                        : vote.resultat_scrutin === 'Rejeté' 
                          ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'"
                    >
                      {{ vote.resultat_scrutin }}
                    </Badge>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ vote.date_vote }}
                    </span>
                  </div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    {{ vote.intitule }}
                  </h3>
                  <div v-if="vote.intitule_complet" class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    {{ vote.intitule_complet }}
                  </div>
                  <div v-if="vote.scrutin_pour !== undefined" class="flex gap-4 text-xs text-gray-500 dark:text-gray-500 mt-2">
                    <span class="flex items-center gap-1">
                      <span class="text-green-600">✅</span>
                      Pour: <strong>{{ vote.scrutin_pour || 0 }}</strong>
                    </span>
                    <span class="flex items-center gap-1">
                      <span class="text-red-600">❌</span>
                      Contre: <strong>{{ vote.scrutin_contre || 0 }}</strong>
                    </span>
                    <span v-if="vote.scrutin_votants" class="flex items-center gap-1">
                      <span>🗳️</span>
                      Votants: <strong>{{ vote.scrutin_votants }}</strong>
                    </span>
                  </div>
                </div>
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

