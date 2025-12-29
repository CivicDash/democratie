<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
  tags: Array,
  popularTags: Array,
});

const searchQuery = ref('');

const breadcrumbItems = [
    { label: 'Accueil', href: '/' },
    { label: 'Législation' },
    { label: 'Explorer par thème' },
];

const filteredTags = computed(() => {
  if (!searchQuery.value) return props.tags;
  
  return props.tags.filter(tag =>
    tag.nom?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    tag.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// Grouper par type
const groupedTags = computed(() => {
  const groups = {
    thematiques: [],
    keywords: [],
    autres: [],
  };

  filteredTags.value.forEach(tag => {
    if (tag.type === 'thematique') {
      groups.thematiques.push(tag);
    } else if (tag.type === 'keyword') {
      groups.keywords.push(tag);
    } else {
      groups.autres.push(tag);
    }
  });

  // Trier par usage_count décroissant
  groups.thematiques.sort((a, b) => (b.usage_count || 0) - (a.usage_count || 0));
  groups.keywords.sort((a, b) => (b.usage_count || 0) - (a.usage_count || 0));

  return groups;
});

const formatCount = (count) => {
  if (!count) return '0';
  return count.toLocaleString();
};
</script>

<template>
  <Head title="Explorer par thème - Législation" />

  <AuthenticatedLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-gray-900">
      <!-- Header -->
      <header class="bg-white dark:bg-gray-800 border-b border-slate-200 dark:border-gray-700">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
          <!-- Breadcrumb -->
          <div class="py-3 border-b border-slate-100 dark:border-gray-700/50">
            <Breadcrumb :items="breadcrumbItems" />
          </div>
          
          <!-- Title Section -->
          <div class="py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
              <div class="flex items-start gap-4">
                <div class="p-3 bg-slate-100 dark:bg-gray-700 rounded-xl">
                  <span class="text-3xl">🏷️</span>
                </div>
                <div>
                  <h1 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">
                    Explorer par thème
                  </h1>
                  <p class="mt-1 text-slate-500 dark:text-slate-400">
                    Découvrez les lois, scrutins et débats classés par thématique
                  </p>
                </div>
              </div>

              <!-- Stats -->
              <div class="flex flex-wrap gap-3">
                <div class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-gray-700 rounded-lg">
                  <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ tags?.length || 0 }}</span>
                  <span class="text-sm text-slate-500 dark:text-slate-400">thématiques</span>
                </div>
                <Link 
                  :href="route('lois.index')"
                  class="flex items-center gap-2 px-4 py-2 bg-sky-50 dark:bg-sky-900/20 rounded-lg hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors"
                >
                  <span class="text-sm text-sky-600 dark:text-sky-400">📜 Voir toutes les lois →</span>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Search -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-4 mb-6">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher une thématique..."
              class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-gray-700 border-0 rounded-lg 
                     text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500
                     focus:ring-2 focus:ring-sky-500 focus:bg-white dark:focus:bg-gray-600"
            />
            <svg class="absolute left-3 top-3.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Popular Tags -->
        <div v-if="popularTags?.length && !searchQuery" class="bg-gradient-to-r from-sky-50 to-teal-50 dark:from-sky-900/20 dark:to-teal-900/20 rounded-xl p-6 mb-6 border border-sky-100 dark:border-sky-800">
          <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span>🔥</span> Thématiques les plus actives
          </h2>
          <div class="flex flex-wrap gap-3">
            <Link
              v-for="tag in popularTags.slice(0, 10)"
              :key="tag.id"
              :href="route('lois.index', { thematique: tag.slug })"
              class="group inline-flex items-center gap-2 px-4 py-2.5 rounded-xl transition-all 
                     bg-white dark:bg-gray-800 hover:shadow-lg hover:scale-105 border-2"
              :style="{ borderColor: tag.couleur }"
            >
              <span class="text-xl">{{ tag.icone }}</span>
              <div>
                <div class="font-semibold text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400">
                  {{ tag.nom }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ formatCount(tag.usage_count) }} lois
                </div>
              </div>
            </Link>
          </div>
        </div>

        <!-- Thématiques Grid -->
        <div v-if="groupedTags.thematiques.length > 0" class="mb-8">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span>📚</span> Toutes les thématiques
            <span class="text-sm font-normal text-slate-500 dark:text-slate-400">({{ groupedTags.thematiques.length }})</span>
          </h2>
          
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <Link
              v-for="tag in groupedTags.thematiques"
              :key="tag.id"
              :href="route('lois.index', { thematique: tag.slug })"
              class="group bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 
                     hover:border-slate-300 dark:hover:border-gray-600 hover:shadow-lg
                     transition-all duration-200 overflow-hidden"
            >
              <div class="p-4">
                <div class="flex items-start gap-3">
                  <div 
                    class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center text-2xl"
                    :style="{ backgroundColor: tag.couleur + '20' }"
                  >
                    {{ tag.icone }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 truncate">
                      {{ tag.nom }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                      {{ formatCount(tag.usage_count) }} lois
                    </p>
                  </div>
                </div>
              </div>
              
              <!-- Footer with action -->
              <div class="px-4 py-2.5 bg-slate-50 dark:bg-gray-800/50 border-t border-slate-100 dark:border-gray-700/50">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-400 dark:text-slate-500">Voir les lois</span>
                  <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            </Link>
          </div>
        </div>

        <!-- Keywords (if any) -->
        <div v-if="groupedTags.keywords.length > 0" class="mb-8">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span>🏷️</span> Mots-clés
            <span class="text-sm font-normal text-slate-500 dark:text-slate-400">({{ groupedTags.keywords.length }})</span>
          </h2>
          
          <div class="flex flex-wrap gap-2">
            <Link
              v-for="tag in groupedTags.keywords"
              :key="tag.id"
              :href="route('lois.index', { thematique: tag.slug })"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium
                     bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 
                     hover:bg-slate-200 dark:hover:bg-gray-600 transition-colors"
            >
              <span v-if="tag.icone">{{ tag.icone }}</span>
              {{ tag.nom }}
              <span class="text-xs opacity-60">({{ tag.usage_count }})</span>
            </Link>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredTags.length === 0" class="text-center py-16">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 dark:bg-gray-700 rounded-full mb-4">
            <span class="text-3xl">🔍</span>
          </div>
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Aucune thématique trouvée</h3>
          <p class="mt-1 text-slate-500 dark:text-slate-400">Essayez avec d'autres mots-clés</p>
          <button
            @click="searchQuery = ''"
            class="mt-4 px-4 py-2 bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 dark:hover:bg-gray-600 
                   rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 transition-colors"
          >
            Réinitialiser la recherche
          </button>
        </div>
      </main>
    </div>
  </AuthenticatedLayout>
</template>
