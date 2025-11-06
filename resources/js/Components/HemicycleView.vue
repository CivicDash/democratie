<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    deputes: {
        type: Array,
        required: true,
    },
    groupes: {
        type: Array,
        required: true,
    },
    selectedGroupe: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['select-depute', 'select-groupe']);

const hoveredSeat = ref(null);
const selectedSeat = ref(null);

// Mapper les couleurs des groupes
const groupeColors = computed(() => {
    const colors = {};
    props.groupes.forEach(groupe => {
        colors[groupe.sigle] = groupe.couleur_hex;
    });
    return colors;
});

// Obtenir la couleur d'un siège selon le groupe
const getSeatColor = (sigle) => {
    if (props.selectedGroupe && sigle !== props.selectedGroupe) {
        return '#E5E7EB'; // Grisé si un groupe est sélectionné et ce n'est pas le bon
    }
    return groupeColors.value[sigle] || '#9CA3AF';
};

// Gérer le survol d'un siège
const handleSeatHover = (depute) => {
    hoveredSeat.value = depute;
};

// Gérer le clic sur un siège
const handleSeatClick = (depute) => {
    selectedSeat.value = depute;
    emit('select-depute', depute);
};

// Statistiques par groupe
const groupeStats = computed(() => {
    const stats = {};
    props.groupes.forEach(groupe => {
        stats[groupe.sigle] = {
            nom: groupe.nom,
            sigle: groupe.sigle,
            couleur: groupe.couleur_hex,
            count: props.deputes.filter(d => d.groupe_sigle === groupe.sigle).length,
        };
    });
    return stats;
});
</script>

<template>
    <div class="hemicycle-container">
        <!-- Légende des groupes -->
        <div class="mb-6 flex flex-wrap gap-3 justify-center">
            <button
                v-for="groupe in groupes"
                :key="groupe.sigle"
                @click="emit('select-groupe', groupe.sigle === selectedGroupe ? null : groupe.sigle)"
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg transition-all',
                    selectedGroupe === groupe.sigle 
                        ? 'ring-2 ring-offset-2 shadow-lg scale-105' 
                        : 'hover:scale-105 hover:shadow-md'
                ]"
                :style="{ 
                    backgroundColor: groupe.couleur_hex + '20',
                    borderLeft: `4px solid ${groupe.couleur_hex}`,
                    ringColor: groupe.couleur_hex
                }"
            >
                <div 
                    class="w-4 h-4 rounded-full"
                    :style="{ backgroundColor: groupe.couleur_hex }"
                ></div>
                <span class="font-semibold text-sm">{{ groupe.sigle }}</span>
                <span class="text-xs text-gray-600 dark:text-gray-400">
                    ({{ groupeStats[groupe.sigle]?.count || 0 }})
                </span>
            </button>
        </div>

        <!-- Vue de l'hémicycle -->
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <svg
                viewBox="0 0 850 475"
                class="w-full h-auto"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Fond de l'hémicycle -->
                <path
                    d="M425,450 Q125,450 50,250 Q50,150 150,75 Q250,0 425,0 Q600,0 700,75 Q800,150 800,250 Q725,450 425,450 Z"
                    fill="#F3F4F6"
                    class="dark:fill-gray-700"
                />

                <!-- Tribune centrale -->
                <rect
                    x="350"
                    y="400"
                    width="150"
                    height="60"
                    rx="8"
                    fill="#E5E7EB"
                    class="dark:fill-gray-600"
                />
                <text
                    x="425"
                    y="435"
                    text-anchor="middle"
                    class="text-xs fill-gray-600 dark:fill-gray-300"
                    font-weight="600"
                >
                    TRIBUNE
                </text>

                <!-- Sièges des députés (disposition en arc) -->
                <g v-for="(depute, index) in deputes" :key="depute.id">
                    <circle
                        :cx="getSeatPosition(index).x"
                        :cy="getSeatPosition(index).y"
                        r="6"
                        :fill="getSeatColor(depute.groupe_sigle)"
                        :stroke="hoveredSeat?.id === depute.id ? '#000' : '#fff'"
                        :stroke-width="hoveredSeat?.id === depute.id ? 2 : 1"
                        class="cursor-pointer transition-all hover:r-8"
                        @mouseenter="handleSeatHover(depute)"
                        @mouseleave="hoveredSeat = null"
                        @click="handleSeatClick(depute)"
                    >
                        <title>{{ depute.nom_complet }} ({{ depute.groupe_sigle }})</title>
                    </circle>
                </g>
            </svg>

            <!-- Tooltip pour le siège survolé -->
            <div
                v-if="hoveredSeat"
                class="absolute top-4 right-4 bg-white dark:bg-gray-800 shadow-xl rounded-lg p-4 border border-gray-200 dark:border-gray-700 max-w-xs z-10"
            >
                <div class="flex items-start gap-3">
                    <div 
                        class="w-3 h-3 rounded-full mt-1 flex-shrink-0"
                        :style="{ backgroundColor: groupeColors[hoveredSeat.groupe_sigle] }"
                    ></div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-gray-100">
                            {{ hoveredSeat.nom_complet }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ hoveredSeat.groupe_sigle }} - {{ hoveredSeat.circonscription }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Cliquez pour voir le profil
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations sur le député sélectionné -->
        <div
            v-if="selectedSeat"
            class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 border border-blue-200 dark:border-gray-700"
        >
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    <img
                        v-if="selectedSeat.photo_url"
                        :src="selectedSeat.photo_url"
                        :alt="selectedSeat.nom_complet"
                        class="w-20 h-20 rounded-full object-cover border-4"
                        :style="{ borderColor: groupeColors[selectedSeat.groupe_sigle] }"
                    />
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ selectedSeat.nom_complet }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            {{ selectedSeat.groupe_sigle }} - {{ selectedSeat.circonscription }}
                        </p>
                        <div class="flex gap-3 mt-3">
                            <span class="text-sm bg-white dark:bg-gray-800 px-3 py-1 rounded-full">
                                📜 {{ selectedSeat.nb_propositions || 0 }} propositions
                            </span>
                            <span class="text-sm bg-white dark:bg-gray-800 px-3 py-1 rounded-full">
                                📝 {{ selectedSeat.nb_amendements || 0 }} amendements
                            </span>
                            <span class="text-sm bg-white dark:bg-gray-800 px-3 py-1 rounded-full">
                                ✅ {{ selectedSeat.taux_presence || 0 }}% présence
                            </span>
                        </div>
                    </div>
                </div>
                <button
                    @click="selectedSeat = null"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    methods: {
        // Calculer la position d'un siège dans l'hémicycle
        getSeatPosition(index) {
            const total = this.deputes.length;
            const rows = 10; // Nombre de rangées
            const seatsPerRow = Math.ceil(total / rows);
            
            const row = Math.floor(index / seatsPerRow);
            const seatInRow = index % seatsPerRow;
            
            // Rayon de base + offset par rangée
            const baseRadius = 100;
            const radius = baseRadius + (row * 30);
            
            // Angle pour cette rangée (arc de 180°)
            const startAngle = Math.PI; // 180°
            const endAngle = 0; // 0°
            const angleRange = startAngle - endAngle;
            const angleStep = angleRange / (seatsPerRow - 1 || 1);
            const angle = startAngle - (seatInRow * angleStep);
            
            // Centre de l'hémicycle
            const centerX = 425;
            const centerY = 400;
            
            return {
                x: centerX + radius * Math.cos(angle),
                y: centerY - radius * Math.sin(angle),
            };
        },
    },
};
</script>

<style scoped>
.hemicycle-container {
    @apply w-full;
}

/* Animation pour les sièges au survol */
circle {
    transition: all 0.2s ease-in-out;
}

circle:hover {
    transform: scale(1.3);
    filter: brightness(1.1);
}
</style>

