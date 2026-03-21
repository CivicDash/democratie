<script setup>
import NuanceBadge from './NuanceBadge.vue';

const props = defineProps({
    listes: Array,
    tour: { type: Number, default: 1 },
});

const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Liste</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tête de liste</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Voix</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">%</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sièges</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="(liste, idx) in listes" :key="idx" :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': liste.elu }">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 w-5">{{ liste.numero_panneau || idx + 1 }}</span>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ liste.nom_liste || 'Liste sans nom' }}</div>
                                <NuanceBadge v-if="liste.nuance_politique" :nuance="liste.nuance_politique" size="xs" />
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ liste.tete_de_liste || '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                        {{ formatNumber(liste.voix) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-2 rounded-full"
                                    :class="liste.elu ? 'bg-emerald-500' : 'bg-indigo-400'"
                                    :style="{ width: Math.min(liste.pourcentage_exprimes || 0, 100) + '%' }"
                                ></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 w-14 text-right">
                                {{ liste.pourcentage_exprimes?.toFixed(2) }}%
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                        {{ liste.sieges_obtenus ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span v-if="liste.elu" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-800/30 text-emerald-800 dark:text-emerald-300 text-xs font-medium px-2.5 py-0.5">
                            ✓ Élu
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
