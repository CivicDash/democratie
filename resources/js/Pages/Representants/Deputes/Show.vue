<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
  depute: Object,
});

const hasReseauxSociaux = computed(() => {
  return props.depute.reseaux_sociaux?.twitter ||
         props.depute.reseaux_sociaux?.facebook ||
         props.depute.reseaux_sociaux?.linkedin ||
         props.depute.reseaux_sociaux?.instagram;
});
</script>

<template>
  <Head :title="`${depute.nom_complet} - Député`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('representants.mes-representants')" class="hover:text-blue-600 transition">
            Mes Représentants
          </Link>
          <span>/</span>
          <Link :href="route('representants.deputes.index')" class="hover:text-blue-600 transition">
            Députés
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100 font-medium">{{ depute.nom }}</span>
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

              <!-- Réseaux sociaux -->
              <div v-if="hasReseauxSociaux" class="flex items-center gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">Suivre :</span>
                <div class="flex gap-3">
                  <a
                    v-if="depute.reseaux_sociaux.twitter"
                    :href="depute.reseaux_sociaux.twitter"
                    target="_blank"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-black hover:bg-gray-800 text-white transition"
                    title="Twitter/X"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                  </a>
                  <a
                    v-if="depute.reseaux_sociaux.facebook"
                    :href="depute.reseaux_sociaux.facebook"
                    target="_blank"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition"
                    title="Facebook"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                  </a>
                  <a
                    v-if="depute.reseaux_sociaux.linkedin"
                    :href="depute.reseaux_sociaux.linkedin"
                    target="_blank"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-700 hover:bg-blue-800 text-white transition"
                    title="LinkedIn"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                  </a>
                  <a
                    v-if="depute.reseaux_sociaux.instagram"
                    :href="depute.reseaux_sociaux.instagram"
                    target="_blank"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 hover:opacity-90 text-white transition"
                    title="Instagram"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                  </a>
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
