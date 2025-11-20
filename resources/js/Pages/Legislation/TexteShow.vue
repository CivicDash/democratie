<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

defineProps({
  texte: Object,
  amendements: Array,
  statistiques: Object,
});
</script>

<template>
  <Head :title="`${texte.titre_court}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('legislation.index')" class="hover:text-blue-600">
            Législation
          </Link>
          <span>/</span>
          <Link
            v-if="texte.dossier"
            :href="route('legislation.dossiers.show', texte.dossier.uid)"
            class="hover:text-blue-600"
          >
            {{ texte.dossier.titre_court }}
          </Link>
          <span v-if="texte.dossier">/</span>
          <span class="text-gray-900 dark:text-gray-100">{{ texte.titre_court }}</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-cyan-700 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <Badge class="text-sm px-3 py-1 bg-white/20">
                  {{ texte.type }}
                </Badge>
                <Badge v-if="texte.legislature" class="text-sm px-3 py-1 bg-white/20">
                  {{ texte.legislature }}ème législature
                </Badge>
              </div>
              <h1 class="text-3xl font-bold mb-3">
                {{ texte.titre }}
              </h1>
              <p v-if="texte.titre_court" class="text-blue-100 text-lg">
                {{ texte.titre_court }}
              </p>
            </div>
            <div v-if="texte.date_depot" class="text-right">
              <div class="text-sm text-blue-200">Déposé le</div>
              <div class="text-lg font-semibold">{{ texte.date_depot }}</div>
            </div>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="grid md:grid-cols-2 gap-4">
          <Card>
            <div class="text-center">
              <div class="text-4xl font-bold text-green-600">{{ statistiques.amendements_count }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Amendements déposés</div>
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

        <!-- Informations -->
        <Card>
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>ℹ️</span>
            <span>Informations</span>
          </h2>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                Type
              </div>
              <div class="text-gray-900 dark:text-gray-100">
                {{ texte.type }}
              </div>
            </div>
            <div v-if="texte.date_depot">
              <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                Date de dépôt
              </div>
              <div class="text-gray-900 dark:text-gray-100">
                {{ texte.date_depot }}
              </div>
            </div>
            <div v-if="texte.legislature">
              <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                Législature
              </div>
              <div class="text-gray-900 dark:text-gray-100">
                {{ texte.legislature }}ème
              </div>
            </div>
            <div v-if="texte.dossier">
              <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                Dossier législatif
              </div>
              <Link
                :href="route('legislation.dossiers.show', texte.dossier.uid)"
                class="text-blue-600 hover:text-blue-700"
              >
                {{ texte.dossier.titre_court }}
              </Link>
            </div>
          </div>
        </Card>

        <!-- Amendements -->
        <Card v-if="amendements && amendements.length > 0">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>📝</span>
              <span>Amendements ({{ amendements.length }})</span>
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
                    <span v-if="amendement.co_signataires_count > 0">
                      + {{ amendement.co_signataires_count }} co-signataire(s)
                    </span>
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

        <Card v-else>
          <div class="text-center text-gray-500 dark:text-gray-400 py-8">
            Aucun amendement déposé pour ce texte
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>


