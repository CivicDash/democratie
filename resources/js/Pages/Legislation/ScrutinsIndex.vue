<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  scrutins: Object,
  filters: Object,
  stats: Object,
});

const search = ref(props.filters.search || '');
const legislature = ref(props.filters.legislature || '17');

const applyFilters = () => {
  router.get(route('legislation.scrutins.index'), {
    search: search.value,
    legislature: legislature.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getResultatColor = (resultat) => {
  if (!resultat) return 'gray';
  if (resultat.includes('adopté')) return 'green';
  if (resultat.includes('rejeté')) return 'red';
  return 'gray';
};

const getResultatIcon = (resultat) => {
  if (!resultat) return '❓';
  if (resultat.includes('adopté')) return '✅';
  if (resultat.includes('rejeté')) return '❌';
  return '📊';
};
</script>

<template>
  <Head title="Scrutins publics - Assemblée Nationale" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
              🗳️ Scrutins publics
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
              Votes solennels de l'Assemblée Nationale
            </p>
          </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card class="bg-blue-50 dark:bg-blue-900/20">
            <div class="text-center">
              <div class="text-3xl font-bold text-blue-600">{{ stats.total }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Scrutins L17</div>
            </div>
          </Card>
          <Card class="bg-green-50 dark:bg-green-900/20">
            <div class="text-center">
              <div class="text-3xl font-bold text-green-600">{{ stats.adoptes }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Adoptés</div>
            </div>
          </Card>
          <Card class="bg-red-50 dark:bg-red-900/20">
            <div class="text-center">
              <div class="text-3xl font-bold text-red-600">{{ stats.rejetes }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Rejetés</div>
            </div>
          </Card>
          <Card class="bg-purple-50 dark:bg-purple-900/20">
            <div class="text-center">
              <div class="text-3xl font-bold text-purple-600">{{ stats.taux_adoption }}%</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Taux adoption</div>
            </div>
          </Card>
        </div>

        <!-- Filtres -->
        <Card>
          <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
              <TextInput
                v-model="search"
                type="text"
                placeholder="Rechercher un scrutin (titre, objet)..."
                class="w-full"
                @keyup.enter="applyFilters"
              />
            </div>
            <select
              v-model="legislature"
              @change="applyFilters"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
            >
              <option value="17">Législature 17</option>
              <option value="16">Législature 16</option>
              <option value="15">Législature 15</option>
            </select>
            <button
              @click="applyFilters"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
              Rechercher
            </button>
          </div>
        </Card>

        <!-- Liste des scrutins -->
        <Card>
          <div class="space-y-4">
            <Link
              v-for="scrutin in scrutins.data"
              :key="scrutin.uid"
              :href="route('legislation.scrutins.show', scrutin.uid)"
              class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <Badge class="text-sm">
                      N° {{ scrutin.numero }}
                    </Badge>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ scrutin.date }}
                    </span>
                    <Badge
                      v-if="scrutin.resultat_libelle"
                      :class="`bg-${getResultatColor(scrutin.resultat_libelle)}-100 text-${getResultatColor(scrutin.resultat_libelle)}-800 dark:bg-${getResultatColor(scrutin.resultat_libelle)}-900/20 dark:text-${getResultatColor(scrutin.resultat_libelle)}-400`"
                    >
                      {{ getResultatIcon(scrutin.resultat_libelle) }} {{ scrutin.resultat_libelle }}
                    </Badge>
                  </div>
                  
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    {{ scrutin.titre }}
                  </h3>
                  
                  <p v-if="scrutin.objet" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                    {{ scrutin.objet }}
                  </p>
                </div>

                <!-- Résultats -->
                <div class="flex items-center gap-6 text-sm">
                  <div class="text-center">
                    <div class="font-bold text-green-600">{{ scrutin.pour }}</div>
                    <div class="text-xs text-gray-500">Pour</div>
                  </div>
                  <div class="text-center">
                    <div class="font-bold text-red-600">{{ scrutin.contre }}</div>
                    <div class="text-xs text-gray-500">Contre</div>
                  </div>
                  <div class="text-center">
                    <div class="font-bold text-orange-600">{{ scrutin.abstentions }}</div>
                    <div class="text-xs text-gray-500">Abst.</div>
                  </div>
                </div>
              </div>
            </Link>
          </div>

          <!-- Pagination -->
          <div v-if="scrutins.links.length > 3" class="mt-6 flex justify-center gap-2">
            <Link
              v-for="(link, index) in scrutins.links"
              :key="index"
              :href="link.url"
              :class="[
                'px-4 py-2 rounded-lg transition',
                link.active
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                !link.url && 'opacity-50 cursor-not-allowed'
              ]"
              v-html="link.label"
            />
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

