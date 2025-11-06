<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    regionalData: {
        type: Array,
        default: () => []
    },
    heatmapMetric: {
        type: String,
        default: 'unemployment_rate' // unemployment_rate, poverty_rate, gdp_billions_euros
    }
});

const emit = defineEmits(['region-selected']);

const hoveredRegion = ref(null);
const selectedRegion = ref(null);

// Mapping des codes régions vers leurs données
const regionDataMap = computed(() => {
    const map = {};
    props.regionalData.forEach(region => {
        map[region.region_code] = region;
    });
    return map;
});

// Calculer la couleur en fonction de la métrique (heatmap)
const getRegionColor = (regionCode) => {
    const region = regionDataMap.value[regionCode];
    if (!region) return '#E5E7EB'; // Gris par défaut
    
    let value;
    let min, max;
    
    switch (props.heatmapMetric) {
        case 'unemployment_rate':
            value = region.unemployment_rate;
            min = 6.0;
            max = 10.0;
            break;
        case 'poverty_rate':
            value = region.poverty_rate;
            min = 10.0;
            max = 20.0;
            break;
        case 'gdp_billions_euros':
            value = region.gdp_billions_euros;
            min = 150;
            max = 800;
            // Pour le PIB, plus c'est élevé, plus c'est vert
            const normalized = Math.min(Math.max((value - min) / (max - min), 0), 1);
            const hue = normalized * 120; // 0 (rouge) à 120 (vert)
            return `hsl(${hue}, 70%, 50%)`;
        default:
            return '#3B82F6';
    }
    
    // Pour chômage et pauvreté : plus c'est élevé, plus c'est rouge
    const normalized = Math.min(Math.max((value - min) / (max - min), 0), 1);
    const hue = (1 - normalized) * 120; // 120 (vert) à 0 (rouge)
    return `hsl(${hue}, 70%, 50%)`;
};

const handleRegionClick = (regionCode) => {
    const region = regionDataMap.value[regionCode];
    if (region) {
        selectedRegion.value = regionCode;
        emit('region-selected', region);
    }
};

const handleRegionHover = (regionCode) => {
    hoveredRegion.value = regionCode;
};

const handleRegionLeave = () => {
    hoveredRegion.value = null;
};

// Coordonnées simplifiées des régions (paths SVG)
// Source: Carte administrative de France métropolitaine
const regions = {
    '11': { // Île-de-France
        name: 'Île-de-France',
        path: 'M 280,180 L 320,170 L 330,190 L 310,210 L 280,200 Z',
        labelX: 300,
        labelY: 190
    },
    '84': { // Auvergne-Rhône-Alpes
        name: 'Auvergne-Rhône-Alpes',
        path: 'M 340,280 L 400,260 L 420,300 L 400,340 L 350,330 L 330,300 Z',
        labelX: 370,
        labelY: 300
    },
    '93': { // Provence-Alpes-Côte d'Azur
        name: 'PACA',
        path: 'M 380,360 L 450,350 L 470,390 L 450,420 L 390,410 Z',
        labelX: 420,
        labelY: 385
    },
    '76': { // Occitanie
        name: 'Occitanie',
        path: 'M 200,350 L 280,340 L 300,380 L 280,420 L 200,410 L 180,380 Z',
        labelX: 240,
        labelY: 380
    },
    '75': { // Nouvelle-Aquitaine
        name: 'Nouvelle-Aquitaine',
        path: 'M 120,260 L 200,250 L 220,300 L 200,350 L 120,340 L 100,300 Z',
        labelX: 160,
        labelY: 300
    },
    '32': { // Hauts-de-France
        name: 'Hauts-de-France',
        path: 'M 240,80 L 320,70 L 340,110 L 320,140 L 240,130 L 220,100 Z',
        labelX: 280,
        labelY: 105
    },
    '44': { // Grand Est
        name: 'Grand Est',
        path: 'M 360,120 L 450,110 L 470,160 L 450,210 L 360,200 L 340,160 Z',
        labelX: 405,
        labelY: 160
    },
    '27': { // Bourgogne-Franche-Comté
        name: 'Bourgogne-FC',
        path: 'M 320,200 L 380,190 L 400,240 L 380,280 L 320,270 L 300,230 Z',
        labelX: 350,
        labelY: 235
    },
    '24': { // Centre-Val de Loire
        name: 'Centre-VdL',
        path: 'M 220,200 L 290,190 L 310,230 L 290,270 L 220,260 L 200,230 Z',
        labelX: 255,
        labelY: 230
    },
    '52': { // Pays de la Loire
        name: 'Pays de la Loire',
        path: 'M 120,200 L 190,190 L 210,230 L 190,270 L 120,260 L 100,230 Z',
        labelX: 155,
        labelY: 230
    },
    '53': { // Bretagne
        name: 'Bretagne',
        path: 'M 40,180 L 110,170 L 130,210 L 110,250 L 40,240 L 20,210 Z',
        labelX: 75,
        labelY: 210
    },
    '28': { // Normandie
        name: 'Normandie',
        path: 'M 160,120 L 240,110 L 260,150 L 240,190 L 160,180 L 140,150 Z',
        labelX: 200,
        labelY: 150
    },
    '94': { // Corse
        name: 'Corse',
        path: 'M 480,420 L 500,410 L 510,440 L 500,470 L 480,460 Z',
        labelX: 495,
        labelY: 440
    }
};
</script>

<template>
    <div class="france-map-container">
        <svg
            viewBox="0 0 550 500"
            class="france-map"
            xmlns="http://www.w3.org/2000/svg"
        >
            <!-- Définition des filtres pour les effets -->
            <defs>
                <filter id="shadow">
                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.3"/>
                </filter>
            </defs>

            <!-- Régions -->
            <g v-for="(region, code) in regions" :key="code">
                <path
                    :d="region.path"
                    :fill="getRegionColor(code)"
                    :stroke="hoveredRegion === code || selectedRegion === code ? '#1F2937' : '#FFFFFF'"
                    :stroke-width="hoveredRegion === code || selectedRegion === code ? '3' : '2'"
                    :class="[
                        'region-path',
                        { 'region-hovered': hoveredRegion === code },
                        { 'region-selected': selectedRegion === code }
                    ]"
                    @click="handleRegionClick(code)"
                    @mouseenter="handleRegionHover(code)"
                    @mouseleave="handleRegionLeave"
                />
                
                <!-- Labels des régions -->
                <text
                    :x="region.labelX"
                    :y="region.labelY"
                    class="region-label"
                    text-anchor="middle"
                    @click="handleRegionClick(code)"
                    @mouseenter="handleRegionHover(code)"
                    @mouseleave="handleRegionLeave"
                >
                    {{ region.name }}
                </text>
            </g>
        </svg>

        <!-- Tooltip au survol -->
        <div
            v-if="hoveredRegion && regionDataMap[hoveredRegion]"
            class="map-tooltip"
        >
            <div class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                {{ regionDataMap[hoveredRegion].region_name }}
            </div>
            <div class="space-y-1 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600 dark:text-gray-400">Population:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                        {{ (regionDataMap[hoveredRegion].population / 1000000).toFixed(2) }}M
                    </span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600 dark:text-gray-400">Chômage:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                        {{ regionDataMap[hoveredRegion].unemployment_rate }}%
                    </span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600 dark:text-gray-400">PIB:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                        {{ regionDataMap[hoveredRegion].gdp_billions_euros }}Md€
                    </span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600 dark:text-gray-400">Pauvreté:</span>
                    <span class="font-medium text-red-600 dark:text-red-400">
                        {{ regionDataMap[hoveredRegion].poverty_rate }}%
                    </span>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                Cliquez pour plus de détails
            </div>
        </div>

        <!-- Légende de la heatmap -->
        <div class="heatmap-legend">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Légende
            </div>
            <div class="flex items-center gap-2">
                <div class="legend-gradient"></div>
                <div class="flex justify-between w-full text-xs text-gray-600 dark:text-gray-400">
                    <span v-if="heatmapMetric === 'unemployment_rate'">6%</span>
                    <span v-else-if="heatmapMetric === 'poverty_rate'">10%</span>
                    <span v-else>150Md€</span>
                    
                    <span v-if="heatmapMetric === 'unemployment_rate'">10%</span>
                    <span v-else-if="heatmapMetric === 'poverty_rate'">20%</span>
                    <span v-else>800Md€</span>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                <span v-if="heatmapMetric === 'unemployment_rate'">Taux de chômage</span>
                <span v-else-if="heatmapMetric === 'poverty_rate'">Taux de pauvreté</span>
                <span v-else>PIB régional</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.france-map-container {
    position: relative;
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
}

.france-map {
    width: 100%;
    height: auto;
    background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
    border-radius: 12px;
    padding: 20px;
}

.region-path {
    cursor: pointer;
    transition: all 0.3s ease;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
}

.region-path:hover {
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    transform: scale(1.02);
    transform-origin: center;
}

.region-hovered {
    opacity: 0.9;
}

.region-selected {
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.3));
}

.region-label {
    font-size: 11px;
    font-weight: 600;
    fill: #1F2937;
    pointer-events: none;
    user-select: none;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
}

.map-tooltip {
    position: absolute;
    top: 20px;
    right: 20px;
    background: white;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    min-width: 200px;
    z-index: 10;
    animation: fadeIn 0.2s ease;
}

.dark .map-tooltip {
    background: #1F2937;
    border-color: #374151;
}

.heatmap-legend {
    margin-top: 20px;
    padding: 12px;
    background: white;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
}

.dark .heatmap-legend {
    background: #1F2937;
    border-color: #374151;
}

.legend-gradient {
    width: 100%;
    height: 20px;
    background: linear-gradient(to right, 
        hsl(0, 70%, 50%),
        hsl(30, 70%, 50%),
        hsl(60, 70%, 50%),
        hsl(90, 70%, 50%),
        hsl(120, 70%, 50%)
    );
    border-radius: 4px;
    margin-bottom: 4px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .region-label {
        font-size: 9px;
    }
    
    .map-tooltip {
        top: 10px;
        right: 10px;
        font-size: 12px;
        min-width: 160px;
    }
}
</style>

