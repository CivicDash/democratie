<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
  effectifs: Object,
  ages: Object,
  parite: Object,
  professions: Object,
  groupes: Object,
});

// Calcul différence d'âge
const diffAge = computed(() => {
  return (props.ages.senateurs.moyenne - props.ages.deputes.moyenne).toFixed(1);
});

// Calcul différence parité
const diffParite = computed(() => {
  return (props.parite.senateurs.pct_femmes - props.parite.deputes.pct_femmes).toFixed(1);
});
</script>

<template>
  <Head title="Comparaison AN vs Sénat" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">
            ⚖️ Assemblée Nationale vs Sénat
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-400">
            Comparaison des deux chambres du Parlement français
          </p>
        </div>

        <!-- Effectifs -->
        <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            📊 Effectifs
          </h2>
          <div class="grid md:grid-cols-2 gap-6">
            <!-- AN -->
            <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-lg">
              <div class="text-6xl mb-3">🏛️</div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                Assemblée Nationale
              </h3>
              <div class="text-5xl font-bold text-blue-600 mb-2">
                {{ effectifs.deputes_actifs }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                députés actifs / {{ effectifs.deputes_total }}
              </div>
            </div>

            <!-- Sénat -->
            <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-lg">
              <div class="text-6xl mb-3">🏰</div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                Sénat
              </h3>
              <div class="text-5xl font-bold text-purple-600 mb-2">
                {{ effectifs.senateurs_actifs }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                sénateurs actifs / {{ effectifs.senateurs_total }}
              </div>
            </div>
          </div>
        </Card>

        <!-- Âge -->
        <Card>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            🎂 Répartition par âge
          </h2>

          <!-- Stats clés -->
          <div class="grid md:grid-cols-3 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Âge moyen AN</div>
              <div class="text-3xl font-bold text-blue-600">{{ ages.deputes.moyenne }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-500">ans</div>
            </div>
            <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Âge moyen Sénat</div>
              <div class="text-3xl font-bold text-purple-600">{{ ages.senateurs.moyenne }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-500">ans</div>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Différence</div>
              <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                {{ diffAge > 0 ? '+' : '' }}{{ diffAge }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-500">ans</div>
            </div>
          </div>

          <!-- Distribution -->
          <div class="grid md:grid-cols-2 gap-6">
            <!-- AN -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                🏛️ Assemblée Nationale
              </h3>
              <div class="space-y-2">
                <div v-for="(count, tranche) in ages.deputes.distribution" :key="tranche" class="flex items-center gap-3">
                  <div class="w-24 text-sm text-gray-600 dark:text-gray-400">{{ tranche }}</div>
                  <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                    <div 
                      class="h-full bg-blue-500 flex items-center justify-center text-white text-xs font-semibold transition-all duration-500"
                      :style="{ width: (count / effectifs.deputes_actifs * 100) + '%' }"
                    >
                      {{ count > 0 ? count : '' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sénat -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                🏰 Sénat
              </h3>
              <div class="space-y-2">
                <div v-for="(count, tranche) in ages.senateurs.distribution" :key="tranche" class="flex items-center gap-3">
                  <div class="w-24 text-sm text-gray-600 dark:text-gray-400">{{ tranche }}</div>
                  <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                    <div 
                      class="h-full bg-purple-500 flex items-center justify-center text-white text-xs font-semibold transition-all duration-500"
                      :style="{ width: (count / effectifs.senateurs_actifs * 100) + '%' }"
                    >
                      {{ count > 0 ? count : '' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Parité -->
        <Card>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            ⚖️ Parité Hommes / Femmes
          </h2>

          <div class="grid md:grid-cols-2 gap-6">
            <!-- AN -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 text-center">
                🏛️ Assemblée Nationale
              </h3>
              <div class="flex items-center justify-center mb-4">
                <div class="text-center">
                  <div class="text-4xl font-bold text-pink-600">{{ parite.deputes.pct_femmes }}%</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">de femmes</div>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <span class="font-medium">👨 Hommes</span>
                  <span class="font-bold">{{ parite.deputes.hommes }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                  <span class="font-medium">👩 Femmes</span>
                  <span class="font-bold">{{ parite.deputes.femmes }}</span>
                </div>
              </div>
            </div>

            <!-- Sénat -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 text-center">
                🏰 Sénat
              </h3>
              <div class="flex items-center justify-center mb-4">
                <div class="text-center">
                  <div class="text-4xl font-bold text-pink-600">{{ parite.senateurs.pct_femmes }}%</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">de femmes</div>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                  <span class="font-medium">👨 Hommes</span>
                  <span class="font-bold">{{ parite.senateurs.hommes }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                  <span class="font-medium">👩 Femmes</span>
                  <span class="font-bold">{{ parite.senateurs.femmes }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Différence de parité</div>
            <div class="text-2xl font-bold" :class="diffParite > 0 ? 'text-green-600' : 'text-red-600'">
              {{ diffParite > 0 ? '+' : '' }}{{ diffParite }}%
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
              {{ diffParite > 0 ? 'Le Sénat est plus paritaire' : 'L\'AN est plus paritaire' }}
            </div>
          </div>
        </Card>

        <!-- Top Professions -->
        <Card>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            💼 Top 10 des professions
          </h2>

          <div class="grid md:grid-cols-2 gap-6">
            <!-- AN -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                🏛️ Assemblée Nationale
              </h3>
              <div class="space-y-1">
                <div v-for="(prof, index) in professions.deputes" :key="index" 
                     class="flex items-center justify-between py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded transition">
                  <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-500 w-6">
                      {{ index + 1 }}.
                    </span>
                    <span class="text-sm text-gray-900 dark:text-gray-100 truncate">
                      {{ prof.profession }}
                    </span>
                  </div>
                  <span class="text-sm font-bold text-blue-600 ml-2">{{ prof.count }}</span>
                </div>
              </div>
            </div>

            <!-- Sénat -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                🏰 Sénat
              </h3>
              <div class="space-y-1">
                <div v-for="(prof, index) in professions.senateurs" :key="index"
                     class="flex items-center justify-between py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded transition">
                  <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-500 w-6">
                      {{ index + 1 }}.
                    </span>
                    <span class="text-sm text-gray-900 dark:text-gray-100 truncate">
                      {{ prof.profession }}
                    </span>
                  </div>
                  <span class="text-sm font-bold text-purple-600 ml-2">{{ prof.count }}</span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Groupes politiques -->
        <Card>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            🎨 Groupes parlementaires
          </h2>

          <div class="grid md:grid-cols-2 gap-6">
            <!-- AN -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                🏛️ Assemblée Nationale
              </h3>
              <div class="space-y-2">
                <div v-for="groupe in groupes.deputes" :key="groupe.sigle"
                     class="flex items-center justify-between py-2 px-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                      {{ groupe.sigle }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 truncate">
                      {{ groupe.nom }}
                    </div>
                  </div>
                  <div class="text-lg font-bold text-blue-600 ml-3">
                    {{ groupe.effectif }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Sénat -->
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                🏰 Sénat
              </h3>
              <div class="space-y-2">
                <div v-for="groupe in groupes.senateurs" :key="groupe.sigle"
                     class="flex items-center justify-between py-2 px-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                      {{ groupe.sigle }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 truncate">
                      {{ groupe.nom }}
                    </div>
                  </div>
                  <div class="text-lg font-bold text-purple-600 ml-3">
                    {{ groupe.effectif }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

