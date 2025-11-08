<template>
  <div class="space-y-6">
    <!-- Sélecteur de vue -->
    <div class="flex justify-center gap-2">
      <button
        @click="viewType = 'deputes'"
        :class="[
          'px-4 py-2 rounded-lg font-medium transition',
          viewType === 'deputes' 
            ? 'bg-blue-600 text-white' 
            : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
        ]"
      >
        👤 Députés
      </button>
      <button
        @click="viewType = 'senateurs'"
        :class="[
          'px-4 py-2 rounded-lg font-medium transition',
          viewType === 'senateurs' 
            ? 'bg-red-600 text-white' 
            : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
        ]"
      >
        🏛️ Sénateurs
      </button>
    </div>

    <!-- Titre -->
    <div class="text-center">
      <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
        {{ viewType === 'deputes' ? 'Répartition des Députés' : 'Répartition des Sénateurs' }}
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        {{ viewType === 'deputes' ? 'Par département (577 députés)' : 'Par département (348 sénateurs)' }}
      </p>
    </div>

    <!-- Carte SVG -->
    <div class="relative w-full bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
      <svg 
        viewBox="0 0 1000 1000" 
        class="w-full h-auto"
        style="max-height: 600px;"
      >
        <!-- Départements -->
        <g
          v-for="dept in departments"
          :key="dept.id"
        >
          <path
            :d="dept.path"
            :fill="getDepartmentColor(dept.code)"
            :stroke="selectedDepartment === dept.code ? '#1F2937' : '#E5E7EB'"
            :stroke-width="selectedDepartment === dept.code ? 3 : 1"
            class="transition-all duration-200 cursor-pointer hover:opacity-80"
            @click="selectDepartment(dept)"
            @mouseenter="hoveredDepartment = dept"
            @mouseleave="hoveredDepartment = null"
          />
        </g>
      </svg>

      <!-- Tooltip au survol -->
      <div
        v-if="hoveredDepartment"
        class="absolute top-4 left-4 bg-white dark:bg-gray-700 shadow-lg rounded-lg p-4 pointer-events-none"
        style="z-index: 10;"
      >
        <div class="font-bold text-gray-900 dark:text-gray-100">
          {{ hoveredDepartment.name }} ({{ hoveredDepartment.code }})
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          <div v-if="viewType === 'deputes'">
            👤 {{ getDepartmentCount(hoveredDepartment.code, 'deputes') }} député(s)
          </div>
          <div v-else>
            🏛️ {{ getDepartmentCount(hoveredDepartment.code, 'senateurs') }} sénateur(s)
          </div>
        </div>
      </div>
    </div>

    <!-- Légende -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
      <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Nombre de {{ viewType === 'deputes' ? 'députés' : 'sénateurs' }} :
      </div>
      <div class="flex flex-wrap gap-2">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded" :style="{ backgroundColor: getColorForCount(0) }"></div>
          <span class="text-xs text-gray-600 dark:text-gray-400">0</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded" :style="{ backgroundColor: getColorForCount(2) }"></div>
          <span class="text-xs text-gray-600 dark:text-gray-400">1-2</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded" :style="{ backgroundColor: getColorForCount(5) }"></div>
          <span class="text-xs text-gray-600 dark:text-gray-400">3-5</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded" :style="{ backgroundColor: getColorForCount(10) }"></div>
          <span class="text-xs text-gray-600 dark:text-gray-400">6-10</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded" :style="{ backgroundColor: getColorForCount(15) }"></div>
          <span class="text-xs text-gray-600 dark:text-gray-400">10+</span>
        </div>
      </div>
    </div>

    <!-- Détail du département sélectionné -->
    <div v-if="selectedDepartmentData" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <h4 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-3">
        {{ selectedDepartmentData.name }} ({{ selectedDepartmentData.code }})
      </h4>
      <div class="text-sm text-gray-600 dark:text-gray-400">
        <div v-if="viewType === 'deputes'">
          👤 <strong>{{ getDepartmentCount(selectedDepartmentData.code, 'deputes') }}</strong> député(s)
        </div>
        <div v-else>
          🏛️ <strong>{{ getDepartmentCount(selectedDepartmentData.code, 'senateurs') }}</strong> sénateur(s)
        </div>
      </div>
      <button
        @click="viewDepartmentRepresentants"
        class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
      >
        Voir les {{ viewType === 'deputes' ? 'députés' : 'sénateurs' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  deputesByDepartment: Object, // { '75': 21, '13': 16, ... }
  senateursByDepartment: Object, // { '75': 12, '13': 8, ... }
});

const viewType = ref('deputes'); // 'deputes' ou 'senateurs'
const hoveredDepartment = ref(null);
const selectedDepartment = ref(null);
const selectedDepartmentData = ref(null);

// Départements simplifiés (on utilise les mêmes que FranceMapInteractive mais version légère)
const departments = [
  { id: 'dep_75', code: '75', name: 'Paris', path: "M503 231h-5l-2 1-1 1h-1l-2 2v1l0 1 3 1 4 2h2l1 0 2-1 4 1 0-2v-1l-3 0 1 1-1 0h-1l1-1-1-4-1-2z" },
  { id: 'dep_13', code: '13', name: 'Bouches-du-Rhône', path: "M687 750l-10 5-3 20-11-2-3 8 3 4-12 7-3 8 11 0 15 1 3 3h-5l-4 6 16 3 12-2-7-6 5-4 7 3 3 7 20 1 6-3 1 4-6 5 8 0-1 4-2 2h17l9 3 1 1 0-7 3-3 3-2 0-2-3-2h-3l-2-2 3-3v-1l-3-1v-3l7 0 2-1-6-6 0-7-4-3 3-7 8-5-6-4-4 3-10 3-7-1-15-6-8 0-7-3-3-4-5-6-13-5h-1z" },
  { id: 'dep_69', code: '69', name: 'Rhône', path: "M672 501l-4 0-3 4-2-3-4 3-4-3h-4l-1 1-1 4 2 2v3l2 4-1 2h-8l0 7-3 3 2 6v3l3 3v5l5 4v5l-3 4 3 1v3l-2 2v3l10 9 12 2 1 4-4 4 4 2 3-1 5 1 7-4-1-3-5-5 8-2 10-3 8-10-5-4 0-4-7-2-5 1-1-6-4-4-3 0-7-4 1-3v-12l2-3 1-8-1 2-3 0-1-7-2-6z" },
  // ... ajouter tous les autres départements (96 au total)
  // Pour l'instant, liste simplifiée
];

// Obtenir le nombre de représentants pour un département
const getDepartmentCount = (deptCode, type) => {
  const data = type === 'deputes' ? props.deputesByDepartment : props.senateursByDepartment;
  return data?.[deptCode] || 0;
};

// Obtenir la couleur selon le nombre
const getColorForCount = (count) => {
  if (count === 0) return '#E5E7EB'; // Gris clair
  if (count <= 2) return viewType.value === 'deputes' ? '#DBEAFE' : '#FEE2E2'; // Bleu/Rouge très clair
  if (count <= 5) return viewType.value === 'deputes' ? '#93C5FD' : '#FECACA'; // Bleu/Rouge clair
  if (count <= 10) return viewType.value === 'deputes' ? '#3B82F6' : '#F87171'; // Bleu/Rouge moyen
  return viewType.value === 'deputes' ? '#1E40AF' : '#DC2626'; // Bleu/Rouge foncé
};

// Couleur d'un département
const getDepartmentColor = (deptCode) => {
  const count = getDepartmentCount(deptCode, viewType.value);
  return getColorForCount(count);
};

// Sélectionner un département
const selectDepartment = (dept) => {
  selectedDepartment.value = dept.code;
  selectedDepartmentData.value = dept;
};

// Voir les représentants du département
const viewDepartmentRepresentants = () => {
  if (!selectedDepartmentData.value) return;
  
  const route = viewType.value === 'deputes' 
    ? 'representants.deputes.index' 
    : 'representants.senateurs.index';
    
  router.visit(window.route(route, { 
    department: selectedDepartmentData.value.code 
  }));
};
</script>

