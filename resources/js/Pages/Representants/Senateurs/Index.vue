<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
  senateurs: Object,
  groupes: Array,
  filters: Object,
  stats: {
    type: Object,
    default: () => ({
      total: 348,
      groupes: 8,
      femmes_pct: 35,
      age_moyen: 60,
      serie: 2,
    })
  },
});

const search = ref(props.filters.search || '');
const selectedGroupe = ref(props.filters.groupe || '');

const applyFilters = () => {
  router.get(route('representants.senateurs.index'), {
    search: search.value,
    groupe: selectedGroupe.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Parlement', href: route('representants.mes-representants'), icon: '🏛️' },
    { label: 'Sénat', current: true, icon: '🏰' },
];
</script>

<template>
  <Head title="Sénateurs - Sénat" />

  <AuthenticatedLayout>
    <!-- Hero Section Full Width -->
    <section class="relative overflow-hidden bg-gradient-to-br from-rose-900 via-rose-800 to-pink-900">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>
      
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <!-- Breadcrumb -->
        <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
        
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <!-- Titre -->
          <div>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
              <span class="text-4xl">🏰</span>
              Sénat
            </h1>
            <p class="text-rose-200 text-lg">
              La chambre haute du Parlement français, représentante des collectivités territoriales
            </p>
          </div>
          
          <!-- Stats rapides -->
          <div class="flex flex-wrap gap-4">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
              <div class="text-2xl md:text-3xl font-bold text-white">348</div>
              <div class="text-rose-200 text-xs uppercase tracking-wide">Sièges</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
              <div class="text-2xl md:text-3xl font-bold text-white">{{ senateurs.total || stats.total || 348 }}</div>
              <div class="text-rose-200 text-xs uppercase tracking-wide">Sénateurs</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
              <div class="text-2xl md:text-3xl font-bold text-amber-400">6 ans</div>
              <div class="text-rose-200 text-xs uppercase tracking-wide">Mandat</div>
            </div>
          </div>
        </div>

        <!-- Stats secondaires -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">👥</span>
              <div>
                <div class="text-xl font-bold text-white">{{ stats.groupes || 8 }}</div>
                <div class="text-rose-300 text-sm">Groupes politiques</div>
              </div>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">👩</span>
              <div>
                <div class="text-xl font-bold text-white">{{ stats.femmes_pct || 35 }}%</div>
                <div class="text-rose-300 text-sm">Femmes sénatrices</div>
              </div>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">📊</span>
              <div>
                <div class="text-xl font-bold text-white">{{ stats.age_moyen || 60 }} ans</div>
                <div class="text-rose-300 text-sm">Âge moyen</div>
              </div>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
            <div class="flex items-center gap-3">
              <span class="text-2xl">🗳️</span>
              <div>
                <div class="text-xl font-bold text-white">Série {{ stats.serie || 2 }}</div>
                <div class="text-rose-300 text-sm">Prochaine élection</div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Liens rapides -->
        <div class="flex flex-wrap gap-3 mt-6">
          <Link 
            :href="route('legislation.scrutins-senat.index')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg border border-white/20 transition-colors"
          >
            🗳️ Scrutins publics
          </Link>
          <Link 
            :href="route('legislation.groupes.index')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg border border-white/20 transition-colors"
          >
            🎨 Groupes parlementaires
          </Link>
        </div>
      </div>
    </section>

    <!-- Contenu principal -->
    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        
        <!-- Filtres -->
        <Card>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🔍 Rechercher un sénateur
              </label>
              <TextInput
                v-model="search"
                placeholder="Nom, prénom, circonscription..."
                @keyup.enter="applyFilters"
                class="w-full min-h-[44px]"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🎨 Groupe parlementaire
              </label>
              <select
                v-model="selectedGroupe"
                @change="applyFilters"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 min-h-[44px]"
              >
                <option value="">Tous les groupes</option>
                <option v-for="groupe in props.groupes" :key="groupe.sigle" :value="groupe.sigle">
                  {{ groupe.nom }} ({{ groupe.sigle }})
                </option>
              </select>
            </div>
            <div class="flex items-end sm:col-span-2 lg:col-span-1 gap-2">
              <button
                @click="applyFilters"
                class="flex-1 px-4 py-3 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition min-h-[44px] font-medium"
              >
                🔎 Rechercher
              </button>
              <button
                @click="search = ''; selectedGroupe = ''; applyFilters()"
                class="px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition min-h-[44px]"
                title="Réinitialiser"
              >
                🔄
              </button>
            </div>
          </div>
        </Card>

        <!-- Résultats -->
        <Card>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              📋 Liste des sénateurs
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ senateurs.total || senateurs.data?.length || 0 }} résultat(s)
            </span>
          </div>

          <!-- Vue Desktop : Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Sénateur
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Groupe
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Circonscription
                  </th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="senateur in senateurs.data"
                  :key="senateur.matricule"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                        <img
                          v-if="senateur.photo_url"
                          :src="senateur.photo_url"
                          :alt="senateur.nom_complet"
                          class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-xl">
                          👤
                        </div>
                      </div>
                      <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                          {{ senateur.nom_complet }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ senateur.profession || 'Profession non renseignée' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <Badge
                      v-if="senateur.groupe"
                      :style="{ 
                        backgroundColor: senateur.groupe.couleur || '#6B7280',
                        color: '#fff'
                      }"
                    >
                      {{ senateur.groupe.sigle || senateur.groupe.nom }}
                    </Badge>
                    <span v-else class="text-gray-500 dark:text-gray-400 text-sm">Non inscrit</span>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                    {{ senateur.circonscription }}
                  </td>
                  <td class="px-4 py-4 text-right">
                    <Link
                      :href="route('representants.senateurs.show', senateur.matricule)"
                      class="inline-flex items-center px-4 py-2 bg-rose-600 text-white text-sm rounded-lg hover:bg-rose-700 transition font-medium"
                    >
                      Voir la fiche →
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Vue Mobile : Cards -->
          <div class="lg:hidden space-y-4">
            <Link
              v-for="senateur in senateurs.data"
              :key="senateur.matricule"
              :href="route('representants.senateurs.show', senateur.matricule)"
              class="block p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition active:bg-gray-50 dark:active:bg-gray-700"
            >
              <div class="flex items-start gap-4">
                <!-- Photo -->
                <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                  <img
                    v-if="senateur.photo_url"
                    :src="senateur.photo_url"
                    :alt="senateur.nom_complet"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-2xl">
                    👤
                  </div>
                </div>
                
                <!-- Infos -->
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                    {{ senateur.nom_complet }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ senateur.profession || 'Profession non renseignée' }}
                  </p>
                  
                  <div class="flex flex-wrap items-center gap-2 mt-2">
                    <Badge
                      v-if="senateur.groupe"
                      :style="{ 
                        backgroundColor: senateur.groupe.couleur || '#6B7280',
                        color: '#fff'
                      }"
                      class="text-xs"
                    >
                      {{ senateur.groupe.sigle || senateur.groupe.nom }}
                    </Badge>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      📍 {{ senateur.circonscription }}
                    </span>
                  </div>
                </div>
                
                <!-- Chevron -->
                <div class="flex-shrink-0 text-gray-400">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            </Link>
          </div>

          <!-- Pagination -->
          <div v-if="senateurs.links" class="mt-6 flex flex-wrap justify-center gap-1 sm:gap-2">
            <Link
              v-for="(link, index) in senateurs.links"
              :key="index"
              :href="link.url"
              v-html="link.label"
              :class="[
                'px-2 sm:px-3 py-2 rounded text-xs sm:text-sm min-h-[44px] flex items-center justify-center',
                link.active 
                  ? 'bg-rose-600 text-white' 
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600',
                !link.url && 'opacity-50 cursor-not-allowed pointer-events-none'
              ]"
            />
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
