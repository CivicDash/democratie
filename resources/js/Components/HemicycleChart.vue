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

const totalSieges = computed(() => {
    return props.groupes.reduce((sum, g) => sum + g.nombre_membres, 0);
});

const seatDotBase = computed(() => {
    const t = totalSieges.value;
    if (t > 500) return 2.8;
    if (t > 300) return 3.4;
    if (t > 150) return 4;
    if (t > 50) return 5;
    return 6;
});

const sieges = computed(() => {
    const total = totalSieges.value;
    if (total === 0) return [];

    const centerX = props.width / 2;
    const centerY = props.height - 25;
    const maxRadius = Math.min(props.width / 2 - 10, props.height - 35);
    const minRadius = maxRadius * 0.35;

    const numRows = Math.max(3, Math.min(15, Math.ceil(Math.sqrt(total / 2.5))));

    const rowData = [];
    let totalArc = 0;
    for (let r = 0; r < numRows; r++) {
        const t = numRows === 1 ? 0.5 : r / (numRows - 1);
        const radius = minRadius + t * (maxRadius - minRadius);
        const arc = Math.PI * radius;
        rowData.push({ radius, arc });
        totalArc += arc;
    }

    const seatsPerRow = [];
    let assigned = 0;
    for (let r = 0; r < numRows; r++) {
        const n = Math.round((rowData[r].arc / totalArc) * total);
        seatsPerRow.push(n);
        assigned += n;
    }

    let diff = total - assigned;
    let ri = numRows - 1;
    while (diff > 0) {
        seatsPerRow[ri]++;
        diff--;
        ri = (ri - 1 + numRows) % numRows;
    }
    while (diff < 0) {
        if (seatsPerRow[ri] > 1) {
            seatsPerRow[ri]--;
            diff++;
        }
        ri = (ri + 1) % numRows;
    }

    const pad = 0.03;
    const allPositions = [];

    for (let r = 0; r < numRows; r++) {
        const n = seatsPerRow[r];
        const radius = rowData[r].radius;
        for (let s = 0; s < n; s++) {
            const fraction = n === 1 ? 0.5 : s / (n - 1);
            const angle = Math.PI * (1 - pad) - fraction * Math.PI * (1 - 2 * pad);
            allPositions.push({
                x: centerX + radius * Math.cos(angle),
                y: centerY - radius * Math.sin(angle),
                row: r,
                fraction,
            });
        }
    }

    allPositions.sort((a, b) => {
        const df = a.fraction - b.fraction;
        if (Math.abs(df) > 0.0001) return df;
        return a.row - b.row;
    });

    const seats = [];
    let seatIdx = 0;
    for (const groupe of props.groupes) {
        for (let i = 0; i < groupe.nombre_membres && seatIdx < allPositions.length; i++) {
            const pos = allPositions[seatIdx];
            seats.push({
                x: pos.x,
                y: pos.y,
                groupe,
                index: seatIdx,
                row: pos.row,
            });
            seatIdx++;
        }
    }

    return seats;
});

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

const getSiegeOpacity = (siege) => {
    if (!hoveredGroupe.value && !selectedGroupe.value) return 1;
    const targetGroupe = selectedGroupe.value || hoveredGroupe.value;
    return siege.groupe.id === targetGroupe.id ? 1 : 0.2;
};

const getSiegeRadius = (siege) => {
    const base = seatDotBase.value;
    if (!hoveredGroupe.value && !selectedGroupe.value) return base;
    const targetGroupe = selectedGroupe.value || hoveredGroupe.value;
    return siege.groupe.id === targetGroupe.id ? base * 1.4 : base * 0.85;
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
            :viewBox="`0 0 ${width} ${height}`"
            class="mx-auto w-full"
            :style="{ maxWidth: width + 'px' }"
            preserveAspectRatio="xMidYMid meet"
        >
            <!-- Background arcs (inner + outer) -->
            <path
                :d="`M ${width / 2 - (Math.min(width / 2 - 10, height - 35))} ${height - 25} A ${Math.min(width / 2 - 10, height - 35)} ${Math.min(width / 2 - 10, height - 35)} 0 0 1 ${width / 2 + (Math.min(width / 2 - 10, height - 35))} ${height - 25}`"
                fill="none"
                stroke="#e5e7eb"
                stroke-width="1"
                stroke-dasharray="4,4"
                opacity="0.5"
                class="dark:stroke-gray-700"
            />
            <path
                :d="`M ${width / 2 - (Math.min(width / 2 - 10, height - 35) * 0.35)} ${height - 25} A ${Math.min(width / 2 - 10, height - 35) * 0.35} ${Math.min(width / 2 - 10, height - 35) * 0.35} 0 0 1 ${width / 2 + (Math.min(width / 2 - 10, height - 35) * 0.35)} ${height - 25}`"
                fill="none"
                stroke="#e5e7eb"
                stroke-width="1"
                stroke-dasharray="4,4"
                opacity="0.3"
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

            <!-- Texte central -->
            <text
                :x="width / 2"
                :y="height - 8"
                text-anchor="middle"
                class="fill-gray-700 dark:fill-gray-300 font-bold transition-all duration-300"
                font-size="14"
            >
                {{ selectedGroupe
                    ? `${selectedGroupe.nombre_membres} sièges ${selectedGroupe.sigle}`
                    : hoveredGroupe
                        ? `${hoveredGroupe.nombre_membres} sièges ${hoveredGroupe.sigle}`
                        : `${totalSieges} sièges`
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

