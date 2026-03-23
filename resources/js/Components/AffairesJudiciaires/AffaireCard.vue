<script setup>
import { ref } from 'vue';
import AffaireBadge from './AffaireBadge.vue';
import VerificationBadge from './VerificationBadge.vue';
import SourcesList from './SourcesList.vue';

const props = defineProps({
    affaire: { type: Object, required: true },
});

const expanded = ref(false);

const typeLabels = {
    corruption: 'Corruption', detournement_fonds: 'Détournement de fonds', fraude_fiscale: 'Fraude fiscale',
    abus_biens_sociaux: 'Abus de biens sociaux', prise_illegale_interet: 'Prise illégale d\'intérêts',
    favoritisme: 'Favoritisme', trafic_influence: 'Trafic d\'influence', emploi_fictif: 'Emploi fictif',
    recel: 'Recel', blanchiment: 'Blanchiment', harcelement: 'Harcèlement', violence: 'Violence',
    diffamation: 'Diffamation', injure: 'Injure', financement_illegal_campagne: 'Financement illégal',
    compte_campagne_rejete: 'Compte campagne rejeté', conflit_interets: 'Conflit d\'intérêts',
    manquement_probite: 'Manquement probité', autre: 'Autre',
};
</script>

<template>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <AffaireBadge :statut="affaire.statut_judiciaire" size="xs" />
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ typeLabels[affaire.type_affaire] || affaire.type_affaire_libelle || affaire.type_affaire }}
                        </span>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ affaire.titre }}
                    </p>
                    <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span v-if="affaire.date_condamnation">Condamnation : {{ affaire.date_condamnation }}</span>
                        <span v-else-if="affaire.date_mise_en_examen">Mise en examen : {{ affaire.date_mise_en_examen }}</span>
                        <span v-if="affaire.juridiction">{{ affaire.juridiction }}</span>
                    </div>
                </div>
                <button
                    @click="expanded = !expanded"
                    class="shrink-0 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-transform"
                    :class="{ 'rotate-180': expanded }"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <div v-if="affaire.peine_resume" class="mt-2">
                <span class="text-xs font-medium px-2 py-0.5 rounded bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300">
                    Peine : {{ affaire.peine_resume }}
                </span>
            </div>
        </div>

        <div v-if="expanded" class="px-4 py-3 space-y-3 border-t border-gray-200 dark:border-gray-700">
            <p v-if="affaire.description" class="text-sm text-gray-700 dark:text-gray-300">
                {{ affaire.description }}
            </p>

            <SourcesList :sources="affaire.sources" />

            <VerificationBadge
                :statut-validation="affaire.statut_validation || 'valide'"
                :date-validation="affaire.valide_at"
                :nb-sources="affaire.sources?.length || 0"
            />
        </div>
    </div>
</template>
