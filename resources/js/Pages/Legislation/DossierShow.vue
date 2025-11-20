<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

defineProps({
  dossier: Object,
  textes: Array,
  amendements: Array,
  statistiques: Object,
});
</script>

<template>
  <Head :title="`${dossier.titre_court}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('legislation.index')" class="hover:text-blue-600">
            Législation
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100">{{ dossier.titre_court }}</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-700 to-indigo-700 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <Badge class="text-sm px-3 py-1 bg-white/20 mb-3">
                Dossier Législatif
              </Badge>
              <h1 class="text-3xl font-bold mb-3">
                {{ dossier.titre }}
              </h1>
              <p v-if="dossier.titre_court" class="text-purple-100 text-lg">
                {{ dossier.titre_court }}
              </p>
            </div>
            <div v-if="dossier.legislature" class="text-right">
              <Badge class="text-lg px-4 py-2 bg-white/20">
                {{ dossier.legislature }}ème législature
              </Badge>
            </div>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="grid md:grid-cols-3 gap-4">
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-purple-600">{{ statistiques.textes_count }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Textes</div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-green-600">{{ statistiques.amendements_count }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Amendements</div>
            </div>
          </Card>
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-blue-600">{{ statistiques.amendements_adoptes_count }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Amendements adoptés</div>
              <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ statistiques.taux_adoption }}%
              </div>
            </div>
          </Card>
        </div>

        <!-- Textes législatifs -->
        <Card v-if="textes && textes.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
            <span>📄</span>
            <span>Textes législatifs ({{ textes.length }})</span>
          </h2>
          <div class="space-y-3">
            <Link
              v-for="texte in textes"
              :key="texte.uid"
              :href="route('legislation.textes.show', texte.uid)"
              class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-purple-400 dark:hover:border-purple-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <Badge class="text-xs">
                      {{ texte.type }}
                    </Badge>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ texte.date_depot }}
                    </span>
                  </div>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    {{ texte.titre_court }}
                  </h3>
                  <p v-if="texte.titre" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                    {{ texte.titre }}
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <span class="text-blue-600 hover:text-blue-700">→</span>
                </div>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Amendements récents -->
        <Card v-if="amendements && amendements.length > 0">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>📝</span>
              <span>Amendements récents ({{ amendements.length }})</span>
            </h2>
          </div>
          <div class="space-y-3">
            <Link
              v-for="amendement in amendements"
              :key="amendement.uid"
              :href="route('legislation.amendements.show', amendement.uid)"
              class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-400 dark:hover:border-green-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <Badge class="text-xs font-mono">
                      {{ amendement.numero }}
                    </Badge>
                    <Badge
                      :class="[
                        amendement.sort === 'Adopté' 
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                          : amendement.sort === 'Rejeté'
                          ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                          : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                      ]"
                      class="text-xs"
                    >
                      {{ amendement.sort }}
                    </Badge>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ amendement.date_depot }}
                    </span>
                  </div>
                  <p v-if="amendement.auteur" class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                    Par {{ amendement.auteur.nom_complet }}
                  </p>
                  <p v-if="amendement.dispositif" class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2">
                    {{ amendement.dispositif }}
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <span class="text-blue-600 hover:text-blue-700">→</span>
                </div>
              </div>
            </Link>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>


