<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
  prochaines_elections: Array,
  statistiques: Object,
});

const breadcrumbItems = [
  { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
  { label: 'Élections', icon: '🗳️' },
];

const typeElections = [
  {
    id: 'municipales',
    title: 'Élections Municipales',
    icon: '🏘️',
    description: 'Maires et conseils municipaux de 34 914 communes',
    color: 'emerald',
    prochaine: 'Mars 2026',
    route: 'elections.municipales.index',
    stats: { label: 'Communes', value: '34 914' },
  },
  {
    id: 'legislatives',
    title: 'Élections Législatives',
    icon: '🏛️',
    description: '577 députés élus au scrutin uninominal majoritaire',
    color: 'indigo',
    prochaine: 'Juin 2027',
    route: 'elections.legislatives',
    stats: { label: 'Circonscriptions', value: '577' },
  },
  {
    id: 'senatoriales',
    title: 'Élections Sénatoriales',
    icon: '🔴',
    description: '348 sénateurs élus par les grands électeurs',
    color: 'rose',
    prochaine: 'Septembre 2026',
    route: 'elections.senatoriales',
    stats: { label: 'Sièges', value: '170' },
  },
  {
    id: 'presidentielle',
    title: 'Élection Présidentielle',
    icon: '🇫🇷',
    description: 'Chef de l\'État élu au suffrage universel direct',
    color: 'amber',
    prochaine: 'Avril 2027',
    route: 'elections.presidentielle',
    stats: { label: 'Mandat', value: '5 ans' },
  },
];

const getColorClasses = (color) => ({
  bg: `bg-${color}-50 dark:bg-${color}-900/20`,
  border: `border-${color}-200 dark:border-${color}-800`,
  text: `text-${color}-600 dark:text-${color}-400`,
  hover: `hover:border-${color}-400 dark:hover:border-${color}-600`,
  badge: `bg-${color}-100 text-${color}-800 dark:bg-${color}-900/50 dark:text-${color}-300`,
});
</script>

<template>
  <Head title="Élections" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-8" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <Breadcrumb :items="breadcrumbItems" />

        <!-- Hero Banner -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-900 via-indigo-800 to-purple-900 rounded-2xl shadow-xl">
          <!-- Pattern décoratif -->
          <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
              <defs>
                <pattern id="elections-grid" x="0" y="0" width="10" height="10" patternUnits="userSpaceOnUse">
                  <circle cx="1" cy="1" r="1" fill="white"/>
                </pattern>
              </defs>
              <rect fill="url(#elections-grid)" width="100" height="100"/>
            </svg>
          </div>
          
          <div class="relative px-8 py-12 lg:py-16">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
              <div>
                <h1 class="text-4xl lg:text-5xl font-bold text-white tracking-tight mb-4">
                  🗳️ Élections en France
                </h1>
                <p class="text-xl text-blue-100 max-w-2xl">
                  Suivez les échéances électorales, découvrez les candidats et préparez votre vote
                </p>
              </div>
              
              <!-- Prochaine élection -->
              <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                <div class="text-blue-200 text-sm font-medium mb-2">📅 Prochaine élection</div>
                <div class="text-white text-2xl font-bold">Municipales 2026</div>
                <div class="text-blue-100 text-lg mt-1">Mars 2026</div>
                <div class="mt-3 flex items-center gap-2">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Dans ~2 mois
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Grille des types d'élections -->
        <div class="grid md:grid-cols-2 gap-6">
          <Link
            v-for="election in typeElections"
            :key="election.id"
            :href="route(election.route)"
            class="group block"
          >
            <Card class="h-full transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                  :class="`border-2 border-transparent hover:border-${election.color}-400 dark:hover:border-${election.color}-600`">
              <div class="flex items-start gap-4">
                <!-- Icône -->
                <div :class="`flex-shrink-0 w-16 h-16 rounded-xl flex items-center justify-center text-3xl bg-${election.color}-50 dark:bg-${election.color}-900/30`">
                  {{ election.icon }}
                </div>
                
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                      {{ election.title }}
                    </h2>
                    <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${election.color}-100 text-${election.color}-800 dark:bg-${election.color}-900/50 dark:text-${election.color}-300`">
                      {{ election.stats.value }} {{ election.stats.label }}
                    </span>
                  </div>
                  
                  <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ election.description }}
                  </p>
                  
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm">
                      <span class="text-gray-500 dark:text-gray-400">📅 Prochaine :</span>
                      <span :class="`font-semibold text-${election.color}-600 dark:text-${election.color}-400`">
                        {{ election.prochaine }}
                      </span>
                    </div>
                    
                    <span class="text-gray-400 group-hover:text-indigo-500 transition transform group-hover:translate-x-1">
                      →
                    </span>
                  </div>
                </div>
              </div>
            </Card>
          </Link>
        </div>

        <!-- Calendrier électoral -->
        <Card>
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
            <span>📅</span>
            <span>Calendrier électoral 2026-2027</span>
          </h2>
          
          <div class="relative">
            <!-- Timeline -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
            
            <div class="space-y-6">
              <!-- Mars 2026 - Municipales -->
              <div class="relative flex items-start gap-4 pl-10">
                <div class="absolute left-2 w-4 h-4 rounded-full bg-emerald-500 border-4 border-white dark:border-gray-800"></div>
                <div class="flex-1 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Mars 2026</span>
                      <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">🏘️ Élections Municipales</h3>
                    </div>
                    <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 rounded-full text-sm font-medium">
                      À venir
                    </span>
                  </div>
                  <p class="text-gray-600 dark:text-gray-400 mt-2">Renouvellement des 34 914 conseils municipaux</p>
                </div>
              </div>
              
              <!-- Septembre 2026 - Sénatoriales -->
              <div class="relative flex items-start gap-4 pl-10">
                <div class="absolute left-2 w-4 h-4 rounded-full bg-rose-500 border-4 border-white dark:border-gray-800"></div>
                <div class="flex-1 bg-rose-50 dark:bg-rose-900/20 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="text-sm font-medium text-rose-600 dark:text-rose-400">Septembre 2026</span>
                      <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">🔴 Élections Sénatoriales</h3>
                    </div>
                    <span class="px-3 py-1 bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 rounded-full text-sm font-medium">
                      Série 2
                    </span>
                  </div>
                  <p class="text-gray-600 dark:text-gray-400 mt-2">Renouvellement de 170 sièges de sénateurs</p>
                </div>
              </div>
              
              <!-- Avril 2027 - Présidentielle -->
              <div class="relative flex items-start gap-4 pl-10">
                <div class="absolute left-2 w-4 h-4 rounded-full bg-amber-500 border-4 border-white dark:border-gray-800"></div>
                <div class="flex-1 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="text-sm font-medium text-amber-600 dark:text-amber-400">Avril 2027</span>
                      <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">🇫🇷 Élection Présidentielle</h3>
                    </div>
                    <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium">
                      2027
                    </span>
                  </div>
                  <p class="text-gray-600 dark:text-gray-400 mt-2">Élection du Président de la République</p>
                </div>
              </div>
              
              <!-- Juin 2027 - Législatives -->
              <div class="relative flex items-start gap-4 pl-10">
                <div class="absolute left-2 w-4 h-4 rounded-full bg-indigo-500 border-4 border-white dark:border-gray-800"></div>
                <div class="flex-1 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Juin 2027</span>
                      <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">🏛️ Élections Législatives</h3>
                    </div>
                    <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium">
                      2027
                    </span>
                  </div>
                  <p class="text-gray-600 dark:text-gray-400 mt-2">Renouvellement de l'Assemblée nationale (577 sièges)</p>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Ressources -->
        <div class="grid md:grid-cols-3 gap-6">
          <Card>
            <div class="text-center">
              <span class="text-4xl">📋</span>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-3">S'inscrire sur les listes</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                Vérifiez votre inscription et les modalités de vote
              </p>
              <a 
                href="https://www.service-public.fr/particuliers/vosdroits/N47" 
                target="_blank"
                rel="noopener"
                class="inline-flex items-center mt-4 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
              >
                Service-Public.fr ↗️
              </a>
            </div>
          </Card>
          
          <Card>
            <div class="text-center">
              <span class="text-4xl">🗳️</span>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-3">Procurations</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                Donnez ou recevez une procuration pour voter
              </p>
              <a 
                href="https://www.maprocuration.gouv.fr/" 
                target="_blank"
                rel="noopener"
                class="inline-flex items-center mt-4 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
              >
                MaProcuration.gouv.fr ↗️
              </a>
            </div>
          </Card>
          
          <Card>
            <div class="text-center">
              <span class="text-4xl">📊</span>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-3">Résultats officiels</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                Consultez les résultats des élections passées
              </p>
              <a 
                href="https://www.resultats-elections.interieur.gouv.fr/" 
                target="_blank"
                rel="noopener"
                class="inline-flex items-center mt-4 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
              >
                Ministère de l'Intérieur ↗️
              </a>
            </div>
          </Card>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
