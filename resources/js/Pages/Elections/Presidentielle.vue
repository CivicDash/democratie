<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const breadcrumbItems = [
  { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
  { label: 'Élections', href: route('elections.hub'), icon: '🗳️' },
  { label: 'Présidentielle', icon: '🇫🇷' },
];
</script>

<template>
  <Head title="Élection Présidentielle" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-8" style="max-width: 100%;">
        
        <Breadcrumb :items="breadcrumbItems" />

        <!-- Hero Banner -->
        <div class="relative overflow-hidden bg-gradient-to-br from-amber-800 via-yellow-700 to-orange-800 rounded-2xl shadow-xl">
          <div class="relative px-8 py-12 lg:py-16">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
              <div>
                <h1 class="text-4xl lg:text-5xl font-bold text-white tracking-tight mb-4">
                  🇫🇷 Élection Présidentielle
                </h1>
                <p class="text-xl text-amber-100 max-w-2xl">
                  Élection du Président de la République au suffrage universel direct pour un mandat de 5 ans
                </p>
              </div>
              
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                <div class="text-amber-200 text-sm font-medium mb-2">📅 Prochaine élection</div>
                <div class="text-white text-2xl font-bold">Avril 2027</div>
                <div class="text-amber-100 text-lg mt-1">1er et 2ème tour</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Coming Soon -->
        <Card>
          <div class="text-center py-12">
            <span class="text-6xl">🚧</span>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-6">Page en construction</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-4 max-w-md mx-auto">
              Cette section sera bientôt disponible avec les candidats déclarés, parrainages et sondages.
            </p>
            <div class="mt-8 flex justify-center gap-4">
              <Link
                :href="route('elections.hub')"
                class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
              >
                ← Retour aux élections
              </Link>
              <Link
                :href="route('gouvernement.president')"
                class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition"
              >
                🏛️ Voir le Président actuel
              </Link>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
