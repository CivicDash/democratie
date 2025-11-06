<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  query: String,
});

const results = ref({
  topics: [],
  posts: [],
  documents: [],
});

const loading = ref(false);
const searchQuery = ref(props.query || '');
const selectedType = ref('all');
const selectedScope = ref('');
const selectedTopicType = ref('');
const totalResults = ref(0);
const searchTime = ref(0);

/**
 * Perform search
 */
const performSearch = async () => {
  if (searchQuery.value.length < 2) return;

  loading.value = true;

  try {
    const response = await axios.get('/api/search', {
      params: {
        q: searchQuery.value,
        type: selectedType.value,
        scope: selectedScope.value || undefined,
        type_topic: selectedTopicType.value || undefined,
        limit: 20,
      },
    });

    if (response.data.success) {
      results.value = response.data.results;
      totalResults.value = response.data.total;
      searchTime.value = response.data.took_ms;
    }
  } catch (error) {
    console.error('Erreur recherche:', error);
  } finally {
    loading.value = false;
  }
};

/**
 * Clear filters
 */
const clearFilters = () => {
  selectedScope.value = '';
  selectedTopicType.value = '';
  performSearch();
};

/**
 * Get type badge color
 */
const getTypeBadgeColor = (type) => {
  const colors = {
    'question': 'bg-blue-100 text-blue-800',
    'proposal': 'bg-green-100 text-green-800',
    'debate': 'bg-purple-100 text-purple-800',
    'announcement': 'bg-yellow-100 text-yellow-800',
  };
  return colors[type] || 'bg-gray-100 text-gray-800';
};

/**
 * Get type icon
 */
const getTypeIcon = (type) => {
  const icons = {
    'question': '❓',
    'proposal': '💡',
    'debate': '💬',
    'announcement': '📢',
  };
  return icons[type] || '📝';
};

// Load initial results
onMounted(() => {
  if (searchQuery.value) {
    performSearch();
  }
});
</script>

<template>
  <Head title="Recherche" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
        🔍 Recherche
      </h2>
    </template>

    <div class="py-8">
      <div class="mx-auto max-w-full sm:px-6 lg:px-8">
        
        <!-- Search Bar -->
        <div class="mb-8">
          <div class="flex gap-4">
            <input
              v-model="searchQuery"
              @keyup.enter="performSearch"
              type="text"
              placeholder="Rechercher des sujets, posts, documents..."
              class="flex-1 px-6 py-4 text-lg border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition"
            />
            <button
              @click="performSearch"
              :disabled="loading || searchQuery.length < 2"
              class="px-8 py-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              {{ loading ? 'Recherche...' : 'Rechercher' }}
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Filtres
            </h3>
            <button
              v-if="selectedScope || selectedTopicType"
              @click="clearFilters"
              class="text-sm text-blue-600 hover:text-blue-700 font-medium"
            >
              Réinitialiser
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Type Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Type de résultat
              </label>
              <select
                v-model="selectedType"
                @change="performSearch"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
              >
                <option value="all">Tous</option>
                <option value="topics">Sujets</option>
                <option value="posts">Posts</option>
                <option value="documents">Documents</option>
              </select>
            </div>

            <!-- Scope Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Portée
              </label>
              <select
                v-model="selectedScope"
                @change="performSearch"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
              >
                <option value="">Toutes</option>
                <option value="national">National</option>
                <option value="region">Régional</option>
                <option value="dept">Départemental</option>
              </select>
            </div>

            <!-- Topic Type Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Type de sujet
              </label>
              <select
                v-model="selectedTopicType"
                @change="performSearch"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
              >
                <option value="">Tous</option>
                <option value="question">Question</option>
                <option value="proposal">Proposition</option>
                <option value="debate">Débat</option>
                <option value="announcement">Annonce</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Results Summary -->
        <div v-if="!loading && totalResults > 0" class="mb-6 text-gray-600 dark:text-gray-400">
          <p>
            <strong>{{ totalResults }}</strong> résultat(s) pour 
            <strong>"{{ searchQuery }}"</strong>
            <span class="text-sm ml-2">({{ searchTime }}ms)</span>
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          <p class="mt-4 text-gray-600 dark:text-gray-400">Recherche en cours...</p>
        </div>

        <!-- No Results -->
        <div v-else-if="!loading && totalResults === 0 && searchQuery" class="text-center py-12">
          <div class="text-6xl mb-4">🔍</div>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            Aucun résultat
          </h3>
          <p class="text-gray-600 dark:text-gray-400">
            Essayez avec d'autres mots-clés ou modifiez vos filtres
          </p>
        </div>

        <!-- Results -->
        <div v-else-if="!loading && totalResults > 0" class="space-y-6">
          
          <!-- Topics Results -->
          <div v-if="results.topics && results.topics.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-blue-50 dark:bg-blue-900/20 px-6 py-3 border-b border-blue-100 dark:border-blue-800">
              <h3 class="font-semibold text-blue-900 dark:text-blue-100 flex items-center gap-2">
                <span>📝</span>
                <span>Sujets ({{ results.topics.length }})</span>
              </h3>
            </div>
            <div class="p-6 space-y-4">
              <Link
                v-for="topic in results.topics"
                :key="topic.id"
                :href="topic.url"
                class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 hover:shadow-md transition"
              >
                <div class="flex items-start justify-between gap-4 mb-2">
                  <div class="flex items-center gap-2">
                    <span class="text-2xl">{{ getTypeIcon(topic.type) }}</span>
                    <span :class="`px-3 py-1 rounded-full text-xs font-semibold ${getTypeBadgeColor(topic.type)}`">
                      {{ topic.type }}
                    </span>
                    <span class="text-xs text-gray-500">{{ topic.scope }}</span>
                  </div>
                  <span class="text-sm text-gray-500">{{ topic.created_at }}</span>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                  {{ topic.title }}
                </h4>
                <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">
                  {{ topic.description }}
                </p>
                <div class="mt-2 text-sm text-gray-500">
                  👤 {{ topic.author }}
                </div>
              </Link>
            </div>
          </div>

          <!-- Posts Results -->
          <div v-if="results.posts && results.posts.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-green-50 dark:bg-green-900/20 px-6 py-3 border-b border-green-100 dark:border-green-800">
              <h3 class="font-semibold text-green-900 dark:text-green-100 flex items-center gap-2">
                <span>💬</span>
                <span>Posts ({{ results.posts.length }})</span>
              </h3>
            </div>
            <div class="p-6 space-y-4">
              <Link
                v-for="post in results.posts"
                :key="post.id"
                :href="post.url"
                class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-green-500 hover:shadow-md transition"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Dans: {{ post.topic_title }}
                  </span>
                  <span class="text-sm text-gray-500">{{ post.created_at }}</span>
                </div>
                <p class="text-gray-900 dark:text-gray-100 mb-2">
                  {{ post.content }}
                </p>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <span>👤 {{ post.author }}</span>
                  <span>👍 {{ post.upvotes }}</span>
                </div>
              </Link>
            </div>
          </div>

          <!-- Documents Results -->
          <div v-if="results.documents && results.documents.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-purple-50 dark:bg-purple-900/20 px-6 py-3 border-b border-purple-100 dark:border-purple-800">
              <h3 class="font-semibold text-purple-900 dark:text-purple-100 flex items-center gap-2">
                <span>📄</span>
                <span>Documents ({{ results.documents.length }})</span>
              </h3>
            </div>
            <div class="p-6 space-y-4">
              <Link
                v-for="document in results.documents"
                :key="document.id"
                :href="document.url"
                class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-purple-500 hover:shadow-md transition"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    📎 {{ document.filename }}
                  </span>
                  <span class="text-sm text-gray-500">{{ document.created_at }}</span>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                  {{ document.title }}
                </h4>
                <p v-if="document.description" class="text-gray-600 dark:text-gray-400 text-sm mb-2">
                  {{ document.description }}
                </p>
                <div class="text-sm text-gray-500">
                  👤 {{ document.uploader }}
                </div>
              </Link>
            </div>
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

