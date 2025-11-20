<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  regions: Object, // { code: { name, deputesCount, senateursCount } }
  selectedRegionCode: String,
});

const emit = defineEmits(['select-region', 'hover-region']);

const hoveredRegion = ref(null);

const getRegionColor = (regionCode) => {
  const data = props.regions[regionCode];
  if (!data) return '#e5e7eb'; // gray-200
  
  const count = data.deputesCount || 0;
  if (count > 40) return '#1e40af'; // blue-800
  if (count > 30) return '#3b82f6'; // blue-600
  if (count > 20) return '#60a5fa'; // blue-400
  if (count > 10) return '#93c5fd'; // blue-300
  return '#dbeafe'; // blue-100
};

const getRegionStroke = (regionCode) => {
  if (props.selectedRegionCode === regionCode) return '#dc2626'; // red-600
  if (hoveredRegion.value === regionCode) return '#ea580c'; // orange-600
  return '#94a3b8'; // slate-400
};

const getRegionStrokeWidth = (regionCode) => {
  if (props.selectedRegionCode === regionCode) return '3';
  if (hoveredRegion.value === regionCode) return '2';
  return '1';
};

const handleRegionClick = (regionCode) => {
  emit('select-region', regionCode);
};

const handleRegionHover = (regionCode) => {
  hoveredRegion.value = regionCode;
  emit('hover-region', regionCode);
};

const handleRegionLeave = () => {
  hoveredRegion.value = null;
  emit('hover-region', null);
};
</script>

<template>
  <div class="relative w-full">
    <svg
      viewBox="0 0 1200 1100"
      class="w-full h-auto"
      xmlns="http://www.w3.org/2000/svg"
    >
      <defs>
        <filter id="shadow">
          <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.3"/>
        </filter>
      </defs>

      <!-- Hauts-de-France (32) - Nord -->
      <path
        id="region-32"
        d="M 420 60 L 520 50 L 620 70 L 680 110 L 690 150 L 670 200 L 630 240 L 580 260 L 520 270 L 470 250 L 430 210 L 400 150 L 410 100 Z"
        :fill="getRegionColor('32')"
        :stroke="getRegionStroke('32')"
        :stroke-width="getRegionStrokeWidth('32')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('32')"
        @mouseenter="handleRegionHover('32')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Normandie (28) - Nord-Ouest -->
      <path
        id="region-28"
        d="M 240 180 L 340 140 L 400 150 L 430 210 L 470 250 L 460 300 L 420 340 L 360 350 L 300 330 L 250 290 L 220 230 Z"
        :fill="getRegionColor('28')"
        :stroke="getRegionStroke('28')"
        :stroke-width="getRegionStrokeWidth('28')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('28')"
        @mouseenter="handleRegionHover('28')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Bretagne (53) - Ouest -->
      <path
        id="region-53"
        d="M 40 240 L 140 210 L 220 230 L 250 290 L 270 340 L 250 390 L 200 410 L 130 420 L 70 390 L 40 340 L 30 280 Z"
        :fill="getRegionColor('53')"
        :stroke="getRegionStroke('53')"
        :stroke-width="getRegionStrokeWidth('53')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('53')"
        @mouseenter="handleRegionHover('53')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Pays de la Loire (52) - Ouest-Centre -->
      <path
        id="region-52"
        d="M 200 410 L 250 390 L 270 340 L 300 330 L 360 350 L 390 390 L 410 450 L 390 510 L 350 540 L 290 540 L 240 510 L 210 460 Z"
        :fill="getRegionColor('52')"
        :stroke="getRegionStroke('52')"
        :stroke-width="getRegionStrokeWidth('52')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('52')"
        @mouseenter="handleRegionHover('52')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Centre-Val de Loire (24) - Centre -->
      <path
        id="region-24"
        d="M 360 350 L 420 340 L 460 300 L 520 270 L 560 280 L 590 320 L 600 380 L 570 430 L 530 460 L 480 470 L 430 450 L 390 390 Z"
        :fill="getRegionColor('24')"
        :stroke="getRegionStroke('24')"
        :stroke-width="getRegionStrokeWidth('24')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('24')"
        @mouseenter="handleRegionHover('24')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Île-de-France (11) - Petit Centre -->
      <path
        id="region-11"
        d="M 520 380 L 560 370 L 580 395 L 570 425 L 540 435 L 510 420 Z"
        :fill="getRegionColor('11')"
        :stroke="getRegionStroke('11')"
        :stroke-width="getRegionStrokeWidth('11')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('11')"
        @mouseenter="handleRegionHover('11')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Grand Est (44) - Nord-Est -->
      <path
        id="region-44"
        d="M 630 240 L 720 200 L 800 220 L 860 270 L 880 340 L 860 400 L 810 440 L 750 460 L 690 450 L 650 410 L 620 360 L 600 300 Z"
        :fill="getRegionColor('44')"
        :stroke="getRegionStroke('44')"
        :stroke-width="getRegionStrokeWidth('44')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('44')"
        @mouseenter="handleRegionHover('44')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Bourgogne-Franche-Comté (27) - Est-Centre -->
      <path
        id="region-27"
        d="M 600 380 L 650 410 L 690 450 L 750 460 L 790 500 L 800 560 L 770 620 L 720 650 L 660 650 L 610 620 L 570 570 L 550 510 L 560 450 Z"
        :fill="getRegionColor('27')"
        :stroke="getRegionStroke('27')"
        :stroke-width="getRegionStrokeWidth('27')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('27')"
        @mouseenter="handleRegionHover('27')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Auvergne-Rhône-Alpes (84) - Est-Centre-Sud -->
      <path
        id="region-84"
        d="M 550 510 L 570 570 L 610 620 L 660 650 L 720 650 L 770 690 L 790 750 L 780 810 L 740 870 L 680 900 L 620 910 L 560 890 L 510 840 L 470 770 L 460 700 L 480 640 L 520 580 Z"
        :fill="getRegionColor('84')"
        :stroke="getRegionStroke('84')"
        :stroke-width="getRegionStrokeWidth('84')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('84')"
        @mouseenter="handleRegionHover('84')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Nouvelle-Aquitaine (75) - Sud-Ouest -->
      <path
        id="region-75"
        d="M 210 460 L 240 510 L 290 540 L 350 540 L 390 580 L 420 640 L 430 720 L 410 800 L 370 870 L 310 920 L 240 940 L 170 920 L 120 860 L 90 780 L 90 700 L 110 620 L 150 560 Z"
        :fill="getRegionColor('75')"
        :stroke="getRegionStroke('75')"
        :stroke-width="getRegionStrokeWidth('75')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('75')"
        @mouseenter="handleRegionHover('75')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Occitanie (76) - Sud -->
      <path
        id="region-76"
        d="M 430 720 L 420 640 L 460 700 L 470 770 L 510 840 L 540 900 L 550 960 L 530 1010 L 480 1040 L 420 1050 L 360 1030 L 310 990 L 310 920 L 370 870 L 410 800 Z"
        :fill="getRegionColor('76')"
        :stroke="getRegionStroke('76')"
        :stroke-width="getRegionStrokeWidth('76')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('76')"
        @mouseenter="handleRegionHover('76')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Provence-Alpes-Côte d'Azur (93) - Sud-Est -->
      <path
        id="region-93"
        d="M 560 890 L 620 910 L 680 900 L 740 920 L 790 960 L 820 1010 L 800 1050 L 750 1070 L 690 1060 L 640 1030 L 590 990 L 550 960 L 540 900 Z"
        :fill="getRegionColor('93')"
        :stroke="getRegionStroke('93')"
        :stroke-width="getRegionStrokeWidth('93')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('93')"
        @mouseenter="handleRegionHover('93')"
        @mouseleave="handleRegionLeave"
      />

      <!-- Corse (94) - Île Sud-Est -->
      <path
        id="region-94"
        d="M 890 900 L 920 880 L 945 910 L 955 960 L 945 1010 L 920 1040 L 890 1050 L 865 1020 L 860 970 L 875 920 Z"
        :fill="getRegionColor('94')"
        :stroke="getRegionStroke('94')"
        :stroke-width="getRegionStrokeWidth('94')"
        class="cursor-pointer transition-all duration-200 hover:brightness-110"
        @click="handleRegionClick('94')"
        @mouseenter="handleRegionHover('94')"
        @mouseleave="handleRegionLeave"
      />

      <!-- DOM-TOM (en bas à droite) -->
      
      <!-- Guadeloupe (01) -->
      <g id="drom-01">
        <path
          d="M 1050 850 L 1080 840 L 1095 855 L 1090 875 L 1070 885 L 1045 875 Z"
          :fill="getRegionColor('01')"
          :stroke="getRegionStroke('01')"
          :stroke-width="getRegionStrokeWidth('01')"
          class="cursor-pointer transition-all duration-200 hover:brightness-110"
          @click="handleRegionClick('01')"
          @mouseenter="handleRegionHover('01')"
          @mouseleave="handleRegionLeave"
        />
        <text x="1067" y="867" text-anchor="middle" class="text-[8px] font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">GP</text>
      </g>

      <!-- Martinique (02) -->
      <g id="drom-02">
        <path
          d="M 1110 850 L 1135 840 L 1148 855 L 1143 875 L 1125 885 L 1105 875 Z"
          :fill="getRegionColor('02')"
          :stroke="getRegionStroke('02')"
          :stroke-width="getRegionStrokeWidth('02')"
          class="cursor-pointer transition-all duration-200 hover:brightness-110"
          @click="handleRegionClick('02')"
          @mouseenter="handleRegionHover('02')"
          @mouseleave="handleRegionLeave"
        />
        <text x="1126" y="867" text-anchor="middle" class="text-[8px] font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">MQ</text>
      </g>

      <!-- Guyane (03) -->
      <g id="drom-03">
        <path
          d="M 1050 900 L 1095 890 L 1115 910 L 1105 935 L 1075 945 L 1045 930 Z"
          :fill="getRegionColor('03')"
          :stroke="getRegionStroke('03')"
          :stroke-width="getRegionStrokeWidth('03')"
          class="cursor-pointer transition-all duration-200 hover:brightness-110"
          @click="handleRegionClick('03')"
          @mouseenter="handleRegionHover('03')"
          @mouseleave="handleRegionLeave"
        />
        <text x="1075" y="920" text-anchor="middle" class="text-[8px] font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">GF</text>
      </g>

      <!-- La Réunion (04) -->
      <g id="drom-04">
        <path
          d="M 1050 960 L 1075 950 L 1090 965 L 1085 985 L 1065 995 L 1045 985 Z"
          :fill="getRegionColor('04')"
          :stroke="getRegionStroke('04')"
          :stroke-width="getRegionStrokeWidth('04')"
          class="cursor-pointer transition-all duration-200 hover:brightness-110"
          @click="handleRegionClick('04')"
          @mouseenter="handleRegionHover('04')"
          @mouseleave="handleRegionLeave"
        />
        <text x="1067" y="977" text-anchor="middle" class="text-[8px] font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">RE</text>
      </g>

      <!-- Mayotte (06) -->
      <g id="drom-06">
        <path
          d="M 1100 960 L 1120 950 L 1133 965 L 1128 985 L 1110 995 L 1095 985 Z"
          :fill="getRegionColor('06')"
          :stroke="getRegionStroke('06')"
          :stroke-width="getRegionStrokeWidth('06')"
          class="cursor-pointer transition-all duration-200 hover:brightness-110"
          @click="handleRegionClick('06')"
          @mouseenter="handleRegionHover('06')"
          @mouseleave="handleRegionLeave"
        />
        <text x="1115" y="977" text-anchor="middle" class="text-[8px] font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">YT</text>
      </g>

      <!-- Labels des régions métropole -->
      <text x="545" y="408" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">IDF</text>
      <text x="520" y="160" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">HDF</text>
      <text x="340" y="260" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">NOR</text>
      <text x="135" y="320" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">BRE</text>
      <text x="300" y="470" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">PDL</text>
      <text x="460" y="380" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">CVL</text>
      <text x="740" y="330" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">GES</text>
      <text x="680" y="550" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">BFC</text>
      <text x="620" y="750" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">ARA</text>
      <text x="230" y="720" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">NAQ</text>
      <text x="420" y="870" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">OCC</text>
      <text x="680" y="980" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">PACA</text>
      <text x="908" y="975" text-anchor="middle" class="text-xs font-semibold fill-gray-900 dark:fill-gray-100 pointer-events-none">COR</text>

      <!-- Titre DROM-COM -->
      <text x="1090" y="830" text-anchor="middle" class="text-[10px] font-bold fill-gray-600 dark:fill-gray-400 pointer-events-none">DROM-COM</text>
    </svg>

    <!-- Tooltip au survol -->
    <div
      v-if="hoveredRegion && regions[hoveredRegion]"
      class="absolute top-4 right-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl p-4 pointer-events-none z-10"
    >
      <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2">
        {{ regions[hoveredRegion].name }}
      </h4>
      <div class="text-sm space-y-1">
        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
          <span>🏛️</span>
          <span>{{ regions[hoveredRegion].deputesCount || 0 }} députés</span>
        </div>
        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
          <span>🏰</span>
          <span>{{ regions[hoveredRegion].senateursCount || 0 }} sénateurs</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Smooth transitions pour les régions */
path {
  transition: all 0.2s ease-in-out;
}

path:hover {
  filter: brightness(1.1);
}

/* Animation au clic */
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
}

path:active {
  animation: pulse 0.3s ease-in-out;
}
</style>

