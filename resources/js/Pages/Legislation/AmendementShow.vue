<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

defineProps({
  amendement: Object,
});
</script>

<template>
  <Head :title="`Amendement ${amendement.numero}`" />

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
            v-if="amendement.dossier"
            :href="route('legislation.dossiers.show', amendement.dossier.uid)"
            class="hover:text-blue-600"
          >
            {{ amendement.dossier.titre_court }}
          </Link>
          <span v-if="amendement.dossier">/</span>
          <span class="text-gray-900 dark:text-gray-100">Amendement {{ amendement.numero }}</span>
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-green-700 to-teal-700 rounded-xl shadow-lg p-8 text-white">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <Badge class="text-lg px-4 py-2 bg-white/20 font-mono">
                  {{ amendement.numero }}
                </Badge>
                <Badge
                  :class="[
                    amendement.sort === 'Adopté' 
                      ? 'bg-green-200 text-green-900' 
                      : amendement.sort === 'Rejeté'
                      ? 'bg-red-200 text-red-900'
                      : 'bg-yellow-200 text-yellow-900'
                  ]"
                  class="text-lg px-4 py-2"
                >
                  {{ amendement.sort }}
                </Badge>
              </div>
              <h1 class="text-3xl font-bold mb-2">
                Amendement {{ amendement.numero }}
              </h1>
              <p v-if="amendement.dossier" class="text-green-100 text-lg">
                {{ amendement.dossier.titre }}
              </p>
            </div>
            <div class="text-right">
              <div class="text-sm text-green-200">Déposé le {{ amendement.date_depot }}</div>
              <div v-if="amendement.date_sort" class="text-sm text-green-200 mt-1">
                {{ amendement.sort }} le {{ amendement.date_sort }}
              </div>
            </div>
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
          <!-- Informations principales -->
          <div class="md:col-span-2 space-y-6">
            <!-- Auteur principal -->
            <Card>
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>👤</span>
                <span>Auteur principal</span>
              </h2>
              <div v-if="amendement.auteur" class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                  <img
                    v-if="amendement.auteur.photo_url"
                    :src="amendement.auteur.photo_url"
                    :alt="amendement.auteur.nom_complet"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-2xl">
                    👤
                  </div>
                </div>
                <div>
                  <Link
                    :href="route('representants.deputes.show', amendement.auteur.uid)"
                    class="text-lg font-semibold text-gray-900 dark:text-gray-100 hover:text-blue-600"
                  >
                    {{ amendement.auteur.nom_complet }}
                  </Link>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ amendement.auteur.groupe?.nom || 'Non inscrit' }}
                  </p>
                </div>
              </div>
              <div v-else class="text-gray-500 dark:text-gray-400">
                Auteur non renseigné
              </div>
            </Card>

            <!-- Dispositif -->
            <Card>
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>📝</span>
                <span>Dispositif</span>
              </h2>
              <div
                v-if="amendement.dispositif"
                class="prose dark:prose-invert max-w-none bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border-l-4 border-blue-500"
              >
                <p class="text-gray-900 dark:text-gray-100 leading-relaxed whitespace-pre-line">
                  {{ amendement.dispositif }}
                </p>
              </div>
              <div v-else class="text-gray-500 dark:text-gray-400">
                Dispositif non renseigné
              </div>
            </Card>

            <!-- Exposé sommaire -->
            <Card v-if="amendement.expose_sommaire">
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>💡</span>
                <span>Exposé sommaire</span>
              </h2>
              <div class="prose dark:prose-invert max-w-none bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
                <p class="text-gray-900 dark:text-gray-100 leading-relaxed whitespace-pre-line">
                  {{ amendement.expose_sommaire }}
                </p>
              </div>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Métadonnées -->
            <Card>
              <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                Informations
              </h2>
              <div class="space-y-3">
                <div>
                  <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Numéro
                  </div>
                  <div class="font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ amendement.numero }}
                  </div>
                </div>
                <div>
                  <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Sort
                  </div>
                  <Badge
                    :class="[
                      amendement.sort === 'Adopté' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                        : amendement.sort === 'Rejeté'
                        ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                    ]"
                  >
                    {{ amendement.sort }}
                  </Badge>
                </div>
                <div>
                  <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Date de dépôt
                  </div>
                  <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ amendement.date_depot }}
                  </div>
                </div>
                <div v-if="amendement.date_sort">
                  <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Date de sort
                  </div>
                  <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ amendement.date_sort }}
                  </div>
                </div>
              </div>
            </Card>

            <!-- Co-signataires -->
            <Card v-if="amendement.co_signataires && amendement.co_signataires.length > 0">
              <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>👥</span>
                <span>Co-signataires ({{ amendement.co_signataires.length }})</span>
              </h2>
              <div class="space-y-2 max-h-64 overflow-y-auto">
                <div
                  v-for="(cosignataire, index) in amendement.co_signataires"
                  :key="index"
                  class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded"
                >
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                    <img
                      v-if="cosignataire.photo_url"
                      :src="cosignataire.photo_url"
                      :alt="cosignataire.nom_complet"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-xs">
                      👤
                    </div>
                  </div>
                  <Link
                    v-if="cosignataire.uid"
                    :href="route('representants.deputes.show', cosignataire.uid)"
                    class="text-sm text-gray-900 dark:text-gray-100 hover:text-blue-600 truncate"
                  >
                    {{ cosignataire.nom_complet }}
                  </Link>
                  <span v-else class="text-sm text-gray-900 dark:text-gray-100 truncate">
                    {{ cosignataire.nom_complet }}
                  </span>
                </div>
              </div>
            </Card>

            <!-- Liens -->
            <Card>
              <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                Liens
              </h2>
              <div class="space-y-2">
                <Link
                  v-if="amendement.dossier"
                  :href="route('legislation.dossiers.show', amendement.dossier.uid)"
                  class="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition text-sm"
                >
                  <span>📂</span>
                  <span class="text-gray-900 dark:text-gray-100">Voir le dossier</span>
                </Link>
                <Link
                  v-if="amendement.texte"
                  :href="route('legislation.textes.show', amendement.texte.uid)"
                  class="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition text-sm"
                >
                  <span>📄</span>
                  <span class="text-gray-900 dark:text-gray-100">Voir le texte</span>
                </Link>
                <Link
                  v-if="amendement.auteur"
                  :href="route('representants.deputes.amendements', amendement.auteur.uid)"
                  class="flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition text-sm"
                >
                  <span>📝</span>
                  <span class="text-gray-900 dark:text-gray-100">Autres amendements de l'auteur</span>
                </Link>
              </div>
            </Card>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>


