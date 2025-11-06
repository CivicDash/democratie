<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

defineProps({
  hasLocation: Boolean,
  depute: Object,
  senateurs: Array,
  location: Object,
});
</script>

<template>
  <Head title="Mes Représentants" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 rounded-xl shadow-lg p-8 text-white">
          <h1 class="text-4xl font-bold mb-2">🏛️ Mes Représentants</h1>
          <p class="text-blue-100 text-lg">Découvrez vos élus à l'Assemblée Nationale et au Sénat</p>
        </div>

        <!-- Pas de localisation -->
        <Card v-if="!hasLocation" class="text-center py-12">
          <div class="max-w-md mx-auto">
            <div class="text-6xl mb-4">📍</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              Configurez votre localisation
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
              Pour découvrir vos représentants politiques, veuillez renseigner votre ville et votre circonscription dans votre profil.
            </p>
            <Link
              :href="route('profile.edit')"
              class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
            >
              ⚙️ Configurer mon profil
            </Link>
          </div>
        </Card>

        <!-- Avec localisation -->
        <template v-else>
          <!-- Ma localisation -->
          <Card>
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                  📍 Ma localisation
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                  {{ location.city }} ({{ location.postal_code }}) - {{ location.department }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                  Circonscription : <span class="font-semibold">{{ location.circonscription }}</span>
                </p>
              </div>
              <Link
                :href="route('profile.edit')"
                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
              >
                Modifier
              </Link>
            </div>
          </Card>

          <!-- Mon Député -->
          <Card v-if="depute">
            <div class="border-l-4 border-blue-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🗳️ Mon Député
              </h2>
              <p class="text-gray-600 dark:text-gray-400">Assemblée Nationale</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
              <!-- Photo et infos principales -->
              <div class="md:col-span-1">
                <div class="text-center">
                  <div class="w-40 h-40 mx-auto mb-4 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                    <img
                      v-if="depute.photo_url"
                      :src="depute.photo_url"
                      :alt="depute.nom_complet"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-6xl">
                      👤
                    </div>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    {{ depute.nom_complet }}
                  </h3>
                  <Badge
                    :style="{ backgroundColor: depute.groupe.couleur, color: '#fff' }"
                    class="mb-2"
                  >
                    {{ depute.groupe.sigle }}
                  </Badge>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ depute.groupe.nom }}
                  </p>
                </div>
              </div>

              <!-- Statistiques -->
              <div class="md:col-span-2">
                <div class="grid grid-cols-2 gap-4 mb-6">
                  <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="text-3xl font-bold text-blue-600">{{ depute.nb_propositions }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Propositions de loi</div>
                  </div>
                  <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-3xl font-bold text-green-600">{{ depute.nb_amendements }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Amendements</div>
                  </div>
                  <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="text-3xl font-bold text-purple-600">{{ depute.taux_presence }}%</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Taux de présence</div>
                  </div>
                  <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                    <div class="text-2xl font-bold text-orange-600">{{ depute.circonscription }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Circonscription</div>
                  </div>
                </div>

                <div class="space-y-3">
                  <div v-if="depute.profession" class="flex items-center text-gray-700 dark:text-gray-300">
                    <span class="text-xl mr-2">💼</span>
                    <span>{{ depute.profession }}</span>
                  </div>
                  
                  <div class="flex gap-3">
                    <Link
                      :href="route('representants.deputes.show', depute.id)"
                      class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                      📊 Voir la fiche complète
                    </Link>
                    <a
                      v-if="depute.url_profil"
                      :href="depute.url_profil"
                      target="_blank"
                      class="flex-1 text-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    >
                      🔗 Site officiel
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Pas de député trouvé -->
          <Card v-else>
            <div class="text-center py-8">
              <div class="text-4xl mb-3">🔍</div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Aucun député trouvé
              </h3>
              <p class="text-gray-600 dark:text-gray-400">
                Nous n'avons pas trouvé de député pour votre circonscription.
              </p>
            </div>
          </Card>

          <!-- Mes Sénateurs -->
          <Card v-if="senateurs && senateurs.length > 0">
            <div class="border-l-4 border-red-600 pl-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                🏛️ Mes Sénateurs
              </h2>
              <p class="text-gray-600 dark:text-gray-400">Sénat ({{ location.department }})</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div
                v-for="senateur in senateurs"
                :key="senateur.id"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition"
              >
                <div class="flex items-start gap-4">
                  <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                    <img
                      v-if="senateur.photo_url"
                      :src="senateur.photo_url"
                      :alt="senateur.nom_complet"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-3xl">
                      👤
                    </div>
                  </div>
                  
                  <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                      {{ senateur.nom_complet }}
                    </h3>
                    <Badge
                      :style="{ backgroundColor: senateur.groupe.couleur, color: '#fff' }"
                      class="mb-2 text-xs"
                    >
                      {{ senateur.groupe.sigle }}
                    </Badge>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                      {{ senateur.profession }}
                    </p>
                    
                    <div class="grid grid-cols-3 gap-2 text-xs mb-3">
                      <div class="text-center">
                        <div class="font-bold text-blue-600">{{ senateur.nb_propositions }}</div>
                        <div class="text-gray-500">Prop.</div>
                      </div>
                      <div class="text-center">
                        <div class="font-bold text-green-600">{{ senateur.nb_amendements }}</div>
                        <div class="text-gray-500">Amend.</div>
                      </div>
                      <div class="text-center">
                        <div class="font-bold text-purple-600">{{ senateur.taux_presence }}%</div>
                        <div class="text-gray-500">Présence</div>
                      </div>
                    </div>

                    <Link
                      :href="route('representants.senateurs.show', senateur.id)"
                      class="block text-center px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition"
                    >
                      Voir la fiche
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Pas de sénateurs -->
          <Card v-else>
            <div class="text-center py-8">
              <div class="text-4xl mb-3">🔍</div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Aucun sénateur trouvé
              </h3>
              <p class="text-gray-600 dark:text-gray-400">
                Nous n'avons pas trouvé de sénateur pour votre département.
              </p>
            </div>
          </Card>

          <!-- Liens rapides -->
          <div class="grid md:grid-cols-2 gap-6">
            <Link
              :href="route('representants.deputes.index')"
              class="block bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white hover:shadow-xl transition group"
            >
              <div class="text-4xl mb-3">🏛️</div>
              <h3 class="text-xl font-bold mb-2 group-hover:translate-x-1 transition">
                Tous les Députés
              </h3>
              <p class="text-blue-100">
                Découvrez les 577 députés de l'Assemblée Nationale
              </p>
            </Link>

            <Link
              :href="route('representants.senateurs.index')"
              class="block bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white hover:shadow-xl transition group"
            >
              <div class="text-4xl mb-3">🏛️</div>
              <h3 class="text-xl font-bold mb-2 group-hover:translate-x-1 transition">
                Tous les Sénateurs
              </h3>
              <p class="text-red-100">
                Découvrez les 348 sénateurs du Sénat
              </p>
            </Link>
          </div>
        </template>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

