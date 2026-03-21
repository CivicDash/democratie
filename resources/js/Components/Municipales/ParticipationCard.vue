<script setup>
const props = defineProps({
    inscrits: Number,
    votants: Number,
    tauxParticipation: Number,
    blancs: { type: Number, default: 0 },
    nuls: { type: Number, default: 0 },
    exprimes: Number,
    tour: { type: Number, default: 1 },
});

const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';
const pct = (n, total) => total > 0 ? ((n / total) * 100).toFixed(1) : '0';
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            Participation — Tour {{ tour }}
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(inscrits) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Inscrits</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatNumber(votants) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Votants</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ tauxParticipation?.toFixed(1) }}%</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Participation</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(exprimes) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Exprimés</div>
            </div>
        </div>

        <!-- Barre de participation -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
            <div
                class="h-3 rounded-full transition-all duration-700 bg-gradient-to-r from-indigo-500 to-purple-500"
                :style="{ width: (tauxParticipation || 0) + '%' }"
            ></div>
        </div>

        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
            <span>Blancs : {{ formatNumber(blancs) }} ({{ pct(blancs, inscrits) }}%)</span>
            <span>Nuls : {{ formatNumber(nuls) }} ({{ pct(nuls, inscrits) }}%)</span>
        </div>
    </div>
</template>
