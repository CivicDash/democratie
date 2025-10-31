<script setup>
import { computed } from 'vue';

const props = defineProps({
    votes: {
        type: Array,
        required: true,
    },
    height: {
        type: Number,
        default: 300,
    },
});

// Calculer les totaux
const totaux = computed(() => {
    return props.votes.reduce((acc, vote) => {
        acc.pour += vote.pour || 0;
        acc.contre += vote.contre || 0;
        acc.abstention += vote.abstention || 0;
        return acc;
    }, { pour: 0, contre: 0, abstention: 0 });
});

const total = computed(() => totaux.value.pour + totaux.value.contre + totaux.value.abstention);

// Calculer les pourcentages pour chaque groupe
const votesWithPercentages = computed(() => {
    return props.votes.map(vote => {
        const voteTotal = (vote.pour || 0) + (vote.contre || 0) + (vote.abstention || 0);
        return {
            ...vote,
            pourcentage_pour: voteTotal > 0 ? ((vote.pour || 0) / voteTotal) * 100 : 0,
            pourcentage_contre: voteTotal > 0 ? ((vote.contre || 0) / voteTotal) * 100 : 0,
            pourcentage_abstention: voteTotal > 0 ? ((vote.abstention || 0) / voteTotal) * 100 : 0,
        };
    });
});

// Pourcentages globaux
const pourcentages = computed(() => {
    if (total.value === 0) return { pour: 0, contre: 0, abstention: 0 };
    
    return {
        pour: (totaux.value.pour / total.value) * 100,
        contre: (totaux.value.contre / total.value) * 100,
        abstention: (totaux.value.abstention / total.value) * 100,
    };
});
</script>

<template>
    <div class="groupe-vote-chart">
        <!-- Graphique global -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Répartition globale
            </h4>
            <div class="flex items-center gap-2 h-8 rounded-lg overflow-hidden shadow-sm">
                <div
                    v-if="pourcentages.pour > 0"
                    class="h-full bg-green-500 flex items-center justify-center text-white text-xs font-medium transition-all"
                    :style="{ width: pourcentages.pour + '%' }"
                >
                    <span v-if="pourcentages.pour > 10">{{ Math.round(pourcentages.pour) }}%</span>
                </div>
                <div
                    v-if="pourcentages.contre > 0"
                    class="h-full bg-red-500 flex items-center justify-center text-white text-xs font-medium transition-all"
                    :style="{ width: pourcentages.contre + '%' }"
                >
                    <span v-if="pourcentages.contre > 10">{{ Math.round(pourcentages.contre) }}%</span>
                </div>
                <div
                    v-if="pourcentages.abstention > 0"
                    class="h-full bg-gray-400 flex items-center justify-center text-white text-xs font-medium transition-all"
                    :style="{ width: pourcentages.abstention + '%' }"
                >
                    <span v-if="pourcentages.abstention > 10">{{ Math.round(pourcentages.abstention) }}%</span>
                </div>
            </div>
            
            <div class="flex justify-between mt-2 text-xs text-gray-600 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-green-500 rounded"></span>
                    Pour: {{ totaux.pour }}
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-red-500 rounded"></span>
                    Contre: {{ totaux.contre }}
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-gray-400 rounded"></span>
                    Abstention: {{ totaux.abstention }}
                </span>
            </div>
        </div>

        <!-- Graphiques par groupe -->
        <div class="space-y-4">
            <div
                v-for="vote in votesWithPercentages"
                :key="vote.groupe_id || vote.groupe?.id"
                class="group"
            >
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div
                            v-if="vote.groupe"
                            class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold"
                            :style="{ backgroundColor: vote.groupe.couleur_hex }"
                        >
                            {{ vote.groupe.sigle?.substring(0, 2) }}
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ vote.groupe?.nom || 'Groupe' }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ (vote.pour || 0) + (vote.contre || 0) + (vote.abstention || 0) }} votes
                    </span>
                </div>

                <div class="flex items-center gap-1 h-6 rounded overflow-hidden shadow-sm">
                    <div
                        v-if="vote.pourcentage_pour > 0"
                        class="h-full bg-green-500 transition-all group-hover:opacity-90"
                        :style="{ width: vote.pourcentage_pour + '%' }"
                        :title="`Pour: ${vote.pour} (${Math.round(vote.pourcentage_pour)}%)`"
                    ></div>
                    <div
                        v-if="vote.pourcentage_contre > 0"
                        class="h-full bg-red-500 transition-all group-hover:opacity-90"
                        :style="{ width: vote.pourcentage_contre + '%' }"
                        :title="`Contre: ${vote.contre} (${Math.round(vote.pourcentage_contre)}%)`"
                    ></div>
                    <div
                        v-if="vote.pourcentage_abstention > 0"
                        class="h-full bg-gray-400 transition-all group-hover:opacity-90"
                        :style="{ width: vote.pourcentage_abstention + '%' }"
                        :title="`Abstention: ${vote.abstention} (${Math.round(vote.pourcentage_abstention)}%)`"
                    ></div>
                </div>

                <div class="flex justify-between mt-1 text-xs text-gray-600 dark:text-gray-400">
                    <span>{{ vote.pour || 0 }} pour</span>
                    <span>{{ vote.contre || 0 }} contre</span>
                    <span>{{ vote.abstention || 0 }} abs.</span>
                </div>
            </div>
        </div>

        <!-- Message si vide -->
        <div v-if="votes.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            Aucune donnée de vote disponible
        </div>
    </div>
</template>

<style scoped>
.groupe-vote-chart {
    @apply bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4;
}
</style>

