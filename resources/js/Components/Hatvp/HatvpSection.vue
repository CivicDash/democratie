<script setup>
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import HatvpDeclarationCard from '@/Components/HatvpDeclarationCard.vue';

defineProps({
    declarations: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: null,
    },
    parlementaireType: {
        type: String,
        default: 'depute',
    },
});
</script>

<template>
    <!-- Liste des declarations HATVP -->
    <Card v-if="declarations.length > 0">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📋</span>
            <span>Déclarations d'intérêts et de patrimoine</span>
            <Badge class="ml-2 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 text-xs">
                {{ declarations.length }}
            </Badge>
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Déclarations publiques auprès de la Haute Autorité pour la Transparence de la Vie Publique
        </p>

        <div class="space-y-3">
            <a
                v-for="(declaration, index) in declarations"
                :key="index"
                :href="declaration.url"
                target="_blank"
                class="block p-4 rounded-lg bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 hover:shadow-md transition"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <span v-if="declaration.type === 'DIA' || declaration.type === 'DIAI' || declaration.type === 'DIAC'">📝</span>
                            <span v-else>💰</span>
                            {{ declaration.type_label }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Déposée le {{ declaration.date_depot }}
                        </div>
                        <div v-if="declaration.type_mandat" class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
                            {{ declaration.type_mandat }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge
                            :class="[
                                declaration.type?.startsWith('D') && declaration.type?.includes('I')
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                    : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                            ]"
                        >
                            {{ declaration.type }}
                        </Badge>
                        <span class="text-amber-600 dark:text-amber-400">→</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm text-gray-600 dark:text-gray-400">
            <p>
                <strong>DIA</strong> = Déclaration d'Intérêts et d'Activités &bull;
                <strong>DSP</strong> = Déclaration de Situation Patrimoniale
            </p>
            <a
                href="https://www.hatvp.fr"
                target="_blank"
                class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 mt-2 inline-block"
            >
                En savoir plus sur la HATVP →
            </a>
        </div>
    </Card>

    <!-- Resume HATVP detaille -->
    <HatvpDeclarationCard
        v-if="summary"
        :summary="summary"
        :parlementaire-type="parlementaireType"
    />
</template>
