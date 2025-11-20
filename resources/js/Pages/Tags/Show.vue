<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
  tag: Object,
  scrutins: Object,
  dossiers: Object,
  topics: Object,
});

const activeTab = ref('scrutins');
</script>

<template>
  <Head :title="`${tag.icon} ${tag.name}`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('tags.index')" class="hover:text-blue-600 transition">
            Thèmes
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100 font-medium">{{ tag.name }}</span>
        </div>

        <!-- Header du tag -->
        <Card
          class="text-center"
          :style="{ background: `linear-gradient(135deg, ${tag.color}15, ${tag.color}05)` }"
        >
          <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
               :style="{ backgroundColor: tag.color + '30' }">
            <span class="text-5xl">{{ tag.icon }}</span>
          </div>
          
          <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">
            {{ tag.name }}
          </h1>
          
          <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-6">
            {{ tag.description }}
          </p>

          <div class="flex items-center justify-center gap-6 text-sm">
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ scrutins.total }}
              </div>
              <div class="text-gray-600 dark:text-gray-400">Scrutins</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ dossiers.total }}
              </div>
              <div class="text-gray-600 dark:text-gray-400">Dossiers</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ topics.total }}
              </div>
              <div class="text-gray-600 dark:text-gray-400">Débats</div>
            </div>
          </div>
        </Card>

        <!-- Tabs -->
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
          <button
            @click="activeTab = 'scrutins'"
            :class="[
              'px-6 py-3 font-semibold transition-all border-b-2',
              activeTab === 'scrutins'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
            ]"
          >
            🗳️ Scrutins ({{ scrutins.total }})
          </button>
          <button
            @click="activeTab = 'dossiers'"
            :class="[
              'px-6 py-3 font-semibold transition-all border-b-2',
              activeTab === 'dossiers'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
            ]"
          >
            📜 Dossiers ({{ dossiers.total }})
          </button>
          <button
            @click="activeTab = 'topics'"
            :class="[
              'px-6 py-3 font-semibold transition-all border-b-2',
              activeTab === 'topics'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
            ]"
          >
            💬 Débats ({{ topics.total }})
          </button>
        </div>

        <!-- Contenu Scrutins -->
        <div v-show="activeTab === 'scrutins'" class="space-y-4">
          <Card v-for="scrutin in scrutins.data" :key="scrutin.uid">
            <Link :href="route('legislation.scrutins.show', scrutin.uid)" class="block group">
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <Badge>Scrutin n°{{ scrutin.numero }}</Badge>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      {{ scrutin.date }}
                    </span>
                    <Badge v-if="scrutin.resultat_libelle" class="bg-green-100 text-green-800 dark:bg-green-900/20">
                      {{ scrutin.resultat_libelle }}
                    </Badge>
                  </div>
                  
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition mb-2">
                    {{ scrutin.titre }}
                  </h3>

                  <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <span>✅ {{ scrutin.nombre_pour }} pour</span>
                    <span>❌ {{ scrutin.nombre_contre }} contre</span>
                    <span>⚪ {{ scrutin.nombre_abstention }} abstentions</span>
                  </div>
                </div>

                <div class="text-blue-600 group-hover:translate-x-1 transition-transform">
                  →
                </div>
              </div>
            </Link>
          </Card>

          <div v-if="scrutins.data.length === 0" class="text-center py-12">
            <div class="text-4xl mb-2">🗳️</div>
            <p class="text-gray-600 dark:text-gray-400">Aucun scrutin pour ce thème</p>
          </div>
        </div>

        <!-- Contenu Dossiers -->
        <div v-show="activeTab === 'dossiers'" class="space-y-4">
          <Card v-for="dossier in dossiers.data" :key="dossier.uid">
            <Link :href="route('legislation.dossiers.show', dossier.uid)" class="block group">
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <Badge>Législature {{ dossier.legislature }}</Badge>
                  </div>
                  
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition mb-2">
                    {{ dossier.titre_court || dossier.titre }}
                  </h3>

                  <p v-if="dossier.titre_court" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                    {{ dossier.titre }}
                  </p>
                </div>

                <div class="text-blue-600 group-hover:translate-x-1 transition-transform">
                  →
                </div>
              </div>
            </Link>
          </Card>

          <div v-if="dossiers.data.length === 0" class="text-center py-12">
            <div class="text-4xl mb-2">📜</div>
            <p class="text-gray-600 dark:text-gray-400">Aucun dossier pour ce thème</p>
          </div>
        </div>

        <!-- Contenu Topics -->
        <div v-show="activeTab === 'topics'" class="space-y-4">
          <Card v-for="topic in topics.data" :key="topic.id">
            <Link :href="route('topics.show', topic.id)" class="block group">
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      Par {{ topic.user_name }} • {{ topic.created_at }}
                    </span>
                  </div>
                  
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition mb-2">
                    {{ topic.title }}
                  </h3>

                  <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-2">
                    {{ topic.description }}
                  </p>

                  <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <span>💬 {{ topic.comments_count }} commentaires</span>
                    <span v-if="topic.votes_count">🗳️ {{ topic.votes_count }} votes</span>
                  </div>
                </div>

                <div class="text-blue-600 group-hover:translate-x-1 transition-transform">
                  →
                </div>
              </div>
            </Link>
          </Card>

          <div v-if="topics.data.length === 0" class="text-center py-12">
            <div class="text-4xl mb-2">💬</div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun débat pour ce thème</p>
            <Link
              :href="route('topics.create', { tag: tag.slug })"
              class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
            >
              💬 Créer un débat
            </Link>
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

