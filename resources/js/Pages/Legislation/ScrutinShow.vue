<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
  scrutin: Object,
  votes_par_groupe: Array,
  deputes_ayant_vote: Array,
});

// Calcul du résultat
const resultat = computed(() => {
  const total = props.scrutin.nombre_pour + props.scrutin.nombre_contre;
  return props.scrutin.nombre_pour > props.scrutin.nombre_contre ? 'Adopté' : 'Rejeté';
});

const resultatColor = computed(() => {
  return resultat.value === 'Adopté' 
    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
});

// Calcul du taux de participation
const tauxParticipation = computed(() => {
  const votants = props.scrutin.nombre_pour + props.scrutin.nombre_contre + props.scrutin.nombre_abstention;
  return Math.round((votants / 577) * 100); // 577 députés
});

const getVoteLabel = (type) => {
  const labels = {
    'pour': 'Pour',
    'contre': 'Contre',
    'abstention': 'Abstention',
    'non-votant': 'Non-votant',
  };
  return labels[type] || 'Non-votant';
};
</script>

<template>
  <Head :title="`Scrutin ${scrutin.numero} - ${scrutin.titre}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('legislation.index')" class="hover:text-blue-600">
            Législation
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100">Scrutin {{ scrutin.numero }}</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-700 to-blue-700 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <Badge class="text-lg px-4 py-2 bg-white/20">
                  Scrutin n°{{ scrutin.numero }}
                </Badge>
                <Badge :class="resultatColor" class="text-lg px-4 py-2">
                  {{ resultat }}
                </Badge>
              </div>
              <h1 class="text-3xl font-bold mb-3">
                {{ scrutin.titre }}
              </h1>
              <p v-if="scrutin.objet" class="text-blue-100 text-lg leading-relaxed">
                {{ scrutin.objet }}
              </p>
            </div>
            <div class="text-right">
              <div class="text-sm text-blue-200">{{ scrutin.date }}</div>
              <div class="text-sm text-blue-200 mt-1">{{ scrutin.moment_scrutin }}</div>
            </div>
          </div>
        </div>

        <!-- Résultats globaux -->
        <div class="grid md:grid-cols-4 gap-4">
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-green-600">{{ scrutin.nombre_pour }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Pour</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ Math.round((scrutin.nombre_pour / (scrutin.nombre_pour + scrutin.nombre_contre + scrutin.nombre_abstention)) * 100) }}%
              </div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-red-600">{{ scrutin.nombre_contre }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Contre</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ Math.round((scrutin.nombre_contre / (scrutin.nombre_pour + scrutin.nombre_contre + scrutin.nombre_abstention)) * 100) }}%
              </div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-yellow-600">{{ scrutin.nombre_abstention }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Abstentions</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ Math.round((scrutin.nombre_abstention / (scrutin.nombre_pour + scrutin.nombre_contre + scrutin.nombre_abstention)) * 100) }}%
              </div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-purple-600">{{ tauxParticipation }}%</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Participation</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ scrutin.nombre_pour + scrutin.nombre_contre + scrutin.nombre_abstention }}/577 députés
              </div>
            </div>
          </Card>
        </div>

        <!-- Barre de progression visuelle -->
        <Card>
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
            Répartition des votes
          </h2>
          <div class="flex h-12 rounded-lg overflow-hidden">
            <div
              class="bg-green-500 flex items-center justify-center text-white font-bold text-sm"
              :style="{ width: `${Math.round((scrutin.nombre_pour / (scrutin.nombre_pour + scrutin.nombre_contre + scrutin.nombre_abstention)) * 100)}%` }"
            >
              <span v-if="scrutin.nombre_pour > 30">{{ scrutin.nombre_pour }}</span>
            </div>
            <div
              class="bg-red-500 flex items-center justify-center text-white font-bold text-sm"
              :style="{ width: `${Math.round((scrutin.nombre_contre / (scrutin.nombre_pour + scrutin.nombre_contre + scrutin.nombre_abstention)) * 100)}%` }"
            >
              <span v-if="scrutin.nombre_contre > 30">{{ scrutin.nombre_contre }}</span>
            </div>
            <div
              class="bg-yellow-500 flex items-center justify-center text-white font-bold text-sm"
              :style="{ width: `${Math.round((scrutin.nombre_abstention / (scrutin.nombre_pour + scrutin.nombre_contre + scrutin.nombre_abstention)) * 100)}%` }"
            >
              <span v-if="scrutin.nombre_abstention > 30">{{ scrutin.nombre_abstention }}</span>
            </div>
          </div>
          <div class="flex justify-between mt-3 text-sm text-gray-600 dark:text-gray-400">
            <span>✅ Pour</span>
            <span>❌ Contre</span>
            <span>⚠️ Abstention</span>
          </div>
        </Card>

        <!-- Votes par groupe -->
        <Card v-if="votes_par_groupe && votes_par_groupe.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            Votes par groupe parlementaire
          </h2>
          <div class="space-y-4">
            <div
              v-for="groupe in votes_par_groupe"
              :key="groupe.sigle"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
            >
              <div class="flex items-start justify-between mb-3">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-lg">
                    {{ groupe.nom }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ groupe.sigle }} • {{ groupe.total_votes }} député(s) ayant voté
                  </p>
                </div>
                <Badge
                  :style="{ backgroundColor: groupe.couleur || '#6B7280', color: '#fff' }"
                >
                  {{ groupe.sigle }}
                </Badge>
              </div>
              
              <!-- Répartition pour ce groupe -->
              <div class="grid grid-cols-3 gap-3">
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded">
                  <div class="text-2xl font-bold text-green-600">{{ groupe.pour }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Pour</div>
                </div>
                <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded">
                  <div class="text-2xl font-bold text-red-600">{{ groupe.contre }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Contre</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                  <div class="text-2xl font-bold text-yellow-600">{{ groupe.abstention }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Abstention</div>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Liste des députés ayant voté -->
        <Card v-if="deputes_ayant_vote && deputes_ayant_vote.length > 0">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Députés ayant voté ({{ deputes_ayant_vote.length }})
            </h2>
          </div>
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-96 overflow-y-auto">
            <div
              v-for="depute in deputes_ayant_vote"
              :key="depute.uid"
              class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
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
              <div class="flex-1 min-w-0">
                <Link
                  :href="route('representants.deputes.show', depute.uid)"
                  class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 block truncate"
                >
                  {{ depute.nom_complet }}
                </Link>
                <div class="flex items-center gap-2 mt-1">
                  <Badge
                    :class="[
                      depute.position === 'pour' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                        : depute.position === 'contre'
                        ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                    ]"
                    class="text-xs"
                  >
                    {{ getVoteLabel(depute.position) }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
