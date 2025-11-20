<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

defineProps({
  depute: Object,
});
</script>

<template>
  <Head :title="`${depute.nom_complet} - Député`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('representants.mes-representants')" class="hover:text-blue-600">
            Mes Représentants
          </Link>
          <span>/</span>
          <Link :href="route('representants.deputes.index')" class="hover:text-blue-600">
            Députés
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100">{{ depute.nom }}</span>
        </div>

        <!-- Header avec photo + Wikipedia -->
        <Card>
          <div class="grid md:grid-cols-4 gap-8">
            <!-- Photo -->
            <div class="md:col-span-1">
              <div class="w-48 h-48 mx-auto rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-700 shadow-lg">
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
              
              <!-- Liens externes -->
              <div class="mt-4 space-y-2">
                <a
                  v-if="depute.wikipedia.url"
                  :href="depute.wikipedia.url"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm"
                >
                  📖 Wikipedia
                </a>
                <a
                  v-if="depute.url_hatvp"
                  :href="depute.url_hatvp"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm"
                >
                  💰 HATVP
                </a>
              </div>
            </div>

            <!-- Infos principales -->
            <div class="md:col-span-3">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    {{ depute.nom_complet }}
                  </h1>
                  <p class="text-lg text-gray-600 dark:text-gray-400">
                    {{ depute.profession || 'Profession non renseignée' }}
                  </p>
                  <p v-if="depute.age" class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                    {{ depute.age }} ans
                    <span v-if="depute.lieu_naissance"> • Né(e) à {{ depute.lieu_naissance }}</span>
                  </p>
                </div>
                <Badge
                  v-if="depute.trigramme"
                  class="text-lg px-4 py-2"
                >
                  {{ depute.trigramme }}
                </Badge>
              </div>

              <!-- Wikipedia Extract -->
              <div v-if="depute.wikipedia.extract" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6 border-l-4 border-blue-500">
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                  {{ depute.wikipedia.extract }}
                </p>
                <a
                  v-if="depute.wikipedia.url"
                  :href="depute.wikipedia.url"
                  target="_blank"
                  class="text-blue-600 hover:text-blue-700 text-xs mt-2 inline-block"
                >
                  Lire la suite sur Wikipedia →
                </a>
              </div>

              <div class="flex flex-wrap gap-3 mb-6">
                <Badge
                  v-if="depute.groupe"
                  :style="{ backgroundColor: depute.groupe.couleur, color: '#fff' }"
                  class="text-base px-4 py-2"
                >
                  {{ depute.groupe.nom }}
                </Badge>
                <Badge v-if="depute.categorie_socio_pro" class="text-base px-4 py-2">
                  💼 {{ depute.categorie_socio_pro }}
                </Badge>
              </div>

              <!-- Stats rapides -->
              <div class="grid grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                  <div class="text-3xl font-bold text-blue-600">{{ depute.statistiques.votes_total }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Votes</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                  <div class="text-3xl font-bold text-green-600">{{ depute.statistiques.amendements_total }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Amendements</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                  <div class="text-3xl font-bold text-purple-600">{{ depute.statistiques.taux_adoption_amendements }}%</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Taux adoption</div>
                </div>
              </div>

              <!-- Navigation vers pages détaillées -->
              <div class="grid grid-cols-3 gap-3 mt-6">
                <Link
                  :href="route('representants.deputes.votes', depute.uid)"
                  class="text-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  🗳️ Voir les votes
                </Link>
                <Link
                  :href="route('representants.deputes.amendements', depute.uid)"
                  class="text-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                  📝 Amendements
                </Link>
                <Link
                  :href="route('representants.deputes.activite', depute.uid)"
                  class="text-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                >
                  📊 Activité
                </Link>
              </div>
            </div>
          </div>
        </Card>

        <div class="grid md:grid-cols-2 gap-6">
          <!-- Mandats -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>📜</span>
              <span>Mandats</span>
            </h2>
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="mandat in depute.mandats"
                :key="mandat.uid"
                :class="[
                  'p-3 rounded-lg border',
                  mandat.actif 
                    ? 'border-green-300 bg-green-50 dark:bg-green-900/20' 
                    : 'border-gray-200 dark:border-gray-700'
                ]"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                      {{ mandat.organe?.nom || mandat.type }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ mandat.date_debut }} 
                      <span v-if="mandat.date_fin">→ {{ mandat.date_fin }}</span>
                      <span v-else class="text-green-600 font-medium">→ En cours</span>
                    </div>
                  </div>
                  <Badge v-if="mandat.organe?.sigle" class="text-xs">
                    {{ mandat.organe.sigle }}
                  </Badge>
                </div>
              </div>
            </div>
          </Card>

          <!-- Commissions -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>🏛️</span>
              <span>Commissions actuelles</span>
            </h2>
            <div v-if="depute.commissions.length > 0" class="space-y-3">
              <div
                v-for="commission in depute.commissions"
                :key="commission.uid"
                class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800"
              >
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ commission.nom }}
                </div>
                <div v-if="commission.sigle" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ commission.sigle }}
                </div>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
              Aucune commission
            </div>
          </Card>
        </div>

        <!-- Contacts -->
        <Card v-if="depute.adresses && depute.adresses.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📧</span>
            <span>Contacts</span>
          </h2>
          <div class="grid md:grid-cols-2 gap-4">
            <div
              v-for="(adresse, index) in depute.adresses"
              :key="index"
              class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
            >
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                {{ adresse.type }}
              </div>
              <div class="text-sm text-gray-900 dark:text-gray-100">
                <div v-if="adresse.intitule">{{ adresse.intitule }}</div>
                <div v-if="adresse.valeur">{{ adresse.valeur }}</div>
                <div v-if="adresse.numero_rue || adresse.nom_rue">
                  {{ adresse.numero_rue }} {{ adresse.nom_rue }}
                </div>
                <div v-if="adresse.code_postal || adresse.ville">
                  {{ adresse.code_postal }} {{ adresse.ville }}
                </div>
              </div>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
