<script setup>
import NuanceBadge from './NuanceBadge.vue';

const props = defineProps({
    ancien: Object,
    nouveau: Object,
    communeNom: String,
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="communeNom" class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ communeNom }}</h4>
        </div>

        <div class="p-4">
            <div class="flex items-center gap-4">
                <!-- Ancien maire -->
                <div class="flex-1 text-center">
                    <div v-if="ancien" class="space-y-2">
                        <div class="mx-auto w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                            <img v-if="ancien.photo" :src="ancien.photo" :alt="ancien.nom_complet" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-2xl">👤</div>
                        </div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ ancien.nom_complet }}</div>
                        <NuanceBadge v-if="ancien.nuance_politique" :nuance="ancien.nuance_politique" size="xs" />
                        <div class="text-xs text-gray-500">{{ ancien.mandature || '2020-2026' }}</div>
                    </div>
                    <div v-else class="text-sm text-gray-400 italic">Aucun maire sortant</div>
                </div>

                <!-- Flèche -->
                <div class="flex-shrink-0">
                    <div v-if="nouveau?.reelu" class="text-center">
                        <div class="text-2xl">🔄</div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-1">Réélu(e)</div>
                    </div>
                    <div v-else class="text-center">
                        <div class="text-2xl">→</div>
                        <div class="text-xs text-amber-600 dark:text-amber-400 font-medium mt-1">Changement</div>
                    </div>
                </div>

                <!-- Nouveau maire -->
                <div class="flex-1 text-center">
                    <div v-if="nouveau" class="space-y-2">
                        <div class="mx-auto w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900 overflow-hidden ring-2 ring-indigo-500">
                            <img v-if="nouveau.photo" :src="nouveau.photo" :alt="nouveau.nom_complet" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full flex items-center justify-center text-indigo-400 text-2xl">👤</div>
                        </div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ nouveau.nom_complet }}</div>
                        <NuanceBadge v-if="nouveau.nuance_politique" :nuance="nouveau.nuance_politique" size="xs" />
                        <div v-if="nouveau.score" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                            {{ nouveau.score?.toFixed(1) }}%
                        </div>
                        <div class="text-xs text-gray-500">{{ nouveau.mandature || '2026-2032' }}</div>
                    </div>
                    <div v-else class="text-sm text-gray-400 italic">En attente</div>
                </div>
            </div>
        </div>
    </div>
</template>
