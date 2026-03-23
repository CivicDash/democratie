<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
  depute: Object,
  statistiques: Object,
  activite_mensuelle: Array,
  derniers_votes: Array,
  derniers_amendements: Array,
});

const breadcrumbItems = computed(() => [
  { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
  { label: 'Députés', href: route('representants.deputes.index') },
  { label: props.depute.nom, href: route('representants.deputes.show', props.depute.uid) },
  { label: 'Activité', icon: '📊' },
]);

// Calcul du pourcentage pour les barres de progression
const getPercentage = (value, total) => {
  return total > 0 ? Math.round((value / total) * 100) : 0;
};

// Graphique simple en barres pour l'activité mensuelle
const maxActivite = computed(() => {
  return Math.max(...props.activite_mensuelle.map(m => m.total), 1);
});
</script>

<template>
  <Head :title="`Activité de ${depute.nom_complet}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <Breadcrumb :items="breadcrumbItems" />

        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-700 to-pink-700 rounded-xl shadow-lg p-8 text-white">
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
              <h1 class="text-4xl font-bold mb-2">📊 Activité de {{ depute.nom }}</h1>
              <p class="text-purple-100 text-lg">{{ depute.groupe?.nom || 'Non inscrit' }}</p>
            </div>
          </div>
        </div>

        <!-- Statistiques globales -->
        <div class="grid md:grid-cols-3 gap-6">
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
              <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                <div class="text-center">
                  <div class="text-2xl font-bold text-purple-600">{{ statistiques.amendements.total }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Total déposés</div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Discipline de vote -->
          <Card>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>🎯</span>
              <span>Discipline</span>
            </h3>
            <div class="text-center">
              <div class="relative inline-flex items-center justify-center w-32 h-32">
                <svg class="transform -rotate-90 w-32 h-32">
                  <circle
                    cx="64"
                    cy="64"
                    r="52"
                    stroke="currentColor"
                    stroke-width="8"
                    fill="transparent"
                    class="text-gray-200 dark:text-gray-700"
                  />
                  <circle
                    cx="64"
                    cy="64"
                    r="52"
                    stroke="currentColor"
                    stroke-width="8"
                    fill="transparent"
                    :stroke-dasharray="`${(statistiques.discipline_groupe / 100) * 326.73} 326.73`"
                    class="text-purple-600"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-3xl font-bold text-purple-600">{{ statistiques.discipline_groupe }}%</span>
                </div>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                Votes conformes au groupe
              </p>
            </div>
          </Card>
        </div>

        <!-- Activité mensuelle -->
        <Card>
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
            <span>📈</span>
            <span>Activité mensuelle (12 derniers mois)</span>
          </h2>
          <div class="relative">
            <!-- Y-axis labels -->
            <div class="absolute left-0 top-0 bottom-8 w-10 flex flex-col justify-between text-xs text-gray-400">
              <span>{{ maxActivite }}</span>
              <span>{{ Math.round(maxActivite / 2) }}</span>
              <span>0</span>
            </div>
            <!-- Chart area -->
            <div class="ml-12 flex items-end gap-1 sm:gap-2" style="height: 220px;">
              <div
                v-for="mois in activite_mensuelle"
                :key="mois.mois"
                class="flex-1 flex flex-col items-center"
              >
                <div class="w-full relative" style="height: 180px;">
                  <div
                    class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-purple-600 to-purple-400 rounded-t transition-all hover:from-purple-500 hover:to-purple-300 cursor-default"
                    :style="{ height: Math.max((mois.total / maxActivite) * 180, mois.total > 0 ? 4 : 0) + 'px' }"
                    :title="`${mois.total} actions en ${mois.label}`"
                  ></div>
                </div>
                <div class="text-[10px] sm:text-xs text-gray-600 dark:text-gray-400 text-center mt-1 truncate w-full">
                  {{ mois.label }}
                </div>
                <div class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-gray-100">
                  {{ mois.total }}
                </div>
              </div>
            </div>
          </div>
        </Card>

        <div class="grid md:grid-cols-2 gap-6">
          <!-- Derniers votes -->
          <Card>
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span>🗳️</span>
                <span>Derniers votes</span>
              </h2>
              <Link
                :href="route('representants.deputes.votes', depute.uid)"
                class="text-sm text-blue-600 hover:text-blue-700"
              >
                Voir tout →
              </Link>
            </div>
            <div class="space-y-3">
              <div
                v-for="vote in derniers_votes"
                :key="vote.id"
                class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
              >
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <Badge
                        :class="[
                          vote.position === 'pour' 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                            : vote.position === 'contre'
                            ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                        ]"
                        class="text-xs"
                      >
                        {{ vote.position.toUpperCase() }}
                      </Badge>
                      <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ vote.date }}
                      </span>
                    </div>
                    <p class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2">
                      {{ vote.scrutin.titre }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Derniers amendements -->
          <Card>
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span>📝</span>
                <span>Derniers amendements</span>
              </h2>
              <Link
                :href="route('representants.deputes.amendements', depute.uid)"
                class="text-sm text-blue-600 hover:text-blue-700"
              >
                Voir tout →
              </Link>
            </div>
            <div class="space-y-3">
              <a
                v-for="amendement in derniers_amendements"
                :key="amendement.uid"
                :href="amendement.url_externe"
                target="_blank"
                rel="noopener noreferrer"
                class="block p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600 hover:shadow-md transition cursor-pointer"
              >
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <Badge class="text-xs font-mono">
                        {{ amendement.numero }}
                      </Badge>
                      <Badge
                        :class="[
                          amendement.sort_code === 'ADO' 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                            : amendement.sort_code === 'REJ'
                            ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                        ]"
                        class="text-xs"
                      >
                        {{ amendement.sort_libelle || 'En cours' }}
                      </Badge>
                      <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ amendement.date_depot }}
                      </span>
                    </div>
                    <p class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2">
                      {{ amendement.texte?.titre_court || amendement.dossier?.titre_court }}
                    </p>
                  </div>
                  <span class="text-gray-400 text-sm flex-shrink-0">↗️</span>
                </div>
              </a>
            </div>
          </Card>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

