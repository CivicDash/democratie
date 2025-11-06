<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

defineProps({
  senateur: Object,
});
</script>

<template>
  <Head :title="`${senateur.nom_complet} - Sénateur`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('representants.mes-representants')" class="hover:text-blue-600">
            Mes Représentants
          </Link>
          <span>/</span>
          <Link :href="route('representants.senateurs.index')" class="hover:text-blue-600">
            Sénateurs
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100">{{ senateur.nom }}</span>
        </div>

        <!-- Header avec photo -->
        <Card>
          <div class="grid md:grid-cols-4 gap-8">
            <!-- Photo -->
            <div class="md:col-span-1">
              <div class="w-48 h-48 mx-auto rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-700 shadow-lg">
                <img
                  v-if="senateur.photo_url"
                  :src="senateur.photo_url"
                  :alt="senateur.nom_complet"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-6xl">
                  👤
                </div>
              </div>
            </div>

            <!-- Infos principales -->
            <div class="md:col-span-3">
              <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                {{ senateur.nom_complet }}
              </h1>
              <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                Sénateur {{ senateur.circonscription }}
              </p>

              <div class="flex flex-wrap gap-3 mb-6">
                <Badge
                  :style="{ backgroundColor: senateur.groupe.couleur, color: '#fff' }"
                  class="text-base px-4 py-2"
                >
                  {{ senateur.groupe.nom }}
                </Badge>
                <Badge v-if="senateur.profession" class="text-base px-4 py-2">
                  💼 {{ senateur.profession }}
                </Badge>
                <Badge v-if="senateur.age" class="text-base px-4 py-2">
                  🎂 {{ senateur.age }} ans
                </Badge>
              </div>

              <!-- Stats rapides -->
              <div class="grid grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                  <div class="text-3xl font-bold text-blue-600">{{ senateur.statistiques.nb_propositions }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Propositions de loi</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                  <div class="text-3xl font-bold text-green-600">{{ senateur.statistiques.nb_amendements }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Amendements déposés</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                  <div class="text-3xl font-bold text-purple-600">{{ senateur.statistiques.taux_presence }}%</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Taux de présence</div>
                </div>
              </div>

              <!-- Liens -->
              <div class="flex gap-3 mt-6">
                <Link
                  v-if="senateur.groupe.id"
                  :href="route('legislation.groupes.show', senateur.groupe.id)"
                  class="flex-1 text-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                >
                  👥 Voir le groupe parlementaire
                </Link>
                <a
                  v-if="senateur.url_profil"
                  :href="senateur.url_profil"
                  target="_blank"
                  class="flex-1 text-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                  🔗 Site officiel
                </a>
              </div>
            </div>
          </div>
        </Card>

        <div class="grid md:grid-cols-2 gap-6">
          <!-- Mandat -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>📜</span>
              <span>Mandat en cours</span>
            </h2>
            <div class="space-y-3">
              <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Législature</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ senateur.mandat.legislature }}ème</span>
              </div>
              <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Début de mandat</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ senateur.mandat.debut }}</span>
              </div>
              <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Fin de mandat</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ senateur.mandat.fin || 'En cours' }}</span>
              </div>
              <div class="flex justify-between py-2">
                <span class="text-gray-600 dark:text-gray-400">Circonscription</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ senateur.circonscription }}</span>
              </div>
            </div>
          </Card>

          <!-- Groupe parlementaire -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>🎨</span>
              <span>Groupe parlementaire</span>
            </h2>
            <div class="space-y-3">
              <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Groupe</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ senateur.groupe.nom }}</span>
              </div>
              <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Sigle</span>
                <Badge :style="{ backgroundColor: senateur.groupe.couleur, color: '#fff' }">
                  {{ senateur.groupe.sigle }}
                </Badge>
              </div>
              <div class="flex justify-between py-2">
                <span class="text-gray-600 dark:text-gray-400">Position politique</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100 capitalize">
                  {{ senateur.groupe.position?.replace('_', ' ') || 'Non définie' }}
                </span>
              </div>
            </div>
          </Card>
        </div>

        <!-- Fonctions -->
        <Card v-if="senateur.fonctions && senateur.fonctions.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>⚖️</span>
            <span>Fonctions</span>
          </h2>
          <div class="space-y-2">
            <div
              v-for="(fonction, index) in senateur.fonctions"
              :key="index"
              class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
            >
              <span class="text-2xl">📌</span>
              <span class="text-gray-900 dark:text-gray-100">{{ fonction }}</span>
            </div>
          </div>
        </Card>

        <!-- Commissions -->
        <Card v-if="senateur.commissions && senateur.commissions.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>👥</span>
            <span>Commissions</span>
          </h2>
          <div class="grid md:grid-cols-2 gap-3">
            <div
              v-for="(commission, index) in senateur.commissions"
              :key="index"
              class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
            >
              <span class="text-2xl">🏛️</span>
              <span class="text-gray-900 dark:text-gray-100">{{ commission }}</span>
            </div>
          </div>
        </Card>

        <!-- Activité parlementaire -->
        <Card>
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📊</span>
            <span>Activité parlementaire</span>
          </h2>
          <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl">
              <div class="text-5xl font-bold text-blue-600 mb-2">
                {{ senateur.statistiques.nb_propositions }}
              </div>
              <div class="text-gray-700 dark:text-gray-300 font-medium">
                Propositions de loi déposées
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Textes législatifs initiés
              </p>
            </div>
            <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl">
              <div class="text-5xl font-bold text-green-600 mb-2">
                {{ senateur.statistiques.nb_amendements }}
              </div>
              <div class="text-gray-700 dark:text-gray-300 font-medium">
                Amendements déposés
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Modifications proposées
              </p>
            </div>
            <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl">
              <div class="text-5xl font-bold text-purple-600 mb-2">
                {{ senateur.statistiques.taux_presence }}%
              </div>
              <div class="text-gray-700 dark:text-gray-300 font-medium">
                Taux de présence
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Participation aux séances
              </p>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

