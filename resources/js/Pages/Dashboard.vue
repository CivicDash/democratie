<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LevelProgressBar from '@/Components/LevelProgressBar.vue';
import StreakCounter from '@/Components/StreakCounter.vue';
import BadgeCard from '@/Components/BadgeCard.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  trendingTopics: Array,
  propositionsLegislatives: Array,
  votesEnCours: Array,
  // budgetStats: Object,  // Temporairement désactivé
  globalStats: Object,
  userActivity: Object,
});

const gamificationStats = ref(null);
const recentAchievements = ref([]);
const loadingGamification = ref(false); // Désactivé temporairement

// TEMPORAIREMENT DÉSACTIVÉ POUR DEBUG
/*
const loadGamification = async () => {
  try {
    const [statsRes, achievementsRes] = await Promise.all([
      axios.get('/api/gamification/my-stats'),
      axios.get('/api/gamification/recent-achievements'),
    ]);
    gamificationStats.value = statsRes.data.data.stats;
    recentAchievements.value = achievementsRes.data.data.slice(0, 3);
  } catch (error) {
    console.error('Error loading gamification:', error);
  } finally {
    loadingGamification.value = false;
  }
};

onMounted(() => {
  loadGamification();
});
*/

/**
 * Obtient la couleur du badge selon le type de topic
 */
const getTopicBadgeColor = (type) => {
  const colors = {
    'question': 'bg-blue-100 text-blue-800',
    'proposal': 'bg-green-100 text-green-800',
    'debate': 'bg-purple-100 text-purple-800',
    'announcement': 'bg-yellow-100 text-yellow-800',
  };
  return colors[type] || 'bg-gray-100 text-gray-800';
};

/**
 * Obtient l'icône du type de topic
 */
const getTopicIcon = (type) => {
  const icons = {
    'question': '❓',
    'proposal': '💡',
    'debate': '💬',
    'announcement': '📢',
  };
  return icons[type] || '📝';
};

/**
 * Obtient la couleur du badge de source
 */
const getSourceBadge = (source) => {
  return source === 'assemblee' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700';
};

/**
 * Score avec couleur
 */
const getScoreClass = (score) => {
  if (score > 50) return 'text-green-600';
  if (score > 0) return 'text-green-500';
  if (score < -50) return 'text-red-600';
  if (score < 0) return 'text-red-500';
  return 'text-gray-500';
};
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
          🏛️ Dashboard CivicDash
        </h2>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Bienvenue sur votre plateforme de démocratie participative
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
        
        <!-- 📊 STATISTIQUES GLOBALES -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-blue-100 text-sm font-medium">Sujets actifs</p>
                <p class="text-3xl font-bold mt-2">{{ globalStats.total_topics }}</p>
              </div>
              <div class="text-5xl opacity-20">💬</div>
            </div>
          </div>

          <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-green-100 text-sm font-medium">Votes exprimés</p>
                <p class="text-3xl font-bold mt-2">{{ globalStats.total_votes.toLocaleString() }}</p>
              </div>
              <div class="text-5xl opacity-20">🗳️</div>
            </div>
          </div>

          <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-purple-100 text-sm font-medium">Propositions de loi</p>
                <p class="text-3xl font-bold mt-2">{{ globalStats.total_propositions }}</p>
              </div>
              <div class="text-5xl opacity-20">🏛️</div>
            </div>
          </div>

          <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-orange-100 text-sm font-medium">Citoyens actifs</p>
                <p class="text-3xl font-bold mt-2">{{ globalStats.total_users_allocated }}</p>
              </div>
              <div class="text-5xl opacity-20">👥</div>
            </div>
          </div>
        </div>

        <!-- 🎮 GAMIFICATION SECTION - Temporairement désactivée pour debug -->
        <!--
        <div v-if="!loadingGamification && gamificationStats" class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl overflow-hidden border border-indigo-100">
          <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                  <span>🎮</span>
                  <span>Votre Progression</span>
                </h3>
                <p class="text-indigo-100 text-sm mt-1">Section gamification temporairement désactivée</p>
              </div>
            </div>
          </div>
        </div>
        -->

        <!-- CONTENU PRINCIPAL -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- COLONNE GAUCHE (2/3) -->
          <div class="lg:col-span-2 space-y-6">
            
            <!-- 🔥 SUJETS TENDANCES -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
              <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                  <span>🔥</span>
                  <span>Sujets Tendances</span>
                </h3>
                <p class="text-blue-100 text-sm mt-1">Les discussions les plus populaires</p>
              </div>
              
              <div class="p-6">
                <div v-if="trendingTopics.length === 0" class="text-center py-8 text-gray-500">
                  Aucun sujet pour le moment
                </div>
                
                <div v-else class="space-y-4">
                  <Link
                    v-for="topic in trendingTopics"
                    :key="topic.id"
                    :href="`/topics/${topic.id}`"
                    class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 hover:shadow-md transition-all duration-200"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <span class="text-2xl">{{ getTopicIcon(topic.type) }}</span>
                          <span :class="`px-3 py-1 rounded-full text-xs font-semibold ${getTopicBadgeColor(topic.type)}`">
                            {{ topic.type }}
                          </span>
                          <span class="text-xs text-gray-500">{{ topic.scope }}</span>
                        </div>
                        
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                          {{ topic.titre }}
                        </h4>
                        
                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                          <span>👤 {{ topic.auteur }}</span>
                          <span>📍 {{ topic.territoire }}</span>
                          <span>💬 {{ topic.nb_posts }} réponses</span>
                          <span>👁️ {{ topic.nb_vues }} vues</span>
                        </div>
                      </div>
                      
                      <div class="text-xs text-gray-500">
                        {{ topic.created_at }}
                      </div>
                    </div>
                  </Link>
                </div>
                
                <div class="mt-4 text-center">
                  <Link href="/topics" class="text-blue-600 hover:text-blue-700 font-semibold text-sm">
                    Voir tous les sujets →
                  </Link>
                </div>
              </div>
            </div>

            <!-- 🏛️ PROPOSITIONS DE LOI TENDANCES -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
              <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                  <span>🏛️</span>
                  <span>Propositions de Loi Populaires</span>
                </h3>
                <p class="text-purple-100 text-sm mt-1">Votez pour exprimer votre avis</p>
              </div>
              
              <div class="p-6">
                <div v-if="propositionsLegislatives.length === 0" class="text-center py-8 text-gray-500">
                  Aucune proposition pour le moment
                </div>
                
                <div v-else class="space-y-4">
                  <div
                    v-for="prop in propositionsLegislatives"
                    :key="prop.id"
                    class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-purple-500 hover:shadow-md transition-all duration-200"
                  >
                    <div class="flex items-start justify-between gap-4 mb-3">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <span :class="`px-3 py-1 rounded-full text-xs font-semibold ${getSourceBadge(prop.source)}`">
                            {{ prop.source === 'assemblee' ? '🏛️ Assemblée' : '🏛️ Sénat' }}
                          </span>
                          <span class="text-xs text-gray-600 dark:text-gray-400">
                            N° {{ prop.numero }}
                          </span>
                          <span v-if="prop.date_depot" class="text-xs text-gray-500">
                            📅 {{ prop.date_depot }}
                          </span>
                        </div>
                        
                        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                          {{ prop.titre }}
                        </h4>
                      </div>
                    </div>
                    
                    <!-- Stats de vote -->
                    <div class="flex items-center gap-6 text-sm">
                      <div class="flex items-center gap-2">
                        <span>👍</span>
                        <span class="font-semibold text-green-600">{{ prop.votes_stats.upvotes }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span>👎</span>
                        <span class="font-semibold text-red-600">{{ prop.votes_stats.downvotes }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span>Score:</span>
                        <span :class="`font-bold ${getScoreClass(prop.votes_stats.score)}`">
                          {{ prop.votes_stats.score > 0 ? '+' : '' }}{{ prop.votes_stats.score }}
                        </span>
                      </div>
                      
                      <!-- Barre de progression -->
                      <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div 
                          class="h-full bg-gradient-to-r from-green-500 to-green-600"
                          :style="{ width: prop.votes_stats.pourcentage_pour + '%' }"
                        ></div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="mt-4 text-center">
                  <a href="/api/legislation/propositions" class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                    Voir toutes les propositions →
                  </a>
                </div>
              </div>
            </div>

          </div>

          <!-- COLONNE DROITE (1/3) -->
          <div class="space-y-6">
            
            <!-- 🗳️ VOTES EN COURS -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
              <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                  <span>🗳️</span>
                  <span>Votes en Cours</span>
                </h3>
              </div>
              
              <div class="p-6">
                <div v-if="votesEnCours.length === 0" class="text-center py-8 text-gray-500 text-sm">
                  Aucun vote en cours
                </div>
                
                <div v-else class="space-y-3">
                  <Link
                    v-for="vote in votesEnCours"
                    :key="vote.id"
                    :href="`/topics/${vote.topic_id}`"
                    class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-green-500 hover:shadow-md transition-all"
                  >
                    <div class="flex items-start gap-2 mb-2">
                      <span v-if="vote.a_vote" class="text-green-600 text-xl" title="Vous avez déjà voté">✓</span>
                      <span v-else class="text-orange-500 text-xl" title="Vous n'avez pas encore voté">⏳</span>
                      <div class="flex-1">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">
                          {{ vote.question }}
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                          {{ vote.topic_titre }}
                        </p>
                        <div class="flex items-center justify-between text-xs">
                          <span class="text-gray-500">Fin: {{ vote.fin }}</span>
                          <span class="font-semibold text-green-600">{{ vote.total_votes }} votes</span>
                        </div>
                      </div>
                    </div>
                  </Link>
                </div>
              </div>
            </div>

            <!-- 💰 VOTRE BUDGET - Temporairement désactivé -->
            <!--
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
              <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                  <span>💰</span>
                  <span>Votre Budget</span>
                </h3>
              </div>
              
              <div class="p-6">
                <p class="text-center text-gray-600">Section budget temporairement désactivée</p>
              </div>
            </div>
            -->

            <!-- 🎯 VOTRE ACTIVITÉ RÉCENTE -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
              <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                  <span>🎯</span>
                  <span>Activité Récente</span>
                </h3>
              </div>
              
              <div class="p-6">
                <div v-if="userActivity.derniers_topics.length > 0" class="mb-4">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vos sujets</h4>
                  <div class="space-y-2">
                    <Link
                      v-for="topic in userActivity.derniers_topics"
                      :key="topic.id"
                      :href="`/topics/${topic.id}`"
                      class="block text-sm p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                      <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ topic.titre }}</p>
                      <p class="text-xs text-gray-500">{{ topic.date }}</p>
                    </Link>
                  </div>
                </div>
                
                <div v-if="userActivity.derniers_votes_loi.length > 0">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vos votes législatifs</h4>
                  <div class="space-y-2">
                    <div
                      v-for="vote in userActivity.derniers_votes_loi"
                      :key="vote.id"
                      class="text-sm p-2 rounded bg-gray-50 dark:bg-gray-700"
                    >
                      <div class="flex items-center gap-2 mb-1">
                        <span>{{ vote.type_vote === 'upvote' ? '👍' : '👎' }}</span>
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                          N° {{ vote.numero }}
                        </span>
                      </div>
                      <p class="text-xs text-gray-900 dark:text-gray-100 truncate">{{ vote.titre }}</p>
                      <p class="text-xs text-gray-500 mt-1">{{ vote.date }}</p>
                    </div>
                  </div>
                </div>
                
                <div v-if="userActivity.derniers_topics.length === 0 && userActivity.derniers_votes_loi.length === 0" class="text-center py-8 text-gray-500 text-sm">
                  Aucune activité récente
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Animations supplémentaires si besoin */
</style>
