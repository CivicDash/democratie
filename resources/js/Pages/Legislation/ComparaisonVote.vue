<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
  scrutin: Object,
  ballot: Object,
  stats: Object,
  concordance: Object,
});

const hasBallot = computed(() => props.ballot && props.ballot.votes_count > 0);

// Calcul des pourcentages pour les graphiques
const scrutinData = computed(() => {
  const total = props.scrutin.nombre_votants || 1;
  return {
    pour: props.scrutin.nombre_pour,
    contre: props.scrutin.nombre_contre,
    abstention: props.scrutin.nombre_abstention,
    pour_percent: ((props.scrutin.nombre_pour / total) * 100).toFixed(1),
    contre_percent: ((props.scrutin.nombre_contre / total) * 100).toFixed(1),
    abstention_percent: ((props.scrutin.nombre_abstention / total) * 100).toFixed(1),
  };
});

const ballotData = computed(() => {
  if (!hasBallot.value) return null;
  
  const total = props.ballot.votes_count || 1;
  return {
    pour: props.ballot.pour_count,
    contre: props.ballot.contre_count,
    abstention: props.ballot.abstention_count,
    pour_percent: ((props.ballot.pour_count / total) * 100).toFixed(1),
    contre_percent: ((props.ballot.contre_count / total) * 100).toFixed(1),
    abstention_percent: ((props.ballot.abstention_count / total) * 100).toFixed(1),
  };
});

// Calcul de l'écart
const ecart = computed(() => {
  if (!hasBallot.value) return null;
  
  return {
    pour: (ballotData.value.pour_percent - scrutinData.value.pour_percent).toFixed(1),
    contre: (ballotData.value.contre_percent - scrutinData.value.contre_percent).toFixed(1),
    abstention: (ballotData.value.abstention_percent - scrutinData.value.abstention_percent).toFixed(1),
  };
});

const getEcartColor = (value) => {
  const abs = Math.abs(parseFloat(value));
  if (abs < 5) return 'text-gray-600';
  if (abs < 15) return 'text-orange-600';
  return 'text-red-600';
};

const getEcartIcon = (value) => {
  const num = parseFloat(value);
  if (Math.abs(num) < 5) return '≈';
  if (num > 0) return '↑';
  return '↓';
};
</script>

<template>
  <Head :title="`Comparaison : ${scrutin.titre}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('legislation.scrutins.index')" class="hover:text-blue-600 transition">
            Scrutins
          </Link>
          <span>/</span>
          <Link :href="route('legislation.scrutins.show', scrutin.uid)" class="hover:text-blue-600 transition">
            Scrutin n°{{ scrutin.numero }}
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100 font-medium">Comparaison</span>
        </div>

        <!-- Header -->
        <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl">
              ⚖️
            </div>
            <div class="flex-1">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Comparaison Vote AN vs Vote Citoyen
              </h1>
              <p class="text-gray-600 dark:text-gray-400">
                {{ scrutin.titre }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 text-sm">
            <Badge>Scrutin n°{{ scrutin.numero }}</Badge>
            <span class="text-gray-600 dark:text-gray-400">{{ scrutin.date }}</span>
            <Badge v-if="scrutin.resultat_libelle" class="bg-green-100 text-green-800 dark:bg-green-900/20">
              {{ scrutin.resultat_libelle }}
            </Badge>
          </div>
        </Card>

        <!-- Alerte si pas de vote citoyen -->
        <Card v-if="!hasBallot" class="bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800">
          <div class="flex items-start gap-4">
            <span class="text-3xl">⚠️</span>
            <div class="flex-1">
              <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-2">
                Aucun vote citoyen disponible
              </h3>
              <p class="text-gray-600 dark:text-gray-400 mb-4">
                Ce scrutin n'a pas encore fait l'objet d'un vote citoyen. Créez un débat pour permettre aux citoyens de s'exprimer !
              </p>
              <Link
                :href="route('topics.create', { scrutin: scrutin.uid })"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                💬 Créer un débat citoyen
              </Link>
            </div>
          </div>
        </Card>

        <!-- Comparaison visuelle -->
        <div v-if="hasBallot" class="grid lg:grid-cols-3 gap-6">
          
          <!-- Vote Assemblée Nationale -->
          <Card>
            <div class="text-center mb-6">
              <div class="w-16 h-16 mx-auto rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-3xl mb-3">
                🏛️
              </div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                Assemblée Nationale
              </h2>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ scrutin.nombre_votants }} députés
              </p>
            </div>

            <!-- Graphique barres -->
            <div class="space-y-4">
              <!-- Pour -->
              <div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="font-medium text-gray-700 dark:text-gray-300">Pour</span>
                  <span class="font-bold text-green-600">{{ scrutinData.pour_percent }}%</span>
                </div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-green-500 flex items-center justify-center text-white text-sm font-semibold transition-all duration-500"
                    :style="{ width: scrutinData.pour_percent + '%' }"
                  >
                    {{ scrutinData.pour }}
                  </div>
                </div>
              </div>

              <!-- Contre -->
              <div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="font-medium text-gray-700 dark:text-gray-300">Contre</span>
                  <span class="font-bold text-red-600">{{ scrutinData.contre_percent }}%</span>
                </div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-red-500 flex items-center justify-center text-white text-sm font-semibold transition-all duration-500"
                    :style="{ width: scrutinData.contre_percent + '%' }"
                  >
                    {{ scrutinData.contre }}
                  </div>
                </div>
              </div>

              <!-- Abstention -->
              <div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="font-medium text-gray-700 dark:text-gray-300">Abstention</span>
                  <span class="font-bold text-orange-600">{{ scrutinData.abstention_percent }}%</span>
                </div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-orange-500 flex items-center justify-center text-white text-sm font-semibold transition-all duration-500"
                    :style="{ width: scrutinData.abstention_percent + '%' }"
                  >
                    {{ scrutinData.abstention }}
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Écarts -->
          <Card class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900">
            <div class="text-center mb-6">
              <div class="w-16 h-16 mx-auto rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-3xl mb-3">
                📊
              </div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                Écarts
              </h2>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Différence de vote
              </p>
            </div>

            <div class="space-y-6">
              <!-- Pour -->
              <div class="text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Pour</div>
                <div :class="['text-4xl font-bold', getEcartColor(ecart.pour)]">
                  {{ getEcartIcon(ecart.pour) }} {{ Math.abs(ecart.pour) }}%
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                  {{ parseFloat(ecart.pour) > 0 ? 'Citoyens plus favorables' : parseFloat(ecart.pour) < 0 ? 'AN plus favorable' : 'Identique' }}
                </div>
              </div>

              <!-- Contre -->
              <div class="text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Contre</div>
                <div :class="['text-4xl font-bold', getEcartColor(ecart.contre)]">
                  {{ getEcartIcon(ecart.contre) }} {{ Math.abs(ecart.contre) }}%
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                  {{ parseFloat(ecart.contre) > 0 ? 'Citoyens plus opposés' : parseFloat(ecart.contre) < 0 ? 'AN plus opposée' : 'Identique' }}
                </div>
              </div>

              <!-- Abstention -->
              <div class="text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Abstention</div>
                <div :class="['text-4xl font-bold', getEcartColor(ecart.abstention)]">
                  {{ getEcartIcon(ecart.abstention) }} {{ Math.abs(ecart.abstention) }}%
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                  {{ parseFloat(ecart.abstention) > 0 ? 'Citoyens plus abstentionnistes' : parseFloat(ecart.abstention) < 0 ? 'AN plus abstentionniste' : 'Identique' }}
                </div>
              </div>
            </div>
          </Card>

          <!-- Vote Citoyens -->
          <Card>
            <div class="text-center mb-6">
              <div class="w-16 h-16 mx-auto rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-3xl mb-3">
                👥
              </div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                Vote Citoyen
              </h2>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ ballot.votes_count }} citoyens
              </p>
            </div>

            <!-- Graphique barres -->
            <div class="space-y-4">
              <!-- Pour -->
              <div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="font-medium text-gray-700 dark:text-gray-300">Pour</span>
                  <span class="font-bold text-green-600">{{ ballotData.pour_percent }}%</span>
                </div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-green-500 flex items-center justify-center text-white text-sm font-semibold transition-all duration-500"
                    :style="{ width: ballotData.pour_percent + '%' }"
                  >
                    {{ ballotData.pour }}
                  </div>
                </div>
              </div>

              <!-- Contre -->
              <div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="font-medium text-gray-700 dark:text-gray-300">Contre</span>
                  <span class="font-bold text-red-600">{{ ballotData.contre_percent }}%</span>
                </div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-red-500 flex items-center justify-center text-white text-sm font-semibold transition-all duration-500"
                    :style="{ width: ballotData.contre_percent + '%' }"
                  >
                    {{ ballotData.contre }}
                  </div>
                </div>
              </div>

              <!-- Abstention -->
              <div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="font-medium text-gray-700 dark:text-gray-300">Abstention</span>
                  <span class="font-bold text-orange-600">{{ ballotData.abstention_percent }}%</span>
                </div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-orange-500 flex items-center justify-center text-white text-sm font-semibold transition-all duration-500"
                    :style="{ width: ballotData.abstention_percent + '%' }"
                  >
                    {{ ballotData.abstention }}
                  </div>
                </div>
              </div>
            </div>
          </Card>

        </div>

        <!-- Analyse -->
        <Card v-if="hasBallot">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
            📈 Analyse
          </h2>
          
          <div class="grid md:grid-cols-2 gap-6">
            <!-- Concordance -->
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">🎯</span>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Concordance</h3>
              </div>
              <div class="text-3xl font-bold text-blue-600 mb-1">
                {{ concordance.score }}%
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ concordance.message }}
              </p>
            </div>

            <!-- Participation -->
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">📊</span>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Participation</h3>
              </div>
              <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                <div class="flex justify-between">
                  <span>Députés :</span>
                  <span class="font-semibold">{{ scrutin.nombre_votants }} / 577</span>
                </div>
                <div class="flex justify-between">
                  <span>Citoyens :</span>
                  <span class="font-semibold">{{ ballot.votes_count }} votes</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Interprétation -->
          <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
              💡 Interprétation
            </h3>
            <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
              <li v-if="Math.abs(parseFloat(ecart.pour)) < 5" class="flex items-start gap-2">
                <span class="text-green-600">✓</span>
                <span>Le vote "Pour" est très proche entre l'AN et les citoyens (écart &lt; 5%)</span>
              </li>
              <li v-if="Math.abs(parseFloat(ecart.pour)) >= 15" class="flex items-start gap-2">
                <span class="text-red-600">!</span>
                <span>Écart significatif sur le vote "Pour" : {{ Math.abs(ecart.pour) }}% de différence</span>
              </li>
              <li v-if="Math.abs(parseFloat(ecart.contre)) >= 15" class="flex items-start gap-2">
                <span class="text-red-600">!</span>
                <span>Écart significatif sur le vote "Contre" : {{ Math.abs(ecart.contre) }}% de différence</span>
              </li>
              <li v-if="concordance.score >= 80" class="flex items-start gap-2">
                <span class="text-green-600">✓</span>
                <span>Forte concordance globale entre représentants et citoyens</span>
              </li>
              <li v-if="concordance.score < 60" class="flex items-start gap-2">
                <span class="text-orange-600">⚠</span>
                <span>Divergence notable entre le vote de l'AN et l'opinion citoyenne</span>
              </li>
            </ul>
          </div>
        </Card>

        <!-- Actions -->
        <div class="flex gap-4">
          <Link
            :href="route('legislation.scrutins.show', scrutin.uid)"
            class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold"
          >
            ← Retour au scrutin
          </Link>
          <Link
            v-if="ballot"
            :href="route('topics.show', ballot.topic_id)"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
          >
            💬 Voir le débat citoyen
          </Link>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

