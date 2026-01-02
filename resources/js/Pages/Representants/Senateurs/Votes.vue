<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  senateur: Object,
  votes: Object,
  filters: Object,
  statistiques: Object,
});

const breadcrumbs = [
  { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
  { label: 'Sénateurs', href: route('representants.senateurs.index'), icon: '🏛️' },
  { label: props.senateur.nom_usuel, href: route('representants.senateurs.show', props.senateur.id) },
  { label: 'Votes', current: true, icon: '🗳️' },
];

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
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      
      <!-- Hero Section Full Width -->
      <section class="relative overflow-hidden bg-gradient-to-br from-rose-900 via-pink-800 to-fuchsia-900">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
          <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        
        <div class="relative w-full px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
          <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
          
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="flex items-center gap-6">
              <div class="w-20 h-20 rounded-full overflow-hidden bg-white/20 flex-shrink-0 ring-4 ring-white/30">
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
              <div>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                  🗳️ Votes de {{ senateur.nom_usuel }}
                </h1>
                <p class="text-rose-200 text-lg">{{ senateur.groupe_politique || 'Non inscrit' }}</p>
              </div>
            </div>
            
            <!-- Stats rapides Hero -->
            <div class="flex flex-wrap gap-4">
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-white">{{ statistiques.total }}</div>
                <div class="text-rose-200 text-xs uppercase tracking-wide">Total</div>
              </div>
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-emerald-400">{{ statistiques.pour }}</div>
                <div class="text-rose-200 text-xs uppercase tracking-wide">Pour</div>
              </div>
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-rose-400">{{ statistiques.contre }}</div>
                <div class="text-rose-200 text-xs uppercase tracking-wide">Contre</div>
              </div>
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-amber-400">{{ statistiques.abstention }}</div>
                <div class="text-rose-200 text-xs uppercase tracking-wide">Abstention</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Content -->
      <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">

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
                  <div v-if="vote.scrutin" class="flex gap-4 text-xs text-gray-500 dark:text-gray-500 mt-2">
                    <span class="flex items-center gap-1">
                      <span class="text-green-600">✅</span>
                      Pour: <strong>{{ vote.scrutin.pour || 0 }}</strong>
                    </span>
                    <span class="flex items-center gap-1">
                      <span class="text-red-600">❌</span>
                      Contre: <strong>{{ vote.scrutin.contre || 0 }}</strong>
                    </span>
                    <span v-if="vote.scrutin.votants" class="flex items-center gap-1">
                      <span>🗳️</span>
                      Votants: <strong>{{ vote.scrutin.votants }}</strong>
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

