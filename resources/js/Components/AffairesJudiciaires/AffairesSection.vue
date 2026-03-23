<script setup>
import AffaireCard from './AffaireCard.vue';
import PresomptionInnocence from './PresomptionInnocence.vue';

defineProps({
    affaires: { type: Array, default: () => [] },
});
</script>

<template>
    <section v-if="affaires.length > 0" class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            Affaires judiciaires
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                ({{ affaires.length }} référencée{{ affaires.length > 1 ? 's' : '' }})
            </span>
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">
            Données vérifiées et sourcées par l'équipe CivicDash
        </p>

        <div class="space-y-3">
            <AffaireCard v-for="affaire in affaires" :key="affaire.id" :affaire="affaire" />
        </div>

        <PresomptionInnocence class="mt-4" />
    </section>
</template>
