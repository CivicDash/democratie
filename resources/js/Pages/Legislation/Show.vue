<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LegislativeTimeline from '@/Components/LegislativeTimeline.vue';
import LegislationSimilar from '@/Components/LegislationSimilar.vue';
import PropositionVote from '@/Components/PropositionVote.vue';
import { ref } from 'vue';

const props = defineProps({
  proposition: Object,
  amendements: Array,
  votes: Array,
  similar: Array,
});

const activeTab = ref('details');

/**
 * Get source badge
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
 * Get statut badge
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
 * Format date
 */
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
};
</script>

<template>
  <Head :title="`${proposition.titre} - Législation`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-4">
        <Link
          href="/legislation"
          class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition"
        >
          ← Retour
        </Link>
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-2">
            <span :class="`px-3 py-1 rounded-full text-sm font-semibold ${getSourceBadge(proposition.source)}`">
              {{ getSourceIcon(proposition.source) }} {{ proposition.source === 'assemblee' ? 'Assemblée Nationale' : 'Sénat' }}
            </span>
            <span :class="`px-3 py-1 rounded-full text-sm font-semibold ${getStatutBadge(proposition.statut)}`">
              {{ getStatutLabel(proposition.statut) }}
            </span>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ proposition.titre }}
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            N° {{ proposition.numero }} • Déposé le {{ formatDate(proposition.date_depot) }}
          </p>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Main Content (2/3) -->
          <div class="lg:col-span-2 space-y-6">
            
            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
              <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex">
                  <button
                    @click="activeTab = 'details'"
                    :class="[
                      'flex-1 px-6 py-4 text-sm font-medium transition',
                      activeTab === 'details'
                        ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                  >
                    📄 Détails
                  </button>
                  <button
                    @click="activeTab = 'timeline'"
                    :class="[
                      'flex-1 px-6 py-4 text-sm font-medium transition',
                      activeTab === 'timeline'
                        ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                  >
                    📅 Timeline
                  </button>
                  <button
                    v-if="amendements && amendements.length > 0"
                    @click="activeTab = 'amendements'"
                    :class="[
                      'flex-1 px-6 py-4 text-sm font-medium transition',
                      activeTab === 'amendements'
                        ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                  >
                    📝 Amendements ({{ amendements.length }})
                  </button>
                  <button
                    v-if="votes && votes.length > 0"
                    @click="activeTab = 'votes'"
                    :class="[
                      'flex-1 px-6 py-4 text-sm font-medium transition',
                      activeTab === 'votes'
                        ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                  >
                    🗳️ Votes ({{ votes.length }})
                  </button>
                </nav>
              </div>

              <div class="p-6">
                <!-- Details Tab -->
                <div v-if="activeTab === 'details'" class="space-y-6">
                  <!-- Résumé -->
                  <div v-if="proposition.resume">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                      Résumé
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                      {{ proposition.resume }}
                    </p>
                  </div>

                  <!-- Texte intégral -->
                  <div v-if="proposition.texte">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                      Texte intégral
                    </h3>
                    <div class="prose dark:prose-invert max-w-none bg-gray-50 dark:bg-gray-900 p-6 rounded-lg">
                      <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ proposition.texte }}</p>
                    </div>
                  </div>

                  <!-- Métadonnées -->
                  <div class="grid grid-cols-2 gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div v-if="proposition.theme">
                      <span class="text-sm text-gray-600 dark:text-gray-400">Thème</span>
                      <p class="font-semibold text-gray-900 dark:text-gray-100">{{ proposition.theme }}</p>
                    </div>
                    <div v-if="proposition.auteurs">
                      <span class="text-sm text-gray-600 dark:text-gray-400">Auteur(s)</span>
                      <p class="font-semibold text-gray-900 dark:text-gray-100">{{ proposition.auteurs }}</p>
                    </div>
                    <div v-if="proposition.date_discussion">
                      <span class="text-sm text-gray-600 dark:text-gray-400">Discussion</span>
                      <p class="font-semibold text-gray-900 dark:text-gray-100">{{ formatDate(proposition.date_discussion) }}</p>
                    </div>
                    <div v-if="proposition.url_dossier">
                      <span class="text-sm text-gray-600 dark:text-gray-400">Dossier législatif</span>
                      <a
                        :href="proposition.url_dossier"
                        target="_blank"
                        class="font-semibold text-blue-600 dark:text-blue-400 hover:underline"
                      >
                        Consulter →
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Timeline Tab -->
                <div v-if="activeTab === 'timeline'">
                  <LegislativeTimeline :proposition="proposition" />
                </div>

                <!-- Amendements Tab -->
                <div v-if="activeTab === 'amendements'" class="space-y-4">
                  <div
                    v-for="amendement in amendements"
                    :key="amendement.id"
                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                  >
                    <div class="flex items-start justify-between mb-2">
                      <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                        Amendement n° {{ amendement.numero }}
                      </h4>
                      <span :class="`px-2 py-1 rounded text-xs font-semibold ${amendement.statut === 'adopte' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`">
                        {{ amendement.statut }}
                      </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                      Par {{ amendement.auteur }} • {{ formatDate(amendement.date_depot) }}
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                      {{ amendement.texte }}
                    </p>
                  </div>
                </div>

                <!-- Votes Tab -->
                <div v-if="activeTab === 'votes'" class="space-y-4">
                  <div
                    v-for="vote in votes"
                    :key="vote.id"
                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                  >
                    <div class="flex items-start justify-between mb-3">
                      <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ vote.libelle || 'Vote en séance' }}
                      </h4>
                      <span class="text-sm text-gray-500">{{ formatDate(vote.date) }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                      <div class="text-center">
                        <p class="text-2xl font-bold text-green-600">{{ vote.pour }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pour</p>
                      </div>
                      <div class="text-center">
                        <p class="text-2xl font-bold text-red-600">{{ vote.contre }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Contre</p>
                      </div>
                      <div class="text-center">
                        <p class="text-2xl font-bold text-gray-600">{{ vote.abstentions }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Abstentions</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Similar Citizen Proposals -->
            <div v-if="similar && similar.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>💡</span>
                <span>Propositions citoyennes similaires</span>
              </h3>
              <LegislationSimilar
                :titre="proposition.titre"
                :resume="proposition.resume"
                :show-input="false"
              />
            </div>
          </div>

          <!-- Sidebar (1/3) -->
          <div class="space-y-6">
            
            <!-- Citizen Vote -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>👥</span>
                <span>Vote citoyen</span>
              </h3>
              <PropositionVote
                :proposition-id="proposition.id"
                :show-details="true"
                :can-vote="true"
              />
            </div>

            <!-- Quick Stats -->
            <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl shadow-sm p-6">
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                📊 Statistiques
              </h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Amendements</span>
                  <span class="font-bold text-gray-900 dark:text-gray-100">
                    {{ amendements?.length || 0 }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Votes officiels</span>
                  <span class="font-bold text-gray-900 dark:text-gray-100">
                    {{ votes?.length || 0 }}
                  </span>
                </div>
                <div v-if="proposition.nb_signataires" class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Signataires</span>
                  <span class="font-bold text-gray-900 dark:text-gray-100">
                    {{ proposition.nb_signataires }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Share -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                🔗 Partager
              </h3>
              <div class="flex gap-2">
                <button class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                  Twitter
                </button>
                <button class="flex-1 px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition">
                  Facebook
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

