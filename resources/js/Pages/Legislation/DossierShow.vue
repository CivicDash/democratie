<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
  dossier: Object,
  textes: Array,
  scrutins: Array,
  amendements: Array,
  stats: Object,
});

const getEtatColor = (etat) => {
  const colors = {
    'EN_COURS': 'blue',
    'ADOPTE': 'green',
    'REJETE': 'red',
    'RETIRE': 'gray',
  };
  return colors[etat] || 'gray';
};
</script>

<template>
  <Head :title="`${dossier.titre} - Dossier législatif`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('legislation.dossiers.index')" class="hover:text-blue-600 transition">
            Dossiers législatifs
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100 font-medium">{{ dossier.titre_court }}</span>
        </div>

        <!-- Header -->
        <Card>
          <div class="flex items-start justify-between gap-6">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <Badge :class="`bg-${getEtatColor(dossier.etat)}-100 text-${getEtatColor(dossier.etat)}-800`">
                  {{ dossier.etat_libelle }}
                </Badge>
                <Badge v-if="dossier.legislature" class="text-sm">
                  Législature {{ dossier.legislature }}
                </Badge>
                <span v-if="dossier.date_depot" class="text-sm text-gray-500">
                  Déposé le {{ dossier.date_depot }}
                </span>
              </div>

              <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                📜 {{ dossier.titre }}
              </h1>

              <p v-if="dossier.resume" class="text-gray-600 dark:text-gray-400 leading-relaxed">
                {{ dossier.resume }}
              </p>
            </div>

            <!-- Stats rapides -->
            <div class="grid grid-cols-2 gap-4">
              <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ stats.textes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Textes</div>
              </div>
              <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ stats.scrutins }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Scrutins</div>
              </div>
              <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ stats.amendements }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Amendements</div>
              </div>
              <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <div class="text-2xl font-bold text-orange-600">{{ stats.votes_deputes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Votes</div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Timeline / Étapes du dossier -->
        <Card>
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
            📅 Chronologie
          </h2>
          <div class="space-y-4">
            <!-- Dépôt -->
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                <span class="text-lg">📥</span>
              </div>
              <div class="flex-1">
                <div class="font-semibold text-gray-900 dark:text-gray-100">Dépôt du texte</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ dossier.date_depot }}</div>
              </div>
            </div>

            <!-- Textes successifs -->
            <div v-for="texte in textes" :key="texte.uid" class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                <span class="text-lg">📄</span>
              </div>
              <div class="flex-1">
                <Link 
                  :href="route('legislation.textes.show', texte.uid)"
                  class="font-semibold text-blue-600 hover:text-blue-700"
                >
                  {{ texte.titre_court }}
                </Link>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  {{ texte.type }} • {{ texte.amendements_count }} amendements
                </div>
              </div>
            </div>

            <!-- Scrutins -->
            <div v-for="scrutin in scrutins" :key="scrutin.uid" class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                <span class="text-lg">🗳️</span>
              </div>
              <div class="flex-1">
                <Link 
                  :href="route('legislation.scrutins.show', scrutin.uid)"
                  class="font-semibold text-blue-600 hover:text-blue-700"
                >
                  Scrutin n°{{ scrutin.numero }} - {{ scrutin.titre }}
                </Link>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  {{ scrutin.date }} • 
                  <span class="text-green-600">{{ scrutin.pour }} pour</span> • 
                  <span class="text-red-600">{{ scrutin.contre }} contre</span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Amendements principaux -->
        <Card v-if="amendements.length > 0">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
              📝 Amendements ({{ amendements.length }})
            </h2>
            <Link 
              :href="route('legislation.amendements.index', { dossier: dossier.uid })"
              class="text-sm text-blue-600 hover:text-blue-700"
            >
              Voir tous →
            </Link>
          </div>
          <div class="space-y-3">
            <Link
              v-for="amendement in amendements.slice(0, 5)"
              :key="amendement.uid"
              :href="route('legislation.amendements.show', amendement.uid)"
              class="block p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ amendement.numero_long }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 line-clamp-1">
                    {{ amendement.dispositif }}
                  </div>
                </div>
                <Badge :class="`bg-${amendement.etat === 'ADO' ? 'green' : 'red'}-100`">
                  {{ amendement.etat_libelle }}
                </Badge>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Débat citoyen associé -->
        <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                💬 Débat citoyen
              </h2>
              <p class="text-gray-600 dark:text-gray-400">
                Participez au débat et votez sur ce dossier législatif
              </p>
            </div>
            <Link
              :href="route('topics.create', { dossier: dossier.uid })"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
            >
              Créer un débat
            </Link>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
