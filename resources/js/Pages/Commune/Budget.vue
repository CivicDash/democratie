<script setup>
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    ville: Object,
    page: Object,
    budgets: Array,
});

const formatMoney = (n) => {
    if (!n) return '-';
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + ' M\u20AC';
    if (n >= 1_000) return (n / 1_000).toFixed(0) + ' k\u20AC';
    return n.toLocaleString('fr-FR') + ' \u20AC';
};

const dernierBudget = computed(() => props.budgets?.[0] ?? null);

const chartData = computed(() => {
    if (!props.budgets?.length) return [];
    return [...props.budgets].reverse().map(b => ({
        annee: b.annee,
        recettes: (b.recettes_fonctionnement ?? 0) + (b.recettes_investissement ?? 0),
        depenses: (b.depenses_fonctionnement ?? 0) + (b.depenses_investissement ?? 0),
        dette: b.encours_dette ?? 0,
    }));
});

const maxValue = computed(() => {
    if (!chartData.value.length) return 1;
    return Math.max(...chartData.value.map(d => Math.max(d.recettes, d.depenses)));
});
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Budget - ${ville.nom}`">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Budget et finances</h1>

            <!-- Dernier budget -->
            <div v-if="dernierBudget" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Recettes fonc.</div>
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatMoney(dernierBudget.recettes_fonctionnement) }}</div>
                    <div class="text-xs text-slate-400">{{ dernierBudget.annee }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Depenses fonc.</div>
                    <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ formatMoney(dernierBudget.depenses_fonctionnement) }}</div>
                    <div class="text-xs text-slate-400">{{ dernierBudget.annee }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Recettes invest.</div>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatMoney(dernierBudget.recettes_investissement) }}</div>
                    <div class="text-xs text-slate-400">{{ dernierBudget.annee }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Encours dette</div>
                    <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ formatMoney(dernierBudget.encours_dette) }}</div>
                    <div class="text-xs text-slate-400">{{ dernierBudget.annee }}</div>
                </div>
            </div>

            <!-- Graphique simple -->
            <div v-if="chartData.length > 1" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                <h2 class="font-bold text-slate-900 dark:text-white mb-4">Evolution budgetaire</h2>
                <div class="flex items-end gap-1 h-48">
                    <div v-for="d in chartData" :key="d.annee" class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full flex gap-0.5">
                            <div
                                class="flex-1 bg-green-400 dark:bg-green-500 rounded-t transition-all"
                                :style="{ height: (d.recettes / maxValue * 160) + 'px' }"
                                :title="'Recettes: ' + formatMoney(d.recettes)"
                            />
                            <div
                                class="flex-1 bg-red-400 dark:bg-red-500 rounded-t transition-all"
                                :style="{ height: (d.depenses / maxValue * 160) + 'px' }"
                                :title="'Depenses: ' + formatMoney(d.depenses)"
                            />
                        </div>
                        <span class="text-xs text-slate-500">{{ d.annee }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-400 rounded"></span> Recettes</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-red-400 rounded"></span> Depenses</span>
                </div>
            </div>

            <!-- Tableau -->
            <div v-if="budgets?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-700/50">
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Annee</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Recettes fonc.</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Depenses fonc.</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 hidden sm:table-cell">Recettes inv.</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 hidden sm:table-cell">Depenses inv.</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Dette</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in budgets" :key="b.annee" class="border-t border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ b.annee }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatMoney(b.recettes_fonctionnement) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ formatMoney(b.depenses_fonctionnement) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600 hidden sm:table-cell">{{ formatMoney(b.recettes_investissement) }}</td>
                            <td class="px-4 py-3 text-right text-orange-600 hidden sm:table-cell">{{ formatMoney(b.depenses_investissement) }}</td>
                            <td class="px-4 py-3 text-right text-amber-600">{{ formatMoney(b.encours_dette) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-16">
                <div class="text-4xl mb-3">💰</div>
                <p class="text-slate-500 dark:text-slate-400">Donnees budgetaires non disponibles.</p>
            </div>
        </div>
    </CommuneLayout>
</template>
