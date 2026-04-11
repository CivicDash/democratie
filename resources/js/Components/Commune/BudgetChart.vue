<script setup>
import { computed } from 'vue';

const props = defineProps({
    budgets: { type: Array, default: () => [] },
});

const sortedBudgets = computed(() => [...props.budgets].sort((a, b) => a.annee - b.annee));

const maxValue = computed(() => {
    let max = 0;
    for (const b of props.budgets) {
        max = Math.max(max, b.recettes_fonctionnement || 0, b.depenses_fonctionnement || 0);
    }
    return max || 1;
});

const barHeight = (val) => `${Math.max(4, (val / maxValue.value) * 100)}%`;
const formatM = (val) => val ? `${(val / 1_000_000).toFixed(1)}M` : '-';
</script>

<template>
    <div v-if="sortedBudgets.length > 1">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
            Evolution budgetaire
        </h3>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <!-- Legend -->
            <div class="flex items-center gap-4 mb-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-sm bg-emerald-500"></div>
                    <span class="text-slate-600 dark:text-slate-400">Recettes</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-sm bg-rose-500"></div>
                    <span class="text-slate-600 dark:text-slate-400">Depenses</span>
                </div>
            </div>

            <!-- Chart -->
            <div class="flex items-end gap-1 h-48">
                <div v-for="b in sortedBudgets" :key="b.annee" class="flex-1 flex flex-col items-center gap-1 min-w-0">
                    <div class="flex items-end gap-0.5 w-full h-40">
                        <div
                            class="flex-1 bg-emerald-500 rounded-t transition-all duration-700 ease-out"
                            :style="{ height: barHeight(b.recettes_fonctionnement) }"
                            :title="`Recettes ${b.annee}: ${formatM(b.recettes_fonctionnement)}€`"
                        />
                        <div
                            class="flex-1 bg-rose-500 rounded-t transition-all duration-700 ease-out"
                            :style="{ height: barHeight(b.depenses_fonctionnement) }"
                            :title="`Depenses ${b.annee}: ${formatM(b.depenses_fonctionnement)}€`"
                        />
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">{{ b.annee }}</span>
                </div>
            </div>

            <!-- Values for latest year -->
            <div v-if="sortedBudgets.length" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-xs text-slate-400 mb-0.5">Recettes {{ sortedBudgets[sortedBudgets.length - 1].annee }}</div>
                    <div class="text-lg font-bold text-emerald-600">{{ formatM(sortedBudgets[sortedBudgets.length - 1].recettes_fonctionnement) }}€</div>
                </div>
                <div>
                    <div class="text-xs text-slate-400 mb-0.5">Depenses {{ sortedBudgets[sortedBudgets.length - 1].annee }}</div>
                    <div class="text-lg font-bold text-rose-600">{{ formatM(sortedBudgets[sortedBudgets.length - 1].depenses_fonctionnement) }}€</div>
                </div>
            </div>
        </div>
    </div>
</template>
