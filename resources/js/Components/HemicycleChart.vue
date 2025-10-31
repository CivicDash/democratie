<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    groupes: {
        type: Array,
        required: true,
    },
    width: {
        type: Number,
        default: 600,
    },
    height: {
        type: Number,
        default: 400,
    },
    interactive: {
        type: Boolean,
        default: true,
    },
});

const hoveredGroupe = ref(null);
const selectedGroupe = ref(null);

// Calculer le total des sièges
const totalSieges = computed(() => {
    return props.groupes.reduce((sum, g) => sum + g.nombre_membres, 0);
});

// Générer les positions des sièges en arc (amélioré)
const sieges = computed(() => {
    const seats = [];
    const centerX = props.width / 2;
    const centerY = props.height - 30;
    const rows = 8; // Augmenté pour plus de réalisme
    const maxRadius = Math.min(props.width, props.height * 1.6) / 2 - 30;
    const minRadius = maxRadius * 0.35;

    let currentSeat = 0;

    props.groupes.forEach(groupe => {
        for (let i = 0; i < groupe.nombre_membres; i++) {
            // Progression globale
            const progress = currentSeat / totalSieges.value;
            
            // Déterminer la rangée (répartition progressive)
            const row = Math.floor(Math.sqrt(currentSeat / totalSieges.value * rows * rows));
            const radius = minRadius + (row / rows) * (maxRadius - minRadius);
            
            // Calculer l'angle pour l'hémicycle
            const seatsInThisRow = Math.ceil(totalSieges.value * (row + 1) / rows) - Math.ceil(totalSieges.value * row / rows);
            const seatIndexInRow = currentSeat % seatsInThisRow;
            const angle = Math.PI * (1 - seatIndexInRow / Math.max(1, seatsInThisRow - 1));
            
            const x = centerX + radius * Math.cos(angle);
            const y = centerY + radius * Math.sin(angle);

            seats.push({
                x,
                y,
                groupe,
                index: currentSeat,
                row,
            });

            currentSeat++;
        }
    });

    return seats;
});

// Calculer la légende avec animations
const legende = computed(() => {
    return props.groupes.map(groupe => ({
        ...groupe,
        pourcentage: totalSieges.value > 0 
            ? ((groupe.nombre_membres / totalSieges.value) * 100).toFixed(1)
            : 0,
        isHovered: hoveredGroupe.value?.id === groupe.id,
        isSelected: selectedGroupe.value?.id === groupe.id,
    }));
});

// Gestion des interactions
const handleSiegeHover = (siege) => {
    if (props.interactive) {
        hoveredGroupe.value = siege.groupe;
    }
};

const handleSiegeLeave = () => {
    hoveredGroupe.value = null;
};

const handleSiegeClick = (siege) => {
    if (props.interactive) {
        selectedGroupe.value = selectedGroupe.value?.id === siege.groupe.id ? null : siege.groupe;
    }
};

// Filtrer les sièges visibles selon le groupe survolé/sélectionné
const getSiegeOpacity = (siege) => {
    if (!hoveredGroupe.value && !selectedGroupe.value) return 1;
    
    const targetGroupe = selectedGroupe.value || hoveredGroupe.value;
    return siege.groupe.id === targetGroupe.id ? 1 : 0.2;
};

const getSiegeRadius = (siege) => {
    if (!hoveredGroupe.value && !selectedGroupe.value) return 4;
    
    const targetGroupe = selectedGroupe.value || hoveredGroupe.value;
    return siege.groupe.id === targetGroupe.id ? 5.5 : 3.5;
};
</script>

<template>
    <div class="hemicycle-chart">
        <!-- Information panel si sélection -->
        <div v-if="selectedGroupe" class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-300 dark:border-blue-700 animate-fade-in">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg"
                        :style="{ backgroundColor: selectedGroupe.couleur_hex }"
                    >
                        {{ selectedGroupe.sigle }}
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ selectedGroupe.nom }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ selectedGroupe.nombre_membres }} sièges ({{ ((selectedGroupe.nombre_membres / totalSieges) * 100).toFixed(1) }}%)
                        </p>
                    </div>
                </div>
                <button
                    @click="selectedGroupe = null"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- SVG de l'hémicycle avec animations -->
        <svg
            :width="width"
            :height="height"
            :viewBox="`0 0 ${width} ${height}`"
            class="mx-auto"
        >
            <!-- Background arc -->
            <path
                :d="`M ${width * 0.1} ${height - 30} A ${(width * 0.8) / 2} ${(width * 0.8) / 2} 0 0 1 ${width * 0.9} ${height - 30}`"
                fill="none"
                stroke="#e5e7eb"
                stroke-width="2"
                stroke-dasharray="5,5"
                class="dark:stroke-gray-700"
            />

            <!-- Sièges avec animations -->
            <g>
                <circle
                    v-for="(siege, index) in sieges"
                    :key="index"
                    :cx="siege.x"
                    :cy="siege.y"
                    :r="getSiegeRadius(siege)"
                    :fill="siege.groupe.couleur_hex"
                    :stroke="siege.groupe.couleur_hex"
                    :stroke-width="selectedGroupe?.id === siege.groupe.id || hoveredGroupe?.id === siege.groupe.id ? 2 : 0.5"
                    :opacity="getSiegeOpacity(siege)"
                    class="siege transition-all duration-300 ease-in-out cursor-pointer"
                    :class="{
                        'animate-pulse': selectedGroupe?.id === siege.groupe.id,
                        'hover:scale-150': interactive
                    }"
                    @mouseenter="handleSiegeHover(siege)"
                    @mouseleave="handleSiegeLeave"
                    @click="handleSiegeClick(siege)"
                    :style="{
                        animationDelay: `${index * 2}ms`,
                        filter: (selectedGroupe?.id === siege.groupe.id || hoveredGroupe?.id === siege.groupe.id) 
                            ? 'drop-shadow(0 0 4px rgba(59, 130, 246, 0.6))' 
                            : 'none'
                    }"
                >
                    <title>{{ siege.groupe.nom }} ({{ siege.groupe.sigle }})</title>
                </circle>
            </g>

            <!-- Texte central animé -->
            <text
                :x="width / 2"
                :y="height - 15"
                text-anchor="middle"
                class="fill-gray-700 dark:fill-gray-300 text-lg font-bold transition-all duration-300"
                :class="{ 'text-2xl': selectedGroupe || hoveredGroupe }"
            >
                {{ selectedGroupe 
                    ? `${selectedGroupe.nombre_membres} sièges ${selectedGroupe.sigle}` 
                    : hoveredGroupe
                        ? `${hoveredGroupe.nombre_membres} sièges ${hoveredGroupe.sigle}`
                        : `${totalSieges} sièges total`
                }}
            </text>
        </svg>

        <!-- Légende interactive -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mt-6">
            <button
                v-for="groupe in legende"
                :key="groupe.id"
                @click="selectedGroupe = selectedGroupe?.id === groupe.id ? null : groupe"
                @mouseenter="hoveredGroupe = groupe"
                @mouseleave="hoveredGroupe = null"
                class="flex items-center gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm transition-all duration-200 hover:shadow-md hover:scale-105 cursor-pointer"
                :class="{
                    'ring-2 ring-blue-500 shadow-lg scale-105': groupe.isSelected,
                    'ring-1 ring-blue-300': groupe.isHovered && !groupe.isSelected,
                }"
            >
                <div
                    class="w-4 h-4 rounded-full flex-shrink-0 transition-transform duration-200"
                    :style="{ backgroundColor: groupe.couleur_hex }"
                    :class="{ 'scale-125': groupe.isHovered || groupe.isSelected }"
                ></div>
                <div class="flex-1 min-w-0 text-left">
                    <p class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ groupe.sigle }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ groupe.nombre_membres }} <span class="text-gray-400">({{ groupe.pourcentage }}%)</span>
                    </p>
                </div>
            </button>
        </div>

        <!-- Message si vide -->
        <div v-if="groupes.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
            Aucun groupe parlementaire à afficher
        </div>
    </div>
</template>

<style scoped>
.hemicycle-chart {
    @apply bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900/20 dark:to-blue-900/10 rounded-lg p-6;
}

.siege {
    transform-origin: center;
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>

