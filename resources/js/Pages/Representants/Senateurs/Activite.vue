<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
  senateur: Object,
  statistiques: Object,
  derniers_votes: Array,
  derniers_amendements: Array,
});

// Calcul du pourcentage pour les barres de progression
const getPercentage = (value, total) => {
  return total > 0 ? Math.round((value / total) * 100) : 0;
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

const getSortBadgeClass = (sortCode) => {
  if (sortCode === 'ADO') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
  if (sortCode === 'REJ') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
  if (sortCode === 'RET') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
  return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
};
</script>

<template>
  <Head :title="`Activité de ${senateur.nom_complet}`" />

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
          <span class="text-gray-900 dark:text-gray-100">Activité</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-700 to-pink-700 rounded-xl shadow-lg p-8 text-white">
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
              <h1 class="text-4xl font-bold mb-2">📊 Activité de {{ senateur.nom_usuel }}</h1>
              <p class="text-purple-100 text-lg">{{ senateur.groupe_politique || 'Non inscrit' }}</p>
            </div>
          </div>
        </div>

        <!-- Statistiques globales -->
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Votes -->
          <Card>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>🗳️</span>
              <span>Votes</span>
            </h3>
            <div class="space-y-3">
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-600 dark:text-gray-400">Pour</span>
                  <span class="font-semibold text-green-600">{{ statistiques.votes.pour }} ({{ getPercentage(statistiques.votes.pour, statistiques.votes.total) }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="bg-green-600 h-2 rounded-full transition-all"
                    :style="{ width: getPercentage(statistiques.votes.pour, statistiques.votes.total) + '%' }"
                  ></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-600 dark:text-gray-400">Contre</span>
                  <span class="font-semibold text-red-600">{{ statistiques.votes.contre }} ({{ getPercentage(statistiques.votes.contre, statistiques.votes.total) }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="bg-red-600 h-2 rounded-full transition-all"
                    :style="{ width: getPercentage(statistiques.votes.contre, statistiques.votes.total) + '%' }"
                  ></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-600 dark:text-gray-400">Abstention</span>
                  <span class="font-semibold text-yellow-600">{{ statistiques.votes.abstention }} ({{ getPercentage(statistiques.votes.abstention, statistiques.votes.total) }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="bg-yellow-600 h-2 rounded-full transition-all"
                    :style="{ width: getPercentage(statistiques.votes.abstention, statistiques.votes.total) + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Amendements -->
          <Card>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>📝</span>
              <span>Amendements</span>
            </h3>
            <div class="space-y-3">
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-600 dark:text-gray-400">Adoptés</span>
                  <span class="font-semibold text-blue-600">{{ statistiques.amendements.adoptes }} ({{ statistiques.amendements.taux_adoption }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="bg-blue-600 h-2 rounded-full transition-all"
                    :style="{ width: statistiques.amendements.taux_adoption + '%' }"
                  ></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-600 dark:text-gray-400">Rejetés</span>
                  <span class="font-semibold text-red-600">{{ statistiques.amendements.rejetes }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="bg-red-600 h-2 rounded-full transition-all"
                    :style="{ width: getPercentage(statistiques.amendements.rejetes, statistiques.amendements.total) + '%' }"
                  ></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-600 dark:text-gray-400">Retirés</span>
                  <span class="font-semibold text-yellow-600">{{ statistiques.amendements.retires }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="bg-yellow-600 h-2 rounded-full transition-all"
                    :style="{ width: getPercentage(statistiques.amendements.retires, statistiques.amendements.total) + '%' }"
                  ></div>
                </div>
              </div>
              <div class="text-center pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ statistiques.amendements.total }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Total amendements</div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Derniers votes -->
        <Card>
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>🗳️</span>
              <span>5 derniers votes</span>
            </h3>
            <Link
              :href="route('representants.senateurs.votes', senateur.id)"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
            >
              Voir tous les votes →
            </Link>
          </div>
          <div class="space-y-3">
            <div
              v-for="vote in derniers_votes"
              :key="vote.id"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:border-blue-400 dark:hover:border-blue-600 transition"
            >
              <div class="flex items-start gap-3">
                <span class="text-xl">{{ getVoteIcon(vote.position) }}</span>
                <div class="flex-1">
                  <div class="font-semibold text-gray-900 dark:text-gray-100">{{ vote.intitule }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ vote.date_vote }}</div>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Derniers amendements -->
        <Card>
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>📝</span>
              <span>5 derniers amendements</span>
            </h3>
            <Link
              :href="route('representants.senateurs.amendements', senateur.id)"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
            >
              Voir tous les amendements →
            </Link>
          </div>
          <div class="space-y-3">
            <div
              v-for="amendement in derniers_amendements"
              :key="amendement.id"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:border-green-400 dark:hover:border-green-600 transition"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 flex-1">
                  <span class="text-xl">📝</span>
                  <div class="flex-1">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                      Amendement n°{{ amendement.numero }}
                    </div>
                    <div v-if="amendement.dispositif" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ amendement.dispositif }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ amendement.date_depot }}</div>
                  </div>
                </div>
                <Badge :class="getSortBadgeClass(amendement.sort_code)">
                  {{ amendement.sort_libelle || amendement.sort_code }}
                </Badge>
              </div>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

