<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
  scrutins: Object,
  filters: Object,
  stats: Object,
});

const search = ref(props.filters?.search || '');
const legislature = ref(props.filters?.legislature || '17');

const applyFilters = () => {
  router.get(route('legislation.scrutins.index'), {
    search: search.value || undefined,
    legislature: legislature.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilters = () => {
  search.value = '';
  legislature.value = '17';
  router.get(route('legislation.scrutins.index'));
};

const getResultatColor = (resultat) => {
  if (!resultat) return 'gray';
  if (resultat.includes('adopté')) return 'green';
  if (resultat.includes('rejeté')) return 'red';
  return 'gray';
};

const getResultatBadgeClass = (resultat) => {
  if (!resultat) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
  if (resultat.includes('adopté')) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (resultat.includes('rejeté')) return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

const breadcrumbs = [
  { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
  { label: 'Législation', href: route('legislation.hub'), icon: '⚖️' },
  { label: 'Scrutins AN', current: true, icon: '🗳️' },
];

const hasActiveFilters = computed(() => search.value || legislature.value !== '17');
</script>

<template>
  <Head title="Scrutins publics - Assemblée Nationale" />

  <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      
      <!-- Hero Section Full Width -->
      <section class="relative overflow-hidden bg-gradient-to-br from-sky-900 via-sky-800 to-blue-900">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
          <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        
        <div class="relative w-full px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
          <!-- Breadcrumb -->
          <div class="max-w-full mx-auto">
            <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
          </div>
          
          <div class="max-w-full mx-auto flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Titre -->
            <div>
              <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                <span class="text-4xl">🗳️</span>
                Scrutins publics
              </h1>
              <p class="text-sky-200 text-lg">
                Votes solennels de l'Assemblée Nationale - XVIIe législature
              </p>
            </div>
            
            <!-- Stats rapides -->
            <div class="flex flex-wrap gap-4">
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-white">{{ stats?.total?.toLocaleString() || 0 }}</div>
                <div class="text-sky-200 text-xs uppercase tracking-wide">Scrutins</div>
              </div>
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-emerald-400">{{ stats?.adoptes?.toLocaleString() || 0 }}</div>
                <div class="text-sky-200 text-xs uppercase tracking-wide">Adoptés</div>
              </div>
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-red-400">{{ stats?.rejetes?.toLocaleString() || 0 }}</div>
                <div class="text-sky-200 text-xs uppercase tracking-wide">Rejetés</div>
              </div>
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                <div class="text-2xl md:text-3xl font-bold text-amber-400">{{ stats?.taux_adoption || 0 }}%</div>
                <div class="text-sky-200 text-xs uppercase tracking-wide">Adoption</div>
              </div>
            </div>
          </div>
          
          <!-- Liens vers scrutins Sénat -->
          <div class="max-w-full mx-auto mt-6">
            <Link 
              :href="route('legislation.scrutins-senat.index')"
              class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition border border-white/20 text-sm"
            >
              <span>🔴</span>
              Voir les scrutins du Sénat →
            </Link>
          </div>
        </div>
      </section>

      <!-- Contenu principal - Full width -->
      <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
          <div class="flex flex-col md:flex-row gap-4">
            <!-- Recherche -->
            <div class="flex-1">
              <input
                v-model="search"
                type="text"
                placeholder="Rechercher un scrutin (titre, objet)..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-sky-500 focus:border-sky-500"
                @keyup.enter="applyFilters"
              />
            </div>
            
            <!-- Législature -->
            <select
              v-model="legislature"
              @change="applyFilters"
              class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-sky-500 focus:border-sky-500"
            >
              <option value="17">XVIIe Législature (2024-...)</option>
              <option value="16">XVIe Législature (2022-2024)</option>
              <option value="15">XVe Législature (2017-2022)</option>
            </select>
            
            <!-- Boutons -->
            <div class="flex gap-2">
              <button
                @click="applyFilters"
                class="px-6 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg transition font-medium"
              >
                Rechercher
              </button>
              <button
                v-if="hasActiveFilters"
                @click="resetFilters"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition"
              >
                Réinitialiser
              </button>
            </div>
          </div>
        </div>

        <!-- Liste des scrutins -->
        <div class="space-y-4">
          <Link
            v-for="scrutin in scrutins.data"
            :key="scrutin.uid"
            :href="route('legislation.scrutins.show', scrutin.uid)"
            class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-200 dark:border-gray-700"
          >
            <div class="flex items-start justify-between gap-6">
              <div class="flex-1 min-w-0">
                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-3 mb-3">
                  <span class="px-3 py-1 bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400 rounded-full text-sm font-medium">
                    N° {{ scrutin.numero }}
                  </span>
                  <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ scrutin.date || 'Date inconnue' }}
                  </span>
                  <span
                    v-if="scrutin.resultat_libelle"
                    class="px-3 py-1 rounded-full text-sm font-medium"
                    :class="getResultatBadgeClass(scrutin.resultat_libelle)"
                  >
                    {{ scrutin.resultat_libelle.includes('adopté') ? '✅' : '❌' }} 
                    {{ scrutin.resultat_libelle }}
                  </span>
                </div>
                
                <!-- Titre -->
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                  {{ scrutin.titre }}
                </h3>
                
                <!-- Objet -->
                <p v-if="scrutin.objet" class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">
                  {{ scrutin.objet }}
                </p>
              </div>

              <!-- Résultats de vote -->
              <div class="flex-shrink-0 flex items-center gap-6">
                <div class="text-center">
                  <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ scrutin.pour }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Pour</div>
                </div>
                <div class="text-center">
                  <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ scrutin.contre }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Contre</div>
                </div>
                <div class="text-center">
                  <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ scrutin.abstentions }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Abst.</div>
                </div>
              </div>
            </div>
            
            <!-- Barre de progression -->
            <div v-if="scrutin.pour || scrutin.contre" class="mt-4 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex">
              <div
                class="h-full bg-emerald-500"
                :style="{ width: `${(scrutin.pour / (scrutin.pour + scrutin.contre + (scrutin.abstentions || 0))) * 100}%` }"
              ></div>
              <div
                class="h-full bg-red-500"
                :style="{ width: `${(scrutin.contre / (scrutin.pour + scrutin.contre + (scrutin.abstentions || 0))) * 100}%` }"
              ></div>
              <div
                class="h-full bg-amber-400"
                :style="{ width: `${((scrutin.abstentions || 0) / (scrutin.pour + scrutin.contre + (scrutin.abstentions || 0))) * 100}%` }"
              ></div>
            </div>
          </Link>
        </div>

        <!-- Pagination -->
        <div v-if="scrutins.links?.length > 3" class="mt-8 flex justify-center">
          <nav class="flex items-center gap-1">
            <template v-for="link in scrutins.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                class="px-4 py-2 rounded-lg text-sm transition-colors"
                :class="link.active 
                  ? 'bg-sky-600 text-white' 
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                v-html="link.label"
              />
              <span
                v-else
                class="px-4 py-2 text-sm text-gray-400"
                v-html="link.label"
              />
            </template>
          </nav>
        </div>
        
        <!-- Empty state -->
        <div v-if="!scrutins.data?.length" class="text-center py-16">
          <div class="text-6xl mb-4">🗳️</div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Aucun scrutin trouvé
          </h3>
          <p class="text-gray-500 dark:text-gray-400">
            Essayez de modifier vos filtres de recherche.
          </p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
