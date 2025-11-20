<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';

const props = defineProps({
  tags: Array,
  popularTags: Array,
});

const selectedTag = ref(null);
const searchQuery = ref('');

const filteredTags = computed(() => {
  if (!searchQuery.value) return props.tags;
  
  return props.tags.filter(tag =>
    tag.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    tag.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const groupedTags = computed(() => {
  const groups = {
    thematiques: [],
    types: [],
    autres: [],
  };

  filteredTags.value.forEach(tag => {
    if (['loi', 'budget', 'constitution', 'referendum'].includes(tag.slug)) {
      groups.types.push(tag);
    } else if (['urgent', 'important', 'controverse'].includes(tag.slug)) {
      groups.autres.push(tag);
    } else {
      groups.thematiques.push(tag);
    }
  });

  return groups;
});
</script>

<template>
  <Head title="Explorer par thème" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">
            🏷️ Explorer par thème
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            Découvrez les scrutins, dossiers législatifs et débats citoyens organisés par thématique
          </p>
        </div>

        <!-- Recherche globale -->
        <Card>
          <GlobalSearch placeholder="Rechercher par thème, député, scrutin..." />
        </Card>

        <!-- Tags populaires -->
        <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
            🔥 Thèmes populaires
          </h2>
          <div class="flex flex-wrap gap-3">
            <Link
              v-for="tag in popularTags"
              :key="tag.id"
              :href="route('tags.show', tag.slug)"
              class="group"
            >
              <div
                class="px-4 py-3 rounded-xl transition-all transform hover:scale-105 hover:shadow-lg cursor-pointer"
                :style="{ backgroundColor: tag.color + '20', borderLeft: `4px solid ${tag.color}` }"
              >
                <div class="flex items-center gap-2">
                  <span class="text-2xl">{{ tag.icon }}</span>
                  <div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100 group-hover:underline">
                      {{ tag.name }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                      {{ tag.usage_count }} contenus
                    </div>
                  </div>
                </div>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Recherche de tags -->
        <Card>
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Filtrer les thèmes..."
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-gray-100"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </Card>

        <!-- Thématiques principales -->
        <Card v-if="groupedTags.thematiques.length > 0">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            📚 Thématiques
          </h2>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
              v-for="tag in groupedTags.thematiques"
              :key="tag.id"
              :href="route('tags.show', tag.slug)"
              class="group"
            >
              <div
                class="p-4 rounded-lg border-2 transition-all hover:shadow-lg hover:-translate-y-1"
                :style="{ borderColor: tag.color }"
              >
                <div class="flex items-start gap-3">
                  <span class="text-3xl">{{ tag.icon }}</span>
                  <div class="flex-1">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 group-hover:underline mb-1">
                      {{ tag.name }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                      {{ tag.description }}
                    </p>
                    <div class="text-xs text-gray-500 dark:text-gray-500">
                      {{ tag.usage_count }} contenus
                    </div>
                  </div>
                </div>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Types de textes -->
        <Card v-if="groupedTags.types.length > 0">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            📜 Types de textes
          </h2>
          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Link
              v-for="tag in groupedTags.types"
              :key="tag.id"
              :href="route('tags.show', tag.slug)"
              class="group"
            >
              <div
                class="p-4 rounded-lg border-2 transition-all hover:shadow-lg text-center"
                :style="{ borderColor: tag.color }"
              >
                <span class="text-4xl block mb-2">{{ tag.icon }}</span>
                <h3 class="font-bold text-gray-900 dark:text-gray-100 group-hover:underline mb-1">
                  {{ tag.name }}
                </h3>
                <div class="text-xs text-gray-500 dark:text-gray-500">
                  {{ tag.usage_count }}
                </div>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Autres tags -->
        <Card v-if="groupedTags.autres.length > 0">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            ⚡ Filtres spéciaux
          </h2>
          <div class="grid sm:grid-cols-3 gap-4">
            <Link
              v-for="tag in groupedTags.autres"
              :key="tag.id"
              :href="route('tags.show', tag.slug)"
              class="group"
            >
              <div
                class="p-4 rounded-lg border-2 transition-all hover:shadow-lg text-center"
                :style="{ borderColor: tag.color }"
              >
                <span class="text-4xl block mb-2">{{ tag.icon }}</span>
                <h3 class="font-bold text-gray-900 dark:text-gray-100 group-hover:underline mb-1">
                  {{ tag.name }}
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                  {{ tag.description }}
                </p>
                <div class="text-xs text-gray-500 dark:text-gray-500">
                  {{ tag.usage_count }}
                </div>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Aucun résultat -->
        <Card v-if="filteredTags.length === 0" class="text-center py-12">
          <div class="text-6xl mb-4">🔍</div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            Aucun thème trouvé
          </h3>
          <p class="text-gray-600 dark:text-gray-400">
            Essayez avec d'autres mots-clés
          </p>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

